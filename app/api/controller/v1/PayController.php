<?php

namespace app\api\controller\v1;

use app\api\controller\ApiBase2Controller;
use app\domain\dao\MonitoringModelDao;
use app\domain\exceptions\FQException;
use app\domain\pay\ChargeService;
use app\domain\thirdpay\chinaums\PayConstant;
use app\domain\thirdpay\service\WeChatService;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UnderAgeService;
use app\query\user\QueryUserService;
use app\service\PayService;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;
use think\facade\Log;
use think\facade\View;

require_once "../app/common/phpqrcode.php";


class PayController extends ApiBase2Controller
{
    /**
     * 安卓app内充值列表接口
     */
    public function appChargeList()
    {
        $userId = $this->headUid;
        $products = ChargeService::getInstance()->getAndroidChargeList($userId);
        $ret = [];
        foreach ($products as $product) {
            $itemData = [
                'id' => $product->productId,
                'rmb' => $product->price,
                'diamond' => $product->bean,
                'present' => $product->present,
                'chargemsg' => $product->chargeMsg,
                'coinimg' => CommonUtil::buildImageUrl($product->image),
                'vipgift' => 0,
                'iosflag' => $product->appStoreProductId,
                'status' => $product->status
            ];
            if ($product->productId === 209) {
                continue;
            }
            $ret[] = $itemData;
        }

        $payChannels = ChargeService::getInstance()->getPayChannels();
        $payChannelList = [];
        foreach ($payChannels as $payChannel) {
            $payChannelList[] = [
                'id' => $payChannel->id,
                'pid' => $payChannel->pid,
                'content' => $payChannel->content,
                'check' => $payChannel->check,
                'type' => $payChannel->type
            ];
        }
        return rjson([
            'charge_list' => $ret,
            'pay_channel' => $payChannelList
        ]);
    }

    /**
     * 苹果app内充值列表接口
     */
    public function appIosChargeList()
    {
        $userId = intval($this->headUid);
        $products = ChargeService::getInstance()->getIOSChargeList($userId);
        $ret = [];
        foreach ($products as $product) {
            $ret[] = [
                'id' => $product->productId,
                'rmb' => $product->price,
                'diamond' => $product->bean,
                'present' => $product->present,
                'chargemsg' => $product->chargeMsg,
                'coinimg' => CommonUtil::buildImageUrl($product->image),
                'vipgift' => 0,
                'iosflag' => $product->appStoreProductId,
                'status' => $product->status,
            ];
        }
        $payChannels = ChargeService::getInstance()->getPayChannels();
        $payChannelList = [];
        foreach ($payChannels as $payChannel) {
            $payChannelList[] = [
                'id' => $payChannel->id,
                'pid' => $payChannel->pid,
                'content' => $payChannel->content,
                'check' => $payChannel->check,
                'type' => $payChannel->type
            ];
        }
        return rjson([
            'charge_list' => $ret,
            'pay_channel' => $payChannelList
        ]);
    }

    public function makePayResult($payResult, $payChannel)
    {
        if (in_array($payChannel, PayConstant::CHINAUMS_CHANNEL_PAYS)) {
            return $payResult;
        }
        switch ($payChannel) {
            case 1:
                return ['app_alipay_normal' => $payResult];
                break;
            case 2:
            case 16:
                return ['web_alipay_normal' => $payResult];
                break;
            case 3:
                return ['app_wechat_normal' => $payResult];
                break;
            case 4:
                return $payResult;
                break;
            case 13:
                return ['web_wxpay_normal' => $payResult];
                break;
            case 15:
                return ['web_wxpay_code' => $payResult];
                break;
            default:
                return [];
                break;
        }
    }

