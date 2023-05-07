<?php
namespace app\api\controller\v1;
//砸蛋类
//
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\game\box\BoxIds;
use app\domain\game\box\BoxSystem;
use app\domain\game\box\service\BoxService;
use app\domain\gift\GiftSystem;
use app\query\prop\dao\PropModelDao;
use app\domain\prop\PropSystem;
use app\domain\user\dao\BeanModelDao;
use app\query\room\dao\QueryRoomDao;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;

class BoxController extends ApiBaseController
{
    public function encodeGift($giftKind, $count, $isSpecial) {
        return [
            'id' => $giftKind->kindId,
            'gift_name' => $giftKind->name,
            'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
            'gift_coin' => $giftKind->price ? $giftKind->price->count : 0,
            'num' => $count,
            'is_special' => 0,
        ];
    }

    public function typeToBoxId($type) {
        if ($type == 1) {
            return BoxIds::$GOLD;
        } elseif ($type == 2) {
            return BoxIds::$SILVER;
        } else {
            throw new FQException('类型错误,请重试', 500);
        }
    }

    /**
     * 开宝箱
     * @param string $num [次数]
     * @param string $type [1金宝箱 2银宝箱]
     */
    public function breakBox()
    {
        $type = Request::param('type');
        $num = intval(Request::param('num'));
        $roomId = Request::param('room_id');
        $userId = intval($this->headUid);

        try {
            $boxId = $this->typeToBoxId($type);
            list($giftMap, $selfSpecial, $globalSpecial) = BoxService::getInstance()->breakBox($userId, $roomId, $boxId, $num);
            $result = [];
            foreach ($giftMap as $giftId => $count) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
                if ($giftKind) {
                    $result[] = $this->encodeGift($giftKind, $count, 0);
                }
            }

            if ($selfSpecial != null) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($selfSpecial);
                if ($giftKind) {
                    $result[] = $this->encodeGift($giftKind, 1, 1);
                }
            }

