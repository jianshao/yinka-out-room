<?php


namespace app\domain\appdata;



use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\open\dao\HuaweiChannelModelDao;
use app\domain\open\model\HuaweiChannelModel;
use app\domain\open\service\HuaweiGalleryService;
use app\event\AndroidActivateEvent;
use app\form\ClientInfo;
use app\utils\Error;
use think\facade\Db;

class ChannelDataService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChannelDataService();
        }
        return self::$instance;
    }

    /**
     * @param $data
     * @return false
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function analysisUserSource($data)
    {
        if ($data['channel'] == 'HuaWei') {
            $parseRes = json_decode($data['data'], true);
            $model = new HuaweiChannelModel;
            $model->userId = $data['user_id'];
            $model->deviceId = $data['device_id'] ?? "";
            $model->taskid = $parseRes['taskid'] ?? "";
            $model->channel = $parseRes['channel'] ?? "";
            $model->ctime = $data['ctime'] ?? time();
            $model->oaid = $data['oaid'] ?? "";
            $model->callback = $parseRes['callback'] ?? "";
            $model->rtaid = $parseRes['RTAID'] ?? "";
            $model->subTaskId = $parseRes['subTaskId'] ?? "";
            HuaweiChannelModelDao::getInstance()->insertOrUpdateMul($model);

//            store华为渠道数据
            HuaweiGalleryService::getInstance()->storeHuaweiReport($model->oaid, $model->taskid, $model->subTaskId, $model->rtaid, $model->channel, $model->callback);
//            上报注册成功
            HuaweiGalleryService::getInstance()->report($model->oaid, "7", $model->userId);
        } else {
            if (isset($data['user_id']) && empty($data['user_id'])) {
                return false;
            }
            $parseRes = json_decode($data['data'], true);
            if (isset($parseRes['attribution'])) {
                if (!$this->isAttribution($parseRes['attribution'])) {
                    return false;
                }
            }

            $params = current($parseRes);

            if (is_array($params)) {
                if (isset($params['iad-attribution'])) {
                    if (!$this->isAttribution($params['iad-attribution'])) {
                        return false;
                    }
                }
                $insertdataios = [
                    "user_id" => $data['user_id'],
                    "device_id" => $data['device_id'] ?? '',
                    "iad_adgroup_id" => $params['iad-adgroup-id'] ?? '',
                    "iad_campaign_id" => $params['iad-campaign-id'] ?? '',
                    "iad_keyword_id" => $params['iad-keyword-id'] ?? '',
                    "iad_adgroup_name" => $params['iad-adgroup-name'] ?? '',
                    "iad_campaign_name" => $params['iad-campaign-name'] ?? '',
                    "iad_keyword" => $params['iad-keyword'] ?? '',
                    "ctime" => $data['ctime'] ?? time(),
                ];
            } else {
                $insertdataios = [
                    "user_id" => $data['user_id'],
                    "device_id" => $data['device_id'] ?? '',
                    "iad_adgroup_id" => $parseRes['adGroupId'] ?? '',
                    "iad_campaign_id" => $parseRes['campaignId'] ?? '',
                    "iad_keyword_id" => $parseRes['keywordId'] ?? '',
                    "iad_adgroup_name" => '',
                    "iad_campaign_name" => '',
                    "iad_keyword" => '',
                    "ctime" => $data['ctime'] ?? time(),
                ];
            }

            if (!empty($insertdataios)) {
                $dnName = Sharding::getInstance()->getDbName('biMaster', 0);
                Db::connect($dnName)->table('bi_channel_appstore')->extra("IGNORE")->insert($insertdataios);
            }
        }
    }


    public function isAttribution($attribution)
    {
        if (is_string($attribution)) {
            if (strtolower($attribution) == 'false') {
                return false;
            }
        } elseif (is_bool($attribution)) {
            if (!$attribution) {
                return false;
            }
        }
        return true;
    }


    /**
     * @param $channel
     * @param $oaid
     * @param $channelData
     * @param ClientInfo $clientInfo
     * @return int|null
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function androidActivate($channel, $oaid, $channelData, ClientInfo $clientInfo)
    {
        $result = null;
        if ($channel === 'HuaWei') {
            $result = $this->storeHuaweiReport($oaid, $channelData);
        }
        event(new AndroidActivateEvent(time(), $clientInfo));
        return $result;
    }


    /**
     * @param $oaid
     * @param $channelData
     * @return int
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function storeHuaweiReport($oaid, $channelData)
    {
        $parseRes = json_decode($channelData, true);
        $taskid = $parseRes["taskid"] ?? "";
        $subTaskId = $parseRes["subTaskId"] ?? "";
        $rtaid = $parseRes["rtaid"] ?? "";
        $channel = $parseRes["channel"] ?? "";
        $callback = $parseRes["callback"] ?? "";
        if (empty($oaid) || empty($callback)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        return HuaweiGalleryService::getInstance()->storeHuaweiReport($oaid, $taskid, $subTaskId, $rtaid, $channel, $callback);
    }


}