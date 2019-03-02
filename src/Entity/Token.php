<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

// TODO Add index, refactor findOnBy
// TODO Add field alias
/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenRepository")
 * @ORM\Table(name="tokens")
 */
class Token
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="guid", name="value")
     */
    private $value;

    /**
     * @var string
     * @ORM\Column(type="guid", name="alias")
     */
    private $alias;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="last_enter_at")
     */
    private $lastEnterAt;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="tokens")
     * @ORM\JoinColumn(nullable=false, name="user_id")
     */
    private $user;

    /**
     * @ORM\Column(type="string", name="user_agent", length=255, nullable=true)
     */
    private $userAgent;

    /**
     * @ORM\Column(type="string", name="ip", length=39, nullable=true)
     */
    private $ip;

    /**
     * Token constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->lastEnterAt = $now;
        $this->value = Uuid::uuid4()->toString();
        $this->alias = Uuid::uuid4()->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastEnterAt(): \DateTime
    {
        return $this->lastEnterAt;
    }

    public function setLastEnterAt(\DateTime $lastEnterAt): self
    {
        $this->lastEnterAt = $lastEnterAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        if ($userAgent !== null) {
            $userAgent = \mb_substr($userAgent, 0, 255);
        }
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        if ($ip !== null) {
            $ip = \mb_substr($ip, 0, 39);
        }
        $this->ip = $ip;

        return $this;
    }
}
