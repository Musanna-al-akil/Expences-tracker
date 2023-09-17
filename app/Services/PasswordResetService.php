<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Entity\PasswordReset;
use App\Entity\User;

class PasswordResetService
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManangerService,
        private readonly hashService $hashService
    )
    {
    }

    public function generate(string $email): PasswordReset
    {
        $passwordReset = new PasswordReset();

        $passwordReset->setToken(bin2hex(random_bytes(32)));
        $passwordReset->setExpiration(new \DateTime('+30 minutes'));
        $passwordReset->setEmail($email);

        $this->entityManangerService->sync($passwordReset);

        return $passwordReset;
    }

    public function deactivateAllPasswordResets(string $email):void 
    {
        $this->entityManangerService->getRepository(PasswordReset::class)
            ->createQueryBuilder('pr')
            ->update()
            ->set('pr.isActive',0)
            ->where('pr.email = :email')
            ->andWhere('pr.isActive = 1')
            ->setParameter('email',$email)
            ->getQuery()
            ->execute();
    }

    public function findBytoken(string $token): ?PasswordReset
    {
        return $this->entityManangerService
            ->getRepository(PasswordReset::class)
            ->createQueryBuilder('pr')
            ->select('pr')
            ->where('pr.token = :token')
            ->andWhere('pr.isActive = :active')
            ->andWhere('pr.expiration > :now')
            ->setParameters([
                'token' => $token,
                'active'=>true,
                'now'   => new \DateTime()
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function updatePassword(User $user, string $password)
    {
        $this->entityManangerService->wrapInTransaction(function() use ($user, $password){
            $this->deactivateAllPasswordResets($user->getEmail());
            $user->setPassword($this->hashService->hashPassword($password));

            $this->entityManangerService->sync($user);
        });
    }
}