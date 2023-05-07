<?php


namespace app\core\elasticSearch;


class DemoElastic extends ElasticSearchBase
{
    public $index = 'zb_languageroom';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * TODO https://www.yuque.com/docs/share/fef658b4-2c22-4174-94c6-5243c3b3e638?#%20%E3%80%8Aes%E7%AE%80%E5%8D%95%E4%BD%BF%E7%94%A8%E3%80%8B
     * DemoModel constructor.
     */
    private function __construct()
    {
        parent::__construct($this->index);
    }
}


/*
---------------------------------------------------- create index -------------------------------------
PUT  http://123.56.11.53:9200/zb_languageroom



---------------------------------------------------- create mapping -------------------------------------
POST  http://123.56.11.53:9200/zb_languageroom/_mapping

{
    "properties":{
        "id":{
            "type":"long"
        },
        "guild_id":{
            "type":"integer"
        },
        "pretty_room_id":{
            "type":"integer"
        },
        "user_id":{
            "type":"integer"
        },
        "room_name":{
            "type":"text",
            "analyzer":"ik_max_word"
        },
        "room_desc":{
            "type":"keyword"
        },
        "room_image":{
            "type":"keyword"
        },
        "room_welcomes":{
            "type":"keyword"
        },
        "room_type":{
            "type":"integer"
        },
        "room_tags":{
            "type":"integer"
        },
        "room_mode":{
            "type":"byte"
        },
        "room_password":{
            "type":"keyword"
        },
        "room_lock":{
            "type":"byte"
        },
        "room_createtime":{
            "type":"long"
        },
        "is_freemai":{
            "type":"byte"
        },
        "isopen_heart_value":{
            "type":"byte"
        },
        "fans_notices":{
            "type":"byte"
        },
        "is_live":{
            "type":"byte"
        },
        "visitor_number":{
            "type":"integer"
        },
        "socitay_id":{
            "type":"integer"
        },
        "visitor_externnumber":{
            "type":"integer"
        },
        "hx_room":{
            "type":"keyword"
        },
        "sw_room":{
            "type":"keyword"
        },
        "visitor_users":{
            "type":"integer"
        },
        "room_channel":{
            "type":"integer"
        },
        "background_image":{
            "type":"keyword"
        },
        "is_hot":{
            "type":"byte"
        },
        "is_wheat":{
            "type":"byte"
        },
        "tag_image":{
            "type":"keyword"
        },
        "guild_index_id":{
            "type":"integer"
        },
        "type":{
            "type":"keyword"
        },
        "tag_id":{
            "type":"integer"
        },
        "is_show":{
            "type":"byte"
        },
        "is_hide":{
            "type":"byte"
        },
        "is_block":{
            "type":"byte"
        }
    }
}
 */