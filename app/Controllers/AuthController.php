<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Entity\User;
use App\Exceptions\ValidationException;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;

class AuthController
{
    public function __construct(private readonly Twig $twig, private readonly EntityManager $entityManager)
    {
    }

    public function loginView(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'auth/login.twig');
    }

    public function registerView(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $v = new Validator($data);

        $v->rule('required', ['name', 'email', 'password', 'confirmPassword']);
        $v->rule('email', 'email');
        $v->rule('equals', 'confirmPassword', 'password')->label('Confirm Password');
        $v->rule(
            fn($field, $value, $params, $fields) => ! $this->entityManager->getRepository(User::class)->count(
                ['email' => $value]
            ),
            'email'
        )->message('User with the given email address already exists');

        if ($v->validate()) {
        } else {
            throw new ValidationException($v->errors());
        }

        $user = new User();

        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $response->withHeader('Location','/')->withStatus(302);;
    }

    public function login(Request $request, Response $response): Response
    {
        //1.validate the request data
        $data = $request->getParsedBody();

        $v = new Validator($data);
        $v->rule('required', [ 'email','password']);
        $v->rule('email', 'email');

        //2.check user ther credentials

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email'=> $data['email']]);

        if(! $user || ! password_verify($data['password'], $user->getPassword())){
            throw new ValidationException(['password' => ['you have entered an invalid username or password']]);
        }
        //save user id in session
        session_regenerate_id();
        $_SESSION['user'] = $user->getId(); 

        return $response->withHeader('Location', '/')->withStatus(302);
    }
    public function logout(Request $request, Response $response): Response
    {
        return $response->withHeader('Locataion','/')->withStatus(302);
    }
}