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

namespace Gally\SyliusPlugin\Indexer\Subscriber;

use Gally\Sdk\Repository\LocalizedCatalogRepository;
use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Indexer\Provider\SourceFieldOptionProvider;
use Gally\SyliusPlugin\Indexer\Provider\SourceFieldProvider;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ProductAttributeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private SourceFieldProvider $sourceFieldProvider,
        private SourceFieldOptionProvider $sourceFieldOptionProvider,
        private StructureSynchonizer $structureSynchonizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product_attribute.post_update' => 'onProductUpdate',
            'sylius.product_attribute.post_create' => 'onProductUpdate',
            'sylius.product_option.post_update' => 'onProductUpdate',
            'sylius.product_option.post_create' => 'onProductUpdate',
        ];
    }

    public function onProductUpdate(GenericEvent $event): void
    {
        $this->localizedCatalogRepository->findAll();
        $attribute = $event->getSubject();
        if ($attribute instanceof ProductAttributeInterface) {
            $sourceField = $this->sourceFieldProvider->buildSourceField('product', $attribute);
            $this->structureSynchonizer->syncSourceField($sourceField);
            if ('select' === $attribute->getType()) {
                $position = 0;
                $configuration = $attribute->getConfiguration();
                $choices = $configuration['choices'] ?? [];
                foreach ($choices as $code => $choice) {
                    $translations = [];
                    foreach ($choice ?? [] as $locale => $translation) {
                        $translations[] = [
                            'locale' => $locale,
                            'translation' => $translation,
                        ];
                    }
                    $defaultLabel = reset($translations)['translation'] ?: $attribute->getCode();

                    $option = $this->sourceFieldOptionProvider->buildSourceFieldOption(
                        $sourceField,
                        $code,
                        $defaultLabel,
                        $translations,
                        ++$position,
                    );
                    $this->structureSynchonizer->syncSourceFieldOption($option);
                }
            }
        } elseif ($attribute instanceof ProductOptionInterface) {
            $sourceField = $this->sourceFieldProvider->buildSourceField('product', $attribute, 'select');
            $this->structureSynchonizer->syncSourceField($sourceField);
            $position = 0;
            /** @var ProductOptionValueInterface $value */
            foreach ($attribute->getValues() as $value) {
                $translations = $value->getTranslations();
                $defaultLabel = reset($translations)['translation'] ?: $value->getCode();
                $option = $this->sourceFieldOptionProvider->buildSourceFieldOption(
                    $sourceField,
                    $value->getCode(),
                    $defaultLabel,
                    $translations,
                    ++$position,
                );
                $this->structureSynchonizer->syncSourceFieldOption($option);
            }
        }
    }
}
