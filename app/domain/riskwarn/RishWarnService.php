<?php


namespace app\domain\riskwarn;



class RishWarnService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RishWarnService();
        }
        return self::$instance;
    }

    public function isRiskWarn($content){
       $keywords = RiskWarnSystem::getInstance()->getKeywords();
       foreach ($keywords as $key => $value){
           if (stristr($content, $value)){
               return true;
           }
       }
       return false;
    }
}