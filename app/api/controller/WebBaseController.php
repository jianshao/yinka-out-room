<?php


namespace app\api\controller;

use app\BaseController;
use app\domain\user\dao\UserBlackModelDao;

class WebBaseController extends BaseController
{

	protected $headUid;
    protected $headToken;
    protected $actionArr = [];

    public function initialize()
    {
    	//api过滤登录
        $this->actionArr = ['login','regist','option'];
        $this->headToken = $this->request->header('token');
        if (empty($this->headToken)) {//兼容老版
             $this->headToken = $this->request->param('token');
        }
        if (!empty($this->headToken)) {
             $redisinit = $this->getRedis();
             $this->headUid = $redisinit->get($this->headToken);
             $model = UserBlackModelDao::getInstance()->loadData($this->headUid);
             if ($model && $model->status==1) {
                 $reason = $model->reason;
                echo json_encode(['code' => 5000, 'desc' => '因'.$reason.',账号封禁异常', 'data' => null]);
                exit;
             }
        }

        $action = $this->request->action();
        // if (!in_array($action, $this->actionArr)) {
        if ($action) {
            if (empty($this->headUid)) {
                echo json_encode(['code'=>5000,'desc'=>'非法请求','data'=>null]);
                exit;
            }
        }
    	
    }


  
}