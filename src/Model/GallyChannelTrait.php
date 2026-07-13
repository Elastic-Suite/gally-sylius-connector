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

namespace Gally\SyliusPlugin\Model;

use Doctrine\ORM\Mapping as ORM;

// @phpstan-ignore-next-line
trait GallyChannelTrait
{
    #[ORM\Column(name: 'gally_active', type: 'boolean', options: ['default' => false])]
    protected bool $gallyActive = false;

    #[ORM\Column(name: 'gally_product_index_batch_size', type: 'integer', options: ['default' => 50])]
    protected int $gallyProductIndexBatchSize = 50;

    #[ORM\Column(name: 'gally_category_index_batch_size', type: 'integer', options: ['default' => 50])]
    protected int $gallyCategoryIndexBatchSize = 50;

    #[ORM\Column(name: 'gally_autocomplete_product_max_size', type: 'integer', options: ['default' => 6])]
    protected int $gallyAutocompleteProductMaxSize = 6;

    #[ORM\Column(name: 'gally_autocomplete_category_max_size', type: 'integer', options: ['default' => 6])]
    protected int $gallyAutocompleteCategoryMaxSize = 6;

    #[ORM\Column(name: 'gally_autocomplete_attribute_max_size', type: 'integer', options: ['default' => 6])]
    protected int $gallyAutocompleteAttributeMaxSize = 6;

    #[ORM\Column(name: 'gally_tracking_active', type: 'boolean', options: ['default' => true])]
    protected bool $gallyTrackingActive = true;

    #[ORM\Column(name: 'gally_use_sylius_endpoint_tracking', type: 'boolean', options: ['default' => true])]
    protected bool $gallyUseSyliusEndpointTracking = true;

    #[ORM\Column(name: 'gally_uid_cookie_lifetime', type: 'integer', options: ['default' => 3600])]
    protected int $gallyUidCookieLifetime = 3600;

    #[ORM\Column(name: 'gally_vid_cookie_lifetime', type: 'integer', options: ['default' => 31536000])]
    protected int $gallyVidCookieLifetime = 31536000;

    #[ORM\Column(name: 'gally_product_recommendation_max_size', type: 'integer', options: ['default' => 4])]
    protected int $gallyProductRecommendationMaxSize = 4;

    #[ORM\Column(name: 'gally_cart_recommendation_type_code', type: 'string', nullable: true)]
    protected ?string $gallyCartRecommendationTypeCode = null;

    #[ORM\Column(name: 'gally_cart_recommendation_max_size', type: 'integer', options: ['default' => 4])]
    protected int $gallyCartRecommendationMaxSize = 4;

    public function getGallyActive(): bool
    {
        return $this->gallyActive;
    }

    public function setGallyActive(bool $isGallyActive): void
    {
        $this->gallyActive = $isGallyActive;
    }

    public function getGallyProductIndexBatchSize(): int
    {
        return $this->gallyProductIndexBatchSize;
    }

    public function setGallyProductIndexBatchSize(int $gallyProductIndexBatchSize): void
    {
        $this->gallyProductIndexBatchSize = $gallyProductIndexBatchSize;
    }

    public function getGallyCategoryIndexBatchSize(): int
    {
        return $this->gallyCategoryIndexBatchSize;
    }

    public function setGallyCategoryIndexBatchSize(int $gallyCategoryIndexBatchSize): void
    {
        $this->gallyCategoryIndexBatchSize = $gallyCategoryIndexBatchSize;
    }

    public function getGallyAutocompleteProductMaxSize(): int
    {
        return $this->gallyAutocompleteProductMaxSize;
    }

    public function setGallyAutocompleteProductMaxSize(int $gallyAutocompleteProductMaxSize): void
    {
        $this->gallyAutocompleteProductMaxSize = $gallyAutocompleteProductMaxSize;
    }

    public function getGallyAutocompleteCategoryMaxSize(): int
    {
        return $this->gallyAutocompleteCategoryMaxSize;
    }

    public function setGallyAutocompleteCategoryMaxSize(int $gallyAutocompleteCategoryMaxSize): void
    {
        $this->gallyAutocompleteCategoryMaxSize = $gallyAutocompleteCategoryMaxSize;
    }

    public function getGallyAutocompleteAttributeMaxSize(): int
    {
        return $this->gallyAutocompleteAttributeMaxSize;
    }

    public function setGallyAutocompleteAttributeMaxSize(int $gallyAutocompleteAttributeMaxSize): void
    {
        $this->gallyAutocompleteAttributeMaxSize = $gallyAutocompleteAttributeMaxSize;
    }

    public function getGallyTrackingActive(): bool
    {
        return $this->gallyTrackingActive;
    }

    public function setGallyTrackingActive(bool $gallyTrackingActive): void
    {
        $this->gallyTrackingActive = $gallyTrackingActive;
    }

    public function getGallyUseSyliusEndpointTracking(): bool
    {
        return $this->gallyUseSyliusEndpointTracking;
    }

    public function setGallyUseSyliusEndpointTracking(bool $gallyUseSyliusEndpointTracking): void
    {
        $this->gallyUseSyliusEndpointTracking = $gallyUseSyliusEndpointTracking;
    }

    public function getGallyUidCookieLifetime(): int
    {
        return $this->gallyUidCookieLifetime;
    }

    public function setGallyUidCookieLifetime(int $gallyUidCookieLifetime): void
    {
        $this->gallyUidCookieLifetime = $gallyUidCookieLifetime;
    }

    public function getGallyVidCookieLifetime(): int
    {
        return $this->gallyVidCookieLifetime;
    }

    public function setGallyVidCookieLifetime(int $gallyVidCookieLifetime): void
    {
        $this->gallyVidCookieLifetime = $gallyVidCookieLifetime;
    }

    public function getGallyProductRecommendationMaxSize(): int
    {
        return $this->gallyProductRecommendationMaxSize;
    }

    public function setGallyProductRecommendationMaxSize(int $gallyProductRecommendationMaxSize): void
    {
        $this->gallyProductRecommendationMaxSize = $gallyProductRecommendationMaxSize;
    }

    public function getGallyCartRecommendationTypeCode(): ?string
    {
        return $this->gallyCartRecommendationTypeCode;
    }

    public function setGallyCartRecommendationTypeCode(?string $gallyCartRecommendationTypeCode): void
    {
        $this->gallyCartRecommendationTypeCode = $gallyCartRecommendationTypeCode;
    }

    public function getGallyCartRecommendationMaxSize(): int
    {
        return $this->gallyCartRecommendationMaxSize;
    }

    public function setGallyCartRecommendationMaxSize(int $gallyCartRecommendationMaxSize): void
    {
        $this->gallyCartRecommendationMaxSize = $gallyCartRecommendationMaxSize;
    }
}
