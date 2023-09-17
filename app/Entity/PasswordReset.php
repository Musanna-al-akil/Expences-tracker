<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\HasTimeStamps;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table('password_resets')]
#[HasLifecycleCallbacks]
class PasswordReset
{
    use HasTimeStamps;

    #[Id,Column(options:['unsigned' => true]), GeneratedValue]
    private int $id;

    #[Column]
    private string $email;

    #[Column(unique: true)]
    private string $token;

    #[Column(name: 'is_active', options:['default' => true])]
    private bool $isActive;

    #[Column]
    private \DateTime $expiration;

    public function __construct()
    {
        $this->isActive=true;
    }

	public function getId(): int {
		return $this->id;
	}

	public function getEmail(): string {
		return $this->email;
	}

	public function setEmail(string $email): self {
		$this->email = $email;
		return $this;
	}

	public function getIsActive(): bool {
		return $this->isActive;
	}

	public function setIsActive(bool $isActive): self {
		$this->isActive = $isActive;
		return $this;
	}

	public function getExpiration(): \DateTime {
		return $this->expiration;
	}

	public function setExpiration(\DateTime $expiration): self {
		$this->expiration = $expiration;
		return $this;
	}

	public function getToken(): string {
		return $this->token;
	}

    public function setToken(string $token): PasswordReset {
		$this->token = $token;

        return $this;
	}
}