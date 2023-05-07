<?php


namespace app\domain\activity\zhuawawa;

use app\domain\exceptions\FQException;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\dao\CoinDao;
use app\utils\TimeUtil;

class ZhuawawaUserDao
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ZhuawawaUserDao();
        }
        return self::$instance;
    }

    /**
     * @demo zhuawawa_user:1454733_date:20211026
     * @param $userId
     * @param $date
     * @return string
     */
    public function buildKey($userId, $timestamp)
    {
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        return sprintf("zhuawawa_user:%s_date:%s", $userId, $date);
    }


    /**
     * @param $userId
     * @param $timestamp
     * @return string
     */
    public function buildLockKey($userId, $timestamp)
    {
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        return sprintf("halloween_buildLockKey:%s_date:%s", $userId, $date);
    }

    /**
     * @param $userId
     * @return array
     * @throws FQException
     */
    public function loadUser($userId)
    {
        if (empty($userId)) {
            throw new FQException("userid error", 500);
        }

        $result['coin']=CoinDao::getInstance()->loadCoin($userId);
        $result['bean']=BeanModelDao::getInstance()->loadBean($userId)->balance();
        return $result;
    }

}