<?php


namespace app\domain\prop\service;


use app\domain\prop\dao\PropModelDao;
use app\domain\prop\PropSystem;
use app\utils\ArrayUtil;

class PropQueryService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PropQueryService();
        }
        return self::$instance;
    }

    public function queryUserProps($userId) {
        $propModels = PropModelDao::getInstance()->loadAllPropByUserId($userId);
        $props = [];
        foreach ($propModels as $propModel) {
            $propKind = PropSystem::getInstance()->findPropKind($propModel->kindId);
            if ($propKind != null) {
                $prop = $propKind->newProp($propModel->propId);
                $prop->initByPropModel($propModel);
                $props[] = $prop;
            }
        }
        return $props;
    }

    public function queryUserPropsLite($userId) {
        $propModels = PropModelDao::getInstance()->loadAllPropByUserId($userId);
        $props = [];
        foreach ($propModels as $propModel) {
            $propKind = PropSystem::getInstance()->findPropKind($propModel->kindId);
            if ($propKind != null) {
                $prop = $propKind->newProp($propModel->propId);
                $prop->initByPropModel($propModel);
                $props[] = $prop;
            }
        }
        return $props;
    }

    public function getWaredProp($userId, $typeName) {
        $timestamp = time();
        $props = $this->queryUserProps($userId);
        foreach ($props as $prop) {
            if (!$prop->isDied($timestamp)
                && $prop->isWore
                && $prop->kind->getTypeName() == $typeName) {
                return $prop;
            }
        }
        return null;
    }
}