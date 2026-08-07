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

namespace Gally\SyliusPlugin\Twig\Component\Filter;

use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\GraphQl\Request as GallyRequest;
use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Search\FilterConverter;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Renders one checkbox facet (in-facet search + "view more" + the choice list itself) and keeps
 * it in sync with Gally without a page reload, replacing the former fetch-based Stimulus
 * controller. Selecting a checkbox is untouched: it's a plain native input, still picked up by
 * the FiltersAutosubmit controller bubbling from #searchbar.
 */
#[AsLiveComponent(name: 'gally_shop:filter:facet_options', template: '@GallySyliusPlugin/shop/product/_shared/facet_options.html.twig')]
class FacetOptionsComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $filterField = '';

    #[LiveProp]
    public string $fieldName = '';

    #[LiveProp]
    public string $baseId = '';

    /** @var list<array{label: string, value: string}> */
    #[LiveProp]
    public array $initialChoices = [];

    /** @var string[] */
    #[LiveProp]
    public array $selectedValues = [];

    #[LiveProp]
    public bool $hasMore = false;

    #[LiveProp]
    public ?string $taxonCode = null;

    /** @var array<string, mixed> */
    #[LiveProp]
    public array $filters = [];

    #[LiveProp]
    public ?string $search = null;

    #[LiveProp(writable: true)]
    public string $optionSearch = '';

    #[LiveProp(writable: true)]
    public bool $expanded = false;

    public function __construct(
        private CatalogProvider $catalogProvider,
        private SearchManager $searchManager,
        private ChannelContextInterface $channelContext,
        private LocaleContextInterface $localeContext,
        private FilterConverter $filterConverter,
    ) {
    }

    #[LiveAction]
    public function clearSearch(): void
    {
        $this->optionSearch = '';
    }

    #[LiveAction]
    public function viewMore(): void
    {
        $this->expanded = true;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    #[ExposeInTemplate('choices')]
    public function getChoices(): array
    {
        if ('' === $this->optionSearch && !$this->expanded) {
            return $this->initialChoices;
        }

        $choices = [];
        $aggregationOptions = $this->searchManager->viewMoreProductFilterOption(
            $this->buildGallyRequest(),
            $this->filterField,
            '' !== $this->optionSearch ? $this->optionSearch : null,
        );

        /** @var array<string, string> $option */
        foreach ($aggregationOptions as $option) {
            if (isset($option['label'])) {
                $choices[] = ['label' => $option['label'], 'value' => $option['value'] ?? ''];
            }
        }

        return $choices;
    }

    private function buildGallyRequest(): GallyRequest
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $currentLocaleCode = $this->localeContext->getLocaleCode();
        $currentLocale = $channel->getDefaultLocale();
        if (null === $currentLocale) {
            throw new \LogicException(sprintf('Missing default locale on channel %s', $channel->getName()));
        }

        foreach ($channel->getLocales() as $locale) {
            if ($currentLocaleCode === $locale->getCode()) {
                $currentLocale = $locale;
                break;
            }
        }

        $gallyFilters = [];
        foreach ($this->filters as $field => $value) {
            $gallyFilter = $this->filterConverter->convert((string) $field, $value);
            if (null !== $gallyFilter) {
                $gallyFilters[] = $gallyFilter;
            }
        }

        return new GallyRequest(
            $this->catalogProvider->buildLocalizedCatalog($channel, $currentLocale),
            new Metadata('product'),
            false,
            ['sku', 'source'],
            1,
            0,
            $this->taxonCode,
            $this->search,
            $gallyFilters,
        );
    }
}
