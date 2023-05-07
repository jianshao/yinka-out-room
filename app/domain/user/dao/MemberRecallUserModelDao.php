<?php

namespace app\domain\user\dao;

use app\core\mysql\ModelDao;
use app\domain\user\model\MemberRecallUserModel;
use app\utils\TimeUtil;


//召回用户数据模型
class MemberRecallUserModelDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberRecallUserModelDao();
        }
        return self::$instance;
    }

    private function dataToModel($data)
    {
        $model = new MemberRecallUserModel();
        $model->userId = $data['id'];
        $model->username = $data['username'];
        $model->nickname = $data['nickname'];
        $model->loginTime = TimeUtil::strToTime($data['login_time']);
        $model->freecoin = $data['freecoin'];
        $model->totalcoin = $data['totalcoin'];
        $model->amount = $data['amount'];
        return $model;
    }

    public function modelToData(MemberRecallUserModel $model)
    {
        $data = [
            'id' => $model->userId,
            'username' => $model->username,
            'nickname' => $model->nickname,
            'login_time' => TimeUtil::timeToStr($model->loginTime),
            'freecoin' => $model->freecoin,
            'totalcoin' => $model->totalcoin,
            'amount' => $model->amount,
        ];
        return $data;
    }

    /**
     * @param $uids
     * $object = $this->alias('m')->leftjoin("zb_member_detail md", 'm.id=md.user_id')->whereIn("m.id", $uids)->where("cancel_user_status", 0)->field(["m.nickname", "m.id", "m.login_time", "m.freecoin","m.totalcoin", "m.username", "md.amount"])->select();
     * @return array|null
     */
    public function findNicknameByUserIdByRecall($uids)
    {
        if (empty($uids)) {
            return null;
        }
        $uids = array_unique($uids);
        $models = $this->getModels($uids);
        $result = [];
        foreach ($models as $model) {
            $itemModel = $model->model->alias('m')->leftjoin("zb_member_detail md", 'm.id=md.user_id')->whereIn("m.id", $uids)->where("cancel_user_status", 0)->field(["m.nickname", "m.id", "m.login_time", "m.freecoin", "m.totalcoin", "m.username", "md.amount"])->select();
            if ($itemModel !== null) {
                foreach ($itemModel->toArray() as $itemArr) {
                    $result[] = $this->dataToModel($itemArr);
                }
            }
        }
        return $result;
    }


}


