<?php


namespace app\query\site\service;


use app\common\RedisCommon;
use app\query\site\cache\SiteCache;
use app\query\site\dao\SiteConfigModelDao;

class SiteService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new SiteService();
        }
        return self::$instance;
    }

    public function getSiteConf($id, $field = '') {
        $siteConf = SiteCache::getInstance()->getSiteConf($id, $field = '');
        if (empty($siteConf)) {
            $siteConf = SiteConfigModelDao::getInstance()->getSiteConf($id);
            SiteCache::getInstance()->setSiteConf($id, $siteConf);
        }
        return $siteConf;
    }
}