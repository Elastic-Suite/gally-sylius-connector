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

use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Sylius\TwigHooks\Twig\Component\HookableComponentTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class CategoryTrackingComponent
{
    use HookableComponentTrait;

    /**
     * @param TaxonRepositoryInterface<TaxonInterface> $taxonRepository
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TaxonRepositoryInterface $taxonRepository,
        private readonly LocaleContextInterface $localeContext,
    ) {
    }

    #[ExposeInTemplate('taxon')]
    public function taxon(): ?TaxonInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $taxonSlug = $request?->attributes->get('slug');

        if (!\is_string($taxonSlug)) {
            return null;
        }

        return $this->taxonRepository->findOneBySlug($taxonSlug, $this->localeContext->getLocaleCode());
    }
}
