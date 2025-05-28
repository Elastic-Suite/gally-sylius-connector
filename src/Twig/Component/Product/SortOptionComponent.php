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

namespace Gally\SyliusPlugin\Twig\Component\Product;

use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class SortOptionComponent
{
    /** @var string[] */
    private array $translationKeys = [
        'name.asc' => 'sylius.ui.from_a_to_z',
        'name.desc' => 'sylius.ui.from_z_to_a',
        'price__price.asc' => 'sylius.ui.cheapest_first',
        'price__price.desc' => 'sylius.ui.most_expensive_first',
    ];

    /** @var array<string, list<array<string, array<string, string>|string|null>>|string>|null */
    private ?array $sortData = null;

    public function __construct(
        private SearchManager $searchManager,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private ChannelContextInterface $channelContext,
    ) {
    }

    /**
     * @return array<string, list<array<string, array<string, string>|string|null>>|string>
     */
    protected function getSortData(): array
    {
        if (null !== $this->sortData) {
            return $this->sortData;
        }
        $currentSortOrder = $this->requestStack->getMainRequest()?->get('sorting', []);
        /** @var array $currentSortOrder */
        $currentSortOrder = $currentSortOrder ?? [];
        $criteria = $this->requestStack->getMainRequest()?->get('criteria', []);
        /** @var array<string, array<string, string>> $criteria */
        $criteria = $criteria ?? [];
        $search = (isset($criteria['search'], $criteria['search']['value'])) ? $criteria['search']['value'] : '';

        $sortData = [];
        $sortData['current_sorting_label'] = '';
        $sortData['sort_options'] = [
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
                    $sortData['sort_options'][] = [
                        'field' => $option->getCode(),
                        'sorting' => [$option->getCode() => $direction],
                        'label' => $label,
                    ];

                    if (isset($currentSortOrder[$option->getCode()]) && $currentSortOrder[$option->getCode()] == $direction) {
                        $sortData['current_sorting_label'] = $label;
                    }
                }
            }

            if ($search) {
                $sortData['sort_options'][] = [
                    'field' => 'category__position',
                    'sorting' => null,
                    'label' => $this->translator->trans('gally_sylius.ui.sort.relevance'),
                ];
            }
        } else {
            // default Sylius sorting logic (copied from @SyliusShopBundle/Product/Index/_sorting.html.twig template)
            $sortData['sort_options'] = [
                ['field' => '', 'sorting' => null, 'label' => $this->translator->trans('sylius.ui.by_position')],
                ['field' => 'name', 'sorting' => ['name' => 'asc'], 'label' => $this->translator->trans('sylius.ui.from_a_to_z')],
                ['field' => 'name', 'sorting' => ['name' => 'desc'], 'label' => $this->translator->trans('sylius.ui.from_z_to_a')],
                ['field' => 'sorting', 'sorting' => ['sorting' => 'desc'], 'label' => $this->translator->trans('sylius.ui.newest_first')],
                ['field' => 'sorting', 'sorting' => ['sorting' => 'asc'], 'label' => $this->translator->trans('sylius.ui.oldest_first')],
                ['field' => 'price', 'sorting' => ['price' => 'asc'], 'label' => $this->translator->trans('sylius.ui.cheapest_first')],
                ['field' => 'price', 'sorting' => ['price' => 'desc'], 'label' => $this->translator->trans('sylius.ui.most_expensive_first')],
            ];

            foreach ($sortData['sort_options'] as $option) {
                if (empty($sortData['current_sorting_label'])) {
                    // set first element by default
                    $sortData['current_sorting_label'] = strtolower($option['label']);
                } elseif ($option['sorting'] === $currentSortOrder) {
                    $sortData['current_sorting_label'] = strtolower($option['label']);
                    break;
                }
            }
        }

        $this->sortData = $sortData;

        return $this->sortData;
    }

    #[ExposeInTemplate('current_sorting_label')]
    public function currentSortingLabel(): string
    {
        $currentSortingLabel = $this->getSortData()['current_sorting_label'] ?? '';

        return \is_string($currentSortingLabel) ? $currentSortingLabel : '';
    }

    #[ExposeInTemplate('sort_options')]
    public function sortOptions(): array
    {
        $sortOptions = $this->getSortData()['sort_options'] ?? [];

        return !\is_string($sortOptions) ? $sortOptions : [];
    }
}
