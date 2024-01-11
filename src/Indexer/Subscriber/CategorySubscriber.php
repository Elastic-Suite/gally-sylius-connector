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

use Gally\SyliusPlugin\Indexer\CategoryIndexer;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class CategorySubscriber implements EventSubscriberInterface
{
    public function __construct(private CategoryIndexer $categoryIndexer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.taxon.post_update' => 'onTaxonUpdate',
            'sylius.taxon.post_create' => 'onTaxonUpdate',
        ];
    }

    public function onTaxonUpdate(GenericEvent $event): void
    {
        $taxon = $event->getSubject();
        if ($taxon instanceof TaxonInterface) {
            $this->categoryIndexer->reindex([$taxon->getId()]);
        }
    }
}
