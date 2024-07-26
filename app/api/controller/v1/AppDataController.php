<?php
/**
 * app信息
 * yond
 * 
 */

namespace app\api\controller\v1;

use AlibabaCloud\Client\AlibabaCloud;
use app\Base2Controller;
use app\common\YunxinCommon;
use app\domain\appdata\ChannelDataService;
use app\domain\appinfo\dao\ChannelDataModelDao;
use app\domain\dao\ReyunModelDao;
use app\domain\exceptions\FQException;
use app\domain\feedback\service\FeedbackService;
use app\domain\gift\GiftSystem;
use app\domain\user\service\UserService;
use app\facade\RequestAes as Request;
use app\form\ClientInfo;
use app\query\site\service\SiteService;
use app\query\user\cache\UserModelCache;
use app\query\user\dao\UserInfoMapDao;
use app\utils\CommonUtil;
use app\utils\Error;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sts\V20180813\Models\GetFederationTokenRequest;
use TencentCloud\Sts\V20180813\StsClient;
use think\facade\Log;


class AppDataController extends Base2Controller
{
    public function apple_association(){
        $out = json_decode('{
    "applinks":{
        "apps":[

        ],
        "details":[
            {
                "appID":"D8YBJ2LV66.com.like.aiyu",
                "paths":[
                    "/*",
                    "/qq_conn/102055488/*"
                ]
            }
        ]
    }
}',true);
        return json($out);
    }

    //根据用户账号id获取用户头像和昵称
    public function userData() {
        $userId = Request::param('uid');
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        if (empty($userModel)) {
            $userId = UserInfoMapDao::getInstance()->getUserIdByPrettyId($userId);
            $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        }
        if($userModel) {
            $data['nickname'] = $userModel->nickname;
            $data['avatar'] = CommonUtil::buildImageUrl($userModel->avatar);
            return rjson($data);
        }
        return rjson([],500);
    }

    public function getApiUrl() {
        $baseUrlArr = config('config.baseUrl2');
        return rjson($baseUrlArr['online_url']['api_url']);
    }


	/**
	 * 用户在线时长
     * 参数描述：用户id,用户在线时间 unit（s）
	 */
	public function userOnline()
	{
		$params = Request::param();
		$userId = intval($params['uid']);
		$onlineSecond = intval($params['duration']);
		UserService::getInstance()->updateUserOnlineTime($userId, $onlineSecond);
		return rjson();
	}

	/**
	 * 用户房间在线时长
     * 参数描述：用户id,用户在线时间 unit（s）,用户在线房间,
	 */
	public function userRoomOnline()
	{
        $params = Request::param();
        $userId = intval($params['uid']);
        $roomId = intval($params['roomid']);
        $onlineSecond = intval($params['duration']);
        UserService::getInstance()->updateUserRoomOnlineTime($userId, $roomId, $onlineSecond);
        return rjson();
	}

    /**
     * 用户在线心跳
     */
    public function userOnlineHeartBeat() {
        $userId = Request::param('uid');
        if(!$userId) {
            return rjson([],500);
        }
        $heartInterval = config('config.heartInterval');
        UserService::getInstance()->updateUserOnlineTime($userId, $heartInterval);
        return rjson();
    }

	//热云统计
	public function reyun()
	{
//		$appkey = strtoupper("6deebe8b455e35a606409eedb7d9dbd9");
		$params = Request::param();
		if (empty($params)) {
			return rjson();
		}
		$data['type'] = 1;
		$data['params'] = json_encode($params);
		$data['spreadurl'] = $params['spreadurl'] ?? '';
		$data['spreadname'] = $params['spreadname'] ?? '';
		$data['channel'] = $params['channel'] ?? '';
		$data['clicktime'] = $params['clicktime'] ?? '';
		$data['ua'] = $params['ua'] ?? '';
		$data['uip'] = $params['uip'] ?? '';
		$data['appkey'] = $params['appkey'] ?? '';
		$data['activetime'] = $params['activetime'] ?? '';
		$data['osversion'] = $params['osversion'] ?? '';
		$data['devicetype'] = $params['devicetype'] ?? '';
		$data['idfa'] = $params['idfa'] ?? '';
		$data['mac'] = $params['mac'] ?? '';
		$data['androidid'] = $params['androidid'] ?? '';
		$data['imei'] = $params['imei'] ?? '';
		$data['aip'] = $params['aip'] ?? '';
		$data['skey'] = $params['skey'] ?? '';
		ReyunModelDao::getInstance()->saveData($data);
		return rjson();
	}

