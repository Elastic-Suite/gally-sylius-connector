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
use Sylius\Bundle\TaxonomyBundle\Doctrine\ORM\TaxonRepository;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Resolves the facet filters currently active in the request query, to be displayed as removable chips.
 */
class ActiveFilterResolver
{
    // Gally excludes the category aggregation from the response as soon as a category filter is
    // active (a product only ever matches one category), so its label can't come from $aggregations
    // like the other facets and has to be resolved from the taxon directly.
    private const CATEGORY_FIELD = 'category__id';

    /**
     * @var Aggregation[]
     */
    private array $aggregations = [];

    /**
     * @param TaxonRepository<TaxonInterface> $taxonRepository
     */
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private TaxonRepository $taxonRepository,
        private LocaleContextInterface $localeContext,
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

            if (self::CATEGORY_FIELD === $field) {
                if (\is_string($value)) {
                    $activeFilters[] = $this->resolveCategoryFilter($field, $value, $queryParameters);
                }
                continue;
            }

            // Gally returns no aggregation at all once a combination of filters yields zero
            // results, so this can legitimately be null; the resolve*Filter methods below fall
            // back to a best-effort label in that case instead of dropping the chip entirely.
            $aggregation = $this->findAggregation($field);

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
                continue;
            }

            if (\is_scalar($value)) {
                $activeFilters[] = $this->resolveCheckboxFilter($aggregation, $field, (string) $value, $queryParameters);
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

        return $this->buildUrlWithGallyCriteria($queryParameters, []);
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    private function resolveSliderFilter(?Aggregation $aggregation, string $field, mixed $value, array $queryParameters): ?ActiveFilter
    {
        if (!\is_string($value)) {
            return null;
        }

        $parts = explode('|', $value, 2);

        return new ActiveFilter(
            \sprintf('%s: %s - %s', $this->resolveLabel($aggregation, $field), $parts[0], $parts[1] ?? ''),
            $this->buildRemoveUrl($queryParameters, $field)
        );
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    private function resolveBooleanFilter(?Aggregation $aggregation, string $field, mixed $value, array $queryParameters): ActiveFilter
    {
        $label = 'true' === $value ? 'sylius.ui.yes_label' : 'sylius.ui.no_label';

        return new ActiveFilter(
            \sprintf('%s: %s', $this->resolveLabel($aggregation, $field), $this->translator->trans($label)),
            $this->buildRemoveUrl($queryParameters, $field)
        );
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    private function resolveCheckboxFilter(?Aggregation $aggregation, string $field, string $optionValue, array $queryParameters): ActiveFilter
    {
        $optionLabel = $optionValue;
        foreach ($aggregation?->getOptions() ?? [] as $option) {
            if ($option->getId() === $optionValue) {
                $optionLabel = $option->getLabel();
                break;
            }
        }

        return new ActiveFilter(
            \sprintf('%s: %s', $this->resolveLabel($aggregation, $field), $optionLabel),
            $this->buildRemoveUrl($queryParameters, $field, $optionValue)
        );
    }

    /**
     * Best-effort label when Gally didn't return the aggregation for this field (e.g. the
     * current combination of filters yields zero results), so the real label isn't available.
     */
    private function resolveLabel(?Aggregation $aggregation, string $field): string
    {
        if (null !== $aggregation) {
            return $aggregation->getLabel();
        }

        $rawField = str_replace(['_slider', '_boolean'], '', $field);

        return ucfirst(str_replace('_', ' ', $rawField));
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    private function resolveCategoryFilter(string $field, string $categoryId, array $queryParameters): ?ActiveFilter
    {
        $taxon = $this->taxonRepository->findOneBy(['code' => $categoryId]);
        if (null === $taxon) {
            return null;
        }

        $translation = $taxon->getTranslation($this->localeContext->getLocaleCode());

        return new ActiveFilter(
            \sprintf('%s: %s', $this->translator->trans('gally_sylius.ui.filters.categories'), $translation->getName()),
            $this->buildRemoveUrl($queryParameters, $field)
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

        return $this->buildUrlWithGallyCriteria($queryParameters, $gallyCriteria);
    }

    /**
     * @param array<string, mixed> $queryParameters
     * @param array<int|string, mixed> $gallyCriteria
     */
    private function buildUrlWithGallyCriteria(array $queryParameters, array $gallyCriteria): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $criteria = \is_array($queryParameters['criteria'] ?? null) ? $queryParameters['criteria'] : [];
        if ([] === $gallyCriteria) {
            unset($criteria['gally']);
        } else {
            $criteria['gally'] = $gallyCriteria;
        }
        $queryParameters['criteria'] = $criteria;
        // filtering changes the result set, the current page number may no longer be valid
        unset($queryParameters['page']);

        $queryString = http_build_query($queryParameters);
        $pathInfo = null !== $request ? $request->getPathInfo() : '';

        return $pathInfo . ('' !== $queryString ? '?' . $queryString : '');
    }
}
