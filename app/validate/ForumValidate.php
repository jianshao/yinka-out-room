<?php

namespace app\validate;

use app\common\GreenCommon;
use app\domain\exceptions\FQException;
use think\Exception;
use think\Validate;
use app\domain\shumei\ShuMeiCheckType;
use app\domain\shumei\ShuMeiCheck;

class ForumValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [];







    public static function ValidContent($content,$userId)
    {
        if (!empty($content)) {
//            if (!GreenCommon::getInstance()->checkText($content)) {
//                throw new FQException('当前动态包含色情或敏感字字符', 500);
//            }
            $checkStatus = ShuMeiCheck::getInstance()->textCheck($content,ShuMeiCheckType::$TEXT_FORUM_EVENT,$userId);
            if(!$checkStatus){
                throw new FQException('动态文字包含敏感字符', 500);
            }
            $check_content = mb_strlen($content, 'gb2312');
            if ($check_content > 300) {
                throw new FQException('发表动态不能超过300个字符', 500);
            }
        }
    }

    public static function validVoiceTime($forum_voice_time)
    {
        if (!empty($forum_voice_time)) {
            if ($forum_voice_time > 60 || $forum_voice_time < 3) {
                throw new FQException('语音时长不能小于3秒且不能超限60秒', 500);
            }
        }
    }

    public static function validImage($image)
    {
        if (!empty($image)) {
            $image = trim($image);
            $imageArr = explode(',', $image);
            if (is_array($imageArr)) {
                foreach ($imageArr as $key => $value) {
                    if (empty($value)) {
                        throw new FQException("图片参数错误", 500);
                    }
                }
            } else {
                throw new FQException("图片参数错误", 500);
            }
        }
    }
}
