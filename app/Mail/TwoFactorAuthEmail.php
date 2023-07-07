<?php

declare(strict_types=1);

namespace App\Mail;
use App\Config;
use App\Entity\User;
use App\Entity\UserLoginCode;
use App\SignedUrl;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;

class TwoFactorAuthEmail
{
    public function __construct(
        private readonly Config $config, 
        private readonly MailerInterface $mailer,
        private readonly BodyRendererInterface $renderer,
        private readonly SignedUrl $signedUrl)
    {

    }

    public function send(UserLoginCode $userLoginCode): Void
    {
        $email = $userLoginCode->getUser()->getEmail();
        $expirationDate = new \DateTime('+30 minutes');

        $message = (new TemplatedEmail())
                ->from($this->config->get('mailer.from'))
                ->to($email)
                ->subject('Your Expennies Verifaction Code')
                ->htmlTemplate('emails/two_factor.html.twig')
                ->context([
                    'code' => $userLoginCode->getCode(),
                ]);

        $this->renderer->render($message);
        $this->mailer->send($message);
    }
}