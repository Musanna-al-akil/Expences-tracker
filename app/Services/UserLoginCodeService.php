<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Entity\User;
use App\Entity\UserLoginCode;

class UserLoginCodeService
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManangerService
    ){
    }

    public function generate(User $user): UserLoginCode
    {
        $userLoginCode = new UserLoginCode();

        $code = random_int(100000, 999999);

        $userLoginCode->setCode((string)$code);
        $userLoginCode->setExpiration(new \DateTime('+10 minutes'));
        $userLoginCode->setUser($user);

        $this->entityManangerService->sync($userLoginCode);

        return $userLoginCode;
    }

    public function verify(User $user, string $code):bool
    {
        $userLoginCode = $this->entityManangerService->getRepository(UserLoginCode::class)->findOneBy(['user' => $user, 'code' => $code, 'isActive' => true]);

        if(! $userLoginCode){
            return false;
        }

        if($userLoginCode->getExpiration() <= new \DateTime()){
            return false;
        }

        return true;
    }

    public function deactivateAllActiveCodes(User $user):void 
    {
        $this->entityManangerService->getRepository(UserLoginCode::class)
            ->createQueryBuilder('c')
            ->update()
            ->set('c.isActive',0)
            ->where('c.user = :user')
            ->andWhere('c.isActive = 1')
            ->setParameter('user',$user)
            ->getQuery()
            ->execute();
    }
}