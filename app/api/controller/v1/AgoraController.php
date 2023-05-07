<?php


namespace app\api\controller\v1;


use app\agora\RtcTokenBuilder;
use app\api\controller\ApiBaseController;
use \app\facade\RequestAes as Request;

class AgoraController
{
    public function buildAuthorization() {
        $apiKey = 'd67633ef286c463e9d8026e27dac8af6';
        $secret = '03bd49c6a6f746b885291bd252b32ce6';

        // 拼接客户 ID 和客户密钥并使用 base64 编码
        $plainCredentials = $apiKey . ':' . $secret;
        $base64Credentials = base64_encode($plainCredentials);

        // 创建 authorization header
        return 'Basic ' . $base64Credentials;
    }

    public function getRoomUserList() {
        //$appId = '2c2af7c4eeb6437cae7de3408e01b67c';
        $channelName = Request::param('channelName');

        // 创建 authorization header
        $authorizationHeader = $this->buildAuthorization();

        return rjson([
            'authorizationHeader' => $this->buildAuthorization()
        ]);
        $data = curlData('https://api.agora.io/dev/v1/channel/user/2c2af7c4eeb6437cae7de3408e01b67c/' . $channelName,
            [],'GET', 'json', ['Authorization' => $authorizationHeader]);

        return rjson([
            'data' => $data
        ]);
    }

    public function getToken() {
        //$roomId = Request::param('roomId');
        $channelName = Request::param('channelName');
        $appId = '16d31176a2e14d6dbabc725503e2fcdd';
        $appCertificate = '7f794bb4c100441cbe5b025f7498be6f';
        $userIdStr = '' . intval($this->headUid);

        $role = RtcTokenBuilder::RoleAttendee;
        $expireTimeInSeconds = 3600;
        $currentTimestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        $token = RtcTokenBuilder::buildTokenWithUserAccount($appId, $appCertificate, $channelName, $userIdStr, $role, $privilegeExpiredTs);

        return rjson([
            'rtcToken' => $token,
            'channelName' => $channelName
        ]);
    }
}