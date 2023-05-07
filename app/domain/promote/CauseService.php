<?php

namespace app\domain\promote;


use app\domain\open\model\PromoteFactoryTypeModel;

/**
 * 推广基类
 */
class CauseService
{
    private $channleType;

    public function __construct($channleType)
    {
        $this->channleType = $channleType;
    }


    /**
     * @param $channleType
     * @return PromoteService|null
     */
    private function loadServiceForChannleType($channleType)
    {
        $serviceMap = [];
        $serviceMap[PromoteFactoryTypeModel::$TOUTIAO] = TouTiaoPromoteService::getInstance();
        $serviceMap[PromoteFactoryTypeModel::$HUAWEI] = HuaweiPromoteService::getInstance();
        $serviceMap[PromoteFactoryTypeModel::$OPPO] = OppoPromoteService::getInstance();
        $serviceMap[PromoteFactoryTypeModel::$BIZHAN] = BiZhanPromoteService::getInstance();
        return isset($serviceMap[$channleType]) ? $serviceMap[$channleType] : null;
    }

    /**
     * @param $event
     * @return false|void
     */
    public function report($event)
    {
        $result = false;
        if (empty($this->channleType)) {
            return $result;
        }
        $className = $this->loadClassName($event);
        $serviceModel = $this->loadServiceForChannleType($this->channleType);
        if ($serviceModel === null) {
            return $result;
        }
        $evetName = sprintf('on%s', $className);
        return $serviceModel->$evetName($event);
    }

    /**
     * @param $event
     * @return string
     * @throws \ReflectionException
     */
    private function loadClassName($event)
    {
        $ReflectionClass = new \ReflectionClass($event);
        return $ReflectionClass->getShortName();
    }
}


