<?php

namespace app\api\script;

use app\common\YunxinCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\user\dao\AccountMapDao;
use app\domain\user\dao\UserInfoMapDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\UserModel;
use app\domain\user\service\UserService;
use app\domain\user\UserRepository;
use app\event\UserCancelEvent;
use app\service\IdService;
use app\service\IdTestService;
use app\utils\CommonUtil;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);

class UserMigrateCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\UserMigrateCommand')
            ->setDescription('user cancellation check');
    }

    protected $id = 0;

    protected $offset = 2000;

    protected $lastId = 0;

    protected $all = 0;

    protected function execute(Input $input, Output $output)
    {
//        $file = fopen(app()->getBasePath() . 'migrate.txt', 'a');
        while (true) {
            $this->lastId = $this->id + $this->offset;
            $where = [];
            $where[] = ['username', '>', 0];
            $where[] = ['id', '>=', $this->id];
            $where[] = ['id', '<', $this->lastId];
            $data = Db::connect('abConnection')->table('zb_member')->where($where)->select();
            $count = count($data);
            echo "id:" . $this->id . " lastId:" . $this->lastId . " count:" . $count . PHP_EOL;
            $this->all = $this->all + $count;
            if ($this->id >= 1800000 && $count <= 0) {
                break;
            }
            //处理迁移
            foreach ($data as $user) {
                $result = $this->dealMigrate($user);
                if ($result == 1) {
                    continue;
                }
            }
            $this->id = $this->id + $this->offset;
        }
        echo $this->all;
    }

    private function dealMigrate($user)
    {
        if (!empty(AccountMapDao::getInstance()->getUserIdByMobile($user['username']))) {
            echo "手机号已存在:" . $user['username'] . PHP_EOL;
            return 1;
        }
        $oldId = $user['id'];
        $userId = $this->getNextUserId();

        if ($user['id'] == $user['pretty_id']) {
            $user['pretty_id'] = $userId;
        }
        $user['id'] = $userId;

        $dbName = Sharding::getInstance()->getDbName('userMaster', $userId);

        Db::connect($dbName)->table('zb_member')->insert($user);

        $memberDetail = Db::connect('abConnection')->table('zb_member_detail')->where('user_id', $oldId)->find();
        if (!empty($memberDetail)) {
            unset($memberDetail['id']);
            $memberDetail['user_id'] = $userId;
            Db::connect($dbName)->table('zb_member_detail')->insert($memberDetail);
        }

        if ($user['attestation'] == 1) {
            $identityInfo = Db::connect('abConnection')->table('zb_user_identity')->where([['uid', '=', $oldId], ['status', '=', 1]])->find();
            if (!empty($identityInfo)) {
                unset($identityInfo['id']);
                $identityInfo['uid'] = $userId;
                Db::connect('commonMaster1')->table('zb_user_identity')->insert($identityInfo);
            }
        }

        $bankInfo = Db::connect('abConnection')->table('zb_user_bank')->where([['uid', '=', $oldId], ['account', '=', 'game:score'], ['count', '>', 0]])->find();
        if (!empty($bankInfo)) {
            $bankInfo['uid'] = $userId;
            Db::connect($dbName)->table('zb_user_bank')->insert($bankInfo);
        }

        Db::connect('commonMaster1')->table('zb_user_account_map')->insert(['type' => 'mobile', 'value' => $user['username'], 'user_id' => $userId]);
    }

    /**
     * 生成userId
     */
    public function getNextUserId()
    {
        // 最多循环20次，防止进入死循环
        for ($i = 0; $i < 20; $i++) {
            $userId = IdService::getInstance()->getNextUserId();
            if (!CommonUtil::isPrettyNumber($userId)) {
                return $userId;
            }
        }
        throw new FQException('用户ID生成错误', 500);
    }

}