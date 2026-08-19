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

namespace Gally\SyliusPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'query',
            TextType::class,
            [
                'label' => false,
                'constraints' => [new NotBlank(normalizer: 'trim')],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'gally_sylius.form.header.query.placeholder',
                    'autocomplete' => 'off',
                    'aria-label' => 'form.header.query.label',
                    'aria-describedby' => 'collapsedSearchResults', // This should be the ID of the button below
                ],
            ]
        );
    }
}
