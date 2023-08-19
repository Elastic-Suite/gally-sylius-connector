<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Form\Extension;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class ChannelTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gallyActive', CheckboxType::class, [
                'required' => true,
                'label' => 'gally_sylius_plugin.form.active',
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChannelType::class];
    }
}
