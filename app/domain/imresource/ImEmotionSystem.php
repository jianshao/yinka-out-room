<?php


namespace app\domain\imresource;


use app\domain\Config;
use app\utils\ArrayUtil;

class ImEmotionSystem
{
    public $map = [];

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ImEmotionSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    /**
     * @param $kindId
     * @return mixed|null
     */
    public function findKind($kindId) {
        return ArrayUtil::safeGet($this->map, $kindId);
    }

    private function loadFromJson()
    {
        $imEmotionConf = Config::getInstance()->getImEmotionConf();

        foreach ($imEmotionConf as $emotion) {
            $imResourceTypeEmotion = new ImResourceTypeEmotion();
            $imResourceTypeEmotion->decodeFromJson($emotion);
            $this->map[$imResourceTypeEmotion->id] = $imResourceTypeEmotion;
        }
    }
}