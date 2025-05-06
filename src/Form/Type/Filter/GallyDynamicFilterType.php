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

namespace Gally\SyliusPlugin\Form\Type\Filter;

use Gally\SyliusPlugin\Event\GridFilterUpdateEvent;
use Gally\SyliusPlugin\Grid\Filter\Type\SelectFilterType;
use Gally\SyliusPlugin\Search\Aggregation\Aggregation;
use Gally\SyliusPlugin\Search\Aggregation\AggregationOption;
use Sylius\Bundle\GridBundle\Form\Type\Filter\BooleanFilterType;
use Sylius\Bundle\TaxonomyBundle\Doctrine\ORM\TaxonRepository;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GallyDynamicFilterType extends AbstractType
{
    /**
     * @param TaxonRepository<TaxonInterface> $taxonRepository
     */
    public function __construct(
        private UrlGeneratorInterface $router,
        private RequestStack $requestStack,
        private TaxonRepository $taxonRepository,
        private LocaleContextInterface $localeContext,
    ) {
    }

    /**
     * @var Aggregation[]
     */
    private array $aggregations = [];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->aggregations as $aggregation) {
            switch ($aggregation->getType()) {
                case 'slider':
                    $attr = [
                        'min' => 0,
                        'max' => 0,
                    ];

                    foreach ($aggregation->getOptions() as $option) {
                        /* @var AggregationOption $option */
                        if (0 === $attr['min']) {
                            $attr['min'] = $option->getId();
                        }
                        if ($option->getId() > $attr['max']) {
                            $attr['max'] = $option->getId();
                        }
                    }

                    // raise the max limit to make sure the most expensive product will be part of the filtering
                    ++$attr['max'];

                    $builder->add(
                        $aggregation->getField() . '_' . $aggregation->getType(),
                        RangeType::class,
                        [
                            'block_prefix' => 'sylius_gally_filter_range',
                            'label' => $aggregation->getLabel(),
                            'attr' => $attr,
                        ]
                    );
                    break;
                case 'boolean':
                    $builder->add(
                        $aggregation->getField() . '_' . $aggregation->getType(),
                        BooleanFilterType::class,
                        [
                            'label' => $aggregation->getLabel(),
                        ]
                    );
                    break;
                case 'checkbox':
                    $choices = [];
                    foreach ($aggregation->getOptions() as $option) {
                        /* @var AggregationOption $option */
                        $choices[$option->getLabel()] = $option->getId();
                    }
                    $options = [
                        'block_prefix' => 'sylius_gally_filter_checkbox',
                        'label' => $aggregation->getLabel(),
                        'choices' => $choices,
                        'expanded' => true,
                        'multiple' => true,
                    ];
                    if ($aggregation->hasMore()) {
                        $options['has_more_url'] = $this->buildHasMoreUrl($aggregation->getField());
                    }
                    $builder->add(
                        $aggregation->getField(),
                        SelectFilterType::class,
                        $options
                    );
                    break;
                default:
                    break;
            }
        }
    }

    public function onFilterUpdate(GridFilterUpdateEvent $event): void
    {
        $this->aggregations = $event->getAggregations();
    }

    private function buildHasMoreUrl(string $field): string
    {
        $request = $this->requestStack->getCurrentRequest();
        /** @var array<string, mixed> $queryParameters */
        $queryParameters = $request?->query ? $request->query->all() : [];
        $parameters = new Parameters($queryParameters);
        /** @var array<string, array<string, mixed>> $criteria */
        $criteria = $parameters->get('criteria', []);
        $search = (isset($criteria['search'], $criteria['search']['value'])) ? $criteria['search']['value'] : '';
        unset($criteria['search']);
        /** @var string $slug */
        $slug = $request?->attributes->get('slug') ?? '';
        $taxon = $this->taxonRepository->findOneBySlug($slug, $this->localeContext->getLocaleCode());

        return $this->router->generate(
            'gally_filter_view_more_ajax',
            [
                'filterField' => $field,
                'search' => $search,
                'filters' => $criteria,
                'taxon' => $taxon?->getId(),
            ]
        );
    }
}
