<?php

namespace app\api\controller\inner;

use app\Base2Controller;
use app\chair\Conf\YunxinCommon;
use app\chair\model\MemberGuild;
use app\chair\model\MemberSocity;
use app\domain\exceptions\FQException;
use app\domain\guild\service\GuildService;
use app\utils\Error;
use think\App;
use think\facade\Request;

class GhMemberController extends Base2Controller
{

    public function __construct(App $app)
    {
        parent::__construct($app);
//        authToken
        $this->checkAuthGuild();
    }

    /**
     * 工会成员审核通过
     * @return \think\response\Json
     */
    public function exitMember()
    {
        $headUid = (int)$this->headUid;
        $pkId = Request::param('pk_id', 0, 'intval');
        $value = Request::param("value", 0, 'intval');  //1通过 2拒绝
        if ($pkId === 0 || $value === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = GuildService::getInstance()->exitMember($headUid, $pkId, $value);
        if (empty($result)) {
            throw new FQException("修改失败", 500);
        }
        return rjson([], 200, '修改成功');
    }

    /**
     * @info 踢出公会成员
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function kickMember()
    {
        $headUid = (int)$this->headUid;
        $pkId = Request::param('pk_id', 0, 'intval');
        if ($pkId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = GuildService::getInstance()->kickMember($headUid, $pkId);
        if (empty($result)) {
            throw new FQException("修改失败", 500);
        }
        return rjson([], 200, '修改成功');
    }

    /**
     * @info 同意申请退出
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agreeApply()
    {
        $headUid = (int)$this->headUid;
        $pkId = Request::param('pk_id', 0, 'intval');
        if ($pkId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = GuildService::getInstance()->agreeApply($headUid, $pkId);
        if (empty($result)) {
            throw new FQException("修改失败", 500);
        }
        return rjson([], 200, '修改成功');
    }


    /**
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refuseApply()
    {
        $headUid = (int)$this->headUid;
        $pkId = Request::param('pk_id', 0, 'intval');
        if ($pkId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = GuildService::getInstance()->refuseApply($headUid, $pkId);
        if (empty($result)) {
            throw new FQException("修改失败", 500);
        }
        return rjson([], 200, '修改成功');
    }

}