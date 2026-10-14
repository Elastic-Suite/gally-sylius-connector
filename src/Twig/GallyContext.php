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

namespace Gally\SyliusPlugin\Twig;

use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GallyContext
{
    public function __construct(
        private CatalogProvider $catalogProvider,
        private GallyConfigurationRepository $gallyConfigurationRepository,
        private ChannelContextInterface $channelContext,
        private UrlGeneratorInterface $urlGenerator,
        private RepositoryInterface $productAssociationTypeRepository,
    ) {
    }

    public function getLocalizedCatalog(): LocalizedCatalog
    {
        return $this->catalogProvider->getLocalizedCatalog();
    }

    public function getGallyConfiguration(): GallyConfiguration
    {
        return $this->gallyConfigurationRepository->getConfiguration();
    }

    public function getTrackingBaseUrl(): string
    {
        /** @var GallyChannelInterface $channel */
        $channel = $this->channelContext->getChannel();

        if ($channel->getGallyUseSyliusEndpointTracking()) {
            $graphqlUrl = $this->urlGenerator->generate(
                'gally_tracking_graphql_proxy',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );

            return str_replace('/graphql', '', $graphqlUrl);
        }

        return $this->gallyConfigurationRepository->getConfiguration()->getBaseUrl();
    }

    /**
     * @return ProductAssociationTypeInterface[]
     */
    public function getProductRecommendationTypes(): array
    {
        return $this->findProductAssociationTypes($this->getGallyConfiguration()->getProductRecommendationTypeCodes());
    }

    /**
     * @return ProductAssociationTypeInterface[]
     */
    public function getCartRecommendationTypes(): array
    {
        return $this->findProductAssociationTypes($this->getGallyConfiguration()->getCartRecommendationTypeCodes());
    }

    /**
     * @param string[] $codes
     *
     * @return ProductAssociationTypeInterface[]
     */
    private function findProductAssociationTypes(array $codes): array
    {
        $productAssociationTypes = [];
        foreach ($codes as $code) {
            /** @var ProductAssociationTypeInterface|null $productAssociationType */
            $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $code]);
            if (null !== $productAssociationType) {
                $productAssociationTypes[] = $productAssociationType;
            }
        }

        return $productAssociationTypes;
    }
}
