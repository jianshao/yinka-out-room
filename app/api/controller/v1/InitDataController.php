<?php

namespace app\api\controller\v1;

use app\domain\dao\ChannelPointModelDao;
use app\query\advert\AdvertService;
use app\query\dao\ChannelPackageModelDao;
use app\query\active\dao\ActiveModelDao;
use app\domain\user\service\UserRegisterService;
use app\form\ClientInfo;
use app\utils\ArrayUtil;
use \app\facade\RequestAes as Request;
use app\BaseController;
use app\domain\Config;
use app\utils\CommonUtil;



class InitDataController extends BaseController
{
    private $first_charge_key = "active_charges_";                      //房间昵称

    /**首页广告
     * @return mixed
     */
    public function initList()
    {
        $clientInfo = new ClientInfo();
        $clientInfo->fromRequest($this->request);
        UserRegisterService::getInstance()->adServingCallBack($clientInfo);
        $adverts = AdvertService::getInstance()->getAdvertList();
        $ret = [];
        foreach ($adverts as $advert) {
            $ret[] = [
                'id' => $advert->id,
                'name'=> $advert->name,
                'image' => $advert->image,
                'linkurl' => $advert->linkUrl,
                'start_time' => $advert->startTime * 1000,
                'end_time' => $advert->endTime * 1000,
                'display_time' => $advert->displayTime
            ];
        }
        return rjson(['advert_list' => $ret]);
    }

    /**活动首页数据
     * @param $token 用户token值
     */
    public function activeList()
    {
        $token = $this->headToken;
        $result = ActiveModelDao::getInstance()->getAll($token);
        return rjson($result);
    }

    //埋点统计
    public function chartsAdd()
    {
        //获取数据
        $token = Request::param('token');
        $redis = $this->getRedis();
        $user_id = $redis->get($token)?$redis->get($token):0;
        $type = Request::param('type');
        $postion = Request::param('postion');
        $channel = Request::header('CHANNEL');
        $deviceId = Request::header('DEVICEID');
        $version = $this->request->header('VERSION');
        $pla = $this->request->header('PLATFORM');
        //获取当前时间
        $data = [
            "riq" => time(),
            "channel" => $channel,
            "type" => $type,
            "device_id" => $deviceId,
            "login_ip" => Request::ip(),
            "version" => $version,
            "platform" => $pla,
            "postion" => $postion,
            "user_id" => $user_id
        ];
        ChannelPointModelDao::getInstance()->saveData($data);
        return rjson();
    }

    public function returnBaseConfig() {
        $version = $this->version;
        $channel = $this->channel;
        $source = $this->source;
        $where = [
            'app_version' => $version,
            'channel_name' => $channel,
            'app_type' => $source
        ];
        $packInfo = ChannelPackageModelDao::getInstance()->getOne($where);
        $baseUrlArr = config('config.baseUrl');
        if ($packInfo) {
            $url = $packInfo['status'] == 1 ? $baseUrlArr['online_url'] : $baseUrlArr['test_url'];
        } else {
            $url = $baseUrlArr['online_url'];
        }
        return rjson(['url' => $url]);
    }

    public function returnBaseConfig2() {
        $version = $this->version;
        $channel = $this->channel;
        $source = $this->source;
        $where = [
            'app_version' => $version,
            'channel_name' => $channel,
            'app_type' => $source
        ];
        $packInfo = ChannelPackageModelDao::getInstance()->getOne($where);
        $conf = config($source . "config");
        if (isset($conf['baseUrl2'])){
            $baseUrlArr = config($source.'config.baseUrl2');
        }else{
            $baseUrlArr = config('config.baseUrl2');
        }
        if ($packInfo) {
            $url = $packInfo['status'] == 1 ? $baseUrlArr['online_url'] : $baseUrlArr['test_url'];
        } else {
            $url = $baseUrlArr['online_url'];
        }
        return rjson(['url' => $url]);
    }

    //底部导航皮肤
    public function getBottomMenuIcon()
    {
        $result = [];
        $source = $this->source;
        $bottomMenuCacheConf = Config::getInstance()->getBottomMenuConf();
        $bottomMenuList = ArrayUtil::safeGet($bottomMenuCacheConf, $source);
        if ($bottomMenuList != null) {
            foreach ($bottomMenuList as $bottomMenuKey => $bottomInfo) {
                $result[$bottomMenuKey] = [
                    'default_icon' => isset($bottomInfo['default_icon']) ? CommonUtil::buildImageUrl($bottomInfo['default_icon']) : '',
                    'click_icon' => isset($bottomInfo['click_icon']) ? CommonUtil::buildImageUrl($bottomInfo['click_icon']) : '',
                    'default_lott' => isset($bottomInfo['default_lott']) ? CommonUtil::buildImageUrl($bottomInfo['default_lott']) : '',
                    'click_lott' => isset($bottomInfo['click_lott']) ? CommonUtil::buildImageUrl($bottomInfo['click_lott']) : '',
                    'default_font_color' => isset($bottomInfo['default_font_color']) ? $bottomInfo['default_font_color']  : '',
                    'click_font_color' => isset($bottomInfo['click_font_color']) ? $bottomInfo['click_font_color']  : '',
                ];
            }
        }
        return rjson($result);
    }

    //type : 1 充值 2下载 3提现 4官网
    public function getUrl() {
        $type = Request::param('type');
        if (empty($type)) {
            $url = "https://www.muayuyin.com/gw/#/download?ts=" . time();
            header("Location: $url");
            exit();
        }
        $redis = $this->getRedis();
        $redis->select(3);
        $url = $redis->hGet('h5url_config', $type);
        $url = $url . time();
        echo "<script>location.href='" . $url . "'</script>";
        exit();
    }



}