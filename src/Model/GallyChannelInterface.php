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

use Sylius\Component\Core\Model\ChannelInterface;

interface GallyChannelInterface extends ChannelInterface
{
    public function getGallyActive(): bool;

    public function setGallyActive(bool $isGallyActive): void;

    public function getGallyProductIndexBatchSize(): int;

    public function setGallyProductIndexBatchSize(int $gallyProductIndexBatchSize): void;

    public function getGallyCategoryIndexBatchSize(): int;

    public function setGallyCategoryIndexBatchSize(int $gallyCategoryIndexBatchSize): void;

    public function getGallyAutocompleteProductMaxSize(): int;

    public function setGallyAutocompleteProductMaxSize(int $gallyAutocompleteProductMaxSize): void;

    public function getGallyAutocompleteCategoryMaxSize(): int;

    public function setGallyAutocompleteCategoryMaxSize(int $gallyAutocompleteCategoryMaxSize): void;

    public function getGallyAutocompleteAttributeMaxSize(): int;

    public function setGallyAutocompleteAttributeMaxSize(int $gallyAutocompleteAttributeMaxSize): void;

    public function getGallyTrackingActive(): bool;

    public function setGallyTrackingActive(bool $gallyTrackingActive): void;

    public function getGallyUseSyliusEndpointTracking(): bool;

    public function setGallyUseSyliusEndpointTracking(bool $gallyUseSyliusEndpointTracking): void;

    public function getGallyUidCookieLifetime(): int;
    
    public function setGallyUidCookieLifetime(int $gallyUidCookieLifetime): void;

    public function getGallyVidCookieLifetime(): int;

    public function setGallyVidCookieLifetime(int $gallyVidCookieLifetime): void;
}
