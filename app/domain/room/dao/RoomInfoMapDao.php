<?php


namespace app\domain\room\dao;


use app\core\mysql\ModelDao;

class RoomInfoMapDao extends ModelDao
{
    protected $table = 'zb_room_info_map';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $guildId
     * @return mixed
     * @throws \app\domain\exceptions\FQException
     */
    public function getRoomIdByGuildId($guildId)
    {
        return $this->getModel()->where(['type' => 'guild_id', 'value' => $guildId])->column("room_id");
    }

    /**
     * @param $pretty
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    public function getRoomIdByPretty($pretty)
    {
        $data = $this->getModel()->where(['type' => 'pretty', 'value' => $pretty])->limit(1)->column("room_id");
        if (empty($data)) {
            return 0;
        }
        return (int)current($data);
    }

    /**
     * @param $guildId
     * @return mixed
     * @throws \app\domain\exceptions\FQException
     */
    public function getRoomIdByNotGuildId($guildId)
    {
        $where[] = ['type', '=', 'guild_id'];
        $where[] = ['value', '<>', $guildId];
        return $this->getModel()->where($where)->column("room_id");
    }

    /**
     * @param $userId
     * @return mixed
     * @throws \app\domain\exceptions\FQException
     */
    public function getRoomIdByUserId($userId)
    {
        return $this->getModel()->where(['type' => 'user_id', 'value' => $userId])->value('room_id');
    }

    /**
     * @param $guildId
     * @param $roomId
     * @return \app\core\model\BaseModel|false
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updatePretty($pretty, $roomId)
    {
        $where = ['room_id' => $roomId, 'type' => 'pretty'];
        $res = $this->getModel()->where($where)->find();
        if ($res) {
            $data = [
                'value' => $pretty,
            ];
            return $this->getModel()->where($where)->update($data);
        }
        return false;
    }

    /**
     * @param $guildId
     * @param $roomId
     * @return \app\core\model\BaseModel|false
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateGuildId($guildId, $roomId)
    {
        $where = ['room_id' => $roomId, 'type' => 'guild_id'];
        $res = $this->getModel()->where($where)->find();
        if ($res) {
            $data = [
                'value' => $guildId,
            ];
            return $this->getModel()->where($where)->update($data);
        }
        return false;
    }

    /**
     * @param $guildId
     * @param $roomId
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function updateInsertGuildId($guildId, $roomId)
    {
        return $this->addByGuildId($guildId, $roomId);
    }

    /**
     * @param $guildId
     * @param $roomId
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function addByGuildId($guildId, $roomId)
    {
        $data = [
            'type' => 'guild_id',
            'value' => $guildId,
            'room_id' => $roomId
        ];
        $exceptUniq = $this->loadExceptUniq();
        return $this->getModel($roomId)->extra("IGNORE")->duplicate($exceptUniq)->insert($data);
    }

    /**
     * @param $guildId
     * @param $roomId
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function addByPretty($prettyRoomId, $roomId)
    {
        $data = [
            'type' => 'pretty',
            'value' => $prettyRoomId,
            'room_id' => $roomId
        ];
        $exceptUniq = $this->loadExceptUniq();
        return $this->getModel($roomId)->extra("IGNORE")->duplicate($exceptUniq)->insert($data);
    }

    /**
     * @return string
     */
    private function loadExceptUniq()
    {
        $unique = $this->getUniqueFiled();
        $getfield = $this->getModel()->getConnection()->getFields($this->table);
        $updateFields = array_diff(array_keys($getfield), $unique);
        return implode(",", $updateFields);
    }

    /**
     * @return string[]
     */
    private function getUniqueFiled()
    {
        return ["room_id", "id"];
    }

    /**
     * @param $userId
     * @param $roomId
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function addByUserId($userId, $roomId)
    {
        $data = [
            'type' => 'user_id',
            'value' => $userId,
            'room_id' => $roomId
        ];
        $exceptUniq = $this->loadExceptUniq();
        return $this->getModel($roomId)->extra("IGNORE")->duplicate($exceptUniq)->insert($data);
    }

}