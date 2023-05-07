<?php

namespace app\api\script;

use app\domain\sensors\service\SensorsUserService;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);


/**
 * @info 用户信息导入神策
 * sudo php think UserRegisterSensorsCommand 0 1000000 500
 * sudo php think UserRegisterSensorsCommand 1000000 2000000 500
 * sudo php think UserRegisterSensorsCommand 2000000 3000000 500
 * sudo php think UserRegisterSensorsCommand 3000000 4000000 500
 */
class UserRegisterSensorsCommand extends Command
{
    private $offset = 0;
    private $end = 500;
    private $limit = 500;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\UserRegisterSensorsCommand')
            ->addArgument('offset', Argument::OPTIONAL, "offset")
            ->addArgument('end', Argument::OPTIONAL, "end")
            ->addArgument('limit', Argument::OPTIONAL, "limit")
            ->setDescription('user register sensors');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->offset = $input->getArgument('offset') ? intval($input->getArgument('offset')) : 0;
        $this->end = $input->getArgument('end') ?? 500;
        $this->limit = $input->getArgument('limit') ?? 500;
        try {
            $this->handler();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\UserRegisterSensorsCommand execute date:%s error:%s error trice:%s", date('Y-m-d H:i:s',time()), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    /**
     * @info 注册到神策
     * @param Output $output
     * @return int
     */
    private function handler()
    {
        if($this->end-$this->offset < 0){
            $this->output->writeln(sprintf('app\command\UserRegisterSensorsCommand params error offset=%s end=%s',$this->offset,$this->end));
        }
        $page = ceil(($this->end-$this->offset) / $this->limit);
        for ($number = 1; $number <= $page; $number++) {
            $field = 'id,register_time,is_vip,vip_exp,svip_exp,register_channel,sex,birthday,role,guild_id,totalcoin,freecoin,username,nickname,mobile,city';
            $userId = $this->offset+1;

            $userInfo = UserModelDao::getInstance()->findLoadUserModel($userId, $field);

            list($result,$sensorsInfo) = SensorsUserService::getInstance()->userImportSensors($userInfo);

            $this->output->writeln(sprintf('app\command\UserRegisterSensorsCommand success userId=%s request:%s response:%s',$userId,json_encode($sensorsInfo),$result));

            $this->offset = $userId;

            if(empty($userInfo)){
                $this->output->writeln(sprintf('app\command\UserRegisterSensorsCommand success userId=%s noUser',$userId));
            }
        }
    }

}