    //支付接口
    public function PayMent()
    {
        $channel = Request::param('channel');
        $rmb = floatval(Request::param('rmb'));
        $code = Request::param('code');
        $isActive = Request::param('is_active');
        $isActive = $isActive ? intval($isActive) : 0;
        $productId = floatval(Request::param('productId'));
        $userId = intval($this->headUid);
        if (empty($userId)) {
            $userId = Request::param('uid');
        }
        // 银联商务微信小程序 code不能为空
        if (!$code && in_array($channel, PayConstant::CHINAUMS_CHANNEL_CODE_PAYS)) {
            return rjson([], 500, 'code不能为空');
        }
        if ($userId) {
            //判断靓号
            $userModel = QueryUserService::getInstance()->searchUser($userId);
            if (empty($userModel)) {
                // 靓号不存在，查用户id
                $userModel = UserModelDao::getInstance()->loadUserModel($userId);
                if (empty($userModel)){
                    return rjson([], 500, '用户不存在');
                }
            }
            $userId = $userModel->userId;
//        注销申请中状态下的用户账号禁止进行公众号充值和公会代充值
            if ($userModel->cancelStatus != 0) {
                return rjson([], 401, '账号已注销或申请注销中，无法充值');
            }
            // 已实名并且未成年限制操作
            $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
            if ($isUnderAge) {
                return rjson([], 500, '未满18周岁用户暂不支持此功能');
            }
        }

        try {
            if ($code && !in_array($channel, PayConstant::CHINAUMS_CHANNEL_CODE_PAYS)) {
                $order = ChargeService::getInstance()->gzhBuyProduct($userId, $rmb, $channel, $isActive, $this->config, $code);
                $openid = curlData('http://api.ddyuyin.com/web/gzhindex', ['code' => $code]);
                Log::record('openid----' . $openid);
                $order = [
                    'out_trade_no' => $order->orderId,
                    'total_amount' => $order->rmb * 100,
                    'subject' => '音咖语音',
                ];
                $result = PayService::getInstance()->wxGzhPay($order, $openid);
                //组装页面中调起支付的参数
                $prePayData = PayService::getInstance()->initPrepayData($result);
                View::assign('appId', $prePayData['appId']);
                View::assign('timeStamp', $prePayData['timeStamp']);
                View::assign('nonceStr', $prePayData['nonceStr']);
                View::assign('package', $prePayData['package']);
                View::assign('signType', $prePayData['signType']);
                View::assign('paySign', $prePayData['paySign']);
                return View::fetch('../view/web/zhifu/pay.html');
            } else {
                list($payResult, $orderId) = ChargeService::getInstance()->androidBuyProduct($userId, $rmb, $channel, $isActive, $this->config, $code, $productId);
                return rjson($this->makePayResult($payResult, $channel));
            }
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function appVipPayment()
    {
        $channel = Request::param('channel');           //支付渠道
        $rmb = Request::param('rmb');                   //支付金额
        $uid = Request::param('uid');
        $code = Request::param('code');
        $userId = intval($this->headUid);
        if (empty($userId)){
            $userId = $uid;
        }

        if (empty($channel) && empty($rmb)) {
            return rjson([], 500, '参数错误');
        }

        $mobile = UserModelDao::getInstance()->getBindMobile($userId);
        if (empty($mobile)) {
            return rjson([], 5100, '您还没有绑定手机号');
        }

        $monitoringModel = MonitoringModelDao::getInstance()->findByUserId($userId);
        if ($monitoringModel != null) {
            return rjson([], 500, '青少年模式已开启');
        }

        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if ($isUnderAge) {
            return rjson([], 500, '未满18周岁用户暂不支持此功能');
        }

        try {
            list($payResult, $orderId) = ChargeService::getInstance()->androidBuyVipProduct($userId, $rmb, $channel, $this->config, $code);
            return rjson($this->makePayResult($payResult, $channel));
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    //支付接口
    public function AndroidBuyProduct()
    {
        $channel = Request::param('channel');
        $productId = floatval(Request::param('productId'));
        $code = Request::param('code');
        $userId = empty($this->headUid) ? Request::param('userId') : intval($this->headUid);
        if (empty($userId)) {
            return rjsonFit([], 500, '用户不存在');
        }
//        注销申请中状态下的用户账号禁止进行公众号充值和公会代充值
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel && $userModel->cancelStatus != 0) {
            throw new FQException('账号已注销或申请注销中，无法充值', 401);
        }
        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if ($isUnderAge) {
            return rjson([], 500, '未满18周岁用户暂不支持此功能');
        }
        if ($code) {
            $order = ChargeService::getInstance()->newBuyProduct($userId, $productId, $channel);
            $openid = curlData('http://api.ddyuyin.com/web/gzhindex', ['code' => $code]);
            Log::info(sprintf('AndroidController::payMent $userId=%d openid=%s', $userId, $openid));

            $order = [
                'out_trade_no' => $order->orderId,
                'total_amount' => $order->rmb * 100,
                'subject' => '音咖',
            ];
            $result = PayService::getInstance()->wxGzhPay($order, $openid);
            //组装页面中调起支付的参数
            $prePayData = PayService::getInstance()->initPrepayData($result);
            View::assign('appId', $prePayData['appId']);
            View::assign('timeStamp', $prePayData['timeStamp']);
            View::assign('nonceStr', $prePayData['nonceStr']);
            View::assign('package', $prePayData['package']);
            View::assign('signType', $prePayData['signType']);
            View::assign('paySign', $prePayData['paySign']);
            return View::fetch('../view/web/zhifu/pay.html');
        } else {
            $order = ChargeService::getInstance()->newBuyProduct($userId, $productId, $channel);
            $payResult = PayService::getInstance()->pay($order, $this->config, false, $code);

            return rjsonFit($this->makePayResult($payResult, $channel));
        }
    }

    /**
     * @desc 获取支付渠道
     * @return \think\response\Json
     */
    public function payChannel()
    {
        $wechatPayChannelWay = config("config.wechat_pay_channel_way", 1);
        $aliPayChannelWay = config("config.ali_pay_channel_way", 1);

        // 初始渠道列表(不完全统计，可能有误)
        $payChannelList = [
            'wechat_app' => 3,
            'ali_app' => 1,
            'wechat_web' => 13,
            'ali_web' => 16,
            'wechat_code' => 15,
            'ali_code' => 2,
            'wechat_applet' => 32,  // 目前原生没有
            'wechat_gzh' => 4,
        ];
        // 如果微信走银联商务渠道
        if ($wechatPayChannelWay == 2) {
            $payChannelList['wechat_app'] = 32;  // 微信app调用微信小程序
            $payChannelList['wechat_web'] = 32;  // 微信h5调用微信小程序
            $payChannelList['wechat_applet'] = 32;
            $payChannelList['wechat_gzh'] = 33;
            $payChannelList['wechat_code'] = 35;
        }

        // 如果微信走银联商务渠道
        if ($aliPayChannelWay == 2) {
            $payChannelList['ali_app'] = 31;
            $payChannelList['ali_web'] = 34;
            $payChannelList['ali_code'] = 35;
        }

        return rjson([
            'wechat_pay_channel_way' => $wechatPayChannelWay,
            'ali_pay_channel_way' => $aliPayChannelWay,
            'pay_channel_list' => $payChannelList
        ]);
    }

    /**
     * @desc 获取小程序支付链接地址(三方支付-音恋商务) H5跳转小程序使用
     * @return \think\response\Json
     */
    public function getWxAppletUrlLink()
    {
        $query = Request::param('query');
        $envVersion = Request::param('env_version');

        if (!$query || !$envVersion) {
            return rjson([], 500, '缺少query或env_version参数');
        }

        $linkParams['query'] = $query;
        $linkParams['env_version'] = $envVersion;
        try {
            $urlLink = WeChatService::getInstance()->getUrlLink($linkParams);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }

        return rjson(['url_link' => $urlLink]);
    }
}
