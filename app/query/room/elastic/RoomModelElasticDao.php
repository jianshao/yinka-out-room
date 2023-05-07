<?php


namespace app\query\room\elastic;


use app\core\elasticSearch\ElasticSearchBase;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\model\RoomModel;

class RoomModelElasticDao extends ElasticSearchBase
{
    public $index = 'zb_languageroom';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * TODO https://www.yuque.com/docs/share/fef658b4-2c22-4174-94c6-5243c3b3e638?#%20%E3%80%8Aes%E7%AE%80%E5%8D%95%E4%BD%BF%E7%94%A8%E3%80%8B
     * DemoModel constructor.
     */
    private function __construct()
    {
        parent::__construct($this->index);
    }


    /**
     * @param $data
     * @return RoomModel
     */
    public function dataToModel($data)
    {
        return RoomModelDao::getInstance()->dataToModel($data);
    }

    /**
     * @param $model
     * @return array
     */
    public function modelToData($model)
    {
        return RoomModelDao::getInstance()->modelToData($model);
    }

    /**
     * @param $id
     * @param $model
     * @return bool
     */
    public function storeData($id, $model)
    {
        $data = $this->modelToData($model);
        return $this->doCreateOrUpdate($id, $data);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function queryHotRooms($offset = 0, $limit = 20)
    {
        $sdata = $this
            ->setMustTerm('is_hot', 1)
            ->from($offset)
            ->size($limit)
            ->search();
        if (empty($sdata)) {
            return [[], 0];
        }
        $datas = $this->getData($sdata);
        $total = $this->getTotal($sdata);
        $list = [];
        foreach ($datas as $data) {
            $list[] = $this->dataToModel($data);
        }
        return [$list, $total];
    }


    /**
     * @Info 房间号 和房间靓号搜索房间
     * @param $id
     * @param $offset
     * @param $count
     * @return array
     */
    public function searchRoomForId($id, $offset, $count)
    {
        $sdata = $this
            ->setShouldTerm('id', $id)
            ->setShouldTerm('pretty_room_id', $id)
            ->from($offset)
            ->size($count)
            ->search();
        if (empty($sdata)) {
            return [[], 0];
        }
        $dataList = $this->getData($sdata);
        $total = $this->getTotal($sdata);
        $listModel = [];
        foreach ($dataList as $data) {
            $listModel[] = $this->dataToModel($data);
        }
        return [$listModel, $total];
    }

    /**
     * @param $name
     * @param $offset
     * @param $count
     * @return array
     */
    public function searchRoomForRoomName($name, $offset, $count)
    {
        $sdata = $this
            ->setMustMatch('room_name', $name)
            ->from($offset)
            ->size($count)
            ->search();
        if (empty($sdata)) {
            return [[], 0];
        }
        $dataList = $this->getData($sdata);
        $total = $this->getTotal($sdata);
        $listModel = [];
        foreach ($dataList as $data) {
            $listModel[] = $this->dataToModel($data);
        }
        return [$listModel, $total];
    }

    /**
     * 根据房间ID 查询房间信息
     * @param $name
     * @param $offset
     * @param $count
     * @return array
     */
    public function queryRoomsInfo($roomIds)
    {
        if(empty($roomIds)){
            return [[],0];
        }
        $sdata = $this
            ->setMustTerm('id',$roomIds)
            ->all();
        if (empty($sdata)) {
            return [[], 0];
        }
        $dataList = $this->getData($sdata);
        $total = $this->getTotal($sdata);
        $listModel = [];
        foreach ($dataList as $data) {
            $listModel[] = $this->dataToModel($data);
        }
        return [$listModel, $total];
    }

}
