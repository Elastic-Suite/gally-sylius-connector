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

namespace Gally\SyliusPlugin\Api;

use Gally\Rest\ApiException;
use Gally\Rest\Configuration;
use GuzzleHttp\Client;

final class RestClient extends AbstractClient
{
    public function query(string $endpoint, string $operation, mixed ...$input): mixed
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('Authorization', $this->getAuthorizationToken())
            ->setApiKeyPrefix('Authorization', 'Bearer')
            ->setHost(trim($this->configuration->getBaseUrl(), '/'));

        $apiInstance = new $endpoint(new Client('prod' !== $this->kernelEnv ? ['verify' => false] : []), $config);

        try {
            if (true === $this->debug) {
                $this->logger->info("Calling {$endpoint}->{$operation} : ");
                $this->logger->info(print_r($input, true));
            }
            $result = $apiInstance->{$operation}(...$input);
            if (true === $this->debug) {
                $this->logger->info("Result of {$endpoint}->{$operation} : ");
                $this->logger->info(print_r($result, true));
            }
        } catch (\Exception|ApiException $e) {
            $this->logger->info($e::class . " when calling {$endpoint}->{$operation}: " . $e->getMessage());
            $this->logger->info($e->getTraceAsString());
            $this->logger->info('Input was');
            $this->logger->info(print_r($input, true));

            throw $e;
        }

        return $result;
    }
}
