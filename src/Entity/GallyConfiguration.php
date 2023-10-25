<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;

final class GallyConfiguration implements ResourceInterface, GallyConfigurationInterface
{
    protected ?int $id;
    protected string $baseUrl;
    protected string $userName;
    protected string $password;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
