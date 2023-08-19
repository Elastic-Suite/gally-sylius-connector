<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait GallyChannelTrait
{
    /**
     * @ORM\Column(name="gally_active", type="boolean")
     **/
    protected $gallyActive = false;

    public function getGallyActive(): bool
    {
        return $this->gallyActive;
    }

    public function setGallyActive(bool $isGallyActive): void
    {
        $this->gallyActive = $isGallyActive;
    }
}
