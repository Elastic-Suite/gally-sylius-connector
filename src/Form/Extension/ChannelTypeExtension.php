<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Form\Extension;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('gallyProductIndexBatchSize', TextType::class, [
                'label' => 'gally_sylius.form.product_index_batch_size',
            ])
            ->add('gallyCategoryIndexBatchSize', TextType::class, [
                'label' => 'gally_sylius.form.category_index_batch_size',
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChannelType::class];
    }
}
