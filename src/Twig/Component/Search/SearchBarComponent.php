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

namespace Gally\SyliusPlugin\Twig\Component\Search;

use Gally\SyliusPlugin\Form\Type\SearchFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Twig Component that exposes the search form to the search_bar_wrapper template.
 * DOM placement (desktop vs mobile slot) is handled client-side by SearchBarMoverController (Stimulus).
 */
#[AsTwigComponent('gally_shop:search:search_bar', template: '@GallySyliusPlugin/shop/shared/components/header/search/search_bar_wrapper.html.twig')]
class SearchBarComponent
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[ExposeInTemplate('searchForm')]
    public function searchForm(): FormView
    {
        /** @var string|null $query */
        $query = $this->requestStack->getMainRequest()?->get('query');
        if (null === $query || '' === $query) {
            /** @var array<string, array<string, string>> $criteria */
            $criteria = $this->requestStack->getMainRequest()?->get('criteria', []);
            $query = $criteria['search']['value'] ?? '';
        }

        $form = $this->formFactory->create(
            SearchFormType::class,
            ['query' => $query],
            [
                'action' => $this->urlGenerator->generate('gally_search_result_page'),
                'method' => 'POST',
            ]
        );

        return $form->createView();
    }
}
