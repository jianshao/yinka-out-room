<?php
namespace app\domain\reddot;

use app\domain\reddot\dao\RedDotItemModelDao;
use app\domain\reddot\model\RedDotItemModel;
use app\domain\reddot\model\RedDotTypes;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;


//红点model
class RedDotModel
{

    protected $userId;

    private $oreMap = [];

    protected $redDotTypes = [];


    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->redDotTypes = [
            RedDotTypes::$FRIEND => 'friend',
            RedDotTypes::$HI => 'hi',
            RedDotTypes::$OFFICIAL => 'official',
            RedDotTypes::$TIMELINES => 'timelines',
            RedDotTypes::$VISIT => 'visit',
            RedDotTypes::$TASKCENTER => 'taskCenter',
        ];
    }

    public function checkRedTypes($type){
        return isset($this->redDotTypes[$type])?$this->redDotTypes[$type]:"";
    }


    /**
     * @info 生成
     */
    public function load()
    {
        foreach ($this->redDotTypes as $type) {
            $reddotItem = new RedDotItem($this->userId, $type);
            $mode = $reddotItem->getItem();
            $this->setOre($type, $mode);
        }
    }

    public function getModelType($type){
        return ArrayUtil::safeGet($this->oreMap,$type,[]);
    }

    public function getModel(){
        return $this->oreMap;
    }

    public function getJson(){
        $result=[];
        foreach($this->oreMap as $type=>$model ){
            $result[$type]=RedDotItemModelDao::getInstance()->modeltoData($model);
        }
        return $result;
    }


    /**
     * @Info  设置
     * @param $oreType
     * @param RedDotItemModel $model
     */
    public function setOre($oreType, RedDotItemModel $model)
    {
        $this->oreMap[$oreType] = $model;
    }

}