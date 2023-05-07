<?php

namespace app\domain\guild\dao;

use app\core\mysql\ModelDao;
use app\domain\guild\model\MemberGuild;

class MemberGuildModelDao extends ModelDao
{
    protected $table = 'zb_member_guild';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberGuildModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $ret = new MemberGuild();
        $ret->id = $data['id'];
        $ret->userId = $data['user_id'];
        $ret->nickname = $data['nickname'];
        $ret->logoUrl = $data['logo_url'];
        return $ret;
    }

    /**查询单条数据
     * @param $where
     * @param $field
     * @return array    返回结果
     */
    public function getOne($where, $field)
    {
        $res = $this->getModel()->field($field)->where($where)->find();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

    public function getOneObject($where)
    {
        $guildInfo = $this->getModel()->where($where)->find();
        if (!$guildInfo) {
            return null;
        }
        return $guildInfo->toArray();
    }

    public function loadNicknameAvatarList($guilds)
    {
        if (empty($guilds)) {
            return [];
        }
        $guilds = array_unique($guilds);
        return $this->getModel()->whereIn("id", $guilds)->field(["nickname", "id", "logo_url"])->column("id,nickname,logo_url", "id");
    }


    /**
     * @param $guildId
     * @return MemberGuild|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadGuildModelForId($guildId)
    {
        $where['id'] = $guildId;
        $model = $this->getModel()->where($where)->find();
        if ($model === null) {
            return null;
        }
        $data = $model->toArray();
        return $this->dataToModel($data);
    }


    public function loadGuildModel($field, $where)
    {
        $guildInfo = $this->getModel()->field($field)->where($where)->find();
        if (!$guildInfo) {
            return [];
        }
        return $guildInfo->toArray();
    }

    /**
     * @desc 通过条件获取公会列表
     * @param string $field
     * @param array $where
     * @return array
     */
    public function getGuildList(string $field = '*', array $where = []): array
    {
        $guildList = $this->getModel()->field($field)->where($where)->select();

        if (!$guildList) {
            return [];
        }
        return $guildList->toArray();
    }

    public function getGuildDataList(){
        // 查询公会数据
        $guildField = 'id as guild_id,nickname,user_id';
        return $this->getGuildList($guildField);
    }

    /**
     * 查询用户创建公会信息
     * @param $userId
     * @param $username
     * @throws \app\domain\exceptions\FQException
     */
    public function findUserGuild($userId,$username)
    {
        $where = [
            ['status', '=', 1], ['user_id', '=', $userId],
        ];
        $phoneWhere = [
            ['phone', '=', $username],
        ];
        return $this->getModel()->where([$where,$phoneWhere])->value('id');
    }

    /**
     * 添加公会
     * @param $data
     * @throws \app\domain\exceptions\FQException
     */
    public function addGuild($data)
    {
        return $this->getModel()->insertGetId($data);
    }

    /**
     * 修改公会信息
     * @param $where
     * @param $data
     * @return mixed
     */
    public function updateGuildInfo($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }

    public function getGuidIdByUserId() {

    }
}