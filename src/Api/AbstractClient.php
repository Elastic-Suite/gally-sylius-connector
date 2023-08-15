<?php
declare(strict_types=1);

namespace Gally\SyliusPlugin\Api;

use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Psr\Log\LoggerInterface;

abstract class AbstractClient
{
    protected string $kernelEnv;
    protected ?string $token = null;
    protected bool $debug;
    protected GallyConfiguration $configuration;

    public function __construct(
        protected AuthenticationTokenProvider $tokenProvider,
        protected GallyConfigurationRepository $gallyConfigurationRepository,
        protected LoggerInterface $logger,
        string $kernelEnv = 'dev'
    ) {
        $this->kernelEnv = $kernelEnv;
        $this->debug = true;
        $this->configuration = $this->gallyConfigurationRepository->getConfiguration();
    }

    public function getAuthorizationToken(): string
    {
        if (null === $this->token) {
            $this->token = $this->tokenProvider->getAuthenticationToken(
                $this->configuration->getBaseUrl(),
                $this->configuration->getUserName(),
                $this->configuration->getPassword()
            );
        }

        return $this->token;
    }
}
