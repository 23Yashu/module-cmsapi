<?php
namespace DevTools\CmsApi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\CacheInterface;

class CmsCacheClearObserver implements ObserverInterface
{
    const CACHE_TAG = 'CMS_API';

    protected $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function execute(Observer $observer)
    {
        // Clear all API cache entries for CMS content
        $this->cache->clean([self::CACHE_TAG]);
    }
}