<?php

namespace app\domain\sms\service;

use app\domain\exceptions\FQException;
use app\domain\notice\model\PushTemplateModel;
use app\domain\sms\api\RongtongdaSmsApi;
use app\domain\user\model\MemberRecallUserModel;
use think\facade\Log;

//蓉通达短信
class RongtongdaService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RongtongdaService();
        }
        return self::$instance;
    }

    /**
     * @param $originData
     * @return string "" 过滤， 其他为有效
     */
    private function filterReport($originData)
    {
        $markpos = strpos($originData, ",") + 1;
        $data = substr($originData, $markpos);
        if ($data == "\"\"") {
            return "";
        }
        if (strpos($data, "frequency") === 0) {
            return "";
        }
        return $data;
    }

    //拉取短信的状态报告 并入库
    public function pullReport()
    {
//        拉取短信数据
        $clapi = new RongtongdaSmsApi();
        $data = $clapi->rptReport();
//        记录日志
        Log::DEBUG(sprintf("RongtongdaService:pullReport rptReport result=%d", $data));
//        filter
        $data = $this->filterReport($data);
        if ($data === "") {
            throw new FQException("RongtongdaService::pullReport empty", 500);
        }
        return $data;
    }


    /**
     *
     * 新用户文案
     * 24h：【{\$sign}】{\$nickname},你收到了新留言哦，快速查看rongqii.cn/{\$link} 退T
     * 3天：【{\$sign}】{\$nickname},小音一直都在呢，快速上线rongqii.cn/{\$link} 退T
     * 7天：【{\$sign}】{\$nickname},有很多话想对你说，快来rongqii.cn/{\$link} 退T
     * 14天：【{\$sign}】{\$nickname},还记得可可爱爱的我嘛~戳rongqii.cn/{\$link} 退T
     * 21天：【{\$sign}】{\$nickname},有个惊喜，快来看看呀~戳rongqii.cn/{\$link} 退T
     * 28天：【{\$sign}】{\$nickname},对你念念不忘，想一直在你身边~戳rongqii.cn/{\$link} 退T
     * 老用户文案
     * 第一次：【{\$sign}】{\$nickname},有个惊喜，快来看看rongqii.cn/{\$link} 退T
     * 第二次：【{\$sign}】{\$nickname},还记得可可爱爱的我嘛~rongqii.cn/{\$link} 退T
     * 第三次：【{\$sign}】{\$nickname},对你念念不忘，想一直在你身边~戳rongqii.cn/{\$link} 退T
     *
     */
    public function createPushTpl()
    {
        $msgList = [
            "【{\$sign}】{\$nickname},你收到了新留言哦，快速查看 {\$link} 退T",
            "【{\$sign}】{\$nickname},小音一直都在呢，快速上线 {\$link} 退T",
            "【{\$sign}】{\$nickname},有很多话想对你说，快来 {\$link} 退T",
            "【{\$sign}】{\$nickname},还记得可可爱爱的我嘛~戳 {\$link} 退T",
            "【{\$sign}】{\$nickname},有个惊喜，快来看看呀~戳 {\$link} 退T",
            "【{\$sign}】{\$nickname},对你念念不忘，想一直在你身边~戳 {\$link} 退T",
            "【{\$sign}】{\$nickname},有个惊喜，快来看看 {\$link} 退T",
            "【{\$sign}】{\$nickname},还记得可可爱爱的我嘛~ {\$link} 退T",
            "【{\$sign}】{\$nickname},对你念念不忘，想一直在你身边~戳 {\$link} 退T",
        ];

        $templateList = [
            "24h模版",
            "3day模版",
            "7day模版",
            "14day模版",
            "21day模版",
            "28day模版",
            "随机模版",
            "随机模版",
            "随机模版",
        ];
        $allModelList = [];
        $allinsertRe = [];
        $unixTime = time();
        $type = "rtdsms";
        foreach ($msgList as $k => $msg) {
            $itemModel = new PushTemplateModel;
            $itemModel->originId = sprintf("sms%s", ($k + 1));
            $itemModel->title = $msg;
            $itemModel->content = $msg;
            $itemModel->type = $type;
            $itemModel->createTime = $unixTime;
            $itemModel->updateTime = $unixTime;
            $itemModel->template_name = $templateList[$k];
            $allModelList[] = $itemModel;
//            $allinsertRe[] = PushTemplateModelDao::getInstance()->storeData($itemModel);
        }
        var_dump($allinsertRe);
        var_dump($allModelList);
    }




}