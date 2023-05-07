<?php


namespace app\query\site\cache;



use app\common\RedisCommon;

class SiteCache
{
    protected static $instance;
    protected $redis = null;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new SiteCache();
            self::$instance->redis = RedisCommon::getInstance()->getRedis();
        }
        return self::$instance;
    }

    public function getSiteConf($id, $field = '') {
        if ($field == '') {
            $conf = $this->redis->hGetAll('site:conf:'. $id);
        } else {
            $conf = $this->redis->hMget('site:conf:'. $id, $field);
        }
        return $conf;
    }

    public function setSiteConf($id, $data) {
        $this->redis->hMSet("site:conf:" . $id, $data);
    }
}