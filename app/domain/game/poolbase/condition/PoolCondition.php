<?php


namespace app\domain\game\poolbase\condition;


abstract class PoolCondition
{
    /**
     * 解析
     *
     * @param $jsonObj
     * @return mixed
     */
    abstract public function decodeFromJson($jsonObj);

    /**
     * 检查boxUser是否符合进池条件
     *
     * @param $rewardPool
     * @param $boxUser
     * @return
     *  如果符合进池条件返回true, 否则返回false
     */
    abstract public function checkCondition($rewardPool, $boxUser);
}