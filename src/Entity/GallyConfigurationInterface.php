<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Entity;


use Sylius\Component\Resource\Model\ResourceInterface;

interface GallyConfigurationInterface extends ResourceInterface
{
    public function getBaseUrl(): string;
    public function setBaseUrl(string $baseUrl): void;
    public function getUserName(): string;
    public function setUserName(string $userName): void;
    public function getPassword(): string;
    public function setPassword(string $password): void;
}
