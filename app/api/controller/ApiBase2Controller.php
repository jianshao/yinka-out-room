<?php


namespace app\api\controller;

use app\BaseController;

class ApiBase2Controller extends BaseController
{

	protected $headUid;
    protected $headToken;
    protected $actionArr = [];
    protected $source;
    protected $config;

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
        }

        $action = $this->request->action();
        $this->source = $this->request->header('source');
//        $this->config = $this->source == 'ccp' ? 'ccpconfig' : 'config';
        switch ($this->source) {
            case 'ccp':
                $this->config = 'ccpconfig';
                break;
            case 'chuchu':
                $this->config = 'chuchuconfig';
                break;
            default:
                $this->config = 'config';
                break;
        }
    }


  
}