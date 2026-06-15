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

namespace Gally\SyliusPlugin\Controller\Shop;

use Gally\SyliusPlugin\Service\TrackingProxyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackingController extends AbstractController
{
    public function __construct(
        private readonly TrackingProxyService $trackingProxyService,
    ) {
    }

    /**
     * GraphQL proxy endpoint for Gally tracking.
     * Forwards GraphQL mutations from the frontend to Gally API.
     */
    public function graphqlProxy(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);

            if (null === $payload || !\is_array($payload)) {
                return new JsonResponse([
                    'errors' => [
                        ['message' => 'Invalid JSON payload'],
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }

            // Forward GraphQL request to Gally
            $response = $this->trackingProxyService->forwardGraphQLRequest($payload);

            return new JsonResponse($response);
        } catch (\InvalidArgumentException $e) {
            // Security validation failed
            return new JsonResponse([
                'errors' => [
                    ['message' => $e->getMessage()],
                ],
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return new JsonResponse([
                'errors' => [
                    ['message' => $e->getMessage()],
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
