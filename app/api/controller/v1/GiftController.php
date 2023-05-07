<?php
namespace app\api\controller\v1;
//礼物类
//

use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\gift\service\GiftService;
use app\domain\user\dao\BeanModelDao;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class GiftController extends ApiBaseController
{
    private $giftListNameToPanelNameMap = [
        'gift_list' => 'gift',
        'active_gift_list' => 'activity',
        'vip_gift_list' => 'privilege'
    ];

     /**
     * [消息送礼列表]
     * @param string is_show  0不展示 1付费礼物 2免费 3全展示
     */ 
    public function msgGiftList()
    {
        try {
            $ret = [];

            foreach ($this->giftListNameToPanelNameMap as $giftListName => $pannelName) {
                $panelGifts = [];
                $panel = GiftSystem::getInstance()->findPrivateChatPanelByName($pannelName);
                if ($panel) {
                    foreach ($panel->gifts as $giftKind) {
                        $panelGifts[] = $this->encodePrivateChatGift($giftKind);
                    }
                }
                $ret[$giftListName] = $panelGifts;
            }

            $ret['is_show'] = 1;

            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }


    /**
     * 获取游戏礼物列表
     * @param string $value [description]
     */
    public function giftGameList()
    {
        $gameGifts = GiftSystem::getInstance()->getGameGifts();
        if(empty($gameGifts)){
            return rjson();
        }
        $res = [];
        foreach ($gameGifts as $giftKind) {
            $res[] = $this->encodeGameGift($giftKind);
        }
        return rjson($res);
    }

    public function calcGiftCoin($giftKind) {
        if ($giftKind->price == null
            || $giftKind->price->assetId != AssetKindIds::$BEAN) {
            return 0;
        }

        return $giftKind->price->count;
    }

    public function encodePrivateChatGift($giftKind) {
        return [
            'gift_id' => $giftKind->kindId,
            'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
            'gift_name' => $giftKind->name,
            'gift_coin' => $this->calcGiftCoin($giftKind),
            'gift_number' => $giftKind->deliveryCharm,
            'gift_diamond' => filter_money($giftKind->getReceiverAssetCount(AssetKindIds::$DIAMOND) / config('config.khd_scale')),
            'is_vip' => 0
        ];
    }

    public function encodeGameGift($giftKind) {
        $coin = $this->calcGiftCoin($giftKind);
        return [
            'gift_id' => $giftKind->kindId,
            'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
            'gift_animation' => CommonUtil::buildImageUrl($giftKind->giftAnimation),
            'gift_mp4animation' => CommonUtil::buildImageUrl($giftKind->giftMp4Animation),
            'animation' => CommonUtil::buildImageUrl($giftKind->animation),
            'gift_name' => $giftKind->name,
            'gift_coin' => $coin,
            'gift_number' => $giftKind->deliveryCharm,
            'is_vip' => 0,
            'describe' => "送出礼物后可获得".floor($coin/2)."体力值,可参与淘金之旅活动"
        ];
    }

    public function encodeGift($giftKind) {
        return [
            'gift_id' => $giftKind->kindId,
            'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
            'gift_animation' => CommonUtil::buildImageUrl($giftKind->giftAnimation),
            'gift_mp4animation' => CommonUtil::buildImageUrl($giftKind->giftMp4Animation),
            'animation' => CommonUtil::buildImageUrl($giftKind->animation),
            'gift_name' => $giftKind->name,
            'gift_coin' => $this->calcGiftCoin($giftKind),
            'gift_number' => $giftKind->deliveryCharm,
            'is_vip' => $giftKind->vipLevel,
            'is_duke' => $giftKind->dukeLevel,
            'gift_classification' => $giftKind->classification,
            'gift_introduce' => $giftKind->intro,
            'gift_tag' => CommonUtil::buildImageUrl($giftKind->tags),
            'giftBoxIntroduction' => GiftService::getInstance()->buildBoxIntroduction($giftKind),
            'clientParams' => $giftKind->clientParams
        ];
    }

    /**
     * 礼物列表
     */
    public function giftList()
    {
        $type = Request::param('type');
        $userId = $this->headUid;

        try {
            $ret = [];

            foreach ($this->giftListNameToPanelNameMap as $giftListName => $pannelName) {
                $panelGifts = [];
                $panel = GiftSystem::getInstance()->findPanelByName($pannelName);
                if ($panel) {
                    foreach ($panel->gifts as $gift) {
                        $panelGifts[] = $this->encodeGift($gift);
                    }
                    foreach ($panelGifts as $key => $val) {
                        if (!empty($val['giftBoxIntroduction']) && $val['gift_id'] != 376) {
                            unset($panelGifts[$key]);
                        }
                    }
                    $panelGifts = array_values($panelGifts);
                }
                $ret[$giftListName] = $panelGifts;
            }

            if ($type == 1) {
                $hongbao = [
                    [
                        'gift_id' => '-1',
                        'gift_name' => '发红包',
                        'gift_number' => '0',
                        'gift_coin' => '0',
                        'gift_image' => 'https://resource.abyy.shuoguo.xyz/image/testtxk/ic_red_envelope.png',
                        'gift_type' => '0',
                        'gift_animation' => '',
                        'gift_mp4animation' => '',
                        'animation' => '',
                        'class_type' => '0',
                        'broadcast' => '0',
                        'is_vip' => '0',
                        'clientParams' => null
                    ]
                ];
                $giftList = ArrayUtil::safeGet($ret, 'active_gift_list');
                if ($giftList !== null) {
                    $ret['active_gift_list'] = ArrayUtil::insert($giftList, 0, $hongbao);
                }
            }

            //获取用户虚拟币
            $bean = BeanModelDao::getInstance()->loadBean($userId);
            $ret['balance'] = $bean->balance();

            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * 新版本礼物列表
     */
    public function newGiftList()
    {
        $type = Request::param('type');
        $userId = $this->headUid;

        try {
            $ret = [];

            foreach ($this->giftListNameToPanelNameMap as $giftListName => $pannelName) {
                $panelGifts = [];
                $panel = GiftSystem::getInstance()->findPanelByName($pannelName);
                if ($panel) {
                    foreach ($panel->gifts as $gift) {
                        $panelGifts[] = $this->encodeGift($gift);
                    }
                }
                $ret[$giftListName] = $panelGifts;
            }
            if ($type == 1) {
                $hongbao = [
                    [
                        'gift_id' => '-1',
                        'gift_name' => '发红包',
                        'gift_number' => '0',
                        'gift_coin' => '0',
                        'gift_image' => 'https://resource.abyy.shuoguo.xyz/image/testtxk/ic_red_envelope.png',
                        'gift_type' => '0',
                        'gift_animation' => '',
                        'gift_mp4animation' => '',
                        'animation' => '',
                        'class_type' => '0',
                        'broadcast' => '0',
                        'is_vip' => '0',
                        'clientParams' => null
                    ]
                ];
                $giftList = ArrayUtil::safeGet($ret, 'active_gift_list');
                if ($giftList !== null) {
                    $ret['active_gift_list'] = ArrayUtil::insert($giftList, 0, $hongbao);
                }
            }

            //获取用户虚拟币
            $bean = BeanModelDao::getInstance()->loadBean($userId);
            $ret['balance'] = $bean->balance();

            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**礼物详情
     * @param gift_id 礼物id
     */
    public function giftDetail()
    {
        $gift_id = (int)Request::param('gift_id');
        if (!$gift_id) {
            return rjson([], 500, '参数错误');
        }

        $giftKind = GiftSystem::getInstance()->findGiftKind($gift_id);

        if($giftKind == null){
            return rjson([], 500, '礼物ID错误');
        }

        $gift_detail = [
            'gift_id' => $giftKind->kindId,
            'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
            'gift_animation' => CommonUtil::buildImageUrl($giftKind->giftAnimation),
            'gift_mp4animation' => CommonUtil::buildImageUrl($giftKind->giftMp4Animation),
            'animation' => CommonUtil::buildImageUrl($giftKind->animation),
            'gift_name' => $giftKind->name
        ];

        return rjson($gift_detail);
    }

    /**
     * @desc 动画礼物地址mp4列表
     */
    public function giftMp4AnimationList()
    {
        $giftMp4AnimationList = GiftService::getInstance()->giftMp4AnimationList();
        if ($this->channel == 'appStore' && $this->version == '2.9.9') {
            $giftMp4AnimationList = [];
        }
        $return = [
            'gift_list' => $giftMp4AnimationList
        ];

        return rjson($return);
    }

    /**
     * 盲盒滚动
     */
    public function giftBoxRoll()
    {
        $key = 'rank_giftbox_scroll';
        $redis = RedisCommon::getInstance()->getRedis();
        $datas = $redis->lRange($key, 0, -1);
        $rankDatas = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $jsonObj = json_decode($data, true);
                $giftKind = GiftSystem::getInstance()->findGiftKind($jsonObj['giftId']);
                if ($giftKind != null) {
                    $rankDatas[] = [
                        'name' => UserModelCache::getInstance()->findNicknameByUserId( $jsonObj['userId']),
                        'giftName' => $giftKind->name,
                        'count' => $jsonObj['count'],
                    ];
                }
            }
        }

        return rjson([
            'list' => $rankDatas
        ]);
    }

    /**
     * 盲盒锦鲤榜
     */
    public function giftBoxRank()
    {
        $pageNo = $this->request->param('pageNo', 0);
        $pageSize = $this->request->param('pageSize', 20);

        list($total, $rankList) = $this->getJinliRankList($pageNo * $pageSize, $pageSize);
        $rankDatas = [];

        if (!empty($rankList)) {
            foreach ($rankList as $rankData) {
                $userId = $rankData['userId'];
                $boxGiftKind = GiftSystem::getInstance()->findGiftKind($rankData['boxGiftId']);
                $giftKind = GiftSystem::getInstance()->findGiftKind($rankData['giftId']);
                if ($boxGiftKind != null && $giftKind != null) {
                    $rankDatas[] = [
                        'name' => UserModelCache::getInstance()->findNicknameByUserId($userId),
                        'boxGiftName' => $boxGiftKind->name,
                        'boxGiftImage' => CommonUtil::buildImageUrl($boxGiftKind->image),
                        'giftName' => $giftKind->name,
                        'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                        'count' => $rankData['count'],
                        'time' => $rankData['time']
                    ];
                }
            }
        }

        return rjson([
            'total' => $total,
            'list' => $rankDatas
        ]);
    }

    private function getJinliRankList($offset, $count) {
        $key = 'rank_giftbox_jinli';
        $redis = RedisCommon::getInstance()->getRedis();
        $datas = $redis->lRange($key, $offset, $offset + $count - 1);
        $ret = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $jsonObj = json_decode($data, true);
                $ret[] = [
                    'userId' => $jsonObj['userId'],
                    'boxGiftId' => $jsonObj['boxGiftId'],
                    'giftId' => $jsonObj['giftId'],
                    'count' => $jsonObj['count'],
                    'time' => $jsonObj['time']
                ];
            }
        }
        $total = $redis->lLen($key);
        if ($total === false) {
            $total = 0;
        }
        return [$total, $ret];
    }

}