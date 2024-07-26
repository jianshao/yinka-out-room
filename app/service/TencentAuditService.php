<?php


namespace app\service;


use app\domain\models\AuditNotifyModel;
use constant\TencentAuditConstant;

class TencentAuditService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TencentAuditService();
        }
        return self::$instance;
    }

    // checkVoiceTro 检查语音
    public function checkVoice($file) :bool
    {
        $result = true;
        $data = AuditNotifyModel::getInstance()->getModel()->where([
            'path' =>  $file,
            'forbidden_status' =>  TencentAuditConstant::TENCENT_AUDIT_FORBIDDEN_ERR]
        )->find();
        if (!empty($data)) {
            // 0（审核正常），1 （判定为违规敏感文件），2（疑似敏感，建议人工复核）
            if ($data['result'] == TencentAuditConstant::TENCENT_AUDIT_RETURN_ERR) {
                $result =  false;
            }
        }

        return $result;
    }
}