<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Form\Type\Filter;

use Gally\SyliusPlugin\Event\GridFilterUpdateEvent;
use Gally\SyliusPlugin\Search\Aggregation\Aggregation;
use Gally\SyliusPlugin\Search\Aggregation\AggregationOption;
use Sylius\Bundle\GridBundle\Form\Type\Filter\BooleanFilterType;
use Sylius\Bundle\GridBundle\Form\Type\Filter\SelectFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GallyDynamicFilterType extends AbstractType
{
    /**
     * @var Aggregation[]
     */
    private array $aggregations = [];

    public function __construct(private RequestStack $requestStack)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $criteria = $this->requestStack->getMainRequest()->get('criteria', []);

        foreach ($this->aggregations as $aggregation) {
            switch ($aggregation->getType()) {
                case 'slider':
                    $attr = [
                        'min' => 0,
                        'max' => 0,
                        'steps' => 10,
                    ];

                    foreach ($aggregation->getOptions() as $option) {
                        /** @var AggregationOption $option */
                        if ($attr['min'] === 0) {
                            $attr['min'] = $option->getId();
                        }
                        if ($option->getId() > $attr['max']) {
                            $attr['max'] = $option->getId();
                        }
                    }

                    // raise the max limit to make sure the most expensive product will be part of the filtering
                    $attr['max'] += $attr['steps'];

                    // for some odd reason the price filter default value seems not to be the max value, that's why
                    // it is explicitly set here
                    if (!isset($criteria['gally'][$aggregation->getField().'_'.$aggregation->getType()])) {
                        $attr['value'] = $attr['max'];
                    }

                    $builder->add(
                        $aggregation->getField().'_'.$aggregation->getType(),
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
                        $aggregation->getField().'_'.$aggregation->getType(),
                        BooleanFilterType::class,
                        [
                            'label' => $aggregation->getLabel(),
                        ]
                    );
                    break;
                case 'checkbox':
                    $choices = [];
                    foreach ($aggregation->getOptions() as $option) {
                        /** @var AggregationOption $option */
                        $choices[$option->getLabel()] = $option->getId();
                    }
                    $builder->add(
                        $aggregation->getField(),
                        SelectFilterType::class,
                        [
                            'label' => $aggregation->getLabel(),
                            'choices' => $choices,
                        ]
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
}
