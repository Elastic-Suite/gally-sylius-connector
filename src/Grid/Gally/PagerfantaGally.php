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

namespace Gally\SyliusPlugin\Grid\Gally;

use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;

class PagerfantaGally extends Pagerfanta implements PagerfantaInterface
{
    private int $currentPage;

    /**
     * {@inheritDoc}
     *
     * Save the current page value in order to reapply it after result calculation.
     */
    public function setCurrentPage(int $currentPage): self
    {
        $this->currentPage = $currentPage;

        return parent::setCurrentPage($currentPage);
    }

    /**
     * {@inheritDoc}
     *
     * Prevent pagerfanta to cache number of results before running gally request.
     */
    public function getNbResults(): int
    {
        return $this->getAdapter()->getNbResults();
    }

    /**
     * {@inheritDoc}
     *
     * Recalculate current page value after fetching result from Gally.
     */
    public function getCurrentPageResults(): iterable
    {
        $currentPageResults = parent::getCurrentPageResults();
        $this->setCurrentPage($this->currentPage);

        return $currentPageResults;
    }
}
