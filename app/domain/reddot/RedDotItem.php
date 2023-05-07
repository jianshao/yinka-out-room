<?php

namespace app\domain\reddot;

use app\domain\reddot\cache\RedDotItemCache;
use app\domain\reddot\dao\RedDotItemModelDao;
use app\domain\reddot\model\RedDotItemModel;
use app\utils\Arithmetic;

//红点item类
class RedDotItem
{

    protected $userId;
    protected $field;


    public function __construct($userId, $field)
    {
        $this->userId = $userId;
        $this->field = $field;
    }

    /**
     * @param $userId
     * @param int $number
     * @return int
     */
    public function incr($number = 1, $hashKey = 'count')
    {
        $number = intval($number);
        return RedDotItemCache::getInstance()->incr($this->userId, $this->field, $number, $hashKey);
    }


    /**
     * @param $userId
     * @param int $number
     * @return int
     */
    public function decr($number = 1, $hashKey = 'count')
    {
        $number = Arithmetic::negateNumber($number);
        $number = intval($number);
        return RedDotItemCache::getInstance()->decr($this->userId, $this->field, $number, $hashKey);
    }



    /**
     * @param $data
     * @param string $hashKey
     * @return false|int|string
     */
    public function hset($data,$hashKey='count')
    {
        RedDotItemCache::getInstance()->hset($this->userId, $this->field, $data,$hashKey);
        return RedDotItemCache::getInstance()->hget($this->userId, $this->field,$hashKey);
    }


    /**
     * @return RedDotItemModel
     */
    public function getItem()
    {
        if (empty($this->userId) || empty($this->field)) {
            return new RedDotItemModel();
        }
        $data = RedDotItemCache::getInstance()->hgetAll($this->userId, $this->field);
        return RedDotItemModelDao::getInstance()->dataToModel($data);
    }
}