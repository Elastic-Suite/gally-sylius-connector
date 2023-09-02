<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Indexer\Subscriber;

use Gally\SyliusPlugin\Indexer\CategoryIndexer;
use Sylius\Component\Core\Model\Taxon;
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
        if ($taxon instanceof Taxon) {
            $this->categoryIndexer->reindex([$taxon->getId()]);
        }
    }
}
