<?php
namespace app\common;
use app\utils\ArrayUtil;
use think\facade\Log;
use Green\Request\V20180509 as Green;
use Green\Request\Extension\ClientUploader;
use DefaultProfile;
use DefaultAcsClient;

class GreenCommon
{
	protected static $instance;
	 //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GreenCommon();
        }
        return self::$instance;
    }

    public function filterTexts($texts) {
        $kwfilterConf = config('config.kwfilter');
        Log::debug(sprintf('GreeCommon::filterTexts texts=%s config=%s',
            json_encode($texts), $kwfilterConf ? json_encode($kwfilterConf) : ''));
        if (!empty($kwfilterConf)) {
            try {
                $url = $kwfilterConf['url'];
                $res = curlData($url, ['texts' => json_encode($texts)], 'GET', 'form-data');
                Log::debug(sprintf('GreeCommon::filterTexts texts=%s url=%s res=%s',
                    json_encode($texts), $url, $res));
                $retJson = json_decode($res, true);
                $code = ArrayUtil::safeGet($retJson, 'code', 0);
                if ($code == 0) {
                    $data = ArrayUtil::safeGet($retJson, 'data');
                    if (!empty($data)) {
                        return ArrayUtil::safeGet($data, 'texts');
                    }
                }
            } catch (\Exception $e) {
                Log::error(sprintf('GreeCommon::filterTexts texts=%s url=%s ex=%s',
                    json_encode($texts), $url, $e->getTraceAsString()));
                return $texts;
            }
        }
        return $texts;
    }

    public function filterText($text) {
        try {
            $filteredTexts = $this->filterTexts([$text]);
            return $filteredTexts[0];
        } catch (\Exception $e) {
            return $text;
        }
    }

    /**
     *
     * @param $texts
     */
    public function checkText($text) {
        try {
            $filteredTexts = $this->filterTexts([$text]);
            if ($filteredTexts[0] == $text) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     *
     * @param $texts
     */
    public function checkTexts($texts) {
        $filteredTexts = $this->filterTexts($texts);
        if (count($texts) != count($filteredTexts)) {
            return false;
        }
        for ($i = 0; $i < count($texts); $i++) {
            if ($filteredTexts[$i] != $texts[$i]) {
                return false;
            }
        }
        return true;
    }

    //阿里云文本验证 true成功 false 失败
    public function checkTextOld($content)
    {
        return false;
    	$ali = config('config.ALIGREEN');
        $accessKeyID = $ali['AccessKeyID'];
	    $accessKeySecret = $ali['AccessKeySecret'];

    	$iClientProfile = DefaultProfile::getProfile("cn-shanghai", $accessKeyID, $accessKeySecret);
		DefaultProfile::addEndpoint("cn-zhangjiakou", "cn-zhangjiakou", "Green", "green.cn-zhangjiakou.aliyuncs.com");
		$client = new DefaultAcsClient($iClientProfile);
		$request = new Green\TextScanRequest();
		$request->setMethod("POST");
		$request->setAcceptFormat("JSON");
		$task1 = array('dataId' =>  uniqid(),
		    'content' => $content
		);
		$request->setContent(json_encode(array("tasks" => array($task1),
		    // "scenes" => array("antispam"),"bizType"=>"nickname")));
		    "scenes" => array("antispam"),"bizType"=>"msg")));
		try {
		    $response = $client->getAcsResponse($request);
		    Log::record("green--data-----". json_encode($response), "info" );
		    if(200 == $response->code){
		        $taskResults = $response->data;
		        foreach ($taskResults as $taskResult) {
		            if(200 == $taskResult->code){
		                $sceneResults = $taskResult->results;
		                foreach ($sceneResults as $sceneResult) {
		                    // $scene = $sceneResult->scene;
		                    $suggestion = $sceneResult->suggestion;
		                    if ($suggestion == 'block') {
		                    	return true;
		                    }
		                }
		                return false;
		            }else{
		                return true;
		            }
		        }
		    }else{
		        return true;
		    }
		} catch (Exception $e) {
		    return true; 
		}
		return true;
    }
  
}