	//ios上报统计
	public function iosdata()
	{
		$params = Request::param();
		if (empty($params)) {
			return rjson();
		}
		$data['type'] = 2;
		$data['params'] = json_encode($params);
		$data['appid_ios'] = $params['appid'] ?? '';
		$data['source_ios'] = $params['source'] ?? '';
		$data['ip_ios'] = $params['ip'] ?? '';
		$data['idfa_ios'] = $params['idfa'] ?? '';
		$data['os_ios'] = $params['os'] ?? '';
		$data['keyword_ios'] = $params['keyword'] ?? '';
        ReyunModelDao::getInstance()->saveData($data);
		return rjson();
	}


	//检测更新 todo
	public function checkUpdate()
	{
		$version_num = Request::header('VERSION');
		$channel = Request::header('CHANNEL');
        $redis = $this->getRedis();
        if ($this->appId == 'com.party.fq' || $this->appId == 'com.party.ccp') {
            $siteConf = SiteService::getInstance()->getSiteConf(1);
        } elseif ($this->appId == 'com.chuchu.voice') {
            $siteConf = SiteService::getInstance()->getSiteConf(3);
        } else {
            $siteConf = SiteService::getInstance()->getSiteConf(1);
        }
        if ($channel == 'appStore') {
            if (version_compare($version_num,$siteConf['ipaversions'],'<')){
                $result['version'] = $siteConf['ipaversions'];
                $result['isupdate'] = 1;
            } else {
                $result['version'] = $siteConf['ipaversions'];
                $result['isupdate'] = 0;
            }
            $result['version_content'] = $siteConf['version_content'];
            $result['apkaddress'] = $siteConf['iosaddress'];
        } else {
            if (version_compare($version_num,$siteConf['apkversions'],'<')) {
                $result['version'] = $siteConf['apkversions'];
                $result['isupdate'] = 1;
            } else {
                $result['version'] = $siteConf['apkversions'];
                $result['isupdate'] = 0;
            }
            $result['version_content'] = $siteConf['version_content'];
            $result['apkaddress'] = $siteConf['apkaddress'];
        }
        return rjson($result);
	}

	//返回app信息
    public function getAppData()
    {
        $siteConf = SiteService::getInstance()->getSiteConf(1);
        $email = 'likedianjing@163.com';
        $weibo = '';
        $weixin = '964403648';
        $wechat_public = 'like电竞';
        if (version_compare($siteConf['apkversion'], $siteConf['apkversions'], '<')) {
            $appconf = $siteConf['apkversions'];
        } else {
            $appconf = $siteConf['apkversion'];
        }

        $res = [
            'customer_id' => 1001,
            'version' => $appconf,
            'email' => $email,
            'weibo' => $weibo,
            'weixin' => $weixin,
            'fuwu' => '9:00-22:00',
            'wechat_public' => $wechat_public,   // 微信公众号
        ];
        return rjson($res);
    }

