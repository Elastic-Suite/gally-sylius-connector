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

namespace Gally\SyliusPlugin\Controller;

use Gally\SyliusPlugin\Form\Type\Filter\GallyDynamicFilterType;
use Gally\SyliusPlugin\Grid\Filter\Type\SelectFilterType;
use Gally\SyliusPlugin\Search\Adapter;
use Gally\SyliusPlugin\Service\FilterConverter;
use Sylius\Bundle\TaxonomyBundle\Doctrine\ORM\TaxonRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Filter extends AbstractController
{
    public function __construct(
        private Adapter $adapter,
        private ChannelContextInterface $channelContext,
        private LocaleContextInterface $localeContext,
        private TaxonRepository $taxonRepository,
        private FormFactoryInterface $formFactory,
        private FilterConverter $filterConverter,
    ) {
    }

    public function viewMore(Request $request, string $filterField): Response
    {
        $search = $request->get('search');
        $filters = $request->get('filters')['gally'] ?? [];
        $gallyFilters = [];
        foreach ($filters as $field => $value) {
            $gallyFilter = $this->filterConverter->convert($field, $value);
            if ($gallyFilter) {
                $gallyFilters[] = $gallyFilter;
            }
        }

        $choices = [];
        $currentTaxonId = $request->get('taxon');
        $aggregationOptions = $this->adapter->viewMoreOption(
            $this->channelContext->getChannel(),
            $currentTaxonId ? $this->taxonRepository->find($currentTaxonId) : null,
            $this->localeContext->getLocaleCode(),
            $filterField,
            $gallyFilters,
            $search,
        );

        foreach ($aggregationOptions as $option) {
            $choices[$option['label']] = $option['value'];
        }

        $options = [
            'block_prefix' => 'sylius_gally_filter_checkbox',
            'choices' => $choices,
            'expanded' => true,
            'multiple' => true,
        ];

        $form = $this->formFactory->createNamed('criteria')->add('gally', GallyDynamicFilterType::class);
        $form->get('gally')->add($filterField, SelectFilterType::class, $options);
        $form->get('gally')->get($filterField)->setData($filters[$filterField] ?? null);
        $html = $this->renderView('@GallySyliusPlugin/Grid/Filter/gally_dynamic_filter.html.twig', ['form' => $form]);

        return $this->json(['html' => $html]);
    }
}
