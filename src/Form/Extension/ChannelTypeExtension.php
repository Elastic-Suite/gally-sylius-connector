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

namespace Gally\SyliusPlugin\Form\Extension;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

final class ChannelTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gallyActive', CheckboxType::class, [
                'required' => true,
                'label' => 'gally_sylius.form.active',
            ])
            ->add('gallyProductIndexBatchSize', IntegerType::class, [
                'label' => 'gally_sylius.form.product_index_batch_size',
            ])
            ->add('gallyCategoryIndexBatchSize', IntegerType::class, [
                'label' => 'gally_sylius.form.category_index_batch_size',
            ])
            ->add('gallyAutocompleteProductMaxSize', IntegerType::class, [
                'label' => 'gally_sylius.form.autocomplete_product_max_size',
            ])
            ->add('gallyAutocompleteCategoryMaxSize', IntegerType::class, [
                'label' => 'gally_sylius.form.autocomplete_category_max_size',
            ])
            ->add('gallyAutocompleteAttributeMaxSize', IntegerType::class, [
                'label' => 'gally_sylius.form.autocomplete_attribute_max_size',
            ])
            ->add('gallyTrackingActive', CheckboxType::class, [
                'label' => 'gally_sylius.form.tracking_active',
                'required' => false,
            ])
            ->add('gallyUseSyliusEndpointTracking', CheckboxType::class, [
                'label' => 'gally_sylius.form.use_sylius_endpoint_tracking',
                'required' => false,
                'help' => 'gally_sylius.form.use_sylius_endpoint_tracking_help',
            ])
            ->add('gallyUidCookieLifetime', IntegerType::class, [
                'label' => 'gally_sylius.form.uid_cookie_lifetime',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('gallyVidCookieLifetime', IntegerType::class, [
                'label' => 'gally_sylius.form.vid_cookie_lifetime',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChannelType::class];
    }
}
