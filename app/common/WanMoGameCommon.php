<?php


namespace app\common;


use think\facade\Log;

class WanMoGameCommon
{
    protected $token;
    protected $secret;

    protected static $instance;
    //单例
    public static function getInstance(): WanMoGameCommon
    {
        if (!isset(self::$instance)) {
            self::$instance = new WanMoGameCommon();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->token      = config('config.WanMoGame.token');
        $this->secret   = config('config.WanMoGame.secret');
    }

    protected function getHeaders(): array
    {
        $currentTimeMs = round(microtime(true) * 1000);
        $nonce = $this->generateRandomString();
        $params = array(
            'token' => $this->token,
            'timestamp' => $currentTimeMs,
            'nonce' => $nonce,
        );
        $sign = $this->createSign($params, $this->secret);

        return array(
            "token: " . $this->token,
            "timestamp: " . $currentTimeMs,
            "nonce: " . $nonce,
            "sign: " . $sign
        );
    }


    public function newMatch()
    {
        $headers = $this->getHeaders();

        $userList = [
            ['outUserId' => '1056141', 'roleName'=> '您'],
            ['outUserId' => '1009983', 'roleName'=> '哈哈哈'],
        ];
        $params = [
            "outMatchId" => "test-1",
            "gameName" => "和平精英",
            "startTime" => "2023-07-25 00:00:00",
            "userList" =>$userList,
            "modelConfig" => [
                "model"=> "jdms",
                "level"=> "10",
                "map"=> "hd",
                "person"=> "third",
                "team"=> "2",
                "obNum"=> "1",
                "NCS"=> "0.9",
                "airdrop"=> "0",
                "gameArea"=> "10",
                "arms"=> "0"
            ]
        ];

        $url = "http://sdk.wanmogame.com/sdk/newMatch";
        $res = curlData($url, json_encode($params), 'POST', 'json', $headers);
        Log::info(sprintf('WanMoGameCommon newMatch res=%s', $res));

        $rest = json_decode($res,true);
        $resCode = $rest['code'] ?? -1;
        if ($resCode != 0) {
            Log::error(sprintf('WanMoGameCommon newMatch error res=%s', $res));
            return [];
        }

        return $rest['data'] ?? [];
    }

    /**
     * 玩么接口4：支持游戏列表
     * @return array
     * {"code":0,"msg":"请求成功","data":["和平精英","CODM_activision","CODM_garena"]}
     */
    public function getGameList(): array
    {
        $headers = $this->getHeaders();

        $url = "http://sdk.wanmogame.com/sdk/getGameList";
        $res = curlData($url, [], 'GET', 'json', $headers);
        Log::info(sprintf('WanMoGameCommon getGameList res=%s', $res));

        $rest = json_decode($res,true);
        $resCode = $rest['code'] ?? -1;
        if ($resCode != 0) {
            Log::error(sprintf('WanMoGameCommon getGameList error res=%s', $res));
            return [];
        }

        return $rest['data'] ?? [];
    }

    /**
     * 玩么接口3：游戏参数配置查询
     * @param string $gameName
     * @return array
     * {"code":0,"msg":"请求成功","data":[{"modelKey":"map","itsRequired":true,"descr":"地图:sm-沙漠,hd-海岛,yl-雨林,xd-雪地,sg-山谷,hjd-黄金岛,ftpd-飞艇派对,cqwl-重启未来","valueType":0,"value":"[\"sm\", \"hd\", \"yl\", \"xd\", \"sg\", \"hjd\", \"ftpd\", \"cqwl\"]"},{"modelKey":"level","itsRequired":true,"descr":"用户等级","valueType":0,"value":"{\"max\": 30, \"min\": 1}"},{"modelKey":"gameArea","itsRequired":true,"descr":"分区:10-QQ区,20-微信区","valueType":0,"value":"[10, 20]"},{"modelKey":"model","itsRequired":true,"descr":"模式:jdms-经典模式,djms-电竞模式,yytx-鹰眼特训,zyms-综艺模式,hldj-火力对决,scxd-赛场行动","valueType":0,"value":"[\"jdms\", \"djms\", \"yytx\", \"zyms\", \"hldj\", \"scxd\"]"},{"modelKey":"obNum","itsRequired":true,"descr":"开放观站位数量","valueType":0,"value":"[0, 1, 2]"},{"modelKey":"person","itsRequired":true,"descr":"人称:first-第1人称,third-第3人称","valueType":0,"value":"[\"first\", \"third\"]"},{"modelKey":"team","itsRequired":true,"descr":"队伍:1-单人,2-双人,4-四人","valueType":0,"value":"[1, 2, 4]"},{"modelKey":"airdrop","itsRequired":false,"descr":"高级参数空投","valueType":0,"value":"[0, 1, 2]"},{"modelKey":"arms","itsRequired":false,"descr":"高级参数武器","valueType":0,"value":"[0, 0.5, 1, 2, 3]"},{"modelKey":"invite","itsRequired":false,"descr":"是否可邀请好友,默认false","valueType":0,"value":"[true, false]"},{"modelKey":"NCS","itsRequired":false,"descr":"高级参数缩圈速度","valueType":0,"value":"[0.9, 1, 1.1, 1.2]"},{"modelKey":"playerNum","itsRequired":false,"descr":"玩家人数","valueType":0,"value":"[100]"}]}
     */
    public function getGameModelList(string $gameName): array
    {
        $headers = $this->getHeaders();

        $url = "http://sdk.wanmogame.com/sdk/getGameModelList";
        $res = curlData($url, ["gameName"=>$gameName], 'GET', 'json', $headers);
        Log::info(sprintf('WanMoGameCommon getGameModelList res=%s', $res));

        $rest = json_decode($res,true);
        $resCode = $rest['code'] ?? -1;
        if ($resCode != 0) {
            Log::error(sprintf('WanMoGameCommon getGameModelList error res=%s', $res));
            return [];
        }

        return $rest['data'] ?? [];
    }


    protected function createSign($params, $secret): string
    {
        // 将参数按照字典序排序
        ksort($params);
        // 拼接参数和值
        $stringA = '';
        foreach ($params as $key => $value) {
            if (!empty($value) && $key !== 'sign') {
                $stringA .= "{$key}={$value}&";
            }
        }
        $stringA .= "secret={$secret}";
        // 计算 MD5 值并转换为大写
        $signValue = strtoupper(md5($stringA));
        return $signValue;
    }

    protected function generateRandomString($length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        return $randomString;
    }
}