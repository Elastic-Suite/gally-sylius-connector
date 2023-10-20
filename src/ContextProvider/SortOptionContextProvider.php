<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\ContextProvider;

use Gally\Rest\Api\CategorySortingOptionApi;
use Gally\Rest\Model\CategorySortingOption;
use Gally\SyliusPlugin\Api\RestClient;
use Sylius\Bundle\UiBundle\ContextProvider\ContextProviderInterface;
use Sylius\Bundle\UiBundle\Registry\TemplateBlock;

class SortOptionContextProvider implements ContextProviderInterface
{
    public function __construct(protected RestClient $client)
    {
    }

    public function provide(array $templateContext, TemplateBlock $templateBlock): array
    {
        $templateContext['current_sorting_label'] = 'Sort by...';
        $templateContext['sort_options'] = [];

        $sortingOptions = $this->client->query(CategorySortingOptionApi::class, 'getCategorySortingOptionCollection');
        foreach ($sortingOptions as $option) {
            /** @var CategorySortingOption $option */
            $templateContext['sort_options'][] = [
                'field' => $option->getCode(),
                'sorting' => [$option->getCode() => 'asc'],
                'label' => $option->getLabel(),
            ];
        }
        return $templateContext;
    }

    public function supports(TemplateBlock $templateBlock): bool
    {
        return 'sylius.shop.product.index.search' === $templateBlock->getEventName()
            && 'sorting' === $templateBlock->getName();
    }
}
