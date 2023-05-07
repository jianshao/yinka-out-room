<?php

namespace app\utils;

use app\domain\exceptions\FQException;
use think\facade\Log;

class ClassRegister
{
    # 类型map
    private $typeMap = [];

    public function register($typeName, $factory) {
        $this->typeMap[$typeName] = $factory;
    }

    public function decodeFromJson($jsonObj) {
        $typeName = ArrayUtil::safeGet($jsonObj, 'type');
        if ($typeName != null) {
            if (array_key_exists($typeName, $this->typeMap)) {
                $factory = $this->typeMap[$typeName];
                $ret = new $factory();
                $ret->decodeFromJson($jsonObj);
                return $ret;
            }
        }
        Log::error(sprintf('NotFoundType %s:%s', json_encode($jsonObj), $typeName));
        throw new FQException('配置错误', -1);
    }

    public function encodeList($jsonObjs) {
        $ret = [];
        foreach ($jsonObjs as $jsonObj) {
            $typeName = ArrayUtil::safeGet($jsonObj, 'type');
            $ret[$typeName] = $this->decodeFromJson($jsonObj);
        }
        return $ret;
    }
}