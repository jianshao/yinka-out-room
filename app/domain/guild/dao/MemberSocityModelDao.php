<?php


namespace app\domain\guild\dao;


use app\core\mysql\ModelDao;
use app\domain\guild\model\MemberSocityModel;

class MemberSocityModelDao extends ModelDao
{
    protected $table = 'zb_member_socity';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberSocityModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new MemberSocityModel();
        $model->id = $data['id'];
        $model->roomId = $data['room_id'];
        $model->guildId = $data['guild_id'];
        $model->userId = $data['user_id'];
        $model->socity = $data['socity'];
        $model->status = $data['status'];
        $model->auditTime = $data['audit_time'];
        $model->refuseTime = $data['refuse_time'];
        $model->applyQuitTime = $data['apply_quit_time'];
        return $model;
    }

    /**统计当前数据
     * @param $where
     */
    public function count($where)
    {
        return $this->getModel()->where($where)->count();
    }

    public function getOnes($where)
    {
        $res = $this->getModel()->where($where)->find();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

    public function getOne($where)
    {
        $res = $this->getModel()->where($where)->order('id desc')->limit(1)->find();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

    public function getUserIdsByUserIds($toUserIds)
    {
        return $this->getModel()->whereIn('user_id', $toUserIds)->where('status', '=', 1)->column('user_id');
    }

    public function updateDatas($where, $data) {
        return $this->getModel()->where($where)->update($data);
    }

    public function updateForId($id, $data)
    {
        $where['id'] = $id;
        return $this->getModel()->where($where)->update($data);
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function removeForId($userId)
    {
        if (empty($userId)) {
            return false;
        }
        return $this->getModel()->where('user_id', $userId)->delete();
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function removeForPk($pkId)
    {
        if (empty($pkId)) {
            return false;
        }
        return $this->getModel()->where('id', $pkId)->delete();
    }

    public function getGuidIdByUserId($user_id)
    {
        //查询当前用户属于哪个公会下
        $where[] = ['status', '=', 1];
        $where[] = ['user_id', '=', $user_id];
        $guildId = $this->getModel()->where($where)->value('guild_id');
        if (empty($guildId)) {
            return 0;
        }
        return $guildId;
    }

    /**
     * @info 用户与公会关系
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadMemberGuildModel($where)
    {
        $userGuildInfo = $this->getModel()->where($where)->order('id desc')->find();
        if (!$userGuildInfo) {
            return [];
        }
        return $userGuildInfo->toArray();
    }

    /**
     * 申请退出公会 15天未处理 的数据
     * @param $userIds
     * @param $guildIds
     * @param $applyQuitTime
     * @param int $limit
     * @return array  [[user_id=>guild_id]]
     */
    public function getApplyQuitGuildMemberUid($userIds,$guildIds,$applyQuitTime,$limit = 200)
    {
        $where[] = ['apply_quit_time', '<>', 0];
        $where[] = ['apply_quit_time', '<', $applyQuitTime];
        $where[] = ['status', '=', 1];
        $data = $this->getModel()->where($where)->whereIn('guild_id', $guildIds)->whereNotIn('user_id', $userIds)->limit($limit)->column("guild_id", "user_id");
        return $data;
    }

    /**
     * @info 公会成员数
     * @param $guildId
     * @return int
     */
    public function getGuildMemberNum($guildId)
    {
        $where[] = ['status','=',1];
        $where[] = ['guild_id','=',$guildId];
        return $this->getModel()->where($where)->count();
    }

    public function getUserRole($userId){
        $where[] = ['user_id', '=', $userId];
        $where[] = ['status', '=', 1];
        $res = $this->getModel()->where($where)->find();
        if (!empty($res)) {
            return 1;
        }
        return 0;
    }

    /**
     * @desc 分页获取公会成员
     * @param int $prePage
     * @param int $pageSize
     * @param string $field
     * @param array $where
     * @return array
     */
    public function getGuildMemberByPage(int $prePage = 1, int $pageSize = 20, string $field = '*',array $where = []): array
    {
        $guildMemberList =  $this->getModel()->field($field)->where($where)->page($prePage, $pageSize)->select();

        if (!$guildMemberList) {
            return [];
        }
        return $guildMemberList->toArray();
    }

    /**
     * @desc 分页公会成员
     * @param int $prePage
     * @param int $pageSize
     * @param string $field
     * @param array $where
     * @return array
     */
    public function getGuildMember($where,$field)
    {
        $guildMemberList =  $this->getModel()->field($field)->where($where)->select();
        if (!$guildMemberList) {
            return [];
        }
        return $guildMemberList->toArray();
    }

    /**
     * 添加公会成员
     * @param $data
     * @throws \app\domain\exceptions\FQException
     */
    public function addGuildMember($data)
    {
        return $this->getModel()->insert($data);
    }

    /**
     * 修改公会成员信息
     * @param $where
     * @param $data
     * @return mixed
     */
    public function editGuildMemberInfo($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }


    /**
     * @param $userId
     * @param $guildId
     * @return array|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadForUserGuild($userId, $guildId)
    {
        $where['user_id'] = $userId;
        $where['guild_id'] = $guildId;
        $where['status'] = 1;
        $object = $this->getModel()->where($where)->find();
        if ($object === null) {
            return null;
        }
        return $object->toArray();
    }


    /**
     * @param $id
     * @return MemberSocityModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModel($id)
    {
        if (empty($id)) {
            return null;
        }

        $object = $this->getModel()->where('id', $id)->find();
        if ($object === null) {
            return null;
        }
        $data=$object->toArray();
        return $this->dataToModel($data);
    }
}









