<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\SyliusPlugin\Search;

use Gally\SyliusPlugin\Event\GridFilterUpdateEvent;
use Gally\SyliusPlugin\Search\Aggregation\ActiveFilter;
use Gally\SyliusPlugin\Search\Aggregation\Aggregation;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Resolves the facet filters currently active in the request query, to be displayed as removable chips.
 */
class ActiveFilterResolver
{
    /**
     * @var Aggregation[]
     */
    private array $aggregations = [];

    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    public function onFilterUpdate(GridFilterUpdateEvent $event): void
    {
        $this->aggregations = $event->getAggregations();
    }

    /**
     * @return ActiveFilter[]
     */
    public function resolve(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        $queryParameters = $request->query->all();
        $gallyCriteria = $this->extractGallyCriteria($queryParameters);

        $activeFilters = [];
        foreach ($gallyCriteria as $field => $value) {
            $field = (string) $field;
            if (null === $value || '' === $value || [] === $value) {
                continue;
            }

            $aggregation = $this->findAggregation($field);
            if (null === $aggregation) {
                continue;
            }

            if (str_contains($field, '_slider')) {
                $activeFilters[] = $this->resolveSliderFilter($aggregation, $field, $value, $queryParameters);
                continue;
            }

            if (str_contains($field, '_boolean')) {
                $activeFilters[] = $this->resolveBooleanFilter($aggregation, $field, $value, $queryParameters);
                continue;
            }

            if (\is_array($value)) {
                foreach ($value as $optionValue) {
                    if (!\is_scalar($optionValue)) {
                        continue;
                    }
                    $activeFilters[] = $this->resolveCheckboxFilter($aggregation, $field, (string) $optionValue, $queryParameters);
                }
            }
        }

        return array_values(array_filter($activeFilters));
    }

    public function resolveClearAllUrl(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        $queryParameters = $request->query->all();
        if ([] === $this->extractGallyCriteria($queryParameters)) {
            return null;
        }

        $criteria = \is_array($queryParameters['criteria'] ?? null) ? $queryParameters['criteria'] : [];
        unset($criteria['gally']);
        $queryParameters['criteria'] = $criteria;
        // clearing the filters changes the result set, the current page number may no longer be valid
        unset($queryParameters['page']);

        $queryString = http_build_query($queryParameters);

        return $request->getPathInfo() . ('' !== $queryString ? '?' . $queryString : '');
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    private function resolveSliderFilter(Aggregation $aggregation, string $field, mixed $value, array $queryParameters): ?ActiveFilter
    {
        if (!\is_string($value)) {
            return null;
        }

        $parts = explode('|', $value, 2);

        return new ActiveFilter(
            \sprintf('%s: %s - %s', $aggregation->getLabel(), $parts[0], $parts[1] ?? ''),
            $this->buildRemoveUrl($queryParameters, $field)
        );
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    private function resolveBooleanFilter(Aggregation $aggregation, string $field, mixed $value, array $queryParameters): ActiveFilter
    {
        $label = 'true' === $value ? 'sylius.ui.yes_label' : 'sylius.ui.no_label';

        return new ActiveFilter(
            \sprintf('%s: %s', $aggregation->getLabel(), $this->translator->trans($label)),
            $this->buildRemoveUrl($queryParameters, $field)
        );
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    private function resolveCheckboxFilter(Aggregation $aggregation, string $field, string $optionValue, array $queryParameters): ActiveFilter
    {
        $optionLabel = $optionValue;
        foreach ($aggregation->getOptions() as $option) {
            if ($option->getId() === $optionValue) {
                $optionLabel = $option->getLabel();
                break;
            }
        }

        return new ActiveFilter(
            \sprintf('%s: %s', $aggregation->getLabel(), $optionLabel),
            $this->buildRemoveUrl($queryParameters, $field, $optionValue)
        );
    }

    private function findAggregation(string $field): ?Aggregation
    {
        $rawField = str_replace(['_slider', '_boolean'], '', $field);
        foreach ($this->aggregations as $aggregation) {
            if ($aggregation->getField() === $rawField) {
                return $aggregation;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $queryParameters
     *
     * @return array<int|string, mixed>
     */
    private function extractGallyCriteria(array $queryParameters): array
    {
        $criteria = $queryParameters['criteria'] ?? null;
        if (!\is_array($criteria)) {
            return [];
        }

        $gallyCriteria = $criteria['gally'] ?? null;

        return \is_array($gallyCriteria) ? $gallyCriteria : [];
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    private function buildRemoveUrl(array $queryParameters, string $field, ?string $optionValue = null): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $gallyCriteria = $this->extractGallyCriteria($queryParameters);

        if (null !== $optionValue && \is_array($gallyCriteria[$field] ?? null)) {
            $remainingValues = [];
            foreach ($gallyCriteria[$field] as $currentValue) {
                if (\is_scalar($currentValue) && (string) $currentValue !== $optionValue) {
                    $remainingValues[] = $currentValue;
                }
            }
            if ([] === $remainingValues) {
                unset($gallyCriteria[$field]);
            } else {
                $gallyCriteria[$field] = $remainingValues;
            }
        } else {
            unset($gallyCriteria[$field]);
        }

        $criteria = \is_array($queryParameters['criteria'] ?? null) ? $queryParameters['criteria'] : [];
        $criteria['gally'] = $gallyCriteria;
        $queryParameters['criteria'] = $criteria;
        // filtering out an option changes the result set, the current page number may no longer be valid
        unset($queryParameters['page']);

        $queryString = http_build_query($queryParameters);
        $pathInfo = null !== $request ? $request->getPathInfo() : '';

        return $pathInfo . ('' !== $queryString ? '?' . $queryString : '');
    }
}
