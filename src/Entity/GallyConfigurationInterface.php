<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Entity;

interface GallyConfigurationInterface
{
    public function getBaseUrl(): string;
    public function setBaseUrl(string $baseUrl): void;
    public function getUserName(): string;
    public function setUserName(string $userName): void;
    public function getPassword(): string;
    public function setPassword(string $password): void;
}
