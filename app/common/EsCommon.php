<?php


namespace app\common;

use Elasticsearch\ClientBuilder;

/**
 * es
 */
class EsCommon
{
    private static $instance = null;

    public static function getInstance() //公共的静态方法，实例化该类本身，只实例化一次
    {
        if (!self::$instance) {
            $host = explode(',', config('config.es_host'));
            self::$instance = ClientBuilder::create()->setHosts($host)->setRetries(5)->build();
        }
        return self::$instance;
    }
}

/*
---------------------------------------------------- create index -------------------------------------
PUT  http://123.56.11.53:9200/zb_check_im_message



---------------------------------------------------- create mapping -------------------------------------
POST  http://123.56.11.53:9200/zb_check_im_message/_mapping

{
    "properties":{
        "id":{
            "type":"long"
        },
        "from_uid":{
            "type":"integer"
        },
        "to_uid":{
            "type":"integer"
        },
        "type":{
            "type":"byte"
        },
        "message":{
            "type":"text",
            "analyzer":"ik_max_word"
        },
        "check_response":{
            "type":"keyword"
        },
        "api_response":{
            "type":"keyword"
        },
        "status":{
            "type":"byte"
        },
        "created_time":{
            "type":"long"
        },
        "updated_time":{
            "type":"long"
        },
        "ext_1":{
            "type":"keyword"
        },
        "ext_2":{
            "type":"keyword"
        },
        "ext_3":{
            "type":"keyword"
        }
    }
}
 */