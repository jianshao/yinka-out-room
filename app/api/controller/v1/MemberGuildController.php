<?php

namespace app\api\controller\v1;

use app\domain\guild\dao\MemberGuildModelDao;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\guild\service\GuildService;
use app\domain\room\dao\RoomModelDao;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use Exception;

class MemberGuildController extends ApiBaseController
{
    private $check_username = "/^1\d{10}$/";                //手机号检测
    private $user_code_key = 'verify_code_';            //验证码

    /**该成员加入的公会列表
     * @param $token    token值
     * @return mixed    返回类型
     */
    public function guildList()
    {
        //获取数据
        $user_id = $this->headUid;
        //查询当前用户属于哪个公会下
        $where[] = ['status','in', '0,1'];
        $where[] = ['user_id','=',$user_id];
        $guild = MemberSocityModelDao::getInstance()->getOnes($where);

        $guild_list = [];
        if (!empty($guild)) {
            $guild_detail = MemberGuildModelDao::getInstance()->getOneObject(['id'=>$guild['guild_id']]);
            if (!empty($guild_detail)){
                $guild_list = [
                    'id' => $guild['id'],
                    'guild_id' => $guild['guild_id'],
                    'user_id' => $guild_detail['user_id'],
                    'nickname' => $guild_detail['nickname'],
                    'status' => $guild['status'],

                ];
            }
        }
        //返回数据
        return rjson($guild_list);
    }

    /**根据公会id获取用户昵称
     * @param $token    token值
     * @param $guild_id     公会id
     */
    public function guildDetail()
    {
        //获取数据
        $guild_id = Request::param('guild_id');
        if(!$guild_id){
            return rjson([],'500', '参数错误');
        }
        if(!is_numeric($guild_id)){
            return rjson([], 601, '公会不正确');
        }
        $where['id'] = $guild_id;
        $field = "id,user_id,nickname";
        $guild_detail = MemberGuildModelDao::getInstance()->getOne($where,$field);
        if(empty($guild_detail)){
            $guild_detail = null;
        }
        //返回数据
        $result = [
            "guild_detail" => $guild_detail,
        ];
        return rjson($result);
    }

    /**加入公会审请
     * @param $token    token值
     * @param $guild_id     公会id
     * @param $phone        手机号
     * @param $vertify      验证码
     */
    public function guildAdd(){
        //获取数据
        $guild_id = Request::param('guild_id');
        $phone = Request::param('phone');
        $vertify = Request::param('vertify');
        //校验数据格式
        if(!$guild_id || !$phone || !$vertify){
            return rjson([],'500', '参数错误');
        }
        try{
            GuildService::getInstance()->guildAdd($this->headUid, $guild_id, $phone, $vertify);
            return rjson([], 200, '恭喜您审请成功');
        }catch(Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }

    }

    /**
     * @info 加入公会审请
     * @return \think\response\Json
     */
    public function addGuild(){
        //获取数据
        $guild_id = Request::param('guild_id');
        $phone = Request::param('phone');
        $vertify = Request::param('vertify');
        //校验数据格式
        if(!$guild_id || !$phone || !$vertify){
            return rjson([],500, '参数错误');
        }
        try{
            $userGuildInfo = GuildService::getInstance()->guildAdd($this->headUid, $guild_id, $phone, $vertify);
            $retData = [];
            if(!empty($userGuildInfo)){
                $retData = [
                    'id' => $userGuildInfo['id'],
                    'create_time' => time()
                ];
            }
            return rjson($retData, 200, '恭喜您审请成功');
        }catch(Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }

    }

    /**
     * 取消工会申请
     * @return mixed
     */
    public function cancelGuild() {
        //获取数据
        $guild_id = Request::param('guild_id');
        $id = Request::param('id');
        try {
            GuildService::getInstance()->cancelGuild($guild_id, $id);
            return rjson([],200,'取消申请成功');
        } catch (Exception $e) {
            return rjson([],$e->getCode(), $e->getMessage());
        }
    }

    /**
     * @info 搜索公会
     * @return \think\response\Json
     * @throws \app\domain\exceptions\FQException
     */
    public function searchGuild(){

        try {
            $guildId = Request::param('guildId');
            if(!$guildId){
                return rjson([],500, '参数错误');
            }
            $userId = (int)$this->headUid;
            // 获取公会信息
            $guildInfo = GuildService::getInstance()->searchGuild($guildId);
            $guildInfo['logo_url'] = CommonUtil::buildImageUrl($guildInfo['logo_url']);
            // 公会房间数
            $guildInfo['guild_room_count'] = RoomModelDao::getInstance()->getGuildRoomCount($guildId);
            // 获取公会成员数
            $guildInfo['guild_member_count'] = MemberSocityModelDao::getInstance()->getGuildMemberNum($guildId);
            // 用户与公会关系
            list($guildInfo['status'],$guildInfo['id']) = GuildService::getInstance()->getUserGuildStatus($guildId,$userId);

            return rjson($guildInfo);
        }catch(Exception $e) {
            return rjson([],$e->getCode(), $e->getMessage());
        }

    }

    /**
     * @info 用户公会
     * @return \think\response\Json
     * @throws \app\domain\exceptions\FQException
     */
    public function userGuild(){

        try {
            $userId = (int)$this->headUid;
            //用户申请公会数据
            $userGuildData = GuildService::getInstance()->getUserGuild($userId);
            if(!empty($userGuildData)){

                foreach($userGuildData as $k => $v){
                    if($v['apply_quit_time']){
                        $userGuildData[$k]['status'] = 2;
                        //计算距离自动退出公会时间
                        $autoQuitTime = $v['apply_quit_time']+15*86400;
                        $day = ceil(($autoQuitTime-time())/86400);
                        $userGuildData[$k]['auto_quit_time'] = $day>0?$day:0;
                    }
                    $userGuildData[$k]['addtime'] = strtotime($v['addtime']);
                    $userGuildData[$k]['logo_url'] = CommonUtil::buildImageUrl($v['logo_url']);
                }
            }
            //返回数据
            return rjson($userGuildData);
        }catch(Exception $e) {
            return rjson([],$e->getCode(), $e->getMessage());
        }

    }

    /**
     * @info 用户触发公会相关 类型 1 取消申请公会 2、退出公会 3、取消退出申请
     * @return \think\response\Json
     */
    public function operationGuild(){

        try {
            $guildId = Request::param('guildId');
            $userGuildId = Request::param('id');
            $type = Request::param('type');
            $userId = intval($this->headUid);
            if(!$guildId || !$userGuildId || !in_array($type,[1,2,3])){
                return rjson([],500,'参数错误');
            }
            if($type == 1){
                list($isOk,$message) = GuildService::getInstance()->cancelApplyGuild($guildId,$userGuildId,$userId);
            }else if($type == 2){
                list($isOk,$message) = GuildService::getInstance()->applyQuitGuild($guildId,$userGuildId,$userId);
            }else if($type == 3){
                list($isOk,$message) = GuildService::getInstance()->cancelQuitGuild($guildId,$userGuildId,$userId);
            }else{
                return rjson([],500,'类型不存在');
            }
            $code = $isOk?200:500;
            return rjson([
                'create_time'=>time(),
                'auto_quit_time' => 15
            ],$code,$message);

        }catch(Exception $e) {
            return rjson([],$e->getCode(), $e->getMessage());
        }
    }





}