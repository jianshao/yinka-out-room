<?php

namespace app\query\notice\dao;

use app\common\RedisCommon;
use app\core\mysql\ModelDao;
use app\domain\notice\Notice;
use think\Model;


class NoticeModelDao extends ModelDao
{
    protected $table = 'yyht_notice';
    protected $serviceName = 'biSlave';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new NoticeModelDao();
        }
        return self::$instance;
    }

    private function dataToModel($data) {
        $model = new Notice();
        $model->id = $data['id'];
        $model->title = $data['notice_title'];
        $model->image = $data['notice_img'];
        $model->content = $data['notice_content'];
        $model->status = $data['notice_status'];
        $model->timingTime = $data['timing_time'];
        $model->createTime = $data['created_time'];
        $model->createUser = $data['created_user'];
        $model->updateTime = $data['updated_time'];
        $model->updateUser = $data['updated_user'];
        $model->jumpUrl = $data['jump_url'];
        return $model;
    }

    public function loadNoticeModelsByLimit($start, $pagenum=null) {
        $ret = [];
        $datas = $this->getModel()->where(['notice_status'=>1])->limit($start, $pagenum)->order('id desc')->select()->toArray();
        $count = $this->getModel()->where(['notice_status'=>1])->count();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return [$ret, $count];
    }

    public function updateNoticeTime($userId, $timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->hset('notice_msg_uid', $userId, $timestamp);
    }

    public function getLastNoticeTime($userId){
        $redis = RedisCommon::getInstance()->getRedis();

        $time = $redis->HGET('notice_msg_uid', $userId);
        return $time ? $time : 0;
    }
}