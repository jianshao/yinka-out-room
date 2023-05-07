<?php


namespace app\query\banner;


use app\common\RedisCommon;
use app\query\banner\dao\BannerModelDao;
use app\query\pay\service\ChargeService;
use app\domain\user\service\OnlineTestService;
use app\utils\ArrayUtil;
use Exception;
use think\facade\Log;

class BannerService
{
    // 单例
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BannerService();
        }
        return self::$instance;
    }

    public function getBannerList($userId, $type, $source)
    {
        //type 轮播类型1首页 2房间 5-充值
        if ($type == 1) {
            //判断来源
            if ($source == 'mua') {
                // 先从缓存获取
                $banners = $this->loadBannersFromCache('list_bannerindex_mua');
                if ($banners == null) {
                    $banners = BannerModelDao::getInstance()->listByType(3, 2);
                    $this->saveBannerToCache($banners, 'list_bannerindex_mua');
                }
            } else {
                // 先从缓存获取
                $banners = $this->loadBannersFromCache('list_bannerindex');
                if ($banners == null) {
                    $banners = BannerModelDao::getInstance()->listByType($type, 2);
                    $this->saveBannerToCache($banners, 'list_bannerindex');
                }
            }
        } elseif ($type == 5){
            $banners = $this->loadBannersFromCache('list_bannerpay');
            if ($banners == null) {
                $banners = BannerModelDao::getInstance()->listByType($type, 2);
                $this->saveBannerToCache($banners, 'list_bannerpay');
            }
        }else {
            $banners = $this->loadBannersFromCache('list_bannerroom');
            if ($banners == null) {
                $banners = BannerModelDao::getInstance()->listByType($type, 2);
                $this->saveBannerToCache($banners, 'list_bannerroom');
            }
        }

        if (ChargeService::getInstance()->isFirstCharged($userId)) {
            foreach ($banners as $k => $v) {
                if ($v->id == 108) {
                    unset($banners[$k]);
                    break;
                }
            }
        }

        //如果用户是内部测试用户，则可以看到未开始的banner
        $testerList = OnlineTestService::getInstance()->getOnlineTestUser();
        if (in_array($userId, $testerList)) {
            $otherBanners = BannerModelDao::getInstance()->getSoonStartByType($type);
            if (!empty($otherBanners)) {
                foreach ($otherBanners as $otherBanner) {
                    array_unshift($banners, $otherBanner);
                }
            }
        }
        return $banners;
    }


    private function decodeBanner($data)
    {
        $banner = new BannerModel();
        $banner->id = $data['id'];
        $banner->type = $data['type'];
        $banner->image = $data['image'];
        $banner->linkUrl = $data['linkUrl'];
        $banner->title = $data['title'];
        $banner->channel = $data['channel'];
        $banner->createTime = $data['createTime'];
        $banner->startTime = $data['startTime'];
        $banner->endTime = $data['endTime'];
        $banner->showType = $data['showType'];
        $banner->status = $data['status'];
        $banner->bannerType = ArrayUtil::safeGet($data, 'bannerType', '');
        $banner->location = ArrayUtil::safeGet($data, 'location', 0);
        return $banner;
    }

    private function encodeBanner($banner)
    {
        return [
            'id' => $banner->id,
            'type' => $banner->type,
            'image' => $banner->image,
            'linkUrl' => $banner->linkUrl,
            'title' => $banner->title,
            'channel' => $banner->channel,
            'createTime' => $banner->createTime,
            'startTime' => $banner->startTime,
            'endTime' => $banner->endTime,
            'showType' => $banner->showType,
            'status' => $banner->status,
            'bannerType' => $banner->bannerType,
            'location' => $banner->location
        ];
    }

    private function saveBannerToCache($banners, $key)
    {
        $datas = [];
        foreach ($banners as $banner) {
            $datas[] = $this->encodeBanner($banner);
        }
        $s = json_encode($datas);
        $redis = RedisCommon::getInstance()->getRedis();
        $expiresTime = time() + (7 * 24 * 60 * 60); // 一周时间
        $redis->set($key, $s);
        $redis->expireAt($key, $expiresTime);
    }

    private function loadBannersFromCache($key)
    {
        try {
            $redis = RedisCommon::getInstance()->getRedis();
            $datas = $redis->get($key);
            if (!empty($datas)) {
                $datas = json_decode($datas, true);
                $ret = [];
                foreach ($datas as $data) {
                    $ret[] = $this->decodeBanner($data);
                }
                return $ret;
            }
            return null;
        } catch (Exception $e) {
            Log::wanning(sprintf('BannerService::loadBannersFromCache BadData ex=%d:%s',
                $e->getCode(), $e->getMessage()));
            return null;
        }
    }
}