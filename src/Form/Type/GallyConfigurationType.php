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

use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GallyConfigurationType extends AbstractType
{
    public function __construct(
        private RepositoryInterface $productAssociationTypeRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('baseUrl', TextType::class)
            ->add('checkSsl', CheckboxType::class, [
                'label' => 'gally_sylius.form.checkSSL',
                'required' => false,
            ])
            ->add('userName', TextType::class)
            ->add('password', PasswordType::class)
            ->add('productRecommendationTypeCodes', ChoiceType::class, [
                'label' => 'gally_sylius.form.product_recommendation_type_codes',
                'choices' => $this->getProductAssociationTypeChoices(),
                'multiple' => true,
                'required' => false,
            ])
            ->add('productRecommendationMaxSize', IntegerType::class, [
                'label' => 'gally_sylius.form.product_recommendation_max_size',
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('cartRecommendationTypeCodes', ChoiceType::class, [
                'label' => 'gally_sylius.form.cart_recommendation_type_codes',
                'choices' => $this->getProductAssociationTypeChoices(),
                'multiple' => true,
                'required' => false,
            ])
            ->add('cartRecommendationMaxSize', IntegerType::class, [
                'label' => 'gally_sylius.form.cart_recommendation_max_size',
                'attr' => [
                    'min' => 0,
                ],
            ]);
    }

    /**
     * @return array<string, string>
     */
    private function getProductAssociationTypeChoices(): array
    {
        $choices = [];
        /** @var ProductAssociationTypeInterface $productAssociationType */
        foreach ($this->productAssociationTypeRepository->findAll() as $productAssociationType) {
            $choices[(string) $productAssociationType->getName()] = (string) $productAssociationType->getCode();
        }

        return $choices;
    }
}
