<?php


namespace app\domain\gift;


use app\domain\Config;
use app\utils\ArrayUtil;
use think\App;
use think\facade\Log;

class GiftSystem
{
    protected static $instance;
    // map<kindId, GiftKind>
    private $kindMap = [];
    private $panels = [];
    // map<panelName, GiftPanel>
    private $panelMap = [];
    //私聊礼物面板
    private $privateChatPanels = [];
    // map<panelName, GiftPanel>
    private $privateChatPanelMap = [];
    //游戏礼物列表
    private $gameGifts = [];
    private $walls = [];
    private $giftWall = [];
    // map<wallName, GiftWall>
    private $wallMap = [];

    private $giftCollectionMap = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GiftSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('GiftSystemLoaded count=%d panelCount=%d wallCount=%d',
                count(self::$instance->kindMap),
                count(self::$instance->panels),
                count(self::$instance->wallMap)));
        }
        return self::$instance;
    }

    /**
     * 根据kindId查找
     *
     * @param kindId: 类型ID
     *
     * @return GiftKind 找到返回GiftKind, 没找到返回null
     */
    public function findGiftKind($kindId) {
        return ArrayUtil::safeGet($this->kindMap, $kindId);
    }

    public function findGiftKindByName($giftName) {
        foreach ($this->kindMap as $value) {
            if ($value->name == $giftName) {
                return $value;
            }
        }
        return null;
    }

    /**
     * 获取所有礼物类型
     *
     * @return: map<kindId, PropKind>
     */
    public function getKindMap() {
        return $this->kindMap;
    }

    /**
     * 获取礼物面板
     *
     * @return array
     */
    public function getPanels() {
        return $this->panels;
    }

    /**
     * 获取私聊礼物面板
     *
     * @return array
     */
    public function getPrivateChatPanels() {
        return $this->privateChatPanels;
    }

    /**
     * 获取礼物墙
     *
     * @return array
     */
    public function getWalls() {
        return $this->walls;
    }

    /**
     * 获取新版本礼物墙
     */
    public function getGiftWalls() {
        return $this->giftWall;
    }

    public function getGiftCollectionMap() {
        return $this->giftCollectionMap;
    }

    public function findCollectionByName($name) {
        return ArrayUtil::safeGet($this->giftCollectionMap, $name);
    }

    /**
     * 获取游戏礼物ids
     *
     * @return array
     */
    public function getGameGifts() {
        return $this->gameGifts;
    }

    public function findWallByName($name) {
        return ArrayUtil::safeGet($this->wallMap, $name);
    }

    public function findPanelByName($name) {
        return ArrayUtil::safeGet($this->panelMap, $name);
    }

    public function findPrivateChatPanelByName($name) {
        return ArrayUtil::safeGet($this->privateChatPanelMap, $name);
    }

    private function loadFromJson() {
        $giftsConf = Config::getInstance()->getGiftConf();
        $source = App()->request->header('source');
        if ($source == 'chuchu')  {
            $panelsConfKey = 'qingqing_gift_panels';
        } else {
            $panelsConfKey = 'gift_panels';
        }
        $panelsConf = Config::getInstance()->getPanelsConf($panelsConfKey);
        $wallsConf = Config::getInstance()->getWallsConf();
        $giftWallConf = Config::getInstance()->getGiftWallConf();
        $giftCollectionsConf = Config::getInstance()->getGiftCollectionConf();

        $kindMap = [];
        foreach($giftsConf['gifts'] as $giftConf) {
            $kind = new GiftKind();
            $kind->decodeFromJson($giftConf);
            $kindMap[$kind->kindId] = $kind;
        }
        foreach ($kindMap as $_kindId => $giftKind) {
            $giftKind->initWhenLoaded($kindMap);
        }

        $panels = [];
        $panelMap = [];
        foreach($panelsConf['panels'] as $panelConf) {
            $panel = new GiftPanel();
            $panel->decodeFromJson($panelConf);
            $panel->initByGiftMap($kindMap);
            if (ArrayUtil::safeGet($panelMap, $panel->name) != null) {
                Log::warning(sprintf('GiftSystemLoadError name=%s err=%s',
                    $panel->name, 'DuplicatePanelName'));
            } else {
                $panels[] = $panel;
                $panelMap[$panel->name] = $panel;
            }
        }

        $privateChatPanels = [];
        $privateChatPanelMap = [];
        foreach($panelsConf['private_chat_panels'] as $panelConf) {
            $panel = new GiftPanel();
            $panel->decodeFromJson($panelConf);
            $panel->initByGiftMap($kindMap);
            if (ArrayUtil::safeGet($privateChatPanelMap, $panel->name) != null) {
                Log::warning(sprintf('GiftSystemLoadErrro name=%s err=%s',
                    $panel->name, 'DuplicateChatPanelName'));
            } else {
                $privateChatPanels[] = $panel;
                $privateChatPanelMap[$panel->name] = $panel;
            }
        }

        $gameGifts = [];
//        unset($panelsConf['gameGifts']);
        foreach($panelsConf['gameGifts'] as $giftId) {
            $gift = ArrayUtil::safeGet($kindMap, $giftId);
            if ($gift != null) {
                $gameGifts[] = $gift;
            }
        }

        $walls = [];
        $wallMap = [];
        foreach($wallsConf['walls'] as $wallConf) {
            $wall = new GiftWall();
            $wall->decodeFromJson($wallConf);
            $wall->initByGiftMap($kindMap);
            if (ArrayUtil::safeGet($wallMap, $wall->name) != null) {
                Log::warning(sprintf('GiftSystemLoadErrro name=%s err=%s',
                    $wall->name, 'DuplicateWallName'));
            } else {
                $walls[] = $wall;
                $wallMap[$wall->name] = $wall;
            }
        }
        $giftWall = [];
        foreach ($giftWallConf as $giftId) {
            $gift = ArrayUtil::safeGet($kindMap, $giftId);
            if ($gift != null) {
                $giftWall[] = $gift;
            }
        }

        $GiftCollectionMap = [];
        foreach ($giftCollectionsConf as $giftCollectionConf) {
            $giftCollection = new GiftCollections();
            $giftCollection->decodeFromJson($giftCollectionConf);
            $giftCollection->initByGiftMap($kindMap);
            if (ArrayUtil::safeGet($GiftCollectionMap, $giftCollection->displayName) != null) {
                Log::warning(sprintf('GiftSystemLoadErrro name=%s err=%s',
                    $giftCollection->displayName, 'DuplicateCollectionName'));
            } else {
                $GiftCollectionMap[$giftCollection->displayName] = $giftCollection;
            }
        }
        $this->kindMap = $kindMap;
        $this->panels = $panels;
        $this->panelMap = $panelMap;
        $this->privateChatPanels = $privateChatPanels;
        $this->privateChatPanelMap = $privateChatPanelMap;
        $this->gameGifts = $gameGifts;
        $this->walls = $walls;
        $this->giftWall = $giftWall;
        $this->wallMap = $wallMap;
        $this->giftCollectionMap = $GiftCollectionMap;
    }
}

GiftActionRegister::getInstance()->register(GiftActionBreakup::$TYPE_NAME, GiftActionBreakup::class);

