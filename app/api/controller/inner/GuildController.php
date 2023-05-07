<?php

namespace app\api\controller\inner;

use app\Base2Controller;
use app\domain\exceptions\FQException;
use app\domain\guild\service\GuildService;
use think\facade\Request;

class GuildController extends Base2Controller
{

    /**
     * 创建公会
     * @return \think\response\Json
     * @throws FQException
     */
    public function createGuild()
    {
        $this->checkAuthInner();
        $userId = Request::param('userId', 0, 'intval'); //获取会长id
        $nickname = Request::param('nickname'); //公会昵称
        $password = Request::param('password'); //公会密码
        $proportionally = Request::param('proportionally'); //公会分成比例
        if (!$userId || !$nickname || !$password) {
            return rjson([], 500, '参数有误');
        }
        GuildService::getInstance()->createGuild($userId, $nickname, $password, $proportionally);
        return rjson([], 200, '创建成功');
    }

    /**
     * 修改公会信息
     * @return \think\response\Json
     */
    public function editGuildInfo()
    {
        $this->checkAuthInner();
        $guildId = Request::param('guildId', 0, 'intval'); //公会ID
        $profile = Request::param('profile');
        //根据用户id修改用户属性
        $profile = json_decode($profile, true);
        if (empty($profile) || !$guildId) {
            return rjson([], 500, '参数错误');
        }
        GuildService::getInstance()->editGuildInfo($guildId, $profile);
        return rjson([], 200, '修改成功');
    }

    /**
     * 修改公会成员信息
     * @return \think\response\Json
     * @throws FQException
     */
    public function editGuildMember()
    {
        $this->checkAuthInner();
        $userId = Request::param('userId', 0, 'intval'); //用户ID
        $guildId = Request::param('guildId', 0, 'intval'); //公会ID
        $field = Request::param('field'); //修改字段
        $value = Request::param('value'); //新值
        if (!$userId || !$guildId || !$field || !$value) {
            return rjson([], 500, '参数有误');
        }
        GuildService::getInstance()->editGuildMemberInfo($userId, $guildId, $field, $value);
        return rjson([], 200, '修改成功');
    }

    /**
     * 添加公会成员
     * @return \think\response\Json
     * @throws FQException
     */
    public function addGuildMember()
    {
        $this->checkAuthInner();
        $guildId = Request::param('guildId', 0, 'intval'); //公会id
        $userId = Request::param('userId', 0, 'intval');
        if (!$guildId || !$userId) {
            return rjson([], 500, '参数有误');
        }
        GuildService::getInstance()->addGuildMember($userId, $guildId);
        return rjson([], 200, '添加成功');
    }

    /**
     * 移除公会成员
     * @return \think\response\Json
     */
    public function removeGuildMember()
    {
        $this->checkAuthInner();
        $guildId = Request::param('guildId', 0, 'intval'); //公会id
        $userId = Request::param('userId', 0, 'intval');
        if (!$guildId || !$userId) {
            return rjson([], 500, '参数有误');
        }
        GuildService::getInstance()->removeGuildMember($userId);
        return rjson([], 200, '移除成功');
    }
}