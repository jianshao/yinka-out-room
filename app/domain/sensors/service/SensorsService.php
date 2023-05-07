<?php

namespace app\domain\sensors\service;

use app\domain\asset\AssetKindIds;
use app\domain\dao\UserIdentityModelDao;
use app\domain\guild\dao\MemberGuildModelDao;
use app\domain\pay\ChargeService;
use app\domain\pay\dao\OrderModelDao;
use app\domain\sensors\model\AddDiamondModel;
use app\domain\sensors\model\ConsumeBeanModel;
use app\domain\sensors\model\ConsumeDiamondModel;
use app\domain\sensors\model\RechargeModel;
use app\domain\sensors\SensorsEvent;
use app\domain\sensors\SensorsTypes;
use app\domain\shumei\ShuMeiCheck;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserInfoService;
use app\domain\user\UserRepository;
use app\utils\ArrayUtil;
use think\facade\Log;
use Exception;


class SensorsService
{
    protected $sensorsClass = null;
    protected static $instance;
    protected $sensorsSwitch = false;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SensorsService();
        }
        return self::$instance;
    }

    /**
     * 参数初始化
     */
    public function __construct()
    {
        require_once("SensorsAnalytics.php");
        $logAgentPath = config('config.sensorsData.log_agent_path');
        $this->sensorsSwitch = config('config.sensorsData.switch');
        $consumer = new FileConsumer(sprintf('%s.%s',$logAgentPath,date('Ymd')));
        $this->sensorsClass = new SensorsAnalytics($consumer);
    }

    /**
     * 设置事件公共属性
     * @param $userId
     * @param $isLogin
     */
    public function setCommonAttribute()
    {
        # 设置事件公共属性
        $this->sensorsClass->register_super_properties([
            '$ip'  => (string)getIP(),
        ]);
    }

    /**
     * 设置某个事件 track
     */
    public function track($userId,$isLoginId,$eventName,$properties)
    {
        $this->setCommonAttribute();
        $isSuccess = $this->sensorsClass->track((string)$userId,$isLoginId,$eventName,$properties);
        if(!$isSuccess){
            Log::error(sprintf('SensorsService::track eventName %s properties %s',$eventName,json_encode($properties)));
        }
    }

    /**
     * 登录
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function login($extra,$retInfo)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $anonymous_id = $extra['deviceId'];
            $userId = isset($extra['userId'])?$extra['userId']:'';
            $isLoginId = $userId?true:false;
            $login_method = ['手机号','QQ','微信','账号','苹果','一键登录','其他'];
            $properties = [
                'login_method'    => ArrayUtil::safeGet($login_method,$extra['type']-1,'其他'),
                'is_register'     => isset($extra['isRegister'])?$extra['isRegister']:false,
                'is_success'      => $retInfo['code'] == 200 ? true : false,
                'is_quick_login'  => $extra['type'] == 6 ? true : false,
                'fail_reason'     => $retInfo['code'] == 200 ? '' : $retInfo['desc']
            ];
            $this->track($userId?:$anonymous_id, $isLoginId,SensorsEvent::LOGIN_EVENT,$properties);
            if($userId && $anonymous_id){
                # 用户注册/登录时，将用户注册 ID 与 设备 关联
                $this->sensorsClass->track_signup($userId, $anonymous_id);
            }
            $this->sensorsClass->flush();
        }catch (Exception $e) {
            Log::error(sprintf('SensorsService::login errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 发送验证码
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function sendSms($extra,$retInfo)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $anonymous_id = $extra['deviceId'];
            $userId = isset($extra['userId'])?$extra['userId']:'';
            $isLoginId = $userId?true:false;
//            $service_type = ['登录','忘记密码','绑定手机号','更换手机号','注销账号','申请家族','其他'];
            $properties = [
                'service_type'  => (string)$extra['type'],
                'is_success'    => $retInfo['code'] == 200 ? true : false,
                'fail_reason'   => $retInfo['code'] == 200 ? '' : $retInfo['desc']
            ];
            $this->track($userId?:$anonymous_id, $isLoginId, SensorsEvent::SEND_CODE_EVENT,$properties);
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::sendSms errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 人脸认证
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function faceIdAuth($extra,$retInfo)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $userId = $extra['userId'];
            $properties = [
                'real_name'     => $extra['certName'],
                'phone'         => UserModelDao::getInstance()->getBindMobile($userId),
                'id_num'        => $extra['certNo'],
                'authentication_type' => '支付宝',
                'is_success'    => $retInfo['code'] == 200 ? true : false,
                'fail_reason'   => $retInfo['code'] == 200 ? '' : $retInfo['desc']
            ];
            $this->track($userId, true, SensorsEvent::ID_CARD_AUTH_EVENT,$properties);
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::faceIdAuth errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 查询人脸认证
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function queryIdentity($extra,$retInfo)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $userId = $extra['userId'];
            $certifyId = $extra['certifyId'];
            $model = UserIdentityModelDao::getInstance()->loadByCertifyId($certifyId);
            $properties = [
                'real_name'     => $model != null ? $model->certName : '',
                'phone'         => UserModelDao::getInstance()->getBindMobile($userId),
                'id_num'        => $model != null ? $model->certno : '',
                'authentication_type' => '支付宝',
                'is_success'    => $retInfo['code'] == 200 ? true : false,
                'fail_reason'   => $retInfo['code'] == 200 ? '' : $retInfo['desc']
            ];
            $this->track($userId, true, SensorsEvent::ID_CARD_AUTH_EVENT,$properties);
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::queryIdentity errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 修改用户资料
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function editUser($extra,$retInfo)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $userId = $extra['userId'];
            $properties = [
                'info_complete' => UserInfoService::getInstance()->getProfileCompleteScale($userId),
                'is_success'    => $retInfo['code'] == 200 ? true : false,
                'fail_reason'   => $retInfo['code'] == 200 ? '' : $retInfo['desc']
            ];
            $this->track($userId, true, SensorsEvent::SET_PROFILE_EVENT,$properties);
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::editUser errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * Im私聊
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function imCheck($extra,$retInfo)
    {
        if(!$this->sensorsSwitch){
            return;
        }
        $msgType = '';
        //如果是图片 判断是否是闪萌表情包
        if($extra['type'] == 1 && ShuMeiCheck::getInstance()->checkNotAuthImage($extra['message'])){
            $msgType = '表情包';
        }
        if(!$msgType){
            $msgType = ArrayUtil::safeGet(['文本','图片','语音'], $extra['type'], '其他');
        }
        try {
            $userId = $extra['userId'];
            $properties = [
                'target_id'        => (string)$extra['toUid'],
                'target_nickname'  => (string)UserModelDao::getInstance()->findNicknameByUserId($extra['toUid']),
                'msg_type'         => $msgType,
                'is_success'       => $retInfo['code'] == 200 ? true : false,
                'fail_reason'      => $retInfo['code'] == 200 ? '' : $retInfo['desc']
            ];
            $this->track($userId, true, SensorsEvent::SEND_MSG_EVENT,$properties);
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::imCheck errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 成功领取任务获得的奖品
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function getTaskRewards($userId,$count,$source,$timestamp)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            // 查询金币余额
            $user = UserRepository::getInstance()->loadUser($userId);
            $balance = $user->getAssets()->balance(AssetKindIds::$COIN,$timestamp);
            // 增加金币
            $this->changeCoin($userId,$count,$balance,$source);

            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::addCoin errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 音豆兑换金币
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function beanExchangeCoin($event)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $userId = $event->user->getUserModel()->userId;
            $coinCount = $event->coinCount;
            $coinBalance = $event->coinBalance;
            $beanCount = $event->beanCount;
            $beanBalance = $event->beanBalance;
            $this->changeCoin($userId,$coinCount,$coinBalance,SensorsTypes::$EXCHANGE_COIN);

            // 减少音豆
            $consumeBeanModel = new ConsumeBeanModel();
            $consumeBeanModel->targetId = $userId;
            $consumeBeanModel->scenario = SensorsTypes::$EXCHANGE_COIN;
            $consumeBeanModel->amount = $beanCount;
            $consumeBeanModel->balance = $beanBalance;
            $this->changeBean($userId,$consumeBeanModel,'consume');


            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::consumeBean errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 送礼物成功后
     * @param $fromUid
     * @param $fromUserBeanBalance
     * @param $giftId
     * @param $roomId
     */
    public function sendGift($fromUid,$fromUserBeanBalance,$receiverUserDiamondBalance,$giftKind,$roomId)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $giftBeanCount = $giftKind->price->count;
            $getDiamondCount = $giftBeanCount * config('config.scale') * config('config.self_scale');

            foreach($receiverUserDiamondBalance as $receiveUserId => $diamondBalance){
                // 减豆
                $fromUserBeanBalance = $fromUserBeanBalance-$giftBeanCount;
                $consumeBeanModel = new ConsumeBeanModel();
                $consumeBeanModel->targetId = $receiveUserId;
                $consumeBeanModel->roomId = $roomId;
                $consumeBeanModel->balance = $fromUserBeanBalance;
                $consumeBeanModel->giftType = $giftKind->name;
                $consumeBeanModel->amount = $giftBeanCount;
                $consumeBeanModel->scenario = SensorsTypes::$SEND_GIFT;
                $this->changeBean($fromUid,$consumeBeanModel,'consume');

                // 加钻石
                $addDiamondModel = new AddDiamondModel();
                $addDiamondModel->fromId = $fromUid;
                $addDiamondModel->scenario = SensorsTypes::$SEND_GIFT;
                $addDiamondModel->amount = $getDiamondCount > 0 ? round($getDiamondCount/config('config.khd_scale'),2)  : 0;
                $addDiamondModel->giftType = $giftKind->name;
                $addDiamondModel->roomId = $roomId;
                $addDiamondModel->balance = $diamondBalance > 0 ? round($diamondBalance/config('config.khd_scale'),2)  : 0;
                $this->changeDiamond($receiveUserId,$addDiamondModel);
            }
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::sendGift errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 发红包完成后
     * @param $event
     */
    public function sendRedPacket($event)
    {
        if(!$this->sensorsSwitch){
            return;
        }
        $orderModel = OrderModelDao::getInstance()->loadOrder($event->orderId);
        if(empty($orderModel)){
            return;
        }
        $rechargeModel = new RechargeModel();
        $rechargeModel->orderId = $event->orderId;
        $rechargeModel->scenario = '发红包';
        $rechargeModel->targetId = '';
        $rechargeModel->roomId = $event->roomId;
        $rechargeModel->amount = $event->totalBean;
        $user = UserRepository::getInstance()->loadUser($event->userId);
        $rechargeModel->balance = $user->getAssets()->balance(AssetKindIds::$BEAN,time());
        $rechargeModel->paymentMethod = $orderModel->content;
        $rechargeModel->paymentWay = $orderModel->content;
        $rechargeModel->isSuccess = true;
        $this->payChange($event->userId,$rechargeModel);
    }

    /**
     * 扭蛋机消耗金币完成后
     * @param $userId
     * @param $totalPrice
     * @param $balance
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function doLottery($userId,$totalPrice,$balance)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            // 减少金币
            $this->changeCoin($userId,$totalPrice,$balance,SensorsTypes::$COIN_BUY_NIU_DAN_JI,'consume');

            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::addCoin errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 音豆购买积分
     * @param $userId
     * @param $totalPrice
     * @param $balance
     * @param $timestamp
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function beanBuyIntegral($userId,$roomId,$totalPrice,$balance,$timestamp)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            // 减少音豆
            $consumeBeanModel = new ConsumeBeanModel();
            $consumeBeanModel->targetId = $userId;
            $consumeBeanModel->scenario = SensorsTypes::$BEAN_BUY_INTEGRAL;
            $consumeBeanModel->roomId = $roomId;
            $consumeBeanModel->amount = $totalPrice;
            $consumeBeanModel->balance = $balance;
            $this->changeBean($userId,$consumeBeanModel,'consume');
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::addCoin errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 充值成功
     * @param $userId
     * @param $orderId
     * @param $assetId
     * @param $count
     * @param $timestamp
     */
    public function recharge($userId,$orderId,$assetId,$count,$timestamp)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $orderModel = OrderModelDao::getInstance()->loadOrder($orderId);
            if(empty($orderModel)){
                return;
            }
            $rechargeModel = new RechargeModel();
            // 上报 充值订单->神策
            if($orderModel->type < 4){
                $rechargeModel->orderId = $orderId;
                // 购买类型 1充值 2vip 3svip 4 红包
                if($orderModel->type == 1){
                    $scenario = SensorsTypes::$BUY_BEAN;
                }else if($orderModel->type == 2){
                    $scenario = SensorsTypes::$BUY_VIP;
                }else if($orderModel->type == 3){
                    $scenario = SensorsTypes::$BUY_SVIP;
                }else{
                    $scenario = '其他';
                }
                $rechargeModel->scenario = $scenario;
                $rechargeModel->targetId = '';
                $rechargeModel->roomId = '';
                if($assetId == AssetKindIds::$BEAN){
                    $rechargeModel->amount = $assetId == AssetKindIds::$BEAN ? (int)$count : 0;
                    $user = UserRepository::getInstance()->loadUser($userId);
                    $rechargeModel->balance = $user->getAssets()->balance(AssetKindIds::$BEAN,$timestamp);
                }
                $rechargeModel->paymentMethod = $orderModel->content;
                $rechargeModel->paymentWay = $orderModel->content;
                $rechargeModel->isSuccess = true;
                $this->payChange($userId,$rechargeModel);
            }else{
                return;
            }
            // 更新用户表 音豆余额
            if($assetId == AssetKindIds::$BEAN){
                $this->sensorsClass->profile_set($userId, true,['balance'=>$rechargeModel->balance]);
            }
            // 更新用户表 VIP状态
