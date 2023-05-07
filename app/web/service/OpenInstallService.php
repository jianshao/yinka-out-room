<?php
namespace app\web\service;

use app\common\amqp\AmpService;
use app\query\backsystem\dao\PromoteRoomConfModelDao;
use app\query\backsystem\dao\MarketChannelModelDao;
use app\common\amqp\model\UserRegisterRefereeMessageModel;
use app\core\mysql\Sharding;
use app\domain\user\service\UserRegisterService;
use app\event\UserLoginEvent;
use app\web\model\OpenInstallModel;
use app\web\model\RefereeInfoModel;
use think\facade\Db;
use think\facade\Log;
use think\Validate;

class OpenInstallService
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

    /**查询推广码信息
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getRefereeInfo($param)
    {
        // 验证模板类型 模板名称
        $validate = new Validate;
        $validate->rule([
            'ip' => 'require',
            'key' => 'require',
            'scWidth' => 'require',
            'scHeight' => 'require',
            'pixelRatio' => 'require',
            'version' => 'require',
            // 'renderer' => 'require',
        ]);

        if (!$validate->check($param)) {
            Log::info('RefereeInfo: error' . json_encode($param));
            throw new \Exception('参数不合法');
        }
        $ip = $param['ip'] ?? '';
        /***KOL引流开始********************************************/
        //根据请求的ip来判断在星图里面是否存在此ip的信息
        $dnName = Sharding::getInstance()->getDbName('commonMaster', 0);
        $xingtuRecord = Db::connect($dnName)->table("zb_xingtu_callback")
            ->field("ip,promote_code,click_time")
            ->where("ip", $ip)
            ->order("click_time desc")
            ->limit(1)
            ->find();
        if ($xingtuRecord) {
            //判断是否在有效开场期内 ip存在存在同时点击的时间在推广的有效时间内
            $currentDate = date('Y-m-d H:i:s',$xingtuRecord['click_time']);
            $dnName = Sharding::getInstance()->getDbName('biMaster', 0);
            $havRes = Db::connect($dnName)->table("zb_promote_room_times_conf")
                ->where("promote_code", $xingtuRecord['promote_code'])
                ->where("start_time", "<=", $currentDate)
                ->where("end_time", ">", $currentDate)
                ->find();
            if ($havRes) {
                if(isset($havRes['promote_code']) && $havRes['promote_code']> 0 ){
                    //根据请求的ip来判断如果kol正在推广 则kol把推广码重新赋值
                    return ['promoteCode' => (int)$havRes['promote_code']];
                }
            }
        }
        /***KOL引流结束*****************************************/


        $openinstall_info = OpenInstallModel::getInstance()->getDevice(ip2long($ip), $param['key'], $param);
        Log::info('RefereeInfo:' . json_encode(['params' => $openinstall_info['referee_info']]));

        $referee_info = json_decode($openinstall_info['referee_info'], true);
        return ['promoteCode' => (int) $referee_info['promoteCode']];
    }

    /**查询推广码信息
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getRefereeRoomInfo($param)
    {
        // 验证模板类型 模板名称
        $validate = new Validate;
        $validate->rule([
            'promoteCode' => 'require',
        ]);

        if (!$validate->check($param)) {
            Log::info('RefereeInfo: error' . json_encode($param));
            throw new \Exception('参数不合法');
        }

        $promoteCode = $param['promoteCode'];
        $info = PromoteRoomConfModelDao::getInstance()->getall(['id' => $promoteCode]);
        Log::info('info:' . json_encode($info));

        $market_channel_info = MarketChannelModelDao::getInstance()->getModel()->where('invitcode', $promoteCode)->select()->toArray();
        Log::info('market_channel_info:' . json_encode($market_channel_info));

        $invitcode = 0;
        if (!empty($info) || !empty($market_channel_info)) {
            $invitcode = $promoteCode;
        }
        return ['promoteCode' => $invitcode];
    }

    public function getBindCode(array $headers)
    {
        if (array_key_exists('promoteCode', $headers) && !empty($headers['promoteCode'])) {
            return $this->getRefereeRoomInfo($headers);
        } else {
            return $this->getRefereeInfo($headers);
        }
    }

    /**
     * @param UserLoginEvent $event
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        if ($event->isRegister !== true) {
            return false;
        }
        $param = UserRegisterService::getInstance()->buildParams($event->clientInfo);
        $ip = $param['ip'] ?? '';
        $openinstall_info = OpenInstallModel::getInstance()->getDevice(ip2long($ip), $param['key'], $param);
        if (empty($openinstall_info) || !isset($openinstall_info['referee_info'])) {
            return false;
        }
        $referee_info = json_decode($openinstall_info['referee_info'], true);
        $refereeInfoModel = new RefereeInfoModel();
        $refereeInfoModel->dateToModel($referee_info);
        if (empty($refereeInfoModel->qrCode)) {
            return false;
        }
//        写入队列        user_register_referee
        $messageModel = new UserRegisterRefereeMessageModel();
        $messageModel->tag = "qrcode";
        $messageBody['promotecode'] = $refereeInfoModel->promoteCode;
        $messageBody['qrcode'] = $refereeInfoModel->qrCode;
        $messageBody['user_id'] = $event->userId;
        $messageModel->body = json_encode($messageBody);
        return AmpService::getInstance()->publisherUserRegisterReferee($messageModel);
    }

}
