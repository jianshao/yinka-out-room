<?php


namespace app\domain\led;


use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;

class LedSystem
{
    private $ledMap = [];
    private $ledJumpMap = [];

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LedSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    public function getLedJumpConf($type)
    {
        return ArrayUtil::safeGet($this->ledJumpMap, $type);
    }

    /**
     * @return LedKind;
     */
    public function getLedKind($ledType)
    {
        $ledKind = ArrayUtil::safeGet($this->ledMap, $ledType);
        if (empty($ledKind)){
            $ledKind = ArrayUtil::safeGet($this->ledMap, LedConst::$COMMON);
        }
        return $ledKind;
    }

    protected function loadFromJson()
    {
        $ledCong = Config::getInstance()->getLedConf();
        $ledMap = [];
        foreach ($ledCong as $ledType => $conf) {
            if (!in_array($ledType, LedConst::$ledTypeMap)){
                Log::warning(sprintf('LedSystemErrro ledType=%s err=%s',
                    $ledType, 'notType'));
            }
            $ledKind = new LedKind();
            $ledKind->decodeFromJson($ledType, $conf);
            if (ArrayUtil::safeGet($ledMap, $ledType) != null) {
                Log::warning(sprintf('LedSystemErrro ledType=%s err=%s',
                    $ledType, 'DuplicateLevel'));
            } else {
                $ledMap[$ledType] = $ledKind;
            }
        }
        $this->ledMap = $ledMap;

        $ledJumpCong = Config::getInstance()->getLedJumpConf();
        $ledJumpMap = [];
        foreach ($ledJumpCong as $type => $conf) {
            if (ArrayUtil::safeGet($ledJumpMap, $type) != null || empty($conf)) {
                Log::warning(sprintf('LedSystemErrro type=%s err=%s',
                    $type, 'DuplicateLevel'));
            } else {
                $ledJumpMap[$type] = $conf;
            }
        }
        $this->ledJumpMap = $ledJumpMap;
    }
}