<?php


namespace app\domain\asset\rewardcontent;


use app\utils\ArrayUtil;
use app\utils\ClassRegister;

class ContentRegister extends ClassRegister
{
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ContentRegister();
        }
        return self::$instance;
    }

    public function decodeList($jsonObjs){
        $ret = [];
        foreach ($jsonObjs as $jsonObj){
            $ret[] = $this->decodeFromJson($jsonObj);
        }
        return $ret;
    }

    public function decodeFromJson($jsonObj) {
        if(ArrayUtil::safeGet($jsonObj, 'type') == null){
            $jsonObj['type'] = DefaultContent::$TYPE_ID;
        }

        return parent::decodeFromJson($jsonObj);

    }
}

ContentRegister::getInstance()->register(DefaultContent::$TYPE_ID, DefaultContent::class);
ContentRegister::getInstance()->register(RandomContent::$TYPE_ID, RandomContent::class);
ContentRegister::getInstance()->register(SingleRandomContent::$TYPE_ID, SingleRandomContent::class);