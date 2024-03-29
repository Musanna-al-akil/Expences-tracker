<?php

declare(strict_types = 1);

use App\Auth;
use App\Config;
use App\Contracts\AuthInterface;
use App\Contracts\UserProviderServiceInterface;
use App\Enum\AppEnvironment;
use App\Services\UserProviderService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;
use Twig\Extra\Intl\IntlExtension;
use App\Contracts\SessionInterface;
use App\Session;
use App\DataObjects\SessionConfig;
use App\Enum\SameSite;
use App\RequestValidators\RequestValidatorFactory;
use App\Contracts\RequestValidatorFactoryInterface;
use Slim\Csrf\Guard;
use App\Csrf;

use function DI\create;
use League\Flysystem\Filesystem;
use App\Enum\StorageDriver;
use Doctrine\DBAL\DriverManager;
use Clockwork\Clockwork;
use Clockwork\Storage\FileStorage;
use Clockwork\DataSource\DoctrineDataSource;
use Doctrine\ORM\EntityManagerInterface;
use App\Contracts\EntityManagerServiceInterface;
use App\Services\EntityManagerService;
use App\RouteEntityBindingStrategy;
use App\Filter\UserFilter;
use App\Entity\Transaction;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Component\Mime\BodyRendererInterface;
use Slim\Interfaces\RouteParserInterface;
use Psr\SimpleCache\CacheInterface;
use App\RedisCache;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

return [
    App::class                      => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        $addMiddlewares = require CONFIG_PATH . '/middleware.php';
        $router         = require CONFIG_PATH . '/routes/web.php';

        $app = AppFactory::create();

        $app->getRouteCollector()->setDefaultInvocationStrategy(new RouteEntityBindingStrategy($container->get(EntityManagerServiceInterface::class), $app->getResponseFactory()));

        $router($app);
        $addMiddlewares($app);

        return $app;
    },
    Config::class                   => create(Config::class)->constructor(require CONFIG_PATH . '/app.php'),
    EntityManagerInterface::class   => function(Config $config) { 
        $ormConfig = ORMSetup::createAttributeMetadataConfiguration(
            $config->get('doctrine.entity_dir'),
            $config->get('doctrine.dev_mode')
        );

        $ormConfig->addFilter("user", UserFilter::class);

        return new EntityManager(
            DriverManager::getConnection($config->get('doctrine.connection'), $ormConfig),
            $ormConfig
        );
    },
    Twig::class                      => function (Config $config, ContainerInterface $container) {
        $twig = Twig::create(VIEW_PATH, [
            'cache'       => STORAGE_PATH . '/cache/templates',
            'auto_reload' => AppEnvironment::isDevelopment($config->get('app_environment')),
        ]);

        $twig->addExtension(new IntlExtension());
        $twig->addExtension(new EntryFilesTwigExtension($container));
        $twig->addExtension(new AssetExtension($container->get('webpack_encore.packages')));

        return $twig;
    },
    /**
     * The following two bindings are needed for EntryFilesTwigExtension & AssetExtension to work for Twig
     */
    'webpack_encore.packages'           => fn() => new Packages(
        new Package(new JsonManifestVersionStrategy(BUILD_PATH . '/manifest.json'))
    ),
    'webpack_encore.tag_renderer'       => fn(ContainerInterface $container) => new TagRenderer(
        new EntrypointLookup(BUILD_PATH . '/entrypoints.json'),
        $container->get('webpack_encore.packages')
    ),
    ResponseFactoryInterface::class     => fn(App $app) => $app->getResponseFactory(),
    AuthInterface::class                => fn(ContainerInterface $container) => $container->get(Auth::class),
    UserProviderServiceInterface::class => fn(ContainerInterface $container) => $container->get(UserProviderService::class),
    SessionInterface::class => fn(Config $config)=> new Session(
            new SessionConfig(
                $config->get('session.name',''),
                $config->get('session.secure', false),
                $config->get('session.httpOnly',false),
                SameSite::from($config->get('session.samesite', 'lux')),
                $config->get('session.flashName','flash'),
            )
        ),
    RequestValidatorFactoryInterface::class => fn(ContainerInterface $container) => $container->get(RequestValidatorFactory::class),
    'csrf'  => fn(ResponseFactoryInterface $responseFactoryInterface, Csrf $csrf) => new Guard($responseFactoryInterface, persistentTokenMode: true, failureHandler: $csrf->failureHandler()),
    Filesystem::class => function(Config $config){
        $adapter = match($config->get('storage.driver')) {
            StorageDriver::Local => new League\Flysystem\Local\LocalFilesystemAdapter(STORAGE_PATH),
        };
        return new League\Flysystem\Filesystem($adapter);
    },
    Clockwork::class                        => function(EntityManagerInterface $entityManager){
        $clockwork = new Clockwork();
        $clockwork->storage(new FileStorage(STORAGE_PATH . '/clockwork'));
        $clockwork->addDataSource(new DoctrineDataSource($entityManager));

        return $clockwork;
    },
    EntityManagerServiceInterface::class    => fn(EntityManagerInterface $entityManager) => new EntityManagerService($entityManager),
    \Symfony\Component\Mailer\MailerInterface::class => function(Config $config){
        $transport = Transport::fromDsn($config->get('mailer.dsn'));

        return new Mailer($transport);
    },

    BodyRendererInterface::class => fn(Twig $twig) => new BodyRenderer($twig->getEnvironment()),
    RouteParserInterface::class =>fn(App $app)  =>$app->getRouteCollector()->getRouteParser(),

    CacheInterface::class => fn(RedisAdapter $redisAdapter) => new Psr16Cache($redisAdapter), //return new RedisCache($redis),

    RedisAdapter::class =>function(Config $config){
        $redis = new \Redis();
        $config = $config->get('redis');

        $redis->connect($config['host'], (int) $config['port']);
        $redis->auth($config['password']);

        return new RedisAdapter($redis);
    },

    RateLimiterFactory::class => function(RedisAdapter $redisAdapter) {
        $storage = new CacheStorage($redisAdapter);

        return new RateLimiterFactory([
            'id' => 'default', 
            'policy' => 'fixed_window', 
            'interval' => '1 minute', 
            'limit' => 3
        ], $storage);
    }
];