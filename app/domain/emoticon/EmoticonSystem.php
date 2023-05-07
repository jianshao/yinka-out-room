<?php

namespace app\domain\emoticon;

use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;

class EmoticonSystem
{
    protected static $instance;

    // map<emoticionId, Emoticon>
    private $emoticonMap = [];
    private $panelMap = [];
    private $panels = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new EmoticonSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('EmoticonSystemLoaded'));
        }
        return self::$instance;
    }

    public function getPanels() {
        return $this->panels;
    }

    public function findPanelByName($name) {
        return ArrayUtil::safeGet($this->panelMap, $name);
    }

    private function loadFromJson() {
        $emoticonsConf = Config::getInstance()->getEmoticonConf();
        $panelsConf = Config::getInstance()->getEmoticonPanelsConf();

        $emoticonMap = [];
        foreach (ArrayUtil::safeGet($emoticonsConf, 'emoticons', []) as $emoticonConf) {
            $emoticon = new Emoticon();
            $emoticon->decodeFromJson($emoticonConf);
            $emoticonMap[$emoticon->emoticonId] = $emoticon;
        }

        $panels = [];
        $panelMap = [];
        foreach($panelsConf['panels'] as $panelConf) {
            $panel = new EmoticonPanel();
            $panel->decodeFromJson($panelConf);
            $panel->initByEmoticonMap($emoticonMap);
            if (ArrayUtil::safeGet($panelMap, $panel->name) != null) {
                Log::warning(sprintf('EmoticonSystemLoadErrro name=%s err=%s',
                    $panel->name, 'DuplicatePanelName'));
            } else {
                $panels[] = $panel;
                $panelMap[$panel->name] = $panel;
            }
        }

        $this->emoticonMap = $emoticonMap;
        $this->panelMap = $panelMap;
        $this->panels = $panels;
    }
}
