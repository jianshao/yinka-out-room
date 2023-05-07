<?php


namespace app\common\amqp\model;


class AmpMessageModel
{
//    重试次数
    public $runType = 0;
//    队列标记
    public $topic = "";
    // 模式名称
    public $tag = "";
//    消息id
    public $msgId = "";
//    event
    public $event = "";
//    队列组名int标识
    public $partition = 0;
//    时间戳微秒数
    public $timestamp = 0;
//    消息体内容
    public $body = '';

    /**
     * @param $jsonObj
     * @return $this
     */
    public function fromJson($jsonObj)
    {
        $this->id = isset($jsonObj['run_type']) ? $jsonObj['run_type'] : 0;
        $this->topic = isset($jsonObj['topic']) ? $jsonObj['topic'] : 0;
        $this->tag = isset($jsonObj['tag']) ? $jsonObj['tag'] : "";
        $this->msgId = isset($jsonObj['msg_id']) ? $jsonObj['msg_id'] : "";
        $this->event = isset($jsonObj['event']) ? $jsonObj['event'] : "";
        $this->partition = isset($jsonObj['partition']) ? $jsonObj['partition'] : "";
        $this->timestamp = isset($jsonObj['timestamp']) ? $jsonObj['timestamp'] : "";
        $this->body = isset($jsonObj['body']) ? $jsonObj['body'] : "";
        return $this;
    }


    /**
     * @return array
     */
    public function toJson()
    {
        return [
            "run_type" => $this->runType,
            "topic" => $this->topic,
            "tag" => $this->tag,
            "msg_id" => $this->msgId,
            "event" => $this->event,
            "partition" => $this->partition,
            "timestamp" => $this->timestamp,
            "body" => $this->body,
        ];
    }


    public function getHashkey()
    {
        $hashKey = sprintf("%s%s", $this->msgId, $this->runType);
        return md5($hashKey);
    }
}