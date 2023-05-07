<?php


namespace app\query\bi\service;

use app\domain\bi\BIConfig;
use app\query\bi\dao\BIUserAssetMemberExpendModelDao;
use app\domain\gift\GiftSystem;
use app\domain\mall\MallSystem;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\facade\Log;


class BiOrderService
{
    protected static $instance;
    protected $table_prefix = 'zb_user_asset_log_';
    protected $table_suffix = '';
    protected $eventIdMap = [];
    protected $actionEventIdMap = [];
    protected $filterActionEventIdMap = []; # 不需要展示在钱包的eventId

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiOrderService();
        }
        return self::$instance;
    }

    public function buildTableName($queryStartTime)
    {
        $this->table_suffix = date('Ym', $queryStartTime);
        return sprintf("%s%s", $this->table_prefix, $this->table_suffix);
    }

    public function getActivityDetailList($tableName, $page, $pageNum, $userId, $activityType, $queryStartTime, $queryEndTime)
    {
        //消费明细列表
        list($total, $Models) = BIUserAssetMemberExpendModelDao::getInstance()->getActivityDetailsModels($tableName, $page, $pageNum, $userId, $activityType, $queryStartTime, $queryEndTime);
        $ret = [];
        if (!empty($Models)) {
            foreach ($Models as $model) {
                $ret[] = $this->getActivityContentByType($model);
            }
        }
        return [$total, $ret];
    }

    public function getActivityContentByType($model)
    {
        $activityActionMap = [
            'gopher:1' => '打地鼠',
            'gopher:2' => '打地鼠',
            'gopher:3' => '打地鼠',
            'gopher:4' => '打地鼠',
            'gopher:99' => '打地鼠',
            'gopher:king' => '打国王地鼠',
            'default' => '活动'
        ];

        $key = $model->ext1 . ':' . $model->ext2;
        $content = ArrayUtil::safeGet($activityActionMap, $key, '活动') . ($model->change > 0 ? "获得奖励" : "消耗");
        return [
            'title' => $content,
            'timestamp' => $model->createTime,
            'number' => $model->change
        ];
    }


    public function newGetDetailList($tableName, $page, $pageNum, $userId, $assetType, $queryStartTime, $queryEndTime)
    {
        try {
            list($total, $Models) = $this->AccesstypeToDetails($tableName, $page, $pageNum, $userId, $assetType, $queryStartTime, $queryEndTime);
        } catch (\Exception $e) {
            Log::error(sprintf('BiOrderService::newGetDetailList $userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            return [0, []];
        }
        if (!is_array($Models) || count($Models) === 0) {
            return [0, []];
        }
        $ret = [];
        $this->initEventIdMap();
        $this->initActionEventIdMap();
        foreach ($Models as $model) {
            if (in_array($model->eventId, $this->filterActionEventIdMap)) {
                continue;
            }

            $itemData = $this->resolvingDetailTpl($model, $assetType);

            if ($itemData) {
                $ret[] = $itemData;
            }
        }
        return [$total, $ret];
    }


    private function AccesstypeToDetails($tableName, $page, $pageNum, $userId, $assetTypeStr, $queryStartTime, $queryEndTime)
    {
        switch ($assetTypeStr) {
            case "bean":
                $eventIds = [];
                $assetId = "bean";
                $type = 4;
                return BIUserAssetMemberExpendModelDao::getInstance()->newGetDetailsModels($tableName, $page, $pageNum, $userId, $type, $queryStartTime, $queryEndTime, $eventIds, $assetId);
            case "coin":
                $eventIds = [];
                $assetId = "coin";
                $type = 6;
                return BIUserAssetMemberExpendModelDao::getInstance()->newGetDetailsModels($tableName, $page, $pageNum, $userId, $type, $queryStartTime, $queryEndTime, $eventIds, $assetId);
            case "diamond":
                $eventIds = [];
                $assetId = "diamond";
                $type = 5;
                return BIUserAssetMemberExpendModelDao::getInstance()->newGetDetailsModels($tableName, $page, $pageNum, $userId, $type, $queryStartTime, $queryEndTime, $eventIds, $assetId);
            case "score":
                $eventIds = [];
                $assetId = 'game:score';
                $type = 2;
                return BIUserAssetMemberExpendModelDao::getInstance()->newGetDetailsModels($tableName, $page, $pageNum, $userId, $type, $queryStartTime, $queryEndTime, $eventIds, $assetId);
            case "gift":
                $eventIds = [];
                $assetId = '';
                $type = 3;
                return BIUserAssetMemberExpendModelDao::getInstance()->newGetDetailsModels($tableName, $page, $pageNum, $userId, $type, $queryStartTime, $queryEndTime, $eventIds, $assetId);
        }
        return [null, null];
    }

    private function initActionEventIdMap()
    {
        $this->actionEventIdMap = [
            BIConfig::$CHARGE_EVENTID => 'getChargeContent',
            BIConfig::$SEND_GIFT_EVENTID => 'getSendGIftContent',
            BIConfig::$RECEIVE_GIFT_EVENTID => 'getReceiveGiftContent',
            BIConfig::$DIAMOND_EXCHANGE_EVENTID => 'getDiamondExchangeContent',
            BIConfig::$BUY_EVENTID => 'getBuyGoodsContent',
            BIConfig::$TASK_EVENTID => 'getTaskRewardContent',
            BIConfig::$ACTIVITY_EVENTID => 'getActivityContent',
            BIConfig::$REDPACKETS_EVENTID => 'getSendRedPacketsContent',
            BIConfig::$REDPACKETS_GRAB_EVENTID => 'getReceiveRedPacketContent',
            BIConfig::$REPLACE_CHARGE_EVENTID => 'getReplaceChargeContent',
            BIConfig::$WITHDRAW_PRETAKEOFF_EVENTID => 'getWithdrawContent',
            BIConfig::$WITHDRAW_SUCCESS_EVENTID => 'getWithdrawSuccessContent',
            BIConfig::$WITHDRAW_REFUSE_EVENTID => 'getWithdrawRefuseContent',
            BIConfig::$OPEN_GIFT => 'getActivityContent',
            BIConfig::$MALL_SEND_EVENTID => "getActivityContent",
            BIConfig::$MALL_RECEIVE_EVENTID => "getActivityContent",
            BIConfig::$GM_ADJUST => 'getGmAdjustContent',
            BIConfig::$COIN_EXCHANGE_EVENTID => 'getCoinExchangeContent',
            BIConfig::$PROP_ACTION_EVENTID => 'getPropActionContent',
            BIConfig::$GIFT_ACTION_EVENTID => 'getGiftActionContent'
        ];

        $this->filterActionEventIdMap = [
            BIConfig::$SYSTEM_SEND_GIFT_EVENTID
        ];
    }

    private function getGiftActionContent($model)
    {
        $goods = GiftSystem::getInstance()->findGiftKind((int)$model->ext1);
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => '使用礼物'],
                    ['type' => 'img', 'content' => CommonUtil::buildImageUrl($goods->image)],
                    ['type' => 'txt', 'content' => $goods->name . ' x' . $model->ext2, 'color' => "#FFA927"],
                ],
                'timestamp' => $model->createTime,
                'number' => $model->change
            ];
    }

    public function getPropActionContent($model)
    {
        $goods = GiftSystem::getInstance()->findGiftKind((int)$model->ext1);
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => '使用道具'],
                    ['type' => 'img', 'content' => CommonUtil::buildImageUrl($goods->image)],
                    ['type' => 'txt', 'content' => $goods->name . ' x' . $model->ext2, 'color' => "#FFA927"],
                ],
                'timestamp' => $model->createTime,
                'number' => $model->change
            ];
    }

    private function initEventIdMap()
    {
        $this->eventIdMap = [
            BIConfig::$BEAN_TYPE => 'getBeanContent',
            BIConfig::$COIN_TYPE => 'getCoinContent',
            BIConfig::$DIAMOND_TYPE => 'getDiamondContent',
            BIConfig::$BANK_TYPE => 'getBankContent',
            BIConfig::$GIFT_TYPE => 'getGiftContent',
        ];
    }

    public function getDiamondContent($model)
    {
        $result = $this->resolvingActionDetailTpl($model);
        if (empty($result)) {
            return $this->getDiamondExchangeContent($model);
        }
        return $result;
    }

    public function getGiftContent($model)
    {
        $result = $this->resolvingActionDetailTpl($model);
        if (empty($result)) {
            return $this->getActivityContent($model);
        }
        return $result;
    }

    /**
     * Notes:解析明细记录
     * User: echo
     * Date: 2021/8/3
     * Time: 8:56 下午
     * @param $model
     * @return mixed
     */
    public function resolvingDetailTpl($model, $assetType = "")
    {
        if (!isset($this->eventIdMap[$model->type])) {
            return [];
        }
        $method = $this->eventIdMap[$model->type];
        $result = $this->$method($model);
        $result['assetType'] = $assetType;
        if (isset($result['number'])) {
            $result['number'] = $this->getReturnNumber($result['number']);
        }
        return $result;
    }

    public function resolvingActionDetailTpl($model)
    {
        if (!isset($this->actionEventIdMap[$model->eventId])) {
            return [];
        }
        $method = $this->actionEventIdMap[$model->eventId];
        return $this->$method($model);
    }

    public function getChargeContent($model): array
    {
        $beanChargeMap = [
            '1' => '支付宝app支付',
            '2' => '支付宝网页支付',
            '3' => '微信app支付',
            '4' => '公众号支付',
            '13' => '微信H5支付',
            '15' => '微信扫码支付',
            '16' => '支付宝H5支付',
            '22' => '苹果支付',
            'default' => '支付'
        ];
        $key = $model->ext3;
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => ArrayUtil::safeGet($beanChargeMap, $key, '支付')]
                ],
                'timestamp' => $model->createTime,
                'number' => (string)$model->change
            ];
    }


    public function getSendGIftContent($model, $side = true): array
    {
        $goods = GiftSystem::getInstance()->findGiftKind((int)$model->ext2);

        $goodsText = ['type' => 'txt', 'content' => $goods->name . ' x' . $model->ext3, 'color' => "#FFA927"];
        $imageTxt = [];
        if ($side === false) {
            $imageTxt = ['type' => 'img', 'content' => CommonUtil::buildImageUrl($goods->image)];
        }

        $titleResult = [
            ['type' => 'txt', 'content' => '送给' . $this->filterNickname($model->toNickname)],
            $imageTxt,
            $goodsText,
        ];
        $titleResult = array_values(array_filter($titleResult));
        return [
            'title' => $titleResult,
            'timestamp' => $model->createTime,
            'number' => (string)$model->change,
            'gift_image' => CommonUtil::buildImageUrl($goods->image)
        ];
    }

    public function getReceiveGiftContent($model): array
    {
        $goods = GiftSystem::getInstance()->findGiftKind((int)$model->ext2);
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => '收到' . $this->filterNickname($model->toNickname) . '赠送的'],
                    ['type' => 'img', 'content' => CommonUtil::buildImageUrl($goods->image)],
                    ['type' => 'txt', 'content' => $goods->name . ' x' . $model->ext3, 'color' => "#FFA927"],
                ],
                'timestamp' => $model->createTime,
                'number' => (string)filter_money($model->change / config('config.khd_scale'))
            ];
    }

    public function getDiamondExchangeContent($model): array
    {
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => '钻石兑换咖啡豆'],
                ],
                'timestamp' => $model->createTime,
                'number' => $model->type == BIConfig::$BEAN_TYPE ? (string)$model->change : (string)filter_money($model->change / config('config.khd_scale'))
            ];
    }


    public function getBuyGoodsContent($model): array
    {
        $buyGoodsActionMap = [
            'gashapon' => '扭蛋机消耗',
            'bean:attire' => '购买装扮',
            'coin:coin_exchange' => '购买',
            'game:box2' => '购买积分',
            'game:game' => '购买积分',
            'newer' => '新手任务',
            'daily' => '任务',
            'weekCheckin' => '签到',
            'coin_lottery' => '金币抽好礼',
            'game:turntable' => '购买积分',
            'game' => '购买积分',
            'default' => '购买商品',
        ];
        $key = $model->ext1 . ':' . $model->ext4;
        $goodsText = "";
        if ((int)$model->ext2 > 0) {
            $goods = MallSystem::getInstance()->findGoods((int)$model->ext2);
            $goodsText = ['type' => 'txt', 'content' => $goods->name . ' x' . $model->ext3, 'color' => "#FFA927"];
        }
        return [
            'title' => [
                ['type' => 'txt', 'content' => ArrayUtil::safeGet($buyGoodsActionMap, $key, '支付')],
                $goodsText,
            ],
            'timestamp' => $model->createTime,
            'number' => (string)$model->change
        ];
    }

    public function getCoinContent($model)
    {
        if ($model->eventId === BIConfig::$TASK_EVENTID) {
            $taskActionMap = [
                'daily' => '每日任务',
                'weekCheckin' => '每日签到',
                'activeBoxDay' => '日活跃度任务',
                'activeBoxWeek' => '周活跃度任务',
                'newer' => '新手任务',
                'default' => '参与任务'
            ];
            if ($model->change > 0) {
                $charge = ['type' => 'txt', 'content' => "获得金币"];
            } else {
                $charge = ['type' => 'txt', 'content' => "消耗金币"];
            }

            $result = [
                'title' => [
                    ['type' => 'txt', 'content' => '参与' . ArrayUtil::safeGet($taskActionMap, $model->ext1, '任务')],
                    $charge,
                ],
                'timestamp' => $model->createTime,
                'number' => (string)$model->change,
            ];
        } else {
            $result = $this->resolvingActionDetailTpl($model);
            if (empty($result)) {
                $result = $this->getDefaultContent($model);
            }
        }
        if (isset($result['number'])) {
            $result['number'] = str_replace(".00", "", $result['number']);
        }
        return $result;
    }

    private function getDefaultContent($model)
    {
        if ($model->change > 0) {
            $charge = ['type' => 'txt', 'content' => "获得"];
        } else {
            $charge = ['type' => 'txt', 'content' => "消耗"];
        }
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => '活动'],
                    $charge,
                ],
                'timestamp' => $model->createTime,
                'number' => $model->type == BIConfig::$BEAN_TYPE ? (string)$model->change : (string)filter_money($model->change / config('config.khd_scale'))
            ];
    }

    public function getTaskRewardContent($model): array
    {
        $taskActionMap = [
            'daily' => '每日任务',
            'weekCheckin' => '每日签到',
            'activeBoxDay' => '日活跃度任务',
            'activeBoxWeek' => '周活跃度任务',
            'newer' => '新手任务',
            'default' => '参与任务'
        ];
        list($goodsName, $goodsImage) = $this->initAssetData($model);
        if ($model->change > 0) {
            $charge = ['type' => 'txt', 'content' => "获得"];
        } else {
            $charge = ['type' => 'txt', 'content' => "消耗"];
        }

        return [
            'title' => [
                ['type' => 'txt', 'content' => '参与' . ArrayUtil::safeGet($taskActionMap, $model->ext1, '任务')],
                $charge,
            ],
            'timestamp' => $model->createTime,
            'number' => (string)$model->change,
            'gift_image' => CommonUtil::buildImageUrl($goodsImage)
        ];
    }

    public function getActivityContent($model): array
    {
        $activityActionMap = [
            'coin_lottery' => '金币抽好礼',
            'giftReturn' => '礼物返利',
            'gashapon' => '扭蛋机',
            'duobao3:1' => '抢占幸运位1号桌购买座位*1',
            'duobao3:2' => '抢占幸运位2号桌购买座位*1',
            'duobao3:3' => '抢占幸运位3号桌购买座位*1',
            'box' => '潘多拉魔盒活动',
            'box2' => '潘多拉魔盒活动',
            'taojin:1' => '淘金之旅沙之城',
            'taojin:2' => '淘金之旅海之城',
            'taojin:3' => '淘金之旅雪之城',
            'car' => '赛车',
            'gopher' => '打地鼠',
            'gopher:king' => '国王地鼠',
            'newer' => '新手任务',
            'daily' => '任务',
            'weekCheckin' => '签到',
            'activeBoxDay' => '日活跃任务',
            'activeBoxWeek' => '周活跃度任务',
            'christmas' => '圣诞节许愿树',
            'wabao' => '古墓探险',
            'default' => '活动',
        ];
        $key = $model->ext1 . ':' . $model->ext2;
        list($goodsName, $goodsImage) = $this->initAssetData($model);
        $goodsText = ['type' => 'txt', 'content' => $goodsName . ' x' . abs((int)$model->change), 'color' => "#FFA927"];
        if ($model->change > 0) {
            $charge = ['type' => 'txt', 'content' => "获得"];
            if($model->ext1 == 'christmas' && $model->ext2 == 'backfee'){
                $charge = ['type' => 'txt', 'content' => "退还"];
            }
        } else {
            $charge = ['type' => 'txt', 'content' => "消耗"];
        }
        $huodongName = ArrayUtil::safeGet($activityActionMap, $key, null);
        if ($huodongName === null) {
            $huodongName = ArrayUtil::safeGet($activityActionMap, $model->ext1, "活动");
        }
        $titleResult = [
            ['type' => 'txt', 'content' => sprintf("参与%s", $huodongName)],
            $charge,
            $goodsText,
        ];
        $titleResult = array_values(array_filter($titleResult));
        return [
            'title' => $titleResult,
            'timestamp' => $model->createTime,
            'number' => (string)$model->change,
            'gift_image' => CommonUtil::buildImageUrl($goodsImage)
        ];
    }


    private function initAssetData($model)
    {
        switch ($model->type) {
            case BIConfig::$BANK_TYPE:
                switch ($model->assetId) {
                    case "game:candy":
                        $goodsName = "水球";
                        $goodsImage = "/20211025/45da97c12064b733be2e15dec381453c.png";
                        break;
                    case "game:score":
                        $goodsName = "积分";
                        $goodsImage = "resource/images/jifen2.png";
                        break;
                    case "chip:silver":
                        $goodsName = "银碎片";
                        $goodsImage = "";
                        break;
                    case "chip:gold":
                        $goodsName = "金碎片";
                        $goodsImage = "";
                        break;
                }
                break;
            case BIConfig::$GIFT_TYPE:
                $goods = GiftSystem::getInstance()->findGiftKind((int)$model->assetId);
                $goodsName = $goods->name;
                $goodsImage = $goods->image;
                break;
            case BIConfig::$BEAN_TYPE:
                $goodsName = "咖啡豆";
                $goodsImage = "/image/md.png";
                break;
            case BIConfig::$DIAMOND_TYPE:
                $goodsName = "钻石";
                $goodsImage = "";
                break;
            case BIConfig::$COIN_TYPE:
                $goodsName = "金币";
                $goodsImage = "/gold.png";
                break;
            default:
                $goodsName = "";
                $goodsImage = "";
                break;
        }
        return [$goodsName, $goodsImage];
    }

    public function getSendRedPacketsContent($model): array
    {
        return [
            'title' => [
                ['type' => 'txt', 'content' => '发红包']
            ],
            'timestamp' => $model->createTime,
            'number' => (string)$model->change
        ];
    }

    public function getReceiveRedPacketContent($model): array
    {
        return [
            'title' => [
                ['type' => 'txt', 'content' => '抢红包']
            ],
            'timestamp' => $model->createTime,
            'number' => (string)$model->change
        ];
    }

    public function getWithdrawContent($model): array
    {
        return [
            'title' => [
                ['type' => 'txt', 'content' => '提现']
            ],
            'timestamp' => $model->createTime,
            'number' => BIConfig::$DIAMOND_TYPE == $model->type ? (string)filter_money($model->change / config('config.khd_scale')) : (string)$model->change
        ];
    }

    public function getReplaceChargeContent($model): array
    {
        if (BIConfig::$DIAMOND_TYPE == $model->type) {
            return [
                'title' => [
                    ['type' => 'txt', 'content' => '赠送' . $this->filterNickname($model->toNickname) . abs((int)$model->change) / config('config.scale') . '豆']
                ],
                'timestamp' => $model->createTime,
                'number' => BIConfig::$DIAMOND_TYPE == $model->type ? (string)filter_money($model->change / config('config.khd_scale')) : (string)$model->change
            ];
        } else {
            return [
                'title' => [
                    ['type' => 'txt', 'content' => '收到' . $this->filterNickname($model->toNickname) . '赠送的' . (int)$model->change . '豆'],
                ],
                'timestamp' => $model->createTime,
                'number' => BIConfig::$DIAMOND_TYPE == $model->type ? (string)filter_money($model->change / config('config.khd_scale')) : (string)$model->change
            ];
        }

    }

    public function getWithdrawSuccessContent($model): array
    {
        return [
            'title' => [
                ['type' => 'txt', 'content' => '提现成功']
            ],
            'timestamp' => $model->createTime,
            'number' => (string)filter_money((-$model->change) / config('config.khd_scale'))
        ];
    }

    public function getWithdrawRefuseContent($model): array
    {
        return [
            'title' => [
                ['type' => 'txt', 'content' => '提现拒绝']
            ],
            'timestamp' => $model->createTime,
            'number' => (string)filter_money($model->change / config('config.khd_scale'))
        ];
    }

    public function getGmAdjustContent($model): array
    {
        return [
            'title' => [
                ['type' => 'txt', 'content' => '运营调整']
            ],
            'timestamp' => $model->createTime,
            'number' => BIConfig::$DIAMOND_TYPE == $model->type ? (string)filter_money($model->change / config('config.khd_scale')) : (string)$model->change
        ];
    }

    public function getBankContent($model): array
    {
        if ($model->eventId === BIConfig::$ACTIVITY_EVENTID) {
            $result = $this->getBankScoreContent($model);
        } else {
            $result = $this->resolvingActionDetailTpl($model);
            if (empty($result)) {
                $result = $this->getBankScoreContent($model);
            }
        }
        if (isset($result['number'])) {
            $result['number'] = str_replace(".00", "", $result['number']);
        }
        return $result;
    }

    private function getBankScoreContent($model)
    {
        $activityActionMap = [
            'gopher' => '打地鼠',
            'gopher:king' => '消灭地鼠王',
            'box1' => '潘多拉魔盒',
            'box2' => '潘多拉魔盒',
            'turntable' => '幸运大转盘',
            'taojin' => '淘金之旅',
            'wabao' => '古墓探险',
            'default' => '活动',
        ];

        $key = $model->ext1 . ':' . $model->ext2;
        $huodongName = ArrayUtil::safeGet($activityActionMap, $key, null);
        if ($huodongName === null) {
            $huodongName = ArrayUtil::safeGet($activityActionMap, $model->ext1, "活动");
        }

        if ($model->change > 0) {
            $charge = ['type' => 'txt', 'content' => "获得积分"];
        } else {
            $charge = ['type' => 'txt', 'content' => "消耗积分"];
        }
        return [
            'title' => [
                ['type' => 'txt', 'content' => $huodongName],
                $charge,
            ],
            'timestamp' => $model->createTime,
            'number' => $model->change
        ];
    }

    public function getReturnNumber($change)
    {
        if ($change > 0) {
            return sprintf("+%s", $change);
        }
        return $change;
    }


    /**
     * @param $nickname
     * @return string
     */
    private function filterNickname($nickname)
    {
        $len = mb_strlen($nickname, 'gb2312');
        if ($len <= 10) {
            return $nickname;
        }
        return sprintf("%s...", mb_substr($nickname, 0, 5));
    }

    public function getBeanContent($model)
    {
        if ($model->eventId === BIConfig::$SEND_GIFT_EVENTID) {
            $result = $this->getSendGIftContent($model, false);
        } else {
            $result = $this->resolvingActionDetailTpl($model);
            if (empty($result)) {
                $result = $this->getCoinExchangeContent($model);
            }
        }
        if (isset($result['number'])) {
            $result['number'] = str_replace(".00", "", $result['number']);
        }
        return $result;
    }

    private function getCoinExchangeContent($model)
    {
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => '咖啡豆兑换金币'],
                ],
                'timestamp' => $model->createTime,
                'number' => $model->change
            ];
    }
}