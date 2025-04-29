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

use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Bundle\UiBundle\ContextProvider\ContextProviderInterface;
use Sylius\Bundle\UiBundle\Registry\TemplateBlock;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class SortOptionContextProvider implements ContextProviderInterface
{
    private array $translationKeys = [
        'name.asc' => 'sylius.ui.from_a_to_z',
        'name.desc' => 'sylius.ui.from_z_to_a',
        'price__price.asc' => 'sylius.ui.cheapest_first',
        'price__price.desc' => 'sylius.ui.most_expensive_first',
    ];

    public function __construct(
        private SearchManager $searchManager,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private ChannelContextInterface $channelContext,
    ) {
    }

    public function provide(array $templateContext, TemplateBlock $templateBlock): array
    {
        $currentSortOrder = $this->requestStack->getMainRequest()->get('sorting', []);
        $criteria = $this->requestStack->getMainRequest()->get('criteria', []);
        $search = (isset($criteria['search'], $criteria['search']['value'])) ? $criteria['search']['value'] : '';

        $templateContext['current_sorting_label'] = '';
        $templateContext['sort_options'] = [
            ['field' => 'category__position', 'sorting' => null, 'label' => $this->translator->trans('sylius.ui.by_position')],
        ];

        $channel = $this->channelContext->getChannel();
        if (($channel instanceof GallyChannelInterface) && $channel->getGallyActive()) {
            foreach ($this->searchManager->getProductSortingOptions() as $option) {
                if (\in_array($option->getCode(), ['category__position', '_score'], true)) {
                    continue;
                }
                foreach (['asc', 'desc'] as $direction) {
                    $label = \array_key_exists("{$option->getCode()}.$direction", $this->translationKeys)
                        ? $this->translator->trans($this->translationKeys["{$option->getCode()}.$direction"])
                        : $option->getDefaultLabel() . ' ' . $this->translator->trans('gally_sylius.ui.sort.direction.' . $direction);
                    $templateContext['sort_options'][] = [
                        'field' => $option->getCode(),
                        'sorting' => [$option->getCode() => $direction],
                        'label' => $label,
                    ];

                    if (isset($currentSortOrder[$option->getCode()]) && $currentSortOrder[$option->getCode()] == $direction) {
                        $templateContext['current_sorting_label'] = $label;
                    }
                }
            }

            if ((bool) $search) {
                $templateContext['sort_options'][] = [
                    'field' => 'category__position',
                    'sorting' => null,
                    'label' => $this->translator->trans('gally_sylius.ui.sort.relevance'),
                ];
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
                if ('' === $templateContext['current_sorting_label']) {
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
