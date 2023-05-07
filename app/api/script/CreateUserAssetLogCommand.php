<?php
/**
 * 定时任务
 * 动态创建bi流水表
 */

namespace app\api\script;

use app\core\mysql\Sharding;
use think\console\Command;
use think\facade\Db;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class CreateUserAssetLogCommand extends Command
{


    protected function configure()
    {
        $this->setName('CreateUserAssetLogCommand')->setDescription('CreateUserAssetLogCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $tablePrefix = 'zb_user_asset_log_';
        $nextMonth = date('Ym',strtotime('+1 month'));
        $nextTableName = sprintf("%s%s", $tablePrefix, $nextMonth);
        $this->createTable1($nextTableName, 'userMaster');

        $tablePrefix = 'zb_check_im_message_';
        $nextTableName = sprintf("%s%s", $tablePrefix, $nextMonth);
        $this->createTable2($nextTableName, 'commonMaster');
    }

    private function createTable1($tableName, $serviceName) {
        $sql = sprintf("
            CREATE TABLE IF NOT EXISTS `%s` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '用户ID',
  `touid` int(11) NOT NULL DEFAULT '0' COMMENT '接收用户ID',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房间ID',
  `type` int(11) NOT NULL COMMENT '资产类型',
  `asset_id` varchar(60) NOT NULL DEFAULT '0' COMMENT '资产ID',
  `event_id` int(11) NOT NULL COMMENT '事件ID',
  `change_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '资产变化量',
  `change_before` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '资产变化前',
  `change_after` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '资产变化后',
  `success_time` bigint(20) NOT NULL DEFAULT '0' COMMENT '资产变化时间',
  `created_time` bigint(20) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext_1` varchar(32) NOT NULL DEFAULT '' COMMENT '参数1',
  `ext_2` varchar(32) NOT NULL DEFAULT '' COMMENT '参数2',
  `ext_3` varchar(32) NOT NULL DEFAULT '' COMMENT '参数3',
  `ext_4` varchar(32) NOT NULL DEFAULT '' COMMENT '参数4',
  `ext_5` varchar(32) NOT NULL DEFAULT '' COMMENT '参数5',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_uid_assetid_type_successtime` (`uid`,`asset_id`,`type`,`success_time`),
  KEY `idx_successtime` (`success_time`),
  KEY `idx_room_id` (`room_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户资产变化表';
        ", $tableName);
        $dbMap = Sharding::getInstance()->getDbMap($serviceName);
        if (!empty($dbMap)) {
            foreach ($dbMap as $dbName) {
                Db::connect($dbName)->query($sql);
                Log::info(sprintf("date:%s  dbName:%s createTable:%s success", date('Y-m-d H:i:s'), $dbName, $tableName));
            }
        }
    }

    public function createTable2($tableName, $serviceName) {
        $sql = sprintf("
            CREATE TABLE IF NOT EXISTS `%s` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `from_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发送者id',
  `to_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接收者id',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '消息类型 0文本消息 1图片消息  2语音消息 3视频消息 4位置消息 5文件消息 6提示消息 7自定义消息',
  `message` text COLLATE utf8_bin COMMENT '消息内容',
  `check_response` text COLLATE utf8_bin COMMENT '检测结果',
  `api_response` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '接口返回信息',
  `status` tinyint(1) DEFAULT NULL COMMENT '消息状态 1发送成功 2检测失败 3信息限制 4撤回',
  `created_time` int(20) DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(20) DEFAULT '0' COMMENT '更改时间',
  `ext_1` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '预留字段1',
  `ext_2` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '预留字段2',
  `ext_3` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '预留字段3',
  PRIMARY KEY (`id`),
  KEY `idx_createdtime` (`created_time`) USING BTREE,
  KEY `idx_fromuid` (`from_uid`) USING BTREE,
  KEY `idx_touid` (`to_uid`) USING BTREE,
  KEY `idx_type` (`type`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IM消息';
        ", $tableName);

        $dbMap = Sharding::getInstance()->getDbMap($serviceName);
        if (!empty($dbMap)) {
            foreach ($dbMap as $dbName) {
                Db::connect($dbName)->query($sql);
                Log::info(sprintf("date:%s  dbName:%s createTable:%s success", date('Y-m-d H:i:s'), $dbName, $tableName));
            }
        }
    }

   




}