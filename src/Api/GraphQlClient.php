<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Api;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class GraphQlClient extends AbstractClient
{
    public function query(string $query, array $variables): ?ResponseInterface
    {
        $client = new Client($this->kernelEnv !== 'prod' ? ['verify' => false] : []);

        try {
            if ($this->debug === true) {
                $this->logger->info("Calling : ");
                $this->logger->info(print_r($query, true));
                $this->logger->info(print_r($variables, true));
            }
            $result = $client->request(
                'post',
                $this->configuration->getBaseUrl() . '/graphql',
                [
                    'headers' => [
                        'Authorization' => 'bearer ' . $this->getAuthorizationToken(),
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode(
                        [
                            'query' => $query,
                            'variables' => $variables,
                        ]
                    )
                ]
            );
            if ($this->debug === true) {
                $this->logger->info("Result : ");
                $this->logger->info(print_r($result, true));
            }
        } catch (\Exception $e) {
            $this->logger->info(get_class($e) . ": " . $e->getMessage());
            $this->logger->info($e->getTraceAsString());
            $this->logger->info("Input was");
            $this->logger->info(print_r($query, true));
            $this->logger->info(print_r($variables, true));

            throw $e;
        }

        return $result;
    }
}
