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

namespace Gally\SyliusPlugin\Indexer\Provider;

use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Entity\SourceField;

/**
 * Gally source field provider for the plugin's built-in fields, not tied to any Sylius attribute or option.
 */
class StaticSourceFieldProvider implements ProviderInterface
{
    public function getEntity(): string
    {
        return 'sourceField';
    }

    /**
     * @return iterable<SourceField>
     */
    public function provide(): iterable
    {
        $staticSourceField = [
            'product' => ['slug' => 'text'],
            'category' => ['slug' => 'text'],
        ];

        foreach ($staticSourceField as $entity => $fields) {
            $metadata = new Metadata($entity);
            foreach ($fields as $code => $type) {
                yield new SourceField($metadata, $code, $type, $code, []);
            }
        }
    }
}
