<?php


namespace app\domain\riskwarn;


use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;

class RiskWarnSystem
{
    // 关键字
    private $keywords = [];
    // 提示
    private $prompt = '';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RiskWarnSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }


    public function getKeywords(){
        return $this->keywords;
    }

    public function getPrompt(){
        return $this->prompt;
    }

    protected function loadFromJson()
    {
        $cong = Config::getInstance()->getRiskWarnConf();
        $this->keywords = ArrayUtil::safeGet($cong, 'keywords', []);
        $this->prompt = ArrayUtil::safeGet($cong, 'prompt', '');
    }
}