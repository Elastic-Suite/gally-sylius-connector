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
use Gally\SyliusPlugin\Indexer\Provider\ProductAttributeSourceFieldOptionProvider;
use Gally\SyliusPlugin\Indexer\Provider\ProductAttributeSourceFieldProvider;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ProductAttributeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private ProductAttributeSourceFieldProvider $sourceFieldProvider,
        private ProductAttributeSourceFieldOptionProvider $sourceFieldOptionProvider,
        private StructureSynchonizer $structureSynchonizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product_attribute.post_update' => 'onProductAttributeUpdate',
            'sylius.product_attribute.post_create' => 'onProductAttributeUpdate',
        ];
    }

    public function onProductAttributeUpdate(GenericEvent $event): void
    {
        $this->localizedCatalogRepository->findAll();
        $attribute = $event->getSubject();
        if (!$attribute instanceof ProductAttributeInterface) {
            return;
        }

        $sourceField = $this->sourceFieldProvider->buildSourceField('product', $attribute);
        $this->structureSynchonizer->syncSourceField($sourceField);
        if ('select' === $attribute->getType()) {
            $position = 0;
            $configuration = $attribute->getConfiguration();
            /** @var array<array<string, string>|null> $choices */
            $choices = $configuration['choices'] ?? [];
            $options = [];
            foreach ($choices as $code => $choice) {
                $translations = [];
                foreach ($choice ?? [] as $locale => $translation) {
                    $translations[] = [
                        'locale' => $locale,
                        'translation' => $translation,
                    ];
                }
                /** @var ?string $defaultLabel */
                $defaultLabel = reset($translations)['translation'] ?? $attribute->getCode();

                $options[] = $this->sourceFieldOptionProvider->buildSourceFieldOption(
                    $sourceField,
                    $code,
                    (string) $defaultLabel,
                    $translations,
                    ++$position,
                );
            }
            $this->structureSynchonizer->syncAllSourceFieldOptions($options);
        }
    }
}
