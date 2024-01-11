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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

final class AuthenticationTokenProvider
{
    private Client $client;

    public function __construct(string $kernelEnv = 'dev')
    {
        $this->client = new Client('prod' !== $kernelEnv ? ['verify' => false] : []);
    }

    public function getAuthenticationToken(string $baseUrl, string $user, string $password): string
    {
        $resourcePath = '/authentication_token';

        try {
            $responseJson = $this->client->request(
                'POST',
                trim($baseUrl, '/') . $resourcePath,
                [
                    'headers' => [
                        'accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => ['email' => $user, 'password' => $password],
                    'config' => [
                        'curl' => [
                            \CURLOPT_SSLVERSION => 3,
                        ],
                    ],
                ]
            );
        } catch (RequestException $e) {
            throw new ApiException("[{$e->getCode()}] {$e->getMessage()}", $e->getCode(), $e->getResponse() ? $e->getResponse()->getHeaders() : null, /* @phpstan-ignore-line */ $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null);
        }

        try {
            $response = json_decode($responseJson->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);

            return (string) $response['token'];
        } catch (\Exception $e) {
            throw new \LogicException('Unable to fetch authorization token from Api response.');
        }
    }
}
