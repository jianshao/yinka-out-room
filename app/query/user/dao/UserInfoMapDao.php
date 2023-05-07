<?php


namespace app\query\user\dao;


use app\core\mysql\ModelDao;

class UserInfoMapDao extends ModelDao
{
    protected $table = 'zb_user_info_map';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserInfoMapDao();
        }
        return self::$instance;
    }

    public function getUserIdByPrettyId($prettyId)
    {
        return $this->getModel()->where(['type' => 'pretty', 'value' => $prettyId])->value('user_id');
    }

    public function getUserIdByNickname($nickname)
    {
        return $this->getModel()->where(['type' => 'nickname', 'value' =>$nickname])->value('user_id');
    }

    public function dimSearchByNickname($nickname, $offset, $count)
    {
        $where = [['type', '=', 'nickname'], ['value', 'like', "$nickname%"]];
        $datas = $this->getModel()->where($where)
            ->limit($offset, $count)
            ->select()
            ->toArray();
        $total = $this->getModel()->where($where)->count();
        $ret = [];
        if (!empty($datas)){
            $ret = array_keys($datas);
        }
        return [$ret, $total];
    }
}