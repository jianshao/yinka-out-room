<?php


namespace app\common\amqp\model;


class UserRegisterRefereeMessageModel
{
//    重试次数
    public $tag = "";
//    消息体内容
    public $body = '';

    /**
     * @param $jsonObj
     * @return $this
     */
    public function fromJson($jsonObj)
    {
        $this->tag = isset($jsonObj['tag']) ? $jsonObj['tag'] : "";
        $this->body = isset($jsonObj['body']) ? $jsonObj['body'] : "";
        return $this;
    }


    /**
     * @return array
     */
    public function toJson()
    {
        return [
            "tag" => $this->tag,
            "body" => $this->body,
        ];
    }


}