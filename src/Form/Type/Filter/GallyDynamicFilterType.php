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

class GallyDynamicFilterType extends AbstractType
{
    /**
     * @var Aggregation[]
     */
    private array $aggregations = [];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->aggregations as $aggregation) {
            switch ($aggregation->getType()) {
                case 'slider':
                    $min = 0;
                    $max = 0;
                    $steps = 10;
                    foreach ($aggregation->getOptions() as $option) {
                        /** @var AggregationOption $option */
                        if ($min === 0) {
                            $min = $option->getId();
                        }
                        if ($option->getId() > $max) {
                            $max = $option->getId();
                        }
                    }

                    $builder->add(
                        $aggregation->getField().'_'.$aggregation->getType(),
                        RangeType::class,
                        [
                            'block_prefix' => 'sylius_gally_filter_range',
                            'label' => $aggregation->getLabel(),
                            'attr' => [
                                'min' => $min,
                                'max' => $max + $steps,
                                'steps' => $steps
                            ]
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
                        $choices[$option->getId()] = $option->getLabel();
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
