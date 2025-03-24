<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Stephan Hochdörfer <S.Hochdoerfer@bitexpert.de>, Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\SyliusPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;

class GallyConfiguration implements ResourceInterface, GallyConfigurationInterface
{
    private ?int $id;
    private string $baseUrl;
    private bool $checkSSL = true;
    private string $userName;
    private string $password;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id = null): void
    {
        $this->id = $id;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getCheckSSL(): bool
    {
        return $this->checkSSL;
    }

    public function setCheckSSL(bool $checkSSL): void
    {
        $this->checkSSL = $checkSSL;
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

    public function setPassword(?string $password): void
    {
        if ($password) {
            $this->password = $password;
        }
    }
}
