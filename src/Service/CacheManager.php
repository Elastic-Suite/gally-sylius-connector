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

namespace Gally\SyliusPlugin\Service;

use Gally\Sdk\Service\Cache\AbstractCacheManager;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CacheManager extends AbstractCacheManager
{
    private CacheInterface $cache;

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function doGet(string $cacheKey, callable $callback, ?int $ttl): mixed
    {
        return $this->cache->get($cacheKey, function (CacheItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);

            return $callback($item);
        });
    }

    public function doClear(string $cacheKey): void
    {
        $this->cache->delete($cacheKey);
    }
}
