<?php
//

namespace app\api\controller\v1;

use app\domain\dao\MonitoringModelDao;
use app\domain\exceptions\FQException;
use app\domain\user\service\MonitoringService;
use app\api\controller\ApiBaseController;
use \app\facade\RequestAes as Request;
use app\utils\CommonUtil;


class MonitoringController extends ApiBaseController
{
    // 查询申诉结果
    public function queryMonitor()
    {
        $userId = intval($this->headUid);
        $certifyId = Request::param('certifyId');
        if (empty($certifyId)) {
            return rjson($code = 500, $msg = "参数错误");
        }

        MonitoringService::getInstance()->queryMonitor($userId, $certifyId);
        return rjson($msg = '申诉成功，青少年模式已关闭');
    }

    // 忘记密码，提交申诉
    public function resetMonitor()
    {
        $userId = intval($this->headUid);
        $certName = Request::param('certName');
        $certNo = Request::param('certNo');
        $bizCode = Request::param('bizCode');

        if (empty($certName) || empty($certNo) || empty($bizCode)) {
            return rjson([], 500, "参数错误");
        }
        CommonUtil::validateIdCard($certNo);
        $userAge = CommonUtil::getAge($certNo);
        if ($userAge < 18) {
            return rjson([], 500, "申诉失败，申诉人不满足18周岁");
        }

        list($url, $certifyId) = MonitoringService::getInstance()->resetMonitor($userId, $certName, $certNo, $bizCode, $this->channel, $this->config);
        $data = [
            "url" => $url,
            "certifyId" => $certifyId,
        ];
        return rjson($data);
    }
    public function checkMonitoringStatus() {
        $userId = intval($this->headUid);
        $model = MonitoringModelDao::getInstance()->findByUserId($userId);
        if ($model != null) {
            if ($model->monitoringStatus == 1) {
                return rjson([], 200, '正常模式，可以进行充值');
            }
            if ($model->parentStatus == 1) {
                return rjson([], 200, '开启了家长模式，无法进行充值行为');
            }
        }
        return rjson([], 200, '正常模式，可以进行充值');
    }

    //开始青少年续期
    public function renewalTime()
    {
        $password = Request::param('password');
        $userId = intval($this->headUid);

        try {
            MonitoringService::getInstance()->renewalTime($userId, $password);
            return rjson();
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //开启倒计时
    public function monitorTime()
    {
        $userId = intval($this->headUid);

        try {
            $endTime = MonitoringService::getInstance()->monitorTime($userId);
            return rjson([
                'monitoring_endtime' => $endTime
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //开关青少年模式
    public function switchMonitor()
    {
        $password = Request::param('password');
        $userId = intval($this->headUid);

        try {
            $open = MonitoringService::getInstance()->switchMonitor($userId, $password);
            if ($open) {
                return rjson([], 200, '开启成功');
            } else {
                return rjson([], 200, '关闭成功');
            }
        } catch (FQException $e) {
            return rjson([], $e->getCode(),  $e->getMessage());
        }
    }

    //修改青少年密码
    public function setMonitor()
    {
        $newPassword = Request::param('password');
        $oldPassword = Request::param('oldpassword');
        $userId = intval($this->headUid);

        try {
            if (MonitoringService::getInstance()->setMonitorPassword($userId, $oldPassword, $newPassword)) {
                return rjson([], 200, '修改成功');
            }
            return rjson();
        } catch (FQException $e) {
            return rjson([], $e->getCode(),  $e->getMessage());
        }
    }

    //客服青少年解除申请
    public function delMonitoringData()
    {
        $userId = intval($this->headUid);
        try {
            MonitoringService::getInstance()->delMonitoringData($userId);
            return rjson();
        } catch (FQException $e) {
            return rjson([], $e->getCode(),  $e->getMessage());
        }
    }

    //青少年每日一次弹窗
    public function checkTeen()
    {
        $userId = intval($this->headUid);
        $hours = intval(date("H"));
        if ($hours > 22 || $hours < 6) {
            $now = time();
            if ($hours > 22) {
                $t = mktime(6, 0, 0, date('m'), date('d') + 1, date('Y'));
            } else {
                $t = mktime(6, 0, 0, date('m'), date('d'), date('Y'));
            }

            $disableTime = 1;
            $countdownTime = max(0, intval($t) - intval($now));
        } else {
            $now = time();
            $t = mktime(22, 0, 0, date('m'), date('d'), date('Y'));

            $disableTime = 0;
            $countdownTime = max(0, intval($t) - intval($now));
        }

        list($teenmodal, $open, $isTeen) = MonitoringService::getInstance()->checkTeen($userId);
        return rjson([
            'teenmodal' => $teenmodal,
            'open' => $open,
            'disableTime' => $disableTime,
            'isTeen' => $isTeen,
            'countdownTime' => $countdownTime
        ]);
    }
}
