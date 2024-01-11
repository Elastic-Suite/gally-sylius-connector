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

namespace Gally\SyliusPlugin\ContextProvider;

use Gally\Rest\Api\ProductSortingOptionApi;
use Gally\Rest\Model\ProductSortingOption;
use Gally\SyliusPlugin\Api\RestClient;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Bundle\UiBundle\ContextProvider\ContextProviderInterface;
use Sylius\Bundle\UiBundle\Registry\TemplateBlock;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class SortOptionContextProvider implements ContextProviderInterface
{
    public function __construct(
        private RestClient $client,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private ChannelContextInterface $channelContext
    ) {
    }

    public function provide(array $templateContext, TemplateBlock $templateBlock): array
    {
        $currentSortOrder = $this->requestStack->getMainRequest()->get('sorting', []);

        $templateContext['current_sorting_label'] = '';
        $templateContext['sort_options'] = [];

        $channel = $this->channelContext->getChannel();
        if (($channel instanceof GallyChannelInterface) && $channel->getGallyActive()) {
            $sortingOptions = $this->client->query(ProductSortingOptionApi::class, 'getProductSortingOptionCollection');
            foreach ($sortingOptions as $option) {
                /** @var ProductSortingOption $option */
                $templateContext['sort_options'][] = [
                    'field' => $option->getCode(),
                    'sorting' => [$option->getCode() => 'asc'],
                    'label' => $option->getLabel(),
                ];

                if (isset($currentSortOrder[$option->getCode()])) {
                    $templateContext['current_sorting_label'] = $option->getLabel();
                }
            }
        } else {
            // default Sylius sorting logic (copied from @SyliusShopBundle/Product/Index/_sorting.html.twig template)
            $templateContext['sort_options'] = [
                ['field' => '', 'sorting' => null, 'label' => $this->translator->trans('sylius.ui.by_position')],
                ['field' => 'name', 'sorting' => ['name' => 'asc'], 'label' => $this->translator->trans('sylius.ui.from_a_to_z')],
                ['field' => 'name', 'sorting' => ['name' => 'desc'], 'label' => $this->translator->trans('sylius.ui.from_z_to_a')],
                ['field' => 'sorting', 'sorting' => ['sorting' => 'desc'], 'label' => $this->translator->trans('sylius.ui.newest_first')],
                ['field' => 'sorting', 'sorting' => ['sorting' => 'asc'], 'label' => $this->translator->trans('sylius.ui.oldest_first')],
                ['field' => 'price', 'sorting' => ['price' => 'asc'], 'label' => $this->translator->trans('sylius.ui.cheapest_first')],
                ['field' => 'price', 'sorting' => ['price' => 'desc'], 'label' => $this->translator->trans('sylius.ui.most_expensive_first')],
            ];

            foreach ($templateContext['sort_options'] as $option) {
                if (empty($templateContext['current_sorting_label'])) {
                    // set first element by default
                    $templateContext['current_sorting_label'] = strtolower($option['label']);
                } elseif ($option['sorting'] === $currentSortOrder) {
                    $templateContext['current_sorting_label'] = strtolower($option['label']);
                    break;
                }
            }
        }

        return $templateContext;
    }

    public function supports(TemplateBlock $templateBlock): bool
    {
        return 'sylius.shop.product.index.search' === $templateBlock->getEventName()
            && 'sorting' === $templateBlock->getName();
    }
}