//            if($assetId == AssetKindIds::$VIP_MONTH || $assetId == AssetKindIds::$SVIP_MONTH){
//                SensorsUserService::getInstance()->editUserVip($userId,$orderModel->productId);
//            }
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::recharge errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 充值成功 上报
     * @param $userId
     * @param $rechargeModel
     */
    public function payChange($userId,$rechargeModel)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $properties = [
                '$is_first_time'  => ChargeService::getInstance()->isFirstCharged($userId) ? '是':'否',
                'order_id'        => (string)$rechargeModel->orderId,
                'scenario'        => (string)$rechargeModel->scenario,
                'target_id'       => (string)$rechargeModel->targetId,
                'room_id'         => (string)$rechargeModel->roomId,
                'amount'          => (int)$rechargeModel->amount,
                'surplus_amount'  => (int)$rechargeModel->balance,
                'payment_method'  => (string)$rechargeModel->paymentMethod,
                'payment_way'     => (string)$rechargeModel->paymentWay,
                'is_success'      => (bool)$rechargeModel->isSuccess,
                'fail_reason'     => (string)$rechargeModel->failReason
            ];
            $this->track($userId, true, SensorsEvent::PAY_SUCCESS_EVENT,$properties);
            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::payChange errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 钻石兑换音豆
     * @param $event
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function diamondExchangeCoin($event)
    {
        try {
            if(!$this->sensorsSwitch){
                return;
            }
            $userId = $event->user->getUserModel()->userId;
            // 减钻石
            $consumeDiamondModel = new ConsumeDiamondModel();
            $consumeDiamondModel->userId = $userId;
            $consumeDiamondModel->scenario = SensorsTypes::$DIAMOND_EXCHANGE_BEAN;
            $consumeDiamondModel->amount = $event->diamondCount > 0 ? round($event->diamondCount/config('config.khd_scale'),2)  : 0;
            $consumeDiamondModel->balance = $event->diamondBalance > 0 ? round($event->diamondBalance/config('config.khd_scale'),2)  : 0;
            $this->changeDiamond($userId,$consumeDiamondModel,'consume');

            // 加音豆
            $this->sensorsClass->profile_set($userId, true,['balance'=>$event->beanBalance]);

            $this->sensorsClass->flush();
        } catch (Exception $e) {
            Log::error(sprintf('SensorsData::diamondExchangeCoin errorMessage %d errorLine %d', $e->getMessage(), $e->getLine()));
        }
    }

    /**
     * 改变钻石
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function changeDiamond($userId,$model,$type='add')
    {
        if($type == 'add'){
            $userModel = UserModelDao::getInstance()->loadUserModel($userId);
            if($userModel->guildId){
                $guildModel = MemberGuildModelDao::getInstance()->loadGuildModelForId($userModel->guildId);
            }else{
                $guildModel = null;
            }
            $properties = [
                'from_id'          => (string)$model->fromId,
                'room_id'          => (string)$model->roomId,
                'guild_name'       => empty($guildModel) ? '' : $guildModel->nickname,
                'user_type'        => $userModel->guildId > 0 ? '主播' : '普通用户',
                'scenario'         => (string)$model->scenario,
                'gift_type'        => (string)$model->giftType,
                'amount'           => (int)$model->amount,
                'surplus_amount'   => (int)$model->balance,
            ];
            $this->track($userId, true, SensorsEvent::ADD_DIAMOND_EVENT,$properties);
        }else{
            $properties = [
                'scenario'             => (string)$model->scenario,
                'consumption_quantity' => (int)$model->amount,
                'balance'              => (int)$model->balance,
            ];
            $this->track($userId, true, SensorsEvent::CONSUME_DIAMOND_EVENT,$properties);
        }

    }

    /**
     * 音豆减少
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function changeBean($userId,$model,$type='add')
    {
        if($type == 'add'){

        }else{
            $properties = [
                'target_id'        => (string)$model->targetId,
                'room_id'          => (string)$model->roomId,
                'scenario'         => (string)$model->scenario,
                'gift_type'        => (string)$model->giftType,
                'amount'           => (int)$model->amount,
                'surplus_amount'   => (int)$model->balance,
            ];
            $this->track($userId, true, SensorsEvent::CONSUME_BEAN_EVENT,$properties);
        }
        $this->sensorsClass->profile_set($userId, true,['balance'=>$model->balance]);
    }

    /**
     * 金币增加
     * @param $extra
     * @return mixed
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function changeCoin($userId,$count,$balance,$source,$type='add')
    {
        if($type == 'add'){
            $properties = [
                'acquisition_path' => $source,
                'account'          => (int)$count,
                'balance'          => (int)$balance,
            ];
            $this->track($userId, true, SensorsEvent::ADD_COIN_EVENT,$properties);
        }else{
            $properties = [
                'scenario'              => $source,
                'consumption_quantity'  => (int)$count,
                'balance'               => (int)$balance,
            ];
            $this->track($userId, true, SensorsEvent::CONSUME_COIN_EVENT,$properties);
        }
    }
}