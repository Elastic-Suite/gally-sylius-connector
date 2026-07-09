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

namespace Gally\SyliusPlugin\Grid\Filter\Type;

use Sylius\Bundle\GridBundle\Form\Type\Filter\SelectFilterType as BaseSelectFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SelectFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['has_more', 'search_url'])
            ->addAllowedTypes('has_more', 'bool')
            ->addAllowedTypes('search_url', 'string');
    }

    public function getParent(): string
    {
        return BaseSelectFilterType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $view->vars['has_more'] = \array_key_exists('has_more', $options) ? $options['has_more'] : false;
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $view->vars['search_url'] = \array_key_exists('search_url', $options) ? $options['search_url'] : null;
    }
}
