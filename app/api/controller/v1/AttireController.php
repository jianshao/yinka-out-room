<?php
/**
 * 装扮类
 * yond
 * 
 */

namespace app\api\controller\v1;

use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\bank\BankAccountTypeIds;
use app\domain\bank\dao\BankAccountDao;
use app\query\prop\service\PropQueryService;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\dao\DiamondModelDao;
use app\domain\exceptions\FQException;
use app\domain\mall\MallIds;
use app\domain\mall\MallSystem;
use app\domain\prop\service\PropService;
use app\domain\mall\service\MallService;
use app\domain\user\service\UnderAgeService;
use app\query\user\cache\UserModelCache;
use app\service\CommonCacheService;
use app\utils\ArrayUtil;
use app\utils\CompareUtil;
use app\view\MallView;
use app\view\PropView;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class AttireController extends ApiBaseController
{
    //装扮分类
    public function attireType()
    {
        try {
            $type = Request::param('type');
            $ret = [];
            $mall = MallSystem::getInstance()->findMallByMallId(MallIds::$BEAN);
            if ($mall != null) {
                foreach ($mall->getShelvesAreaList() as $shelvesArea) {
                    if (array_key_exists($shelvesArea->type, PropView::$shelvesAreaTypeMap)) {
                        $ret[] = PropView::$shelvesAreaTypeMap[$shelvesArea->type];
                    }
                }
                if($this->source == 'fanqie'){
                    # 只有背包需要道具这一栏 商城不需要
                    if ($type == 'attire'){
                        $ret[] = PropView::$shelvesAreaTypeMap['simple'];
                    }
                }
            }
            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

	//装扮初始化
	public function attireInit()
	{
        $userId = $this->headUid;
		try {
		    $ret = [];
		    $diamondModel = DiamondModelDao::getInstance()->loadDiamond($userId);
		    $beanModel = BeanModelDao::getInstance()->loadBean($userId);
		    $userModel = UserModelCache::getInstance()->getUserInfo($userId);
            $bankAccountMAp = BankAccountDao::getInstance()->loadAllBankAccount($userId);
            $ret['coin'] = $beanModel->balance();
            $diamond = $diamondModel->balance();
            $diamond = $diamond > 0 ? floor($diamond) /config('config.khd_scale') : 0;
            $ret['diamond'] = $diamond;
            $ret['is_vip'] = $userModel->vipLevel;
            $ret['vip_exp'] = $userModel->vipExpiresTime;
            $ret['svip_exp'] = $userModel->svipExpiresTime;
            $ret['selfSilver'] = ArrayUtil::safeGet($bankAccountMAp, BankAccountTypeIds::$CHIP_SILVER, 0);
            $ret['selfGold'] = ArrayUtil::safeGet($bankAccountMAp, BankAccountTypeIds::$CHIP_GOLD, 0);
            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
	}

    //装扮列表
    public function attireList()
    {
        $type = Request::param('type');

        if (empty($type)) {
            return rjson([],500,'请选择分类');
        }

        $type = intval($type);

        $shelvesAreaType = MallView::calcAreaTypeByPid($type);
        if ($shelvesAreaType == null) {
            return rjson([],500,'分类错误');
        }

        try {
            $ret = [];
            $mall = MallSystem::getInstance()->findMallByMallId(MallIds::$BEAN);
            $shelvesArea = $mall != null ? $mall->findShelvesArea($shelvesAreaType) : null;
            if ($shelvesArea != null) {
                foreach ($shelvesArea->shelvesList as $shelves) {
                    foreach ($shelves->goodsList as $goods) {
                        if (MallView::isShowInMall($goods)) {
                            $ret[] = MallView::encodeGoods($goods, $mall, $shelvesArea);
                        }
                    }
                }
            }
            return $ret;
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

    //我的装扮
    public function selfAttire()
    {
        $type = Request::param('type');
        if (empty($type)) {
            return rjson([],500,'请选择分类');
        }

        $userId = intval($this->headUid);
        $propType = PropView::calcPropTypeByPid($type);

        if ($propType == null) {
            return rjson([],500,'分类错误');
        }

        try {
            $allProps = PropQueryService::getInstance()->queryUserProps($userId);
            $props = [];
            $curTime = time();
            foreach ($allProps as $prop) {
                if ($prop->kind->getTypeName() == $propType
                    && $prop->kind->showInBag
                    && (!$prop->isDied($curTime))) {
                    $props[] = $prop;
                }
            }

            usort($props, function ($a, $b) {
                // 佩戴的优先
                if ($a->isWore && !$b->isWore) {
                    return -1;
                } elseif ($b->isWore) {
                    return 1;
                }

                $timestamp = time();
                if ($a->isTiming() && $b->isTiming()) {
                    return CompareUtil::compare($a->breakUpBalance($timestamp), $b->breakUpBalance($timestamp), true);
                } else {
                    return CompareUtil::compare($a->updateTime, $b->updateTime, true);
                }
            });
            $ret = [];
            $timestamp = time();
            $roomId = CommonCacheService::getInstance()->getUserCurrentRoom($userId);

            foreach ($props as $prop) {
                $ret[] = PropView::encodeProp($prop, $roomId, $timestamp);
            }
            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

    public function setAttire()
    {
        $propId = intval(Request::param('attire_id'));
        $pid = intval(Request::param('pid'));
        $userId = intval($this->headUid);

        try {
            if ($propId == -1) {
                // 卸装
                $typeName = PropView::calcPropTypeByPid($pid);
                PropService::getInstance()->doActionByPropType($userId, $typeName, 'unwear', []);
                return rjson([], 200, '卸载成功');
            } else {
                PropService::getInstance()->doActionByPropId($userId, $propId, 'wear', []);
                return rjson([], 200, '更换成功');
            }
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * @return \think\response\Json
     */
	public function buyAttire()
	{
	    //获取数据
        $propId = intval(Request::param('attid'));
        $count = intval(Request::param('longtime'));

        $userId = $this->headUid;

        $goodsMall = MallSystem::getInstance()->findGoodsByAssetId(AssetUtils::makePropAssetId($propId));
        if ($goodsMall == null) {
            throw new FQException('此商品不存在', 500);
        }
        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if($isUnderAge){
            return rjson([],500,'未满18周岁用户暂不支持此功能');
        }
        try {
            $balance = MallService::getInstance()->buyGoodsByGoods($userId, $goodsMall[0], $count, MallIds::$BEAN, 'attire');
            return rjson($balance);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

    /**
     * 1.0.6最新商城列表
     */
    public function newAttireList()
    {
        $type = Request::param('type');

        if (empty($type)) {
            return rjson([], 500, '请选择分类');
        }

        $type = intval($type);
        if (!array_key_exists($type, PropView::$pidToPropTypeName)) {
            return rjson([], 500, '分类错误');
        }

        $areaType = PropView::$pidToPropTypeName[$type];
        $userId = $this->headUid;

        try {
            $ret = [];
            $timestamp = time();

            $mall = MallSystem::getInstance()->findMallByMallId(MallIds::$BEAN);
            $propBag = PropService::getInstance()->getPropBag($userId);

            if ($mall != null) {
                $shelvesArea = $mall->findShelvesArea($areaType);
                if ($shelvesArea != null) {
                    foreach ($shelvesArea->shelvesList as $shelves) {
                        $propsData = [];
                        foreach ($shelves->goodsList as $goods) {
                            if (MallView::isShowInMall($goods)) {
                                $propsData[] = MallView::encodeGoodsWithPropBag($goods, $mall, $shelvesArea, $propBag, $timestamp);
                            }
                        }
                        if (count($propsData) > 0) {
                            $ret[] = [
                                'list_name' => $shelves->displayName,
                                'attire' => $propsData,
                            ];
                        }
                    }
                }
            }
            $ret = array_values($ret);
            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * @info 装扮action 使用/分解
     * @return \think\response\Json
     */
    public function attireAction()
    {
        $kindIdsStr = Request::param('kindIds');
        $action = Request::param("action", "");// 行为
        $params = Request::param('params', "");     //参数params
        if (empty($kindIdsStr) || empty($action)) {
            return rjsonFit([], 500, '参数错误');
        }
        $kindIds = [];
        foreach (explode(',', $kindIdsStr) as $kindId) {
            $kindIds[] = intval($kindId);
        }

        $params = json_decode($params, true);
        $userId = $this->headUid;
        $result = [];

        $totalAssetList = PropService::getInstance()->doActionByKindIds($userId, $kindIds, $action, $params);
        foreach ($totalAssetList as list($assetList, $count)) {
            foreach ($assetList as $assetItem) {
                $assetKind = AssetSystem::getInstance()->findAssetKind($assetItem->assetId);

                if (!array_key_exists($assetKind->kindId, $result)){
                    $result[$assetKind->kindId] = 0;
                }

                $result[$assetKind->kindId] += $assetItem->count*$count;
            }
        }

        return rjsonFit($result, 200, "操作成功");
    }

    //我的装扮
    public function newSelfAttire()
    {
        $type = Request::param('type');
        $propType = PropView::calcPropType($type);
        if (empty($propType)) {
            return rjson([],500,'分类错误');
        }

        $userId = $this->headUid;
        try {
            $allProps = PropQueryService::getInstance()->queryUserProps($userId);

            $props = [];
            $curTime = time();
            foreach ($allProps as $prop) {
                if ($prop->kind->getTypeName() == $type
                    && $prop->kind->showInBag
                    && !$prop->isDied($curTime)) {
                    $props[] = $prop;
                }
            }

            usort($props, function ($a, $b) {
                // 佩戴的优先
                if ($a->isWore && !$b->isWore) {
                    return -1;
                } elseif ($b->isWore) {
                    return 1;
                }

                $timestamp = time();
                if ($a->isTiming() && $b->isTiming()) {
                    return CompareUtil::compare($a->breakUpBalance($timestamp), $b->breakUpBalance($timestamp), true);
                } else {
                    return CompareUtil::compare($a->updateTime, $b->updateTime, true);
                }
            });
            $attireList = [];
            $timestamp = time();
            foreach ($props as $prop) {
                $attireList[] = PropView::encodeNewProp($prop, $timestamp);
            }

            $ret['attireList'] = $attireList;
            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

    public function newSetAttire()
    {
        $kindId = Request::param('kindId', 0, 'intval'); // 道具id
        $action = Request::param("action", "");// 行为
        $params = Request::param('params', "");     //参数params
        if (empty($kindId) || empty($action)) {
            return rjson([],500,'参数错误');
        }

        $params = json_decode($params, true);
        $userId = $this->headUid;
         PropService::getInstance()->doActionByKindId($userId, $kindId, $action, $params);
        return rjsonFit([], 200, "操作成功");
    }
}


