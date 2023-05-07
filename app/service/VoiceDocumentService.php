<?php


namespace app\service;


use app\common\RedisCommon;

class VoiceDocumentService
{
    protected static $instance;
    private $conf = null;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new VoiceDocumentService();
            self::$instance->loadConf();
        }
        return self::$instance;
    }

    public $config = [
        [
            'title' => '分享你的故事',
            'content' => [
                '用声音来传递你的故事吧',
                '讲讲你的童年趣事吧',
                '上学期间有什么事情是让你特别难忘的吗',
                '工作之后有什么难忘的事情吗',
                '发生在你身上最暖心的事情是什么',
                '讲讲你现在的生活状态吧'
            ]
        ],
        [
            'title' => '聊聊你的生活',
            'content' => [
                '空闲的时候你最喜欢做什么呢',
                '你有什么兴趣爱好呢',
                '你希望未来的在哪里过着怎样的生活呢',
                '你希望未来在哪里生活呢',
                '如果时光倒流，你想回到哪个地方呢',
                '你最向往的生活是怎样的呢',
                '假如你有一年的假期你会做什么呢'
            ]
        ],
        [
            'title' => '说说你喜欢的音乐',
            'content' => [
                '开心的时候你会唱什么歌呢',
                '恋爱的时候你会唱什么歌呢',
                '唱一首你最擅长的歌吧',
                '最近新发的歌曲，你最喜欢哪一首呢',
                '你最喜欢那个歌手呢，唱首他的歌听吧',
                '讲讲你现在的生活状态吧'
            ]
        ],
        [
            'title' => '说说你喜欢的电影',
            'content' => [
                '最近看的电影里哪个片段最难忘呢',
                '你看的最有趣的电影是什么呢'
            ]
        ],
        [
            'title' => '说说你对另一半的要求',
            'content' => [
                '你希望另一半有怎样的特质呢',
                '你希望中的TA是什么样子的呢',
                '你想象中的另一半是什么样的呢'
            ]
        ],
        [
            'title' => '读一段情话',
            'content' => [
                '人生最大的幸福，是发现自己爱的人正好也爱着自己',
                '陪伴是最长情的告白，而守护是最沉默的陪伴',
                '我会化作人间的风雨陪在你身边',
                '有生之年能遇见你，竟花光我所有的运气',
                '人生本不该令我这么欣喜，但是你来了',
                '怪我的眼睛里藏不住心事，一提到你的名字我就温柔泛滥',
                '我的世界里从此没有星辰大海，因为遇见你的那一刻，浩瀚众星，皆降为尘',
                '有一天我会告诉你，我对你的爱就是乌云也拉不住的雨点，再怎么朝着大地也不回头的狂奔',
                '有几次我梦见了你，你如此清晰的站在我面前，是我激动不已，一旦惊醒，心如刀绞',
                '我并不奢望有一天能站在你身边，我只想让你知道，你的光芒曾照亮过我'
            ]
        ],
    ];

    public function loadConf() {
        $this->conf = $this->config;
    }

    public function initVoiceDocument() {
        $conf = $this->conf;
        $count = count($conf);
        $arr = [];
        for ($i = 0; $i < $count; $i++) {
            $arr[] = $i;
        }
        return $arr;
    }

    public function getVoiceDocument($userId) {
        $conf = $this->conf;
        $redis = RedisCommon::getInstance()->getRedis();
        $curJson = $redis->get(sprintf('user:voice:document:%s', $userId));
        if (empty($curJson)) {
            $arr = $this->initVoiceDocument();
        } else {
            $arr = json_decode($curJson,true);
        }
        $index = array_rand($arr);
        $data['title'] = $conf[$arr[$index]]['title'];
        $content = array_rand($conf[$arr[$index]]['content']);
        $data['content'] = $conf[$arr[$index]]['content'][$content];
        unset($arr[$index]);
        if (empty($arr)) {
            $redis->del(sprintf('user:voice:document:%s', $userId));
        } else {
            $arr = array_values($arr);
            $redis->set(sprintf('user:voice:document:%s', $userId), json_encode($arr));
            $redis->expireAt(sprintf('user:voice:document:%s', $userId), time() + (86400*10));
        }
        return $data;
    }

}