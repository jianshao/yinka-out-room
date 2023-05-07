<?php
/**
 * 公共MQTT消息
 * yond
 * 
 */

namespace app\common;

use think\App;
use think\facade\Log;
use think\facade\Request;
use think\cache\driver\Redis;
use constant\CodeConstant as coder;


class SendMqttCommon
{

	protected static $instance;
	 //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new SendMqttCommon();
        }
        return self::$instance;
    }
    
    private function sendMsg($send,$client,$msg,$sess=false)
    {
    	$conf = config('config.MQTT');
		$accessKey = $conf['accessKey'];
		$secretKey = $conf['secretKey'];
		$endPoint = $conf['endPointIn'];
		$instanceId = $conf['instanceId'];
		$topic = $conf['topic'].'/'.$send;
		$groupId = $conf['groupId'];
		
		$port = 1883;
		$keepalive = 90;
		if ($sess) {
			$qos = 0;
			$cleanSession = true;
		}else{
			$qos = 1;
			$cleanSession = false;
		}
		// $clientId = $deviceId;

		$mqttClient = new \Mosquitto\Client($client, $cleanSession);
		// ## 设置鉴权参数，参考 MQTT 客户端鉴权代码计算 username 和 password
		$username = 'Signature|' . $accessKey . '|' . $instanceId;
		$sigStr = hash_hmac("sha1", $client, $secretKey, true);
		$password = base64_encode($sigStr);
		
		$mqttClient->setCredentials($username, $password);
		$mqttClient->connect($endPoint, 1883, 5);

		//send msg
		$mqttClient->loop();
        $mid = $mqttClient->publish($topic, json_encode($msg), $qos);
        $mqttClient->loop();
        return true;
		
    }
  


    public function p2pMsg($cleanSession,$receiverid,$msg,$client)
    {
    	
    	$conf = config('config.MQTT');
		// $redis = $this->getRedis();

		$mqttClient = new \Mosquitto\Client($client, $cleanSession);
		// ## 设置鉴权参数，参考 MQTT 客户端鉴权代码计算 username 和 password
		$username = 'Signature|' . $conf['accessKey'] . '|' . $conf['instanceId'];
		$sigStr = hash_hmac("sha1", $client, $conf['secretKey'], true);
		$password = base64_encode($sigStr);
		
		$mqttClient->setCredentials($username, $password);
		$mqttClient->connect($conf['endPointIn'], 1883, 5);
		
		//send msg
		$mqttClient->loop();
        $mqttP2PTopic = $conf['topic'] . "/p2p/".$receiverid;
        $mid = $mqttClient->publish($mqttP2PTopic, json_encode($msg), 1, 0);
        $mqttClient->loop();
		if ($mid > 0) {
			return true;
		}
		return false;

    }





}