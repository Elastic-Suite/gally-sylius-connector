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

namespace Gally\SyliusPlugin\Service;

use Gally\Sdk\Client\Client;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TrackingProxyService
{
    public function __construct(
        private readonly GallyConfigurationRepository $gallyConfigurationRepository,
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Process tracking event from user payload and send to Gally API.
     * Security: Rebuilds the GraphQL mutation from scratch using only whitelisted data.
     *
     * @param array $payload User payload containing tracking event data
     *
     * @throws \Exception
     *
     * @return array GraphQL response from Gally
     */
    public function forwardGraphQLRequest(array $payload): array
    {
        $gallyConfiguration = null;
        $graphqlUrl = null;

        try {
            // Extract and validate tracking event data (whitelist approach)
            $trackingEvents = $this->extractTrackingEvents($payload);

            if (empty($trackingEvents)) {
                throw new \InvalidArgumentException('No valid tracking events found in payload');
            }

            $gallyConfiguration = $this->gallyConfigurationRepository->getConfiguration();
            $gallyBaseUrl = $gallyConfiguration->getBaseUrl();
            $graphqlUrl = rtrim($gallyBaseUrl, '/') . '/graphql';

            // Build a clean, safe GraphQL mutation
            $safePayload = $this->buildTrackingMutation($trackingEvents);

            // Send via HTTP client (SDK Gally Request could be used here too)
            $response = $this->httpClient->request('POST', $graphqlUrl, [
                'json' => $safePayload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'verify_peer' => $gallyConfiguration->getCheckSSL(),
                'verify_host' => $gallyConfiguration->getCheckSSL(),
            ]);

            $responseData = $response->toArray(false);

            // Log only if there are errors in the GraphQL response
            if (isset($responseData['errors'])) {
                $this->logger->error('Gally GraphQL response contains errors', [
                    'url' => $graphqlUrl,
                    'status_code' => $response->getStatusCode(),
                    'errors' => $responseData['errors'],
                    'events' => $trackingEvents,
                ]);
            }

            return $responseData;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send tracking events to Gally', [
                'error' => $e->getMessage(),
                'url' => $graphqlUrl ?? 'N/A',
                'ssl_verification' => $gallyConfiguration?->getCheckSSL() ?? 'N/A',
            ]);

            throw $e;
        }
    }

    /**
     * Extract and validate tracking events from user payload.
     * Whitelist approach: only extract known safe fields.
     *
     * @param array $payload User payload
     *
     * @throws \InvalidArgumentException
     *
     * @return array Array of validated tracking events
     */
    private function extractTrackingEvents(array $payload): array
    {
        if (!isset($payload['variables']) || !\is_array($payload['variables'])) {
            throw new \InvalidArgumentException('Missing or invalid variables in payload');
        }

        $trackingEvents = [];

        // Extract events from variables (input0, input1, input2, etc.)
        foreach ($payload['variables'] as $key => $value) {
            if (preg_match('/^input\d+$/', $key) && \is_array($value)) {
                $trackingEvents[] = $this->validateTrackingEventData($value);
            }
        }

        // Security: Limit the number of events to prevent DoS
        $maxEvents = 100;
        if (\count($trackingEvents) > $maxEvents) {
            throw new \InvalidArgumentException(sprintf('Too many tracking events. Maximum %d events allowed per request.', $maxEvents));
        }

        // Security: Re-index array to ensure sequential indices 0,1,2...
        // This guarantees we control all variable names ($input0, $input1, etc.)
        return array_values($trackingEvents);
    }

    /**
     * Validate and sanitize a single tracking event.
     * Accepts all scalar fields but rejects complex types to prevent injection.
     *
     * @throws \InvalidArgumentException
     *
     * @return array Validated event data
     */
    private function validateTrackingEventData(array $eventData): array
    {
        // Required fields
        $requiredFields = ['eventType', 'metadataCode', 'localizedCatalogCode'];
        foreach ($requiredFields as $field) {
            if (!isset($eventData[$field]) || !\is_string($eventData[$field])) {
                throw new \InvalidArgumentException("Missing or invalid required field: {$field}");
            }
        }

        // Build clean event: accept all scalar values, reject complex types
        $cleanEvent = [];

        foreach ($eventData as $field => $value) {
            // Accept only scalar types: string, int, float, bool, null
            if (\is_scalar($value) || null === $value) {
                $cleanEvent[$field] = $value;
            }
            // Reject arrays and objects (potential injection vectors)
            elseif (\is_array($value) || \is_object($value)) {
                $this->logger->warning('Rejected non-scalar field in tracking event', [
                    'field' => $field,
                    'type' => \gettype($value),
                ]);
                // Continue without adding this field
            }
        }

        return $cleanEvent;
    }

    /**
     * Build a clean GraphQL mutation for tracking events.
     *
     * @return array GraphQL payload with query and variables
     */
    private function buildTrackingMutation(array $trackingEvents): array
    {
        // Build the mutation query
        $mutations = [];
        $variables = [];

        foreach ($trackingEvents as $index => $event) {
            $varName = "input{$index}";
            $mutations[] = "event{$index}: createTrackingEvent(input: \${$varName}) { trackingEvent { id } }";
            $variables[$varName] = $event;
        }

        $mutationQuery = 'mutation createTrackingEvents(' . implode(', ', array_map(
            fn ($i) => "\$input{$i}: createTrackingEventInput!",
            array_keys($trackingEvents)
        )) . ') { ' . implode(' ', $mutations) . ' }';

        return [
            'query' => $mutationQuery,
            'variables' => $variables,
        ];
    }
}
