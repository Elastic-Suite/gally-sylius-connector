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

namespace Gally\SyliusPlugin\Controller\Shop;

use Gally\Sdk\GraphQl\Response as GallyResponse;
use Gally\SyliusPlugin\Form\Type\SearchFormType;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Search\Finder;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends AbstractController
{
    public function __construct(
        private Finder $finder,
        private ChannelContextInterface $channelContext,
    ) {
    }

    public function getForm(Request $renderRequest, RequestStack $requestStack): Response
    {
        /** @var string $query */
        $query = $requestStack->getMainRequest()?->get('query');
        if (empty($query)) {
            /** @var array $query */
            $query = $requestStack->getMainRequest()?->get('criteria', []);
            $query = $query['search']['value'] ?? '';
        }

        $searchForm = $this->createForm(
            SearchFormType::class,
            ['query' => $query],
            ['action' => $this->generateUrl('gally_search_result_page'), 'method' => 'POST']
        );

        return $this->render(
            '@GallySyliusPlugin/shop/shared/components/header/search/form.html.twig',
            [
                'searchForm' => $searchForm->createView(),
                'mobileMode' => $renderRequest->get('mobile_mode'),
            ]
        );
    }

    public function getResults(Request $request): Response
    {
        $searchForm = $this->createForm(SearchFormType::class);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            return new RedirectResponse(
                $this->generateUrl('gally_search_result_page', ['query' => $searchForm->get('query')->getData()])
            );
        }

        return new RedirectResponse('/');
    }

    public function getPreview(Request $request): Response
    {
        $searchForm = $this->createForm(SearchFormType::class);
        $searchForm->handleRequest($request);
        /** @var GallyChannelInterface $currentChannel */
        $currentChannel = $this->channelContext->getChannel();

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            /** @var string $query */
            $query = $searchForm->get('query')->getData();

            $products = $this->getProductAutocomplete($query, $currentChannel);

            return new JsonResponse([
                'htmlResults' => $this->renderView(
                    '@GallySyliusPlugin/shop/shared/components/header/search/autocomplete/results.html.twig',
                    [
                        'products' => $products->getCollection(),
                        'categories' => $this->getCategoryAutocomplete($query, $currentChannel),
                        'attributes' => $this->getAttributeAutocomplete($products, $currentChannel),
                        'termSuggestions' => $this->getTermSuggestionsAutocomplete($products),
                        'query' => $query, $this->generateUrl('gally_search_result_page', ['query' => $query]),
                    ]
                ),
            ]);
        }

        return new JsonResponse(['gallyError' => true]);
    }

    private function getProductAutocomplete(string $query, GallyChannelInterface $channel): GallyResponse
    {
        return $this->finder->getAutocompleteResults(
            $query,
            $channel->getGallyAutocompleteProductMaxSize(),
            'product',
            ['sku', 'name', 'slug', 'image']
        );
    }

    private function getCategoryAutocomplete(string $query, GallyChannelInterface $channel): array
    {
        $categories = $this->finder
            ->getAutocompleteResults(
                $query,
                $channel->getGallyAutocompleteCategoryMaxSize(),
                'category',
                ['id', 'name', 'path', 'slug']
            )
            ->getCollection();

        foreach ($categories as &$category) {
            $category['path'] = implode(
                ' > ',
                array_map(
                    fn (string $slug) => ucfirst($slug),
                    explode('/', $category['slug'])
                )
            );
        }

        return $categories;
    }

    private function getAttributeAutocomplete(GallyResponse $products, GallyChannelInterface $channel): array
    {
        $attributes = [];
        $count = 0;
        foreach ($products->getAggregations() as $aggregation) {
            foreach ($aggregation['options'] as $option) {
                $attributes[] = [
                    'field' => $aggregation['field'],
                    'label' => $aggregation['label'],
                    'option_label' => $option['label'],
                ];
                ++$count;
                if ($count >= $channel->getGallyAutocompleteAttributeMaxSize()) {
                    break 2;
                }
            }
        }

        return $attributes;
    }

    private function getTermSuggestionsAutocomplete(GallyResponse $products): array
    {
        $termSuggestions = [];
        foreach ($products->getTermSuggestions()['terms'] ?? [] as $termSuggestion) {
            $termSuggestions[] = [
                'term' => $termSuggestion['term'],
                'resultCount' => (int) round((float) $termSuggestion['resultCount']),
            ];
        }

        return $termSuggestions;
    }
}
