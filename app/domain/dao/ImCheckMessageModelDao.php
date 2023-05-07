<?php

namespace app\domain\dao;
use app\core\mysql\ModelDao;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\models\ImCheckMessageModel;
use think\facade\Db;

class ImCheckMessageModelDao extends ModelDao {
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_check_im_message';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ImCheckMessageModelDao();
        }
        return self::$instance;
    }


    public function addRecord(ImCheckMessageModel $model) {
        $data = $model->encodeData();
        $subTable = sprintf('zb_check_im_message_%s', date('Ym', $model->createdTime));
        $insertId = $this->getModel($model->fromUserId)->insert($data, true);
        $dnName = $this->getDbName('commonMaster');
        Db::connect($dnName)->table($subTable)->insert($data);
        return $insertId;
    }

    public function updateRecord(ImCheckMessageModel $model, $messageId)
    {
        $data = $model->encodeData();
        return $this->getModel($model->fromUserId)->where(['id' => $messageId])->update($data);
    }

    public function loadRecord($fromUid, $messageId)
    {
        $data = $this->getModel($fromUid)->where(['id' => $messageId])->find();
        if ($data == null) {
            return null;
        }
        $data = $data->toArray();
        return $this->dataToModel($data);
    }

    public function dataToModel($data)
    {
        return new ImCheckMessageModel(
            $data['from_uid'],
            $data['to_uid'],
            $data['type'],
            $data['message'],
            $data['check_response'],
            $data['api_response'],
            $data['status'],
            $data['created_time'],
            $data['updated_time'],
        );
    }

    public function getTalkData($fromUid, $group)
    {
        $where[] = ['from_uid', '=', $fromUid];
        $where[] = ['status', '=', 1];
        return $this->getModel($fromUid)->where($where)->group($group)->column('to_uid');
    }


    /**
     * @param $fromUid
     * @param $toUid
     * @return array
     */
    public function getUserTalkStatus($fromUid, $toUid)
    {
        $where[] = ['from_uid', '=', $fromUid];
        $where[] = ['to_uid', '=', $toUid];
        $where[] = ['status', '=', 1];
        $re = $this->getModel($fromUid)->where($where)->limit(1)->column('id');
        if (!empty($re)) {
            return $re;
        }
        $where = [];
        $where[] = ['from_uid', '=', $toUid];
        $where[] = ['to_uid', '=', $fromUid];
        $where[] = ['status', '=', 1];
        $re = $this->getModel($fromUid)->where($where)->limit(1)->column('id');
        return $re;
    }

    /**
     * 获取私聊类型
     * Notes:
     * User: echo
     * Date: 2022/3/14
     * Time: 2:38 下午
     * @param $fromUid
     * @param $toUid
     * @return int 1被动回复 2主动回复
     */
    public function getTriggerMode($fromUid, $toUid) {
        $where = [];
        $where[] = ['from_uid', '=', $toUid];
        $where[] = ['to_uid', '=', $fromUid];
        $where[] = ['status', '=', 1];
        $res2 = $this->getModel($fromUid)->where($where)->find();
        if (!empty($res2)) {
            //被动回复
            return 1;
        }
        //主动回复
        return 2;

    }

    /**
     * 获取用户回复多少用户
     * Notes:
     * User: echo
     * Date: 2022/3/14
     * Time: 3:44 下午
     * @param $fromUid
     * @return array
     */
    public function getReplyUserIds($fromUid)
    {
        $where[] = ['to_uid', '=', $fromUid];
        $where[] = ['status', '=', 1];
        //获取主动私聊fromUid的人
        $fromUidS = $this->getModel()->where($where)->group('from_uid')->column('from_uid');
        if (!empty($fromUidS)) {
            //取出其中为用户的id
            $anchorIds = MemberSocityModelDao::getInstance()->getUserIdsByUserIds($fromUidS);
            $userIds = array_diff($fromUidS, $anchorIds);
            $replyUserIds = $this->getModel()->where([
                ['from_uid', '=', $fromUid],
                ['to_uid', 'in', $userIds],
                ['status', '=', 1]
            ])->group('to_uid')->column('to_uid');
            if (!empty($replyUserIds)) {
                return $replyUserIds;
            }
            return [];
        } else {
            return [];
        }

    }

    //获取主动私聊的人 type (0 用户 1主播)
    public function getActiveChatUserIds($userId, $type) {
        if ($type == 1) {
            $where[] = ['from_uid', '=', $userId];
            $where[] = ['status', '=', 1];
            $toUserIds = $this->getModel()->where($where)->group('to_uid')->column('to_uid');
            if (!empty($toUserIds)) {
                return MemberSocityModelDao::getInstance()->getUserIdsByUserIds($toUserIds);
            }
            return [];
        } else {
            $where[] = ['from_uid', '=', $userId];
            $where[] = ['status', '=', 1];
            $toUserIds = $this->getModel()->where($where)->group('to_uid')->column('to_uid');
            if (!empty($toUserIds)) {
                $anchorIds = MemberSocityModelDao::getInstance()->getUserIdsByUserIds($toUserIds);
                return array_diff($toUserIds, $anchorIds);
            }
            return [];
        }

    }
}