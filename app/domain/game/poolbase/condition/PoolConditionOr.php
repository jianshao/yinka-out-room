<?php


namespace app\domain\game\poolbase\condition;


class PoolConditionOr extends PoolCondition
{
    public static $TYPE_ID = 'or';

    public $conditions = [];

    public function __construct($conditions=[]) {
        $this->conditions = $conditions;
    }

    public function decodeFromJson($jsonObj) {
        foreach ($jsonObj['list'] as $conditionJson) {
            $condition = PoolConditionRegister::getInstance()->decodeFromJson($conditionJson);
            $this->conditions[] = $condition;
        }
        return $this;
    }

    public function checkCondition($rewardPool, $boxUser) {
        foreach ($this->conditions as $condition) {
            if ($condition->checkCondition($rewardPool, $boxUser)) {
                return true;
            }
        }
        return false;
    }
}