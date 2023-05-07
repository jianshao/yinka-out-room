<?php

namespace app\domain\open\model;

use app\domain\exceptions\FQException;

class OppoCallbackModel
{
    /**
     * @var string
     * @info 二选一，但优先用imei，然后考虑ouId，ouId就是oaid；
     */
    public $ouId;

    /**
     * @var int
     * @info 事件发生的时间戳(毫秒)，如1522221766623
     */
    public $timestamp;

    /**
     * @var string
     * @Info 包名，如com.xxx或者填，快应用id，如100137
     */
    public $pkg;

    /**
     * @var int
     * @info 转化数据类型：1、激活，2、注册，3、游戏付费，4、次留，5、应用内授信，6、应用内下单（电商），7、应用付费 8、自定义目标， 9、第3日留存 10、第4日留存， 11、第5日留存 12、第6日留存， 13、第7日留存，14、第8日留存，15、拉活，16、快应用付费
     */
    public $dataType;

    /**
     * @var int
     * @info 渠道：0、其他 1、OPPO，2、一加
     */
    public $channel;

    /**
     * @var int
     * @Info Imei原始加密类型1： md5加密 0： 无加密（默认为0）如果传oaid，type值填0
     */
    public $type;

    /**
     * @var int
     * @info 归因类型：1：广告主归因，0：OPPO归因（默认或者不填即为0），2：助攻归因
     */
    public $ascribeType;

    /**
     * @var int
     * 广告主回传转化数据时，附带已经归因好的广告id
     */
    public $adId;


    /**
     * @return array
     */
    public function modelToData()
    {
        return [
            "ouId" => $this->ouId,
            "timestamp" => $this->timestamp,
            "pkg" => $this->pkg,
            "dataType" => $this->dataType,
            "channel" => $this->channel,
            "type" => $this->type,
            "ascribeType" => $this->ascribeType,
            "adId" => $this->adId,
        ];
    }


    /**
     * @param $timestamp
     * @param $salt
     * @return string
     */
    public function loadSignature($timestamp, $salt)
    {
        if (empty($timestamp) || empty($salt)) {
            throw new FQException("OppoCallbackModel:loadSignature param error", 500);
        }
        $jsonStr = json_encode($this->modelToData());
        $string = sprintf("%s%s%s", $jsonStr, $timestamp, $salt);
        return strtolower(md5($string));
    }

}

