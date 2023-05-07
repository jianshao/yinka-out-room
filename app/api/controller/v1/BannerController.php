<?php

namespace app\api\controller\v1;


use app\query\banner\BannerService;
use app\domain\led\LedSystem;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;
use app\utils\StringUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class BannerController extends ApiBaseController
{
    public function encodeBanner($banner, $token) {
        return [
            'banner_id' => $banner->id,
            'image' => StringUtil::ReplaceUrl($banner->image),
            'linkurl' => StringUtil::ReplaceUrl($banner->linkUrl, 'newmapi2.jmyuyin.com') . '?mtoken=' . $token . '&timestamp=' . time() . '&user_id=' . $this->headUid,
            'title' => $banner->title,
            'banner_channel' => $banner->channel,
            'show_type' => $banner->showType,
            'bannerType' => $banner->bannerType
        ];
    }

    /**
     * 首页/房间banner列表
     */
    public function bannerList()
    {
        $versionCheckStatus = Request::middleware('versionCheckStatus', 0); //提审状态 1正在提审 0非提审
        $type = Request::param('type');
        $type = $type == 2 ? 2 : 1;
        $userId = intval($this->headUid);

        if($versionCheckStatus){
            return rjson([
                'bannerList'=>[]
            ]);
        }
        $bannerTotals = BannerService::getInstance()->getBannerList($userId, $type, $this->source);
        $banners = [];

        # v2的banner房间分位置了 v1的只能取location是1的
        foreach ($bannerTotals as $banner) {
            if ($banner->location != 2) {
                $banners[] = $banner;
            }
        }

        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        $bannerList = [];
        foreach ($banners as $banner) {
            if ($banner->id == 1 && $userModel->lvDengji < 20) {
                continue;
            }
            $bannerList[] = $this->encodeBanner($banner, $this->headToken);
        }

        $result = [
            'bannerList' => $bannerList,    //首页轮播接口
        ];
        return rjson($result);
    }

    public function getLedJumpUrl(){
        $action = Request::param('action');
        if (empty($action)){
            return rjsonFit();
        }

        $userId = intval($this->headUid);
        $action = json_decode($action,true);
        $type = ArrayUtil::safeGet($action, 'type');
        $name = ArrayUtil::safeGet($action, 'name');

        $result = [];
        if ($type == 'h5'){
            $userModel = UserModelCache::getInstance()->getUserInfo($userId);
            if ($name ==  'turntable' && $userModel->lvDengji < 20){
                return rjsonFit();
            }

            $jumpConf = LedSystem::getInstance()->getLedJumpConf($name);
            if (!empty($jumpConf)){
                $result['linkUrl'] = $jumpConf['linkUrl']. '?mtoken=' . $this->headToken . '&timestamp=' . time();
                $result['showType'] = $jumpConf['showType'];
                $result['title'] = $jumpConf['title'];
            }
        }
        return rjsonFit($result);
    }
}