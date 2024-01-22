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

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class GraphQlClient extends AbstractClient
{
    public function query(string $query, array $variables): ?ResponseInterface
    {
        $client = new Client('prod' !== $this->kernelEnv ? ['verify' => false] : []);

        try {
            if (true === $this->debug) {
                $this->logger->info('Calling : ');
                $this->logger->info(print_r($query, true));
                $this->logger->info(print_r($variables, true));
            }
            $result = $client->request(
                'post',
                trim($this->configuration->getBaseUrl(), '/') . '/graphql',
                [
                    'headers' => [
                        'Authorization' => 'bearer ' . $this->getAuthorizationToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode(
                        [
                            'query' => $query,
                            'variables' => $variables,
                        ]
                    ),
                ]
            );
            if (true === $this->debug) {
                $this->logger->info('Result : ');
                $this->logger->info(print_r($result, true));
            }
        } catch (\Exception $e) {
            $this->logger->info($e::class . ': ' . $e->getMessage());
            $this->logger->info($e->getTraceAsString());
            $this->logger->info('Input was');
            $this->logger->info(print_r($query, true));
            $this->logger->info(print_r($variables, true));

            throw $e;
        }

        return $result;
    }
}
