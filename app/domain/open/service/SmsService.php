<?php


namespace app\domain\open\service;


use app\domain\recall\model\PushRecallType;
use app\domain\sms\dao\RongtongdaReportModelDao;
use app\domain\sms\model\RongtongdaReportModel;

class SmsService
{
    protected static $instance;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $itemStr
     * @return int|string
     */
    public function storeItemRongtongdaData($itemStr)
    {
        if (empty($itemStr)) {
            return 0;
        }
        parse_str($itemStr, $parr);
        $model = new RongtongdaReportModel();
        $model->uid = isset($parr['uid']) ? (int)$parr['uid'] : 0;
        $model->uname = isset($parr['uname']) ? (string)$parr['uname'] : "";
        $model->seq = isset($parr['seq']) ? (int)$parr['seq'] : 0;
        $model->pn = isset($parr['pn']) ? (int)$parr['pn'] : 0;
        $model->stm = isset($parr['stm']) ? (string)$parr['stm'] : 0;
        $model->sc = isset($parr['sc']) ? (string)$parr['sc'] : 0;
        $model->st = isset($parr['st']) ? (string)$parr['st'] : 0;
        $model->bid = isset($parr['bid']) ? (string)$parr['bid'] : 0;
        $model->str_date = date("Ymd");
        $model->platform = PushRecallType::$RTDSMS;
        $model->create_time = $this->getUnixTime();
        $model->origin_data = $itemStr;
        return RongtongdaReportModelDao::getInstance()->storeData($model);
    }

    private function getUnixTime()
    {
        return time();
    }

}