            if ($globalSpecial != null) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($globalSpecial);
                if ($giftKind) {
                    $result[] = $this->encodeGift($giftKind, 1, 1);
                }
            }

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

	//砸蛋榜单
	public function rankBoxList()
	{
		$redis = RedisCommon::getInstance()->getRedis();
		$today = date('Ymd');
		$listUser = $redis->zRevRange('rank_box_fuxing_' . $today,0,9,true);
		$listRoom = $redis->zRevRange('rank_box_fudi_' . $today,0,9,true);
		$userResTmp = [];
		$roomRes = [];
		$userData = [];
		if (!empty($listUser)) {
            $uids = array_keys($listUser);
            $userModels = UserModelCache::getInstance()->findList($uids);
            if ($userModels) {
                foreach ($userModels as $userModel){
                    $userResTmp[$userModel->userId] = [
                        'id' => $userModel->userId,
                        'nickname' => $userModel->nickname,
                        'pretty_id' => $userModel->prettyId,
                        'username' => $userModel->username,
                        'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                        'rank' => formatNumber($listUser[$userModel->userId])?:0,
                    ];
                }

                for ($i=0; $i < count($uids); $i++) {
                    @$userData[] = $userResTmp[$uids[$i]];
                }
            }
		}
		if (!empty($listRoom)) {
			$room_ids = array_keys($listRoom);
			$roomRes = QueryRoomDao::getInstance()->loadModelForRoomIds($room_ids);
			if ($roomRes) {
				foreach ($roomRes as $key => $roomModel) {
				    $userInfo = UserModelCache::getInstance()->getUserInfo($roomModel->userId);
					$roomRes[$key]['room_image'] = CommonUtil::buildImageUrl($userInfo->avatar);
					@$roomRes[$key]['rank'] = $listRoom[$roomModel->roomId];
				}
                $rArr = array_column($roomRes,'rank');
                array_multisort($rArr,SORT_DESC,$roomRes);
                foreach ($roomRes as $key => $roomModel) {
                    @$roomRes[$key]['rank'] = formatNumber($listRoom[$roomModel->roomId]);
                }
			}
		}

		//判断自己上一名
		$myRank = 0;
		$rankNum = '未上榜';
		$myrankNum = $redis->ZREVRANK('rank_box_fuxing_' . $today,$this->headUid);
		if ($myrankNum === false) {
			@$myRankTmp = $redis->ZREVRANGE('rank_box_fuxing_' . $today,-1,-1,true);
			@$numTmp = array_values($myRankTmp);
			@$myRank = $numTmp[0];
		}else{
			$rankNum = $myrankNum+1;
			if ($myrankNum != 0) {
				@$myRankTmp = $redis->ZREVRANGE('rank_box_fuxing_' . $today,$myrankNum-1,$myrankNum,true);
				@$numTmp = array_values($myRankTmp);
				@$myRank = $numTmp[0] - $numTmp[1];
			}else {
                $myRank = $redis->zScore('rank_box_fuxing_' . $today, $this->headUid);
            }
		}

        $userModel = UserModelCache::getInstance()->getUserInfo($this->headUid);
        $myInfo = [
            'id' => $userModel->userId,
            'nickname' => $userModel->nickname,
            'pretty_id' => $userModel->prettyId,
            'username' => $userModel->username,
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            'myrank' => formatNumber($myRank),
            'ranknumber' => $rankNum
        ];
		$listCount = count($userData);
		for ($i = 0; $i < $listCount; $i++) {
		    if($i <= 2) {
		    	if ($i == 0) {
		    		@$userData[$i]['headFrame'] = CommonUtil::buildImageUrl('/banner/20200618/4396d4185f2d7ca9faee46f0afaa3bcc.png');
		    	} elseif($i == 1) {
		    		@$userData[$i]['headFrame'] = CommonUtil::buildImageUrl('/banner/20200618/04f441a7053554b0a272441b83161158.png');
		    	} else {
		    		@$userData[$i]['headFrame'] = CommonUtil::buildImageUrl('/banner/20200618/4ecbc2a980d547653253a66954b8c327.png');
		    	}
            } else {
                @$userData[$i]['headFrame'] = '';
            }
        }
		if ($myInfo['ranknumber'] == 1) {
            @$myInfo['headFrame'] = CommonUtil::buildImageUrl('/banner/20200618/4396d4185f2d7ca9faee46f0afaa3bcc.png');
        } elseif ($myInfo['ranknumber'] == 2) {
            @$myInfo['headFrame'] = CommonUtil::buildImageUrl('/banner/20200618/04f441a7053554b0a272441b83161158.png');
        } elseif ($myInfo['ranknumber'] == 3) {
            @$myInfo['headFrame'] = CommonUtil::buildImageUrl('/banner/20200618/4ecbc2a980d547653253a66954b8c327.png');
        } else {
            $myInfo['headFrame'] = '';
        }
		return rjson([
		    'user_list' => $userData,
            'room_list' => $roomRes,
            'self_info' => $myInfo
        ]);
	}

	//宝箱进度
	public function boxInit()
	{
		$type = Request::param('type');
        $userId = intval($this->headUid);

        $timestamp = time();

        try {
            $boxId = $this->typeToBoxId($type);
            $boxInfo = BoxService::getInstance()->getBoxInfo($userId, $boxId);

            $jindus = ((float)$boxInfo->selfProgress / $boxInfo->box->maxPersonalProgress);
            if ($jindus >= 1) {
                $jindus = 100;
            }else{
                $jindus = number_format($jindus * 100,2);
            }
            $jindua = ((float)$boxInfo->globalProgress / $boxInfo->box->maxGlobalProgress);
            if ($jindua >= 1) {
                $jindua = 100;
            }else{
                $jindua = number_format($jindua * 100,2);
            }

            $hammerPropKind = $boxInfo->box->hammerPropId ? PropSystem::getInstance()->findPropKind($boxInfo->box->hammerPropId) : null;

            $beanModel = BeanModelDao::getInstance()->loadBean($userId);
            $hammerNum = 0;
            if ($hammerPropKind) {
                $propModel = PropModelDao::getInstance()->loadPropByKindId($userId, $boxInfo->box->hammerPropId);
                if ($propModel) {
                    $hammerNum = $hammerPropKind->unit->balanceByPropModel($propModel, $timestamp);
                }
            }

            $winRecords = BoxService::getInstance()->loadWinRecords($boxId, 0, 20);
            $dataBox = [];
            $redis = RedisCommon::getInstance()->getRedis();

            foreach ($winRecords as list($userId, $giftKindId, $time)) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($giftKindId);
                if ($giftKind) {
                    $dataBox[] = [
                        'nickname' =>  UserModelCache::getInstance()->findNicknameByUserId($userId),
                        'uid' => $userId,
                        'giftid' => $giftKindId,
                        'gift' => $giftKind->name,
                        'time' => date('Y-m-d H:i:s', $time)
                    ];
                }
            }

            $result = [
                'self_rate' => $jindus,
                'all_rate' => $jindua,
                'num_init' => BoxSystem::getInstance()->counts,
                'txk' => $boxInfo->box->avatarKind ? CommonUtil::buildImageUrl($boxInfo->box->avatarKind->image) : '',
                'attireName' => $boxInfo->box->avatarKind ? $boxInfo->box->avatarKind->name : '',
                'keyName' => $hammerPropKind ? $hammerPropKind->name : '',
                'price' => $boxInfo->box->price ? $boxInfo->box->price->count : 0,
                'coin' => $beanModel ? $beanModel->balance() : 0,
                'hammersNum' => $hammerNum,
                'win_record' => $dataBox
            ];
            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	public function encodeBox($box) {
	    $ret = [];
        foreach ($box->giftWeightList as list($gift, $weightLimit, $weight)) {
            if ($weight > 0) {
                $rate = round((float)$weight / $box->totalWeight,6) * 10000;
                $ret[] = [
                    'gift_name' => $gift->name,
                    'num' => $rate . '‱'
                ];
            }
        }
        return ArrayUtil::sort($ret, 'num', SORT_DESC);
    }

	/**
	 * 奖池说明
	 * @return [type] [description]
	 */
	public function boxGiftPool()
	{
        $pla = $this->request->header('PLATFORM');

		$silverBox = BoxSystem::getInstance()->findBox(BoxIds::$SILVER);
        $goldBox = BoxSystem::getInstance()->findBox(BoxIds::$GOLD);

        $result = [
            'gold_list' => $goldBox != null ? $this->encodeBox($goldBox) : [],
            'silver_list' => $silverBox != null ? $this->encodeBox($silverBox) : []
        ];

        $jieshao = "1、在“潘多拉魔盒”内购买头像框,用户每购买一个指定头像框（1天），将会获赠一枚许愿石，使用许愿石可参与“潘多拉魔盒”开箱活动；\r\n2.消耗1颗许愿石可进行1次开箱活动,百分百获得礼物奖励哦。奖励将放入背包中,可以随时赠送给其他用户;背包礼物上限100万；\r\n3、“宙斯宝箱”获得每次需要消耗100个豆,“墨提斯宝箱”获得每次需要消耗20个豆；\r\n4.用户参与宝箱将会获得限定礼物、幸运礼物等随机物品；\r\n5.抽取宝箱的过程中，会有概率掉落幸运礼物哦！每次抽奖都会增加进度，当幸运值集满时则会进入幸运时间，幸运时间状态下，获得幸运礼物的概率翻倍！开出特殊礼物后进度条清空，重新累计；\r\n6.福星榜统计每天获得的礼物总和，榜单的前三名将会获得特殊头像框奖励，人工发放有一定的延迟，请耐心等待哦；\r\n7.该玩法仅供娱乐，用户获得的物品不可反向兑换成现金或价值商品。禁止一切线下交易、收购等不正当行为。平台将对各类以盈利为目的的交易行为进行严厉打击。\r\n8. 本活动仅限18岁以上用户参加；";
        $shuoming = "1、“潘多拉魔盒”玩法仅供娱乐,任何人不得利用该玩法开展任何形式的违法违规活动；\r\n2、该玩法所获得的奖励道具仅可在平台内进行正常的娱乐性消耗,无法兑换成现金或其他有价值的财物，不可反向兑换成现金或价值商品，如有任何人就该道具违规开展线下交易行为,平台有权终止向违规账号提供服务,对违规账户中的虚拟产品进行清空,并对账号进行永久封停,因此造成的损失,由账号所属人自行承担；\r\n3、如平台认为任何人在活动过程中的任何行为超出合理范畴或出现数据异常的情况,平台将有权进行调查,并视情况采取相应的整改措施,包括不限于警告、禁言、强制下线、限制登录、封停账号、清空奖励、追究法律责任等；\r\n4、消费中请注意保管好账号、密码、短信验证是码等登录操作凭证,谨防上当受骗；";

        $plaData = explode(',', $pla);
		if ($plaData[0] == 'iOS') {
            $jieshao .= PHP_EOL . '9、本活动与苹果无关。';
            $shuoming .= PHP_EOL . '5、本活动与苹果无关。';
        }

        $result['jieshao'] = $jieshao;
        $result['shuoming'] = $shuoming;

		return rjson($result);
	}

	public function encodeBoxForIntroduce($box) {
	    $ret = [];
        foreach ($box->giftWeightList as list($gift, $weightLimit, $weight)) {
            $ret[] = [
                'gift_image' => CommonUtil::buildImageUrl($gift->image),
                'gift_name' => $gift->name,
                'gift_coin' => $gift->price != null ? $gift->price->count : 0
            ];
        }
        return ArrayUtil::sort($ret, 'gift_coin', SORT_DESC);
    }

	/**
	 * 奖池礼物
	 * @return [type] [description]
	 */
	public function boxIntroduce()
	{
        $silverBox = BoxSystem::getInstance()->findBox(BoxIds::$SILVER);
        $goldBox = BoxSystem::getInstance()->findBox(BoxIds::$GOLD);

		return rjson([
            'gold_pool' => $this->encodeBoxForIntroduce($goldBox),
            'silver_pool' => $this->encodeBoxForIntroduce($silverBox),
        ]);
	}
}