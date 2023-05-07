<?php


namespace app\domain\imresource;


use app\domain\Config;
use app\utils\ArrayUtil;

class ImBackgroundSystem
{
    public $map = [];

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ImBackgroundSystem();
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
        $imBackgroundConf = Config::getInstance()->getImBackgroundConf();
        foreach ($imBackgroundConf as $background) {
            $imResourceTypeBackground = new ImResourceTypeBackground();
            $imResourceTypeBackground->decodeFromJson($background);
            $this->map[$imResourceTypeBackground->id] = $imResourceTypeBackground;
        }
    }
}