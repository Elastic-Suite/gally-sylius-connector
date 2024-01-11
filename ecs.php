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

use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests/Behat',
        __DIR__ . '/ecs.php',
    ]);

    $ecsConfig->import('vendor/sylius-labs/coding-standard/ecs.php');

    $ecsConfig->skip([
        VisibilityRequiredFixer::class => ['*Spec.php'],
    ]);
};
