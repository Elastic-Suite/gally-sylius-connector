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
use Twig\TwigFunction;

class GallyEnabledExtension extends AbstractExtension
{
    public function __construct(
        private ConfigManager $configManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_gally_enabled', [$this, 'isGallyEnabled']),
        ];
    }

    public function isGallyEnabled(): bool
    {
        return $this->configManager->isGallyEnabled();
    }
}
