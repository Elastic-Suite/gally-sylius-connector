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

use Gally\SyliusPlugin\Config\ConfigManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class GallyExtension extends AbstractExtension
{
    public function __construct(
        private ConfigManager $configManager,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_encode_safe', [$this, 'jsonEncodeSafe']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_gally_enabled', [$this, 'isGallyEnabled']),
            new TwigFunction('is_gally_tracking_enabled', [$this, 'isTrackingEnabled']),
        ];
    }

    public function isTrackingEnabled(): bool
    {
        return $this->configManager->isTrackingEnabled();
    }

    public function isGallyEnabled(): bool
    {
        return $this->configManager->isGallyEnabled();
    }

    public function jsonEncodeSafe($data): string
    {
        return json_encode(
            $data,
            \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_UNESCAPED_SLASHES
        );
    }
}
