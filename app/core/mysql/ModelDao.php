<?php

namespace app\core\mysql;

use app\core\model\BaseModelIds;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

class ModelDao
{
    protected $table = '';
    protected $serviceName = '';


    public function getModel($shardingColumn='')
    {
        if (empty($this->table) || empty($this->serviceName)){
            throw new FQException('获取数据库模型异常', 500);
        }

        return Sharding::getInstance()->getModel($this->serviceName, $this->table, $shardingColumn);
    }

    public function getModels($shardingColumns = [])
    {
        return Sharding::getInstance()->getModels($this->serviceName, $this->table, $shardingColumns);
    }

    public function getDbName($shardingColumns)
    {
        return Sharding::getInstance()->getDbName($this->serviceName, $shardingColumns);
    }


    /**
     * @return array
     * @throws FQException
     */
    public function getServiceModels(){
        return Sharding::getInstance()->getServiceModels($this->serviceName,$this->table);
    }
}
