<?php

namespace app\common\amqp\event;

use app\common\amqp\conf\Event;
use app\domain\events\DomainEvent;
use app\domain\exceptions\FQException;
use app\event\AppEvent;

class EventService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EventService();
        }
        return self::$instance;
    }

    private function loadBuild()
    {
        return Event::getInstance()->getbuildEvent();
    }

    /**
     * @param $eventName
     * @param array $body
     * @return false
     * @throws FQException
     */
    public function doEvent($eventName, $jsonBody)
    {
        $buildMap = Event::getInstance()->getbuildEvent();
        $buildNamespace = $buildMap[$eventName] ?? "";
        if (empty($buildNamespace)) {
            return false;
        }
        $eventModel = $this->loadEventModel($buildNamespace, $jsonBody);
        if (!$eventModel instanceof AppEvent && !$eventModel instanceof DomainEvent) {
            return false;
        }
        $subEventList = Event::getInstance()->getsubscribeEvent();
        foreach($subEventList as $buildNamespace){
            $this->doHandler($buildNamespace, $eventName,$eventModel);
        }
        return true;
    }

    /**
     * @param $buildNamespace
     * @param $jsonBody
     * @return mixed
     */
    private function loadEventModel($buildNamespace, $jsonBody)
    {
        $eventModel = new $buildNamespace();
        $eventModel->jsonToModel($jsonBody);
        return $eventModel;
    }

    private function doHandler($buildNamespace, $eventName,$eventModel)
    {
        //        对象映射
        $method = sprintf("on%s", $eventName);
        $reflModel = $this->getReflectionModel($buildNamespace, $method);
        if ($reflModel === false) {
            return false;
        }
        return $this->doReflection($reflModel,$eventModel);
    }

    /**
     * @param $namespace
     * @param $method
     * @return false|\ReflectionMethod
     */
    private function getReflectionModel($namespace, $method)
    {
        try {
            $class = new \ReflectionClass($namespace);
            $model = $class->getMethod($method);
        } catch (\ReflectionException $e) {
            return false;
        }
        return $model;
    }


    private function doReflection(\ReflectionMethod $reflModel,$eventModel)
    {
        if ($reflModel === false) {
            return false;
        }
        $name = $reflModel->name;
        $obj = new $reflModel->class;
        return $obj->$name($eventModel);
    }

}