<?php


namespace app\domain\game\poolbase\condition;


class PoolConditionAnd extends PoolCondition
{
    public static $TYPE_ID = 'and';

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
            if (!$condition->checkCondition($rewardPool, $boxUser)) {
                return false;
            }
        }
        return true;
    }
}