    /**
     * 礼物盒子规则
     */
	public function giftBoxInfo() {
	    $res['ruleInfo'] = "花费LB可购买并赠送幸运盒子给自己或指定的一名用户或多名用户，收到幸运盒子的用户可收获随机开出的礼物，并按照礼物的实际价值增加魅力值等。";
        $siteConf = SiteService::getInstance()->getSiteConf(1);
        $boxGift = json_decode($siteConf['giftbox'],true);
        $giftsId = implode(',',array_keys($boxGift));
        $boxGiftList = [];
        foreach ($giftsId as $key => $giftId) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if (empty($giftKind)){
                continue;
            }

            $boxGiftList[$key]['gift_image'] = CommonUtil::buildImageUrl($giftKind->image);
            $boxGiftList[$key]['gift_name'] = $giftKind->name;
            $boxGiftList[$key]['gift_id'] = $giftKind->kindId;
            $boxGiftList[$key]['gift_number'] = $giftKind->price ? $giftKind->price->count:0;
        }

        $res['boxGiftList'] = $boxGiftList;
        $res['specialInfo'] = "1、幸运盒子仅作为平台内娱乐\n2、用户通过作弊或其他非正常手段获得的奖励，平台有权收回，同依据相关规则对其进行处罚。";
        return rjson($res);
    }

    /**
     * 意见反馈
     */
    public function setFeedback() {
        $userId = intval($this->headUid);
        $content = Request::param('content');
        try {
            FeedbackService::getInstance()->addFeedback($userId, $content);
            return rjson([],200,'反馈成功');
        } catch(FQException $e) {
            return rjson([],500,'反馈失败');
        }
    }

    public function getStsToken(){
        $stsConf = config('config.STSCONF');
        $accessKeyID = $stsConf['AccessKeyID'];
        $accessKeySecret = $stsConf['AccessKeySecret'];
        $roleArn = $stsConf['RoleArn'];
        try {
            AlibabaCloud::accessKeyClient($accessKeyID,$accessKeySecret)
                ->regionId('cn-hangzhou')
                ->asDefaultClient();
            $result = AlibabaCloud::rpc()
                ->product('Sts')
                ->scheme('https') // https | http
                ->version('2015-04-01')
                ->action('AssumeRole')
                ->method('POST')
                ->host('sts.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'RoleArn' => $roleArn,
                        'RoleSessionName' => "client_name",
                    ],
                ])
                ->request();
            $content = $result->toArray();
            $data['AccessKeyId'] = $content['Credentials']['AccessKeyId'];
            $data['AccessKeySecret'] = $content['Credentials']['AccessKeySecret'];
            $data['Expiration'] = 'http://'.$stsConf['Endpoint'];
            $data['SecurityToken'] = $content['Credentials']['SecurityToken'];
            $data['Endpoint'] = $content['Credentials']['Expiration'];
            $data['BucketName'] = $stsConf['BucketName'];
            return rjson($data,200,'success');
        } catch (\ClientException $e) {
            Log::record('getStsToken:---'.$e->getErrorMessage());
            return rjson([],500,'请稍后再试');
        }
    }

    // getCosStsToken cos sts授权访问
    public function getCosStsToken(){
        $stsConf = config('cos');
        $accessKeyID = $stsConf['ACCESS_KEY_ID'];
        $accessKeySecret = $stsConf['ACCESS_KEY_SECRET'];
        $endpoint = $stsConf['ENDPOINT'];
        $Region = $stsConf['Region'];
        $policy = $stsConf['STS']['Policy'];
        $stsName = $stsConf['STS']['Name'];
        $durationSeconds = $stsConf['STS']['DurationSeconds'];
        $bucket = $stsConf['BUCKET'];

        try {
            // 实例化一个认证对象，入参需要传入腾讯云账户 SecretId 和 SecretKey，此处还需注意密钥对的保密
            // 代码泄露可能会导致 SecretId 和 SecretKey 泄露，并威胁账号下所有资源的安全性。以下代码示例仅供参考，建议采用更安全的方式来使用密钥，请参见：https://cloud.tencent.com/document/product/1278/85305
            // 密钥可前往官网控制台 https://console.cloud.tencent.com/cam/capi 进行获取
            $cred = new Credential($accessKeyID, $accessKeySecret);
            // 实例化一个http选项，可选的，没有特殊需求可以跳过
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint($endpoint);

            // 实例化一个client选项，可选的，没有特殊需求可以跳过
            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            // 实例化要请求产品的client对象,clientProfile是可选的
            $client = new StsClient($cred, $Region, $clientProfile);

            // 实例化一个请求对象,每个接口都会对应一个request对象
            $req = new GetFederationTokenRequest();

            $params = array(
                "Name" => $stsName,
                "Policy" => $policy,
                "DurationSeconds" => $durationSeconds
            );
            $req->fromJsonString(json_encode($params));

            // 返回的resp是一个GetFederationTokenResponse的实例，与请求对象对应
            $resp = $client->GetFederationToken($req);

            // 输出json格式的字符串回包
            $data = json_decode($resp->toJsonString(),true);
            $res = [
                'token'=> $data['Credentials']['Token'],
                'tmp_secretId'=> $data['Credentials']['TmpSecretId'],
                'tmp_secretKey'=> $data['Credentials']['TmpSecretKey'],
                'expired_time'=> $data['ExpiredTime'],
                'bucket'=> $bucket,
                'region'=> $Region,
                'start_time'=> time(),
            ];
            return rjson($res,200,'success');
        } catch(TencentCloudSDKException | \Exception $e) {
            Log::error('getStsToken:---'.$e->getMessage());
            return rjson([],500,'server error');
        }
    }

    public function getSbImHistory() {
        $beginTime = '1613404800000';
        $endTime = '1613664000000';
        $fromUid = 1426407;
        $toUid = 1335540;
        return YunxinCommon::getInstance()->querySessionMsg($fromUid, $toUid, $beginTime, $endTime);
    }

    /**
     * 华为渠道分析数据存储
     */
    public function HuaWeiChannelData() {
        if ($this->channel != 'HuaWei') {
            return rjson([],500,'渠道错误');
        }
        if (empty($this->headUid)) {
            return rjson([],500,'参数错误');
        }
        $channelData = Request::param('data');
        $data['user_id'] = $this->headUid;
        $data['device_id'] = $this->deviceId;
        $data['data'] = $channelData;
        $data['channel'] = 'HuaWei';
        $data['ctime'] = time();
        ChannelDataModelDao::getInstance()->addData($data);
        try {
            $data['oaid'] = $this->oaid;
            ChannelDataService::getInstance()->analysisUserSource($data);
            return rjson([],200,'存储成功');
        } catch (\Exception $e) {
            return rjson([],200,'存储成功');
        }
    }


    public function AppStoreChannelData() {
        if ($this->channel != 'appStore') {
            return rjson([],500,'渠道错误');
        }
        if (empty($this->headUid)) {
            return rjson([],500,'参数错误');
        }
        $channelData = Request::param('data');
        $data['user_id'] = $this->headUid;
        $data['device_id'] = $this->deviceId;
        $data['data'] = $channelData;
        $data['channel'] = 'appStore';
        $data['ctime'] = time();
        ChannelDataModelDao::getInstance()->addData($data);
        try {
            ChannelDataService::getInstance()->analysisUserSource($data);
            return rjson([], 200, '存储成功');
        } catch (\Exception $e) {
            Log::error(sprintf('AppDataController::AppStoreChannelData data=%s ex=%d:%s trace=%s',
                 json_encode($data), $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            return rjson([], 200, '存储成功');
        }
    }

    /**
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function androidActivate()
    {
        $channelData = Request::param('data', "");
        if (empty($channelData)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $clientInfo = new ClientInfo();
        $clientInfo->fromRequest($this->request);
        $result=ChannelDataService::getInstance()->androidActivate($this->channel,$this->oaid,$channelData,$clientInfo);
        return rjson(['result' => $result], 200, 'success');
    }
}
