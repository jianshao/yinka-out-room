<?php
/**
 * User: yond
 * Date: 2020
 * 配置表
 */
namespace app\query\site\dao;

use app\common\RedisCommon;
use app\core\mysql\ModelDao;

class SiteConfigModelDao extends ModelDao {
    protected $serviceName = 'commonSlave';
    protected $table = 'zb_siteconfig';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new SiteConfigModelDao();
        }
        return self::$instance;
    }

    public function getSiteConf($id) {
        $siteConf = $this->getModel()->where(['id' => $id])->find();
        if (!empty($siteConf)) {
            $siteConf = $siteConf->toArray();
        } else {
            $siteConf = [];
        }
        return $siteConf;
    }
}