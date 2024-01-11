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

namespace Gally\SyliusPlugin\Synchronizer;

use Gally\Rest\Model\Metadata;
use Gally\Rest\Model\MetadataMetadataRead;
use Gally\Rest\Model\MetadataMetadataWrite;
use Gally\Rest\Model\ModelInterface;

final class MetadataSynchronizer extends AbstractSynchronizer
{
    public function synchronizeAll(): void
    {
    }

    public function synchronizeItem(array $params): ?ModelInterface
    {
        $this->fetchEntities();
        return $this->createOrUpdateEntity(new MetadataMetadataWrite(['entity' => $params['entity']]));
    }

    public function getIdentity(ModelInterface $entity): string
    {
        /** @var MetadataMetadataRead    $entity */
        return $entity->getEntity();
    }
}
