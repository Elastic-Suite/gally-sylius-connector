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

trait GallyChannelTrait
{
    /**
     * @ORM\Column(name="gally_active", type="boolean")
     */
    protected $gallyActive = false;
    /**
     * @ORM\Column(name="gally_product_index_batch_size", type="integer")
     */
    protected $gallyProductIndexBatchSize = 50;
    /**
     * @ORM\Column(name="gally_category_index_batch_size", type="integer")
     */
    protected $gallyCategoryIndexBatchSize = 50;

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
}
