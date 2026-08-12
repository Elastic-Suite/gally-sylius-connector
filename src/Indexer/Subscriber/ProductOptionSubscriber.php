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
use Gally\SyliusPlugin\Indexer\Provider\ProductOptionSourceFieldOptionProvider;
use Gally\SyliusPlugin\Indexer\Provider\ProductOptionSourceFieldProvider;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ProductOptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private ProductOptionSourceFieldProvider $sourceFieldProvider,
        private ProductOptionSourceFieldOptionProvider $sourceFieldOptionProvider,
        private StructureSynchonizer $structureSynchonizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product_option.post_update' => 'onProductOptionUpdate',
            'sylius.product_option.post_create' => 'onProductOptionUpdate',
        ];
    }

    public function onProductOptionUpdate(GenericEvent $event): void
    {
        $this->localizedCatalogRepository->findAll();
        $option = $event->getSubject();
        if (!$option instanceof ProductOptionInterface) {
            return;
        }

        $sourceField = $this->sourceFieldProvider->buildSourceField('product', $option, 'select');
        $this->structureSynchonizer->syncSourceField($sourceField);
        $position = 0;
        $options = [];
        /** @var ProductOptionValueInterface $value */
        foreach ($option->getValues() as $value) {
            /** @var \Doctrine\Common\Collections\Collection<int, \Sylius\Component\Product\Model\ProductOptionValueTranslation> $translations */
            $translations = $value->getTranslations();
            $firstTranslation = $translations->first();
            $defaultLabel = false !== $firstTranslation ? $firstTranslation->getValue() : (string) $value->getCode();
            $options[] = $this->sourceFieldOptionProvider->buildSourceFieldOption(
                $sourceField,
                (string) $value->getCode(),
                (string) $defaultLabel,
                $translations,
                ++$position,
            );
        }
        $this->structureSynchonizer->syncAllSourceFieldOptions($options);
    }
}
