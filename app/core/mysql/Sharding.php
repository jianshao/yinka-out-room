<?php

namespace app\core\mysql;

use app\core\model\BaseModel;
use app\core\model\BaseModelIds;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

class Sharding
{
    protected $dbMap = [];
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Sharding();
        }
        return self::$instance;
    }

    private $dbNameMap = [
        'userMaster' => [
            'userMaster1',
            'userMaster2',
            'userMaster3',
            'userMaster4',
        ],
        'userSlave' => [
            'userSlave1',
            'userSlave2',
            'userSlave3',
            'userSlave4',
        ],
        'roomMaster' => [
            'roomMaster1'
        ],
        'roomSlave' => [
            'roomSlave1',
        ],
        'commonMaster' => [
            'commonMaster1'
        ],
        'commonSlave' => [
            'commonSlave1'
        ],
        'biMaster' => [
            'biMaster1'
        ],
        'biSlave' => [
            'biSlave1'
        ]
    ];

    /**
     * 获取表model
     * @param $serviceName
     * @param $tableName
     * @param string $shardingColumn
     * @return BaseModel
     * @throws \app\domain\exceptions\FQException
     */
    public function getModel($serviceName, $tableName, $shardingColumn = ''): BaseModel
    {
        $dbName = $this->getDbName($serviceName, $shardingColumn);
        $model = $this->createModel($tableName, $dbName);
        $this->dbMap[sprintf('%s-%s', $dbName, $tableName)] = $model;
        return $model;
    }

    /**
     * 根据分库字段获取多个表model
     * @param $serviceName
     * @param $tableName
     * @param array $shardingColumns
     * @return array
     * @throws \app\domain\exceptions\FQException
     */
    public function getModels($serviceName, $tableName, $shardingColumns = []): array
    {
        $models = [];
        foreach ($shardingColumns as $shardingColumn) {
            $model = $this->getModel($serviceName, $tableName, $shardingColumn);
            if (!ArrayUtil::safeGet($models, $model->getConnect())) {
                $models[$model->getConnect()]['model'] = $model;
            }
            $models[$model->getConnect()]['list'][] = $shardingColumn;
        }
        $res = [];
        foreach ($models as $model) {
            $res[] = new BaseModelIds($model['model'], $model['list']);
        }
        return $res;
    }


    /**
     * 根据分库字段获取数据库名称
     * @param $serviceName
     * @param $shardingColumn
     * @return mixed|null
     */
    public function getDbName($serviceName, $shardingColumn)
    {
        $dbMap = $this->getDbMap($serviceName);
        $count = count($dbMap);
        if (is_numeric($shardingColumn)) {
            $dbName = ArrayUtil::safeGet($dbMap, $shardingColumn % $count);
        } else {
            $dbName = ArrayUtil::safeGet($dbMap, crc32($shardingColumn) % $count);
        }
        return $dbName;
    }

    /**
     * 获取配置中单个表的所有model
     * @param $serviceName
     * @param $tableName
     * @return BaseModel[]|array
     * @throws \app\domain\exceptions\FQException
     */
    public function getServiceModels($serviceName, $tableName): array
    {
        $dbMap = $this->getDbMap($serviceName);
        $models = [];
        foreach ($dbMap as $dbName) {
            $models[] = $this->createModel($tableName, $dbName);
        }
        return $models;
    }

    /**
     * 创建表model
     * @param $tableName
     * @param $dbName
     * @return \app\core\model\BaseModel
     * @throws \app\domain\exceptions\FQException
     */
    public function createModel($tableName, $dbName): BaseModel
    {
        $model = new BaseModel();
        if (empty($tableName)) {
            throw new FQException('获取数据库模型异常', 2002);
        }
        $model->setTableName($tableName);
        $model->setConnect($dbName);
        return $model;
    }

    /**
     * 根据服务名称获取数据库名称集合
     * @param $serviceName
     * @return mixed|null
     */
    public function getDbMap($serviceName)
    {
        return ArrayUtil::safeGet($this->dbNameMap, $serviceName);
    }

    public function getConnectModel($serviceName, $shardingColumn): BaseModel
    {
        $dbName = $this->getDbName($serviceName, $shardingColumn);
        $model = new BaseModel();
        $model->setConnect($dbName);
        return $model;
    }


}
