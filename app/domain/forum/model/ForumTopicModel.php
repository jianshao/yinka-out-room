<?php


namespace app\domain\forum\model;


class ForumTopicModel
{
    public $id = 0;
    // 0-父级id标签 不是0的父级标签的id对应的子集标签
    public $pid = 0;
    // 名称
    public $topicName = '';
    //排序值
    public $topicOrder = 0;
    // 上架状态 0下架1上架
    public $topicStatus = 0;
    // 是否热门0否1是
    public $topicHot = 0;
    // 是否推荐0否1
    public $topicRecommend = 0;
    // 创建时间
    public $createTime = 0;
    // 更新时间
    public $updateTime = 0;
    // 创建人
    public $createUser = '';
    // 修改人
    public $updateUser = '';

}