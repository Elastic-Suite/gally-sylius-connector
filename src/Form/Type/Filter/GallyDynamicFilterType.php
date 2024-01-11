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
                    $attr = [
                        'min' => 0,
                        'max' => 0,
                    ];

                    foreach ($aggregation->getOptions() as $option) {
                        /** @var AggregationOption $option */
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
                        /** @var AggregationOption $option */
                        $choices[$option->getLabel()] = $option->getId();
                    }
                    $builder->add(
                        $aggregation->getField(),
                        SelectFilterType::class,
                        [
                            'block_prefix' => 'sylius_gally_filter_checkbox',
                            'label' => $aggregation->getLabel(),
                            'choices' => $choices,
                            'expanded' => true,
                            'multiple' => true,
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
