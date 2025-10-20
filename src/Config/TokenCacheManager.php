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

namespace Gally\SyliusPlugin\Config;

use Gally\Sdk\Client\TokenCacheManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class TokenCacheManager implements TokenCacheManagerInterface
{
    private const CACHE_KEY = 'gally_api_token';

    private CacheInterface $cache;

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function getToken(callable $getToken, bool $useCache = true): string
    {
        if (!$useCache) {
            $this->clearCache();
        }

        $token = $this->cache->get(self::CACHE_KEY, $getToken);
        if (!\is_scalar($token) && null !== $token) {
            $token = '';
        }

        return (string) $token;
    }

    public function clearCache(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }
}
