<?php
/**
 * 定时任务
 * 每日福星榜送头像框
 */

namespace app\api\script;

use app\admin\model\LanguageroomModel;
use app\common\SmsMessageCommon;
use app\domain\user\dao\UserModelDao;
use think\console\Command;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

class VipNoticeCommand extends Command
{
    protected function configure()
    {
        $this->setName('VipNoticeCommand')->setDescription('VipNoticeCommand');
    }

    /**
     *执行用户会员到期卸载会员即将过期发送短信
     */
    protected function execute(Input $input, Output $output)
    {
        $svip_send_day = [30, 7, 3, 1];
        $vip_send_day = [7, 3, 1];
        $time = time();
        $allUser = UserModelDao::getInstance()->field('id,is_vip,vip_exp,svip_exp')->where(['is_vip', '<>', 0])->select()->toArray();
        foreach($allUser as $k => $v) {
            if($v['is_vip'] == 1 && in_array(floor(($v['vip_exp'] - $time)/86400), $vip_send_day)) {
                $this->sendMsg($v['username'], 'VIP',floor(($v['vip_exp'] - $time)/86400));
            } elseif($v['is_vip'] == 2 && in_array(floor(($v['svip_exp'] - $time)/86400), $svip_send_day)) {
                $this->sendMsg($v['username'], 'SVIP',floor(($v['svip_exp'] - $time)/86400));
            }
        }
    }

    /**
     * 发送提醒短信
     * @param $phone
     * @param $vip_type
     * @param $day_num
     */
    private function sendMsg($phone, $vip_type, $day_num) {
        $params = ['name' => $vip_type, 'num' => $day_num];
        SmsMessageCommon::getInstance()->sendMessage('ali_sms_vip_notice_templateCode', $phone, $params);
    }

}