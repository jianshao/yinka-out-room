<?php

namespace app\api\script;

use app\domain\exceptions\FQException;
use app\domain\user\dao\NicknameLibraryDao;
use app\domain\user\model\NicknameLibraryModel;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Exception;

ini_set('set_time_limit', 0);

/**
 * @info 生成默认昵称
 * Class GenerateNicknameCommand
 * @package app\command
 * @command  php think GenerateNicknameCommand 60  >> /tmp/GenerateNicknameCommand.log 2>&1
 */
class GenerateNicknameCommand extends RoomBaseCommond
{
    private $readAdjectiveData;
    private $readNounData;
    private $emojiData;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\GenerateNicknameCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->setDescription('refresh GenerateNicknameCommand sort bucket data');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln(sprintf('app\command\GenerateNicknameCommand execute entry date:%s', $this->getDateTime()));
        $func = $input->getArgument('func') ?: "fitExeCute";
        $refreshAll = 0;
        try {
            $refreshNumber = $this->{$func}($output);
            $refreshAll += $refreshNumber;
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\GenerateNicknameCommand execute exception date:%s error:%s error trice:%s", $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            return;
        }
        usleep(1000000);
        // 指令输出
        $output->writeln(sprintf('app\command\GenerateNicknameCommand execute success end refrashNumber:%d date:%s', $refreshAll, $this->getDateTime()));
    }


    /**
     * @param Output $output
     * @return int
     * @throws Exception
     */
    private function fitExeCute(Output $output)
    {
//        检查数据库没有使用的数据总量
        $number = NicknameLibraryDao::getInstance()->countNotUsedNumber();
        if ($number > 40000) {
            $output->writeln(sprintf('app\command\GenerateNicknameCommand fitExeCute countNotUsedNumber success number:%d', $number));
            return 0;
        }
        $output->writeln(sprintf('app\command\GenerateNicknameCommand fitExeCute countNotUsedNumber insufficient number:%d', $number));
//        初始化数据
        $this->readAdjectiveData = $this->readAdjective();
//        var_dump(count($readAdjectiveData));//248
        $this->readNounData = $this->readNoun();
//        var_dump(count($readNounData));//660
        $this->emojiData = $this->getOriginEmojiData();
//        var_dump(count($emojiData));die;//426
        $refreshNumber = 0;
        for ($i = 0; $i < 116; $i++) {
            $refreshNumber += $this->handle($output);
        }
        return $refreshNumber;
    }

    /**
     * @param Output $output
     * @return int
     */
    private function handle(Output $output)
    {
        $acount = count($this->emojiData) - 1;
        $bcount = count($this->readAdjectiveData) - 1;
        $ccount = count($this->readNounData) - 1;
        $dcount = $acount;

        list($aArr, $bArr, $cArr, $dArr) = $this->makeSiteArr($acount, $bcount, $ccount, $dcount);
        $arr = array($aArr, $bArr, $cArr, $dArr);
        $Generator = $this->makeNickNameMark($arr);
        $unixtime = time();
        $refreshNumber = 0;
        foreach ($Generator as $itemMarkData) {
            try {
                $nickname = $this->generatorNickStr($itemMarkData);
                $len = mb_strlen($nickname, 'gb2312');
                if ($len > 20) {
                    throw new FQException('昵称不超过10个字', 500);
                }
                $model = new NicknameLibraryModel();
                $model->hashkey = md5($nickname);
                $model->nickname = $nickname;
                $model->createTime = $unixtime;
                $result = NicknameLibraryDao::getInstance()->store($model);
                $output->writeln(sprintf('app\command\GenerateNicknameCommand fitExeCute data:%s result:%d', json_encode($model), $result));
                if ($result) {
                    $refreshNumber++;
                }
            } catch (\Exception $e) {
                $output->writeln(sprintf('app\command\GenerateNicknameCommand fitExeCute Exception err:%s msg:%s ', $e->getCode(), $e->getMessage()));
            }
        }
        return $refreshNumber;
    }

    /**
     * @param $acount
     * @param $bcount
     * @param $ccount
     * @param $dcount
     * @return array
     */
    private function makeSiteArr($acount, $bcount, $ccount, $dcount)
    {
        $aArr = $this->randList($acount);
        $bArr = $this->randList($bcount);
        $cArr = $this->randList($ccount);
        $dArr = $this->randList($dcount);
        return [$aArr, $bArr, $cArr, $dArr];
    }

    private function randList($acount)
    {
        $result = [];
        for ($a = 0; $a < 4; $a++) {
            $result[] = mt_rand(0, $acount);
        }
        return $result;
    }

    private function generatorNickStr($itemMarkData)
    {
        $itemArr = explode(",", $itemMarkData);
        if (count($itemArr) < 4) {
            return "";
        }
        return sprintf("%s%s%s%s", $this->emojiData[$itemArr[0]], $this->readAdjectiveData[$itemArr[1]], $this->readNounData[$itemArr[2]], $this->emojiData[$itemArr[3]]);
    }

    private function makeNickNameMark($arr)
    {
        $cartesian_product = $this->cartesian($arr);
        foreach ($cartesian_product as $itemIds) {
            yield $itemIds;
        }
    }

    private function cartesian($arr, $str = array())
    {
        //去除第一个元素
        $first = array_shift($arr);
        //判断是否是第一次进行拼接
        if (count($str) > 1) {
            foreach ($str as $k => $val) {
                foreach ($first as $key => $value) {
                    //最终实现的格式 1,3,76
                    //可根据具体需求进行变更
                    $str2[] = $val . ',' . $value;
                }
            }
        } else {
            foreach ($first as $key => $value) {
                //最终实现的格式 1,3,76
                //可根据具体需求进行变更
                $str2[] = $value;
            }
        }

        //递归进行拼接
        if (count($arr) > 0) {
            $str2 = $this->cartesian($arr, $str2);
        }
        //返回最终笛卡尔积
        return $str2;
    }

    /**
     * @info 形容词列
     * @return false|string[]
     */
    private function readAdjective()
    {
        $str = <<<STR
天真的
难忘的
疑惑的
悲伤的
兴奋的
傻傻的
开心的
激动的
不开心的
机智的
勇敢的
懦弱的
胆小的
恐惧的
花哨的
话少的
话多的
沉默的
无语的
漠然的
漂亮的
优雅的
高大的
渺小的
臃肿的
肥胖的
干枯的
油腻的
温婉的
纤瘦的
胖胖的
瘦瘦的
高高的
矮矮的
白白的
黑黑的
幸运的
悲惨的
僵硬的
贵气的
穷酸的
热辣的
知性的
丑陋的
粗鲁的
嫩嫩的
鲜嫩的
优美的
儒雅的
文艺的
害羞的
活泼的
开朗的
腼腆的
别扭的
阴郁的
朦胧的
吵闹的
可恶的
水灵的
迷人的
着迷的
帅气的
威武的
安静的
弱弱的
柔弱的
清爽的
泼辣的
伪装的
迷茫的
自信的
自卑的
自负的
自责的
暴躁的
抓狂的
静静的
无助的
飞快的
迟钝的
素净的
浓烈的
可人的
恋爱的
失恋的
梦想的
粗心的
马虎的
讨厌的
可爱的
心仪的
心爱的
爱慕的
期盼的
想念的
喜欢的
爱你的
矮小的
刻薄的
怪异的
灵动的
盼望的
理想的
能说的
大嘴的
白皙的
黝黑的
气人的
想你的
健谈的
加速的
奔跑的
跳跃的
飞奔的
慢跑的
牛气的
懒惰的
珍视的
聪慧的
磨人的
迷恋的
痛恨的
疯狂的
超级的
大个的
大头的
长腿的
短发的
长发的
诡异的
默契的
正面的
直面的
肥肥的
圆圆的
萌萌的
协作的
正义的
软萌的
傲娇的
白色的
黑色的
蓝色的
深色的
浅色的
眼里的
心里的
古怪的
精灵的
好奇的
欣喜的
灰色的
清晰的
酸臭的
酸楚的
感人的
动人的
惹人的
无聊的
有趣的
有钱的
没钱的
富有的
贫穷的
吝啬的
大眼的
大家的
念你的
用心的
败家的
爱玩的
爱车的
爱家的
早睡的
熬夜的
晚睡的
愤怒的
减肥的
温暖的
冰冷的
慈祥的
开车的
打车的
骑车的
跑步的
走路的
运动的
游泳的
卖萌的
欢乐的
紧张的
刚毅的
绅士的
健身的
乐观的
吹牛的
酷酷的
美美的
愉快的
内向的
外向的
看书的
听歌的
弹琴的
画画的
跳舞的
爬山的
滑雪的
登山的
攀岩的
清新的
业余的
秀美的
可怜的
过时的
进步的
安心的
礼貌的
高深的
主要的
一定的
礼貌的
高深的
夕阳的
高远的
亲爱的
聪明的
无知的
简朴的
英俊的
干净的
细心的
俊俏的
努力的
耐心的
奶心的
甜甜的
香香的
柔软的
STR;
        $result = explode("\n", $str);
        return $result;
    }

    /**
     * @Info 名词列
     * @return false|string[]
     */
    private function readNoun()
    {
        $str = <<<STR
加菲猫
奥特曼
鲨鱼
机器猫
静香
马里奥
皮克敏
瓦易吉
公主
王子
阴阳人
艾克
沃鲁夫
吃豆人
乌尔斯
神威
马尔斯
胖虎
小夫
阿欢
铃铃鹿
南瓜王
梦美
里克
狸克
豆狸
西施慧
蛋叔叔
电叔叔
KK
悠悠
优优
呦呦
幽幽
福达
傅达
傅珂
雪蛤
雪哥
巴猎
娟儿
绵儿
麻儿
洛兰
蹦蹦
陌陌
茉莉
曹迈
阿兰
阿德
阿黛尔
埃德尔
麦麦
莎莉
小健
爱哭鬼
玛丽莲
阿泥
肉肉
毛利时
咚比
飞卡
阿邦
阿宝
阿包
阿保
阿彪
阿笨
阿城
阿芙
阿甘
阿郎
阿妹
阿潘
阿斯
阿田
奥利
奥莉
巴克斯
安索尼
安妮
班长
校花
团支书
鲍勃
百合
芭芭拉
保罗
贝果
贝拉
比利
冰冰
冰沙
博士
老师
彩星
草莓
苹果
茶茶
茶茶丸
茶玩玩
丹丹
迪迪
杜美
尔光
法兰克
肥甘
峰峰
古烈
光威
贵妃
果果
海威
黑马
胡拉拉
华尔兹
吉吉
嘉敏
夏目
米老鼠
唐老鸭
汤姆
杰瑞
乔巴
葫芦娃
蓝精灵
海王子
圣斗士
卡比
塞尔达
宝可梦
皮卡丘
高飞
黛丝
米妮
米尼
狮子王
辛巴
皮克斯
怪兽
艾莎
仙蒂
奥利儿
爱丽丝
苏菲亚
安娜
贝儿
爱洛
男神
女生
明明
小丽
聪聪
欧欧
雪雪
丽丽
莉莉
呢喃
绵绵羊
蘑菇头
沫沫
墨墨
恨桃
依秋
依波
香巧
紫萱
涵易
忆之
幻巧
美倩
安寒
白亦
惜玉
碧春
怜雪
听南
念蕾
紫夏
凌旋
芷梦
凌寒
梦竹
千凡
丹蓉
慧贞
思菱
平卉
笑柳
雪卉
南蓉
谷梦
巧兰
绿蝶
飞荷
佳蕊
芷荷
怀瑶
慕易
若芹
紫安
曼冬
寻巧
雅昕
尔槐
以旋
初夏
依丝
怜南
傲菡
谷蕊
笑槐
飞兰
笑卉
迎荷
佳音
梦君
妙绿
觅雪
寒安
沛凝
白容
乐蓉
映安
依云
映冬
凡雁
梦秋
梦凡
秋巧
若云
元容
怀蕾
灵寒
天薇
翠安
乐琴
宛南
怀蕊
白风
访波
亦凝
易绿
夜南
曼凡
亦巧
青易
冰真
白萱
友安
海之
小蕊
又琴
天风
若松
盼菡
秋荷
香彤
语梦
惜蕊
迎彤
沛白
雁彬
易蓉
雪晴
诗珊
春冬
晴钰
冰绿
半梅
笑容
沛凝
映秋
盼烟
晓凡
涵雁
问凝
冬萱
晓山
雁蓉
梦蕊
山菡
南莲
飞双
凝丝
思萱
怀梦
雨梅
冷霜
向松
迎丝
迎梅
雅彤
香薇
以山
碧萱
寒云
向南
书雁
怀薇
思菱
忆文
翠巧
书文
若山
向秋
凡白
绮烟
从蕾
天曼
又亦
从语
绮彤
之玉
凡梅
依琴
沛槐
又槐
元绿
安珊
夏之
易槐
宛亦
白翠
丹云
问寒
易文
傲易
青旋
思真
雨珍
幻丝
代梅
盼曼
妙之
半双
若翠
初兰
惜萍
初之
宛丝
寄南
小萍
静珊
千风
天蓉
雅青
寄文
涵菱
香波
青亦
元菱
翠彤
春海
惜珊
向薇
冬灵
惜芹
凌青
谷芹
雁桃
映雁
书兰
盼香
梅致
寄风
芳荷
绮晴
映之
醉波
幻莲
晓昕
傲柔
寄容
以珊
紫雪
芷容
书琴
美伊
涵阳
怀寒
易云
代秋
惜梦
宇涵
谷槐
怀莲
英莲
芷卉
向彤
新巧
语海
灵珊
凝丹
小蕾
迎夏
慕卉
飞珍
冰夏
亦竹
飞莲
秋月
元蝶
春蕾
怀绿
尔容
小玉
幼南
凡梦
碧菡
初晴
宛秋
傲旋
新之
凡儿
夏真
静枫
芝萱
恨蕊
乐双
念薇
靖雁
菊颂
丹蝶
元瑶
冰蝶
念波
迎翠
海瑶
乐萱
凌兰
曼岚
若枫
傲薇
雅芝
乐蕊
秋灵
凤娇
觅云
依伊
恨山
从寒
忆香
香菱
静曼
青寒
笑天
涵蕾
元柏
代萱
紫真
千青
雪珍
寄琴
绿蕊
荷柳
诗翠
念瑶
兰楠
曼彤
怀曼
香巧
采蓝
芷天
尔曼
巧蕊
媛楚
凝湘
梦媛
韶丹
云慧
湘涵
怡紫
凝羽
琳伶
柔雅
芸萱
彤新
颍丝
宛艺
欣楚
颖春
怡语
佳元
欣缘
颖一
慧嘉
艺蔓
曦娇
媚彤
绮雨
懿梦
靖曼
珍寄
菱莺
旻敏
瑾泉
晓岚
蕴齐
馨静
柔婉
丽愫
心曼
菱曦
听洛
含风
婉梓
采馨
芷熙
贞文
玲慧
丽琀
歆希
云璐
贞妍
语晓
蔓梦
青赫
文南
铭敏
燕佑
聆玉
婉慧
莺昕
琳瑷
薇曼
骞纱
祺朵
畅曦
丽愫
筠枫
阳雪
雅奇
仪梦
宇秋
欣凡
倚家
元莲
松秋
瑾彤
柚如
琳静
敏思
如瑞
曼铃
笑敏
雅玮
嘉熙
瑛傲
惠涵
沛永
熙祎
琦惠
倪芯
雅小
园涵
欣冰
意岚
雨淑
倩真
碧莉
娅丹
霏怡
小妙
凝云
夏雅
映婕
欢妍
曼铭
凌曦
谷凝
悦雪
艺淳
筱萱
湘美
宛伊
阳云
愫媛
馨娴
熙嘉
瞳姝
镟寒
苇雁
佳畅
梦泽
夏馨
一雯
倪怀
岚琳
博筠
侨伊
檀珍
雨琳
梅熹
雅诗
芯融
香怡
菡芝
芷尔
慧晴
佩惠
恨萍
飘纯
婧颖
清思
雪洛
晨欣
然希
佩卿
慧琳
忆平
然梅
儿嘉
轩香
惜婷
云悦
铭素
初嘉
玥忆
岚海
尚天
琪怡
雁语
漫维
菡雁
钰翠
凝如
萱春
宜涵
流蕾
澜烟
惠筱
语琪
彤娜
悠舒
宣芳
嫣英
筱璐
语莲
桑涵
妙熙
梅彦
婷锦
子初
宁旻
雪怡
思慧
郗欣
和巧
聪汶
菁雅
婵曦
颖子
秀俊
媛瑜
彤蕾
欣娟
平怡
STR;
        $result = explode("\n", $str);
        return $result;
    }

    /**
     * @Info emoji 列
     * @return string
     */
    private function readHtmlData()
    {
        return <<<STR
<div class="field-items">
    <div class="field-item even" property="content:encoded">
        <script type="34d6ac4aad572364e0578bbc-text/javascript"
                src="/sites/all/themes/emoji_2020/js/jquery-2.1.4.min.js"></script>
        <script type="34d6ac4aad572364e0578bbc-text/javascript"
                src="/sites/all/themes/emoji_2020/js/clipboard.min.js"></script>
        <script type="34d6ac4aad572364e0578bbc-text/javascript">
			//列表页copy专用的js
			function emoji_copyed() {
				$(".emoji_symbol").click(function() {
					$(".emoji_symbol").removeClass("active");
					$(this).addClass("active");
				});
			}
			//Clipboard
			function emoji_Clipboard() {
			  new ClipboardJS(".emoji_symbol");
			}
			// 启动所有插
			$(function(){
			   emoji_copyed();
			   emoji_Clipboard();
			});


                                            </script>
        <div class="emoji_card_list pages">
            <div class="emoji_card_content px-4 py-3"><p class="mb-0 small">
                这是所有表情符号的列表，包括10个主要类别，大约100个子类别以及1000多个表情符号。</p></div>
        </div>
        <div class="row emoji_card_nav emoji_card_list no-gutters p-2 y_scroll y_scroll_cols_auto">
            <ul class="row no-gutters mb-0">
                <li class="card col-auto"><p> 分类：</p></li>
                <li class="card col-auto"><a href='#categories-A'><span
                        class="emoji_font line">😂</span>笑脸和情感</a></li>
                <li class="card col-auto"><a href='#categories-B'><span
                        class="emoji_font line">👌</span>人类和身体</a></li>
                <li class="card col-auto"><a href='#categories-C'><span
                        class="emoji_font line">🏼</span>肤色和发型</a></li>
                <li class="card col-auto"><a href='#categories-D'><span
                        class="emoji_font line">🐵</span>动物和自然</a></li>
                <li class="card col-auto"><a href='#categories-E'><span
                        class="emoji_font line">🍓</span>食物和饮料</a></li>
                <li class="card col-auto"><a href='#categories-F'><span
                        class="emoji_font line">🚌</span>旅行和地点</a></li>
                <li class="card col-auto"><a href='#categories-G'><span
                        class="emoji_font line">⚽</span>活动</a></li>
                <li class="card col-auto"><a href='#categories-H'><span
                        class="emoji_font line">⌚</span>物品</a></li>
                <li class="card col-auto"><a href='#categories-I'><span
                        class="emoji_font line">🛑</span>符号</a></li>
                <li class="card col-auto"><a href='#categories-J'><span
                        class="emoji_font line">🏁</span>旗帜</a></li>
            </ul>
        </div>
        <div class="emoji_card_list">
            <h2><a href='/zh-hans/categories/A'><span
                    class="emoji_font line">😂</span>笑脸和情感<sup>156</sup></a><i
                    id='categories-A'></i></h2>
            <h3><a href='/zh-hans/sub-categories/A1'><span class="emoji_font line">😄</span>笑脸<sup>13</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%80'
                           class='emoji_font'>😀</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%80'
                           class='emoji_name truncate'>嘿嘿</a>
                        <a class="emoji_symbol" data-clipboard-text="😀">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%81'
                           class='emoji_font'>😁</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%81'
                           class='emoji_name truncate'>嘻嘻</a>
                        <a class="emoji_symbol" data-clipboard-text="😁">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%82'
                           class='emoji_font'>😂</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%82'
                           class='emoji_name truncate'>笑哭了</a>
                        <a class="emoji_symbol" data-clipboard-text="😂">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%83'
                           class='emoji_font'>😃</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%83'
                           class='emoji_name truncate'>哈哈</a>
                        <a class="emoji_symbol" data-clipboard-text="😃">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%84'
                           class='emoji_font'>😄</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%84'
                           class='emoji_name truncate'>大笑</a>
                        <a class="emoji_symbol" data-clipboard-text="😄">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%85'
                           class='emoji_font'>😅</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%85'
                           class='emoji_name truncate'>苦笑</a>
                        <a class="emoji_symbol" data-clipboard-text="😅">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%86'
                           class='emoji_font'>😆</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%86'
                           class='emoji_name truncate'>斜眼笑</a>
                        <a class="emoji_symbol" data-clipboard-text="😆">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%87'
                           class='emoji_font'>😇</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%87'
                           class='emoji_name truncate'>微笑天使</a>
                        <a class="emoji_symbol" data-clipboard-text="😇">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%89'
                           class='emoji_font'>😉</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%89'
                           class='emoji_name truncate'>眨眼</a>
                        <a class="emoji_symbol" data-clipboard-text="😉">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%8A'
                           class='emoji_font'>😊</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%8A'
                           class='emoji_name truncate'>羞涩微笑</a>
                        <a class="emoji_symbol" data-clipboard-text="😊">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%99%82'
                           class='emoji_font'>🙂</a>
                        <a href='/zh-hans/emoji/%F0%9F%99%82'
                           class='emoji_name truncate'>呵呵</a>
                        <a class="emoji_symbol" data-clipboard-text="🙂">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%99%83'
                           class='emoji_font'>🙃</a>
                        <a href='/zh-hans/emoji/%F0%9F%99%83'
                           class='emoji_name truncate'>倒脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🙃">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A3'
                           class='emoji_font'>🤣</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A3'
                           class='emoji_name truncate'>笑得满地打滚</a>
                        <a class="emoji_symbol" data-clipboard-text="🤣">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A2'><span class="emoji_font line">😍</span>表情脸<sup>9</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%98%BA' class='emoji_font'>☺</a>
                        <a href='/zh-hans/emoji/%E2%98%BA'
                           class='emoji_name truncate'>微笑</a>
                        <a class="emoji_symbol" data-clipboard-text="☺">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%8D'
                           class='emoji_font'>😍</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%8D'
                           class='emoji_name truncate'>花痴</a>
                        <a class="emoji_symbol" data-clipboard-text="😍">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%97'
                           class='emoji_font'>😗</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%97'
                           class='emoji_name truncate'>亲亲</a>
                        <a class="emoji_symbol" data-clipboard-text="😗">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%98'
                           class='emoji_font'>😘</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%98'
                           class='emoji_name truncate'>飞吻</a>
                        <a class="emoji_symbol" data-clipboard-text="😘">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%99'
                           class='emoji_font'>😙</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%99'
                           class='emoji_name truncate'>微笑亲亲</a>
                        <a class="emoji_symbol" data-clipboard-text="😙">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%9A'
                           class='emoji_font'>😚</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%9A'
                           class='emoji_name truncate'>羞涩亲亲</a>
                        <a class="emoji_symbol" data-clipboard-text="😚">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A9'
                           class='emoji_font'>🤩</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A9'
                           class='emoji_name truncate'>好崇拜哦</a>
                        <a class="emoji_symbol" data-clipboard-text="🤩">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%B0'
                           class='emoji_font'>🥰</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%B0'
                           class='emoji_name truncate'>喜笑颜开</a>
                        <a class="emoji_symbol" data-clipboard-text="🥰">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%B2'
                           class='emoji_font'>🥲</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%B2'
                           class='emoji_name truncate'>含泪的笑脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🥲">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A3'><span class="emoji_font line">😛</span>吐舌脸<sup>6</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%8B'
                           class='emoji_font'>😋</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%8B'
                           class='emoji_name truncate'>好吃</a>
                        <a class="emoji_symbol" data-clipboard-text="😋">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%9B'
                           class='emoji_font'>😛</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%9B'
                           class='emoji_name truncate'>吐舌</a>
                        <a class="emoji_symbol" data-clipboard-text="😛">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%9C'
                           class='emoji_font'>😜</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%9C'
                           class='emoji_name truncate'>单眼吐舌</a>
                        <a class="emoji_symbol" data-clipboard-text="😜">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%9D'
                           class='emoji_font'>😝</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%9D'
                           class='emoji_name truncate'>眯眼吐舌</a>
                        <a class="emoji_symbol" data-clipboard-text="😝">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%91'
                           class='emoji_font'>🤑</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%91'
                           class='emoji_name truncate'>发财</a>
                        <a class="emoji_symbol" data-clipboard-text="🤑">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%AA'
                           class='emoji_font'>🤪</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%AA'
                           class='emoji_name truncate'>滑稽</a>
                        <a class="emoji_symbol" data-clipboard-text="🤪">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A4'><span class="emoji_font line">🤔</span>帯手脸<sup>4</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%94'
                           class='emoji_font'>🤔</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%94'
                           class='emoji_name truncate'>想一想</a>
                        <a class="emoji_symbol" data-clipboard-text="🤔">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%97'
                           class='emoji_font'>🤗</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%97'
                           class='emoji_name truncate'>抱抱</a>
                        <a class="emoji_symbol" data-clipboard-text="🤗">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%AB'
                           class='emoji_font'>🤫</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%AB'
                           class='emoji_name truncate'>安静的脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🤫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%AD'
                           class='emoji_font'>🤭</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%AD'
                           class='emoji_name truncate'>不说</a>
                        <a class="emoji_symbol" data-clipboard-text="🤭">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A5'><span class="emoji_font line">🤐</span>中性脸-怀疑脸<sup>12</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%8F'
                           class='emoji_font'>😏</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%8F'
                           class='emoji_name truncate'>得意</a>
                        <a class="emoji_symbol" data-clipboard-text="😏">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%90'
                           class='emoji_font'>😐</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%90'
                           class='emoji_name truncate'>冷漠</a>
                        <a class="emoji_symbol" data-clipboard-text="😐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%91'
                           class='emoji_font'>😑</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%91'
                           class='emoji_name truncate'>无语</a>
                        <a class="emoji_symbol" data-clipboard-text="😑">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%92'
                           class='emoji_font'>😒</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%92'
                           class='emoji_name truncate'>不高兴</a>
                        <a class="emoji_symbol" data-clipboard-text="😒">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%AC'
                           class='emoji_font'>😬</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%AC'
                           class='emoji_name truncate'>龇牙咧嘴</a>
                        <a class="emoji_symbol" data-clipboard-text="😬">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%AE%E2%80%8D%F0%9F%92%A8'
                           class='emoji_font'>😮‍💨</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%AE%E2%80%8D%F0%9F%92%A8'
                           class='emoji_name truncate'>呼气</a>
                        <a class="emoji_symbol" data-clipboard-text="😮‍💨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B6'
                           class='emoji_font'>😶</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B6'
                           class='emoji_name truncate'>沉默</a>
                        <a class="emoji_symbol" data-clipboard-text="😶">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B6%E2%80%8D%F0%9F%8C%AB%EF%B8%8F'
                           class='emoji_font'>😶‍🌫️</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B6%E2%80%8D%F0%9F%8C%AB%EF%B8%8F'
                           class='emoji_name truncate'>迷茫</a>
                        <a class="emoji_symbol" data-clipboard-text="😶‍🌫️">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%99%84'
                           class='emoji_font'>🙄</a>
                        <a href='/zh-hans/emoji/%F0%9F%99%84'
                           class='emoji_name truncate'>翻白眼</a>
                        <a class="emoji_symbol" data-clipboard-text="🙄">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%90'
                           class='emoji_font'>🤐</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%90'
                           class='emoji_name truncate'>闭嘴</a>
                        <a class="emoji_symbol" data-clipboard-text="🤐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A5'
                           class='emoji_font'>🤥</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A5'
                           class='emoji_name truncate'>说谎</a>
                        <a class="emoji_symbol" data-clipboard-text="🤥">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A8'
                           class='emoji_font'>🤨</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A8'
                           class='emoji_name truncate'>挑眉</a>
                        <a class="emoji_symbol" data-clipboard-text="🤨">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A6'><span class="emoji_font line">😴</span>睡脸<sup>5</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%8C'
                           class='emoji_font'>😌</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%8C'
                           class='emoji_name truncate'>松了口气</a>
                        <a class="emoji_symbol" data-clipboard-text="😌">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%94'
                           class='emoji_font'>😔</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%94'
                           class='emoji_name truncate'>沉思</a>
                        <a class="emoji_symbol" data-clipboard-text="😔">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%AA'
                           class='emoji_font'>😪</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%AA'
                           class='emoji_name truncate'>困</a>
                        <a class="emoji_symbol" data-clipboard-text="😪">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B4'
                           class='emoji_font'>😴</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B4'
                           class='emoji_name truncate'>睡着了</a>
                        <a class="emoji_symbol" data-clipboard-text="😴">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A4'
                           class='emoji_font'>🤤</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A4'
                           class='emoji_name truncate'>流口水</a>
                        <a class="emoji_symbol" data-clipboard-text="🤤">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A7'><span class="emoji_font line">🤧</span>病脸<sup>12</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B5'
                           class='emoji_font'>😵</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B5'
                           class='emoji_name truncate'>晕头转向</a>
                        <a class="emoji_symbol" data-clipboard-text="😵">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B5%E2%80%8D%F0%9F%92%AB'
                           class='emoji_font'>😵‍💫</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B5%E2%80%8D%F0%9F%92%AB'
                           class='emoji_name truncate'>晕</a>
                        <a class="emoji_symbol" data-clipboard-text="😵‍💫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B7'
                           class='emoji_font'>😷</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B7'
                           class='emoji_name truncate'>感冒</a>
                        <a class="emoji_symbol" data-clipboard-text="😷">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%92'
                           class='emoji_font'>🤒</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%92'
                           class='emoji_name truncate'>发烧</a>
                        <a class="emoji_symbol" data-clipboard-text="🤒">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%95'
                           class='emoji_font'>🤕</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%95'
                           class='emoji_name truncate'>受伤</a>
                        <a class="emoji_symbol" data-clipboard-text="🤕">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A2'
                           class='emoji_font'>🤢</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A2'
                           class='emoji_name truncate'>恶心</a>
                        <a class="emoji_symbol" data-clipboard-text="🤢">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A7'
                           class='emoji_font'>🤧</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A7'
                           class='emoji_name truncate'>打喷嚏</a>
                        <a class="emoji_symbol" data-clipboard-text="🤧">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%AE'
                           class='emoji_font'>🤮</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%AE'
                           class='emoji_name truncate'>呕吐</a>
                        <a class="emoji_symbol" data-clipboard-text="🤮">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%AF'
                           class='emoji_font'>🤯</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%AF'
                           class='emoji_name truncate'>爆炸头</a>
                        <a class="emoji_symbol" data-clipboard-text="🤯">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%B4'
                           class='emoji_font'>🥴</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%B4'
                           class='emoji_name truncate'>头昏眼花</a>
                        <a class="emoji_symbol" data-clipboard-text="🥴">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%B5'
                           class='emoji_font'>🥵</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%B5'
                           class='emoji_name truncate'>脸发烧</a>
                        <a class="emoji_symbol" data-clipboard-text="🥵">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%B6'
                           class='emoji_font'>🥶</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%B6'
                           class='emoji_name truncate'>冷脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🥶">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A8'><span class="emoji_font line">🤠</span>带帽脸<sup>3</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A0'
                           class='emoji_font'>🤠</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A0'
                           class='emoji_name truncate'>牛仔帽脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🤠">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%B3'
                           class='emoji_font'>🥳</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%B3'
                           class='emoji_name truncate'>聚会笑脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🥳">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%B8'
                           class='emoji_font'>🥸</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%B8'
                           class='emoji_name truncate'>伪装的脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🥸">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A9'><span class="emoji_font line">😎</span>眼镜脸<sup>3</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%8E'
                           class='emoji_font'>😎</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%8E'
                           class='emoji_name truncate'>墨镜笑脸</a>
                        <a class="emoji_symbol" data-clipboard-text="😎">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%93'
                           class='emoji_font'>🤓</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%93'
                           class='emoji_name truncate'>书呆子脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🤓">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%90'
                           class='emoji_font'>🧐</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%90'
                           class='emoji_name truncate'>带单片眼镜的脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🧐">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A10'><span class="emoji_font line">😞</span>担忧脸<sup>24</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%98%B9' class='emoji_font'>☹</a>
                        <a href='/zh-hans/emoji/%E2%98%B9'
                           class='emoji_name truncate'>不满</a>
                        <a class="emoji_symbol" data-clipboard-text="☹">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%93'
                           class='emoji_font'>😓</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%93'
                           class='emoji_name truncate'>汗</a>
                        <a class="emoji_symbol" data-clipboard-text="😓">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%95'
                           class='emoji_font'>😕</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%95'
                           class='emoji_name truncate'>困扰</a>
                        <a class="emoji_symbol" data-clipboard-text="😕">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%96'
                           class='emoji_font'>😖</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%96'
                           class='emoji_name truncate'>困惑</a>
                        <a class="emoji_symbol" data-clipboard-text="😖">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%9E'
                           class='emoji_font'>😞</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%9E'
                           class='emoji_name truncate'>失望</a>
                        <a class="emoji_symbol" data-clipboard-text="😞">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%9F'
                           class='emoji_font'>😟</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%9F'
                           class='emoji_name truncate'>担心</a>
                        <a class="emoji_symbol" data-clipboard-text="😟">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A2'
                           class='emoji_font'>😢</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A2'
                           class='emoji_name truncate'>哭</a>
                        <a class="emoji_symbol" data-clipboard-text="😢">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A3'
                           class='emoji_font'>😣</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A3'
                           class='emoji_name truncate'>痛苦</a>
                        <a class="emoji_symbol" data-clipboard-text="😣">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A5'
                           class='emoji_font'>😥</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A5'
                           class='emoji_name truncate'>失望但如释重负</a>
                        <a class="emoji_symbol" data-clipboard-text="😥">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A6'
                           class='emoji_font'>😦</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A6'
                           class='emoji_name truncate'>啊</a>
                        <a class="emoji_symbol" data-clipboard-text="😦">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A7'
                           class='emoji_font'>😧</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A7'
                           class='emoji_name truncate'>极度痛苦</a>
                        <a class="emoji_symbol" data-clipboard-text="😧">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A8'
                           class='emoji_font'>😨</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A8'
                           class='emoji_name truncate'>害怕</a>
                        <a class="emoji_symbol" data-clipboard-text="😨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A9'
                           class='emoji_font'>😩</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A9'
                           class='emoji_name truncate'>累死了</a>
                        <a class="emoji_symbol" data-clipboard-text="😩">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%AB'
                           class='emoji_font'>😫</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%AB'
                           class='emoji_name truncate'>累</a>
                        <a class="emoji_symbol" data-clipboard-text="😫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%AD'
                           class='emoji_font'>😭</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%AD'
                           class='emoji_name truncate'>放声大哭</a>
                        <a class="emoji_symbol" data-clipboard-text="😭">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%AE'
                           class='emoji_font'>😮</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%AE'
                           class='emoji_name truncate'>吃惊</a>
                        <a class="emoji_symbol" data-clipboard-text="😮">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%AF'
                           class='emoji_font'>😯</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%AF'
                           class='emoji_name truncate'>缄默</a>
                        <a class="emoji_symbol" data-clipboard-text="😯">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B0'
                           class='emoji_font'>😰</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B0'
                           class='emoji_name truncate'>冷汗</a>
                        <a class="emoji_symbol" data-clipboard-text="😰">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B1'
                           class='emoji_font'>😱</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B1'
                           class='emoji_name truncate'>吓死了</a>
                        <a class="emoji_symbol" data-clipboard-text="😱">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B2'
                           class='emoji_font'>😲</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B2'
                           class='emoji_name truncate'>震惊</a>
                        <a class="emoji_symbol" data-clipboard-text="😲">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B3'
                           class='emoji_font'>😳</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B3'
                           class='emoji_name truncate'>脸红</a>
                        <a class="emoji_symbol" data-clipboard-text="😳">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%99%81'
                           class='emoji_font'>🙁</a>
                        <a href='/zh-hans/emoji/%F0%9F%99%81'
                           class='emoji_name truncate'>微微不满</a>
                        <a class="emoji_symbol" data-clipboard-text="🙁">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%B1'
                           class='emoji_font'>🥱</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%B1'
                           class='emoji_name truncate'>打呵欠</a>
                        <a class="emoji_symbol" data-clipboard-text="🥱">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%BA'
                           class='emoji_font'>🥺</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%BA'
                           class='emoji_name truncate'>恳求的脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🥺">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A11'><span class="emoji_font line">😠</span>消极脸<sup>8</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%98%A0' class='emoji_font'>☠</a>
                        <a href='/zh-hans/emoji/%E2%98%A0'
                           class='emoji_name truncate'>骷髅</a>
                        <a class="emoji_symbol" data-clipboard-text="☠">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%91%BF'
                           class='emoji_font'>👿</a>
                        <a href='/zh-hans/emoji/%F0%9F%91%BF'
                           class='emoji_name truncate'>生气的恶魔</a>
                        <a class="emoji_symbol" data-clipboard-text="👿">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%80'
                           class='emoji_font'>💀</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%80'
                           class='emoji_name truncate'>头骨</a>
                        <a class="emoji_symbol" data-clipboard-text="💀">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%88'
                           class='emoji_font'>😈</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%88'
                           class='emoji_name truncate'>恶魔微笑</a>
                        <a class="emoji_symbol" data-clipboard-text="😈">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A0'
                           class='emoji_font'>😠</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A0'
                           class='emoji_name truncate'>生气</a>
                        <a class="emoji_symbol" data-clipboard-text="😠">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A1'
                           class='emoji_font'>😡</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A1'
                           class='emoji_name truncate'>怒火中烧</a>
                        <a class="emoji_symbol" data-clipboard-text="😡">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%A4'
                           class='emoji_font'>😤</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%A4'
                           class='emoji_name truncate'>傲慢</a>
                        <a class="emoji_symbol" data-clipboard-text="😤">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%AC'
                           class='emoji_font'>🤬</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%AC'
                           class='emoji_name truncate'>嘴上有符号的脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🤬">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A12'><span class="emoji_font line">💩</span>装扮脸<sup>8</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%91%B9'
                           class='emoji_font'>👹</a>
                        <a href='/zh-hans/emoji/%F0%9F%91%B9'
                           class='emoji_name truncate'>食人魔</a>
                        <a class="emoji_symbol" data-clipboard-text="👹">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%91%BA'
                           class='emoji_font'>👺</a>
                        <a href='/zh-hans/emoji/%F0%9F%91%BA'
                           class='emoji_name truncate'>小妖精</a>
                        <a class="emoji_symbol" data-clipboard-text="👺">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%91%BB'
                           class='emoji_font'>👻</a>
                        <a href='/zh-hans/emoji/%F0%9F%91%BB'
                           class='emoji_name truncate'>鬼</a>
                        <a class="emoji_symbol" data-clipboard-text="👻">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%91%BD'
                           class='emoji_font'>👽</a>
                        <a href='/zh-hans/emoji/%F0%9F%91%BD'
                           class='emoji_name truncate'>外星人</a>
                        <a class="emoji_symbol" data-clipboard-text="👽">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%91%BE'
                           class='emoji_font'>👾</a>
                        <a href='/zh-hans/emoji/%F0%9F%91%BE'
                           class='emoji_name truncate'>外星怪物</a>
                        <a class="emoji_symbol" data-clipboard-text="👾">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%A9'
                           class='emoji_font'>💩</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%A9'
                           class='emoji_name truncate'>大便</a>
                        <a class="emoji_symbol" data-clipboard-text="💩">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%96'
                           class='emoji_font'>🤖</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%96'
                           class='emoji_name truncate'>机器人</a>
                        <a class="emoji_symbol" data-clipboard-text="🤖">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%A1'
                           class='emoji_font'>🤡</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%A1'
                           class='emoji_name truncate'>小丑脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🤡">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A13'><span class="emoji_font line">😸</span>猫咪脸<sup>9</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B8'
                           class='emoji_font'>😸</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B8'
                           class='emoji_name truncate'>微笑的猫</a>
                        <a class="emoji_symbol" data-clipboard-text="😸">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%B9'
                           class='emoji_font'>😹</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%B9'
                           class='emoji_name truncate'>笑出眼泪的猫</a>
                        <a class="emoji_symbol" data-clipboard-text="😹">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%BA'
                           class='emoji_font'>😺</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%BA'
                           class='emoji_name truncate'>大笑的猫</a>
                        <a class="emoji_symbol" data-clipboard-text="😺">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%BB'
                           class='emoji_font'>😻</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%BB'
                           class='emoji_name truncate'>花痴的猫</a>
                        <a class="emoji_symbol" data-clipboard-text="😻">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%BC'
                           class='emoji_font'>😼</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%BC'
                           class='emoji_name truncate'>奸笑的猫</a>
                        <a class="emoji_symbol" data-clipboard-text="😼">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%BD'
                           class='emoji_font'>😽</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%BD'
                           class='emoji_name truncate'>亲亲猫</a>
                        <a class="emoji_symbol" data-clipboard-text="😽">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%BE'
                           class='emoji_font'>😾</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%BE'
                           class='emoji_name truncate'>生气的猫</a>
                        <a class="emoji_symbol" data-clipboard-text="😾">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%98%BF'
                           class='emoji_font'>😿</a>
                        <a href='/zh-hans/emoji/%F0%9F%98%BF'
                           class='emoji_name truncate'>哭泣的猫</a>
                        <a class="emoji_symbol" data-clipboard-text="😿">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%99%80'
                           class='emoji_font'>🙀</a>
                        <a href='/zh-hans/emoji/%F0%9F%99%80'
                           class='emoji_name truncate'>疲倦的猫</a>
                        <a class="emoji_symbol" data-clipboard-text="🙀">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A14'><span class="emoji_font line">🙈</span>猴子脸<sup>3</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%99%88'
                           class='emoji_font'>🙈</a>
                        <a href='/zh-hans/emoji/%F0%9F%99%88'
                           class='emoji_name truncate'>非礼勿视</a>
                        <a class="emoji_symbol" data-clipboard-text="🙈">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%99%89'
                           class='emoji_font'>🙉</a>
                        <a href='/zh-hans/emoji/%F0%9F%99%89'
                           class='emoji_name truncate'>非礼勿听</a>
                        <a class="emoji_symbol" data-clipboard-text="🙉">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%99%8A'
                           class='emoji_font'>🙊</a>
                        <a href='/zh-hans/emoji/%F0%9F%99%8A'
                           class='emoji_name truncate'>非礼勿言</a>
                        <a class="emoji_symbol" data-clipboard-text="🙊">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/A15'><span class="emoji_font line">💋</span>情感<sup>37</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%9D%A3' class='emoji_font'>❣</a>
                        <a href='/zh-hans/emoji/%E2%9D%A3'
                           class='emoji_name truncate'>心叹号</a>
                        <a class="emoji_symbol" data-clipboard-text="❣">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%9D%A4' class='emoji_font'>❤</a>
                        <a href='/zh-hans/emoji/%E2%9D%A4'
                           class='emoji_name truncate'>红心</a>
                        <a class="emoji_symbol" data-clipboard-text="❤">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%9D%A4%EF%B8%8F%E2%80%8D%F0%9F%94%A5'
                           class='emoji_font'>❤️‍🔥</a>
                        <a href='/zh-hans/emoji/%E2%9D%A4%EF%B8%8F%E2%80%8D%F0%9F%94%A5'
                           class='emoji_name truncate'>火上之心</a>
                        <a class="emoji_symbol" data-clipboard-text="❤️‍🔥">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%9D%A4%EF%B8%8F%E2%80%8D%F0%9F%A9%B9'
                           class='emoji_font'>❤️‍🩹</a>
                        <a href='/zh-hans/emoji/%E2%9D%A4%EF%B8%8F%E2%80%8D%F0%9F%A9%B9'
                           class='emoji_name truncate'>修复受伤的心灵</a>
                        <a class="emoji_symbol" data-clipboard-text="❤️‍🩹">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%91%81%EF%B8%8F%E2%80%8D%F0%9F%97%A8%EF%B8%8F'
                           class='emoji_font'>👁️‍🗨️</a>
                        <a href='/zh-hans/emoji/%F0%9F%91%81%EF%B8%8F%E2%80%8D%F0%9F%97%A8%EF%B8%8F'
                           class='emoji_name truncate'>讲话泡泡中的眼睛</a>
                        <a class="emoji_symbol" data-clipboard-text="👁️‍🗨️">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%8B'
                           class='emoji_font'>💋</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%8B'
                           class='emoji_name truncate'>唇印</a>
                        <a class="emoji_symbol" data-clipboard-text="💋">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%8C'
                           class='emoji_font'>💌</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%8C'
                           class='emoji_name truncate'>情书</a>
                        <a class="emoji_symbol" data-clipboard-text="💌">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%93'
                           class='emoji_font'>💓</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%93'
                           class='emoji_name truncate'>心跳</a>
                        <a class="emoji_symbol" data-clipboard-text="💓">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%94'
                           class='emoji_font'>💔</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%94'
                           class='emoji_name truncate'>心碎</a>
                        <a class="emoji_symbol" data-clipboard-text="💔">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%95'
                           class='emoji_font'>💕</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%95'
                           class='emoji_name truncate'>两颗心</a>
                        <a class="emoji_symbol" data-clipboard-text="💕">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%96'
                           class='emoji_font'>💖</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%96'
                           class='emoji_name truncate'>闪亮的心</a>
                        <a class="emoji_symbol" data-clipboard-text="💖">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%97'
                           class='emoji_font'>💗</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%97'
                           class='emoji_name truncate'>搏动的心</a>
                        <a class="emoji_symbol" data-clipboard-text="💗">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%98'
                           class='emoji_font'>💘</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%98'
                           class='emoji_name truncate'>心中箭了</a>
                        <a class="emoji_symbol" data-clipboard-text="💘">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%99'
                           class='emoji_font'>💙</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%99'
                           class='emoji_name truncate'>蓝心</a>
                        <a class="emoji_symbol" data-clipboard-text="💙">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%9A'
                           class='emoji_font'>💚</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%9A'
                           class='emoji_name truncate'>绿心</a>
                        <a class="emoji_symbol" data-clipboard-text="💚">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%9B'
                           class='emoji_font'>💛</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%9B'
                           class='emoji_name truncate'>黄心</a>
                        <a class="emoji_symbol" data-clipboard-text="💛">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%9C'
                           class='emoji_font'>💜</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%9C'
                           class='emoji_name truncate'>紫心</a>
                        <a class="emoji_symbol" data-clipboard-text="💜">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%9D'
                           class='emoji_font'>💝</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%9D'
                           class='emoji_name truncate'>系有缎带的心</a>
                        <a class="emoji_symbol" data-clipboard-text="💝">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%9E'
                           class='emoji_font'>💞</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%9E'
                           class='emoji_name truncate'>舞动的心</a>
                        <a class="emoji_symbol" data-clipboard-text="💞">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%9F'
                           class='emoji_font'>💟</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%9F'
                           class='emoji_name truncate'>心型装饰</a>
                        <a class="emoji_symbol" data-clipboard-text="💟">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%A2'
                           class='emoji_font'>💢</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%A2'
                           class='emoji_name truncate'>怒</a>
                        <a class="emoji_symbol" data-clipboard-text="💢">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%A3'
                           class='emoji_font'>💣</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%A3'
                           class='emoji_name truncate'>炸弹</a>
                        <a class="emoji_symbol" data-clipboard-text="💣">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%A4'
                           class='emoji_font'>💤</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%A4'
                           class='emoji_name truncate'>睡着</a>
                        <a class="emoji_symbol" data-clipboard-text="💤">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%A5'
                           class='emoji_font'>💥</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%A5'
                           class='emoji_name truncate'>爆炸</a>
                        <a class="emoji_symbol" data-clipboard-text="💥">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%A6'
                           class='emoji_font'>💦</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%A6'
                           class='emoji_name truncate'>汗滴</a>
                        <a class="emoji_symbol" data-clipboard-text="💦">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%A8'
                           class='emoji_font'>💨</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%A8'
                           class='emoji_name truncate'>尾气</a>
                        <a class="emoji_symbol" data-clipboard-text="💨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%AB'
                           class='emoji_font'>💫</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%AB'
                           class='emoji_name truncate'>头晕</a>
                        <a class="emoji_symbol" data-clipboard-text="💫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%AC'
                           class='emoji_font'>💬</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%AC'
                           class='emoji_name truncate'>话语气泡</a>
                        <a class="emoji_symbol" data-clipboard-text="💬">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%AD'
                           class='emoji_font'>💭</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%AD'
                           class='emoji_name truncate'>内心活动气泡</a>
                        <a class="emoji_symbol" data-clipboard-text="💭">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%AF'
                           class='emoji_font'>💯</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%AF'
                           class='emoji_name truncate'>一百分</a>
                        <a class="emoji_symbol" data-clipboard-text="💯">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%95%B3'
                           class='emoji_font'>🕳</a>
                        <a href='/zh-hans/emoji/%F0%9F%95%B3'
                           class='emoji_name truncate'>洞</a>
                        <a class="emoji_symbol" data-clipboard-text="🕳">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%96%A4'
                           class='emoji_font'>🖤</a>
                        <a href='/zh-hans/emoji/%F0%9F%96%A4'
                           class='emoji_name truncate'>黑心</a>
                        <a class="emoji_symbol" data-clipboard-text="🖤">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%97%A8'
                           class='emoji_font'>🗨</a>
                        <a href='/zh-hans/emoji/%F0%9F%97%A8'
                           class='emoji_name truncate'>朝左的话语气泡</a>
                        <a class="emoji_symbol" data-clipboard-text="🗨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%97%AF'
                           class='emoji_font'>🗯</a>
                        <a href='/zh-hans/emoji/%F0%9F%97%AF'
                           class='emoji_name truncate'>愤怒话语气泡</a>
                        <a class="emoji_symbol" data-clipboard-text="🗯">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%8D'
                           class='emoji_font'>🤍</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%8D'
                           class='emoji_name truncate'>白心</a>
                        <a class="emoji_symbol" data-clipboard-text="🤍">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A4%8E'
                           class='emoji_font'>🤎</a>
                        <a href='/zh-hans/emoji/%F0%9F%A4%8E'
                           class='emoji_name truncate'>棕心</a>
                        <a class="emoji_symbol" data-clipboard-text="🤎">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%A1'
                           class='emoji_font'>🧡</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%A1'
                           class='emoji_name truncate'>橙心</a>
                        <a class="emoji_symbol" data-clipboard-text="🧡">复制</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%8E%85'
                       class='emoji_font'>🎅</a>
                    <a href='/zh-hans/emoji/%F0%9F%8E%85'
                       class='emoji_name truncate'>圣诞老人</a>
                    <a class="emoji_symbol" data-clipboard-text="🎅">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%91%BC'
                       class='emoji_font'>👼</a>
                    <a href='/zh-hans/emoji/%F0%9F%91%BC'
                       class='emoji_name truncate'>小天使</a>
                    <a class="emoji_symbol" data-clipboard-text="👼">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A4%B6'
                       class='emoji_font'>🤶</a>
                    <a href='/zh-hans/emoji/%F0%9F%A4%B6'
                       class='emoji_name truncate'>圣诞奶奶</a>
                    <a class="emoji_symbol" data-clipboard-text="🤶">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A6%B8'
                       class='emoji_font'>🦸</a>
                    <a href='/zh-hans/emoji/%F0%9F%A6%B8'
                       class='emoji_name truncate'>超级英雄</a>
                    <a class="emoji_symbol" data-clipboard-text="🦸">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A6%B8%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🦸‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A6%B8%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>女超级英雄</a>
                    <a class="emoji_symbol" data-clipboard-text="🦸‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A6%B8%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🦸‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A6%B8%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男超级英雄</a>
                    <a class="emoji_symbol" data-clipboard-text="🦸‍♂️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A6%B9'
                       class='emoji_font'>🦹</a>
                    <a href='/zh-hans/emoji/%F0%9F%A6%B9'
                       class='emoji_name truncate'>超级大坏蛋</a>
                    <a class="emoji_symbol" data-clipboard-text="🦹">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A6%B9%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🦹‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A6%B9%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>女超级反派</a>
                    <a class="emoji_symbol" data-clipboard-text="🦹‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A6%B9%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🦹‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A6%B9%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男超级反派</a>
                    <a class="emoji_symbol" data-clipboard-text="🦹‍♂️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%91%E2%80%8D%F0%9F%8E%84'
                       class='emoji_font'>🧑‍🎄</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%91%E2%80%8D%F0%9F%8E%84'
                       class='emoji_name truncate'>圣诞人</a>
                    <a class="emoji_symbol" data-clipboard-text="🧑‍🎄">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%99'
                       class='emoji_font'>🧙</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%99'
                       class='emoji_name truncate'>法师</a>
                    <a class="emoji_symbol" data-clipboard-text="🧙">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%99%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🧙‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%99%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>女法师</a>
                    <a class="emoji_symbol" data-clipboard-text="🧙‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%99%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🧙‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%99%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男法师</a>
                    <a class="emoji_symbol" data-clipboard-text="🧙‍♂️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9A'
                       class='emoji_font'>🧚</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9A'
                       class='emoji_name truncate'>精灵</a>
                    <a class="emoji_symbol" data-clipboard-text="🧚">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9A%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🧚‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9A%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>女仙子</a>
                    <a class="emoji_symbol" data-clipboard-text="🧚‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9A%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🧚‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9A%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男仙子</a>
                    <a class="emoji_symbol" data-clipboard-text="🧚‍♂️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9B'
                       class='emoji_font'>🧛</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9B'
                       class='emoji_name truncate'>吸血鬼</a>
                    <a class="emoji_symbol" data-clipboard-text="🧛">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9B%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🧛‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9B%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>女吸血鬼</a>
                    <a class="emoji_symbol" data-clipboard-text="🧛‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9B%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🧛‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9B%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男吸血鬼</a>
                    <a class="emoji_symbol" data-clipboard-text="🧛‍♂️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9C'
                       class='emoji_font'>🧜</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9C'
                       class='emoji_name truncate'>人鱼</a>
                    <a class="emoji_symbol" data-clipboard-text="🧜">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9C%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🧜‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9C%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>美人鱼</a>
                    <a class="emoji_symbol" data-clipboard-text="🧜‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9C%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🧜‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9C%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男人鱼</a>
                    <a class="emoji_symbol" data-clipboard-text="🧜‍♂️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9D'
                       class='emoji_font'>🧝</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9D'
                       class='emoji_name truncate'>小精灵</a>
                    <a class="emoji_symbol" data-clipboard-text="🧝">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9D%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🧝‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9D%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>女精灵</a>
                    <a class="emoji_symbol" data-clipboard-text="🧝‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9D%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🧝‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9D%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男精灵</a>
                    <a class="emoji_symbol" data-clipboard-text="🧝‍♂️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9E'
                       class='emoji_font'>🧞</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9E'
                       class='emoji_name truncate'>妖怪</a>
                    <a class="emoji_symbol" data-clipboard-text="🧞">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9E%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🧞‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9E%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>女人精灵</a>
                    <a class="emoji_symbol" data-clipboard-text="🧞‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9E%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🧞‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9E%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男人精灵</a>
                    <a class="emoji_symbol" data-clipboard-text="🧞‍♂️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9F'
                       class='emoji_font'>🧟</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9F'
                       class='emoji_name truncate'>僵尸</a>
                    <a class="emoji_symbol" data-clipboard-text="🧟">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9F%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_font'>🧟‍♀️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9F%E2%80%8D%E2%99%80%EF%B8%8F'
                       class='emoji_name truncate'>女人僵尸</a>
                    <a class="emoji_symbol" data-clipboard-text="🧟‍♀️">复制</a>
                </div>
            </div>
            <div class="col">
                <div class="emoji_card">
                    <a href='/zh-hans/emoji/%F0%9F%A7%9F%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_font'>🧟‍♂️</a>
                    <a href='/zh-hans/emoji/%F0%9F%A7%9F%E2%80%8D%E2%99%82%EF%B8%8F'
                       class='emoji_name truncate'>男人僵尸</a>
                    <a class="emoji_symbol" data-clipboard-text="🧟‍♂️">复制</a>
                </div>
            </div>
        </div>

        <div class="emoji_card_list">
            <h2><a href='/zh-hans/categories/D'><span
                    class="emoji_font line">🐵</span>动物和自然<sup>140</sup></a><i
                    id='categories-D'></i></h2>
            <h3><a href='/zh-hans/sub-categories/D1'><span class="emoji_font line">🐀</span>哺乳动物<sup>64</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%80'
                           class='emoji_font'>🐀</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%80'
                           class='emoji_name truncate'>耗子</a>
                        <a class="emoji_symbol" data-clipboard-text="🐀">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%81'
                           class='emoji_font'>🐁</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%81'
                           class='emoji_name truncate'>老鼠</a>
                        <a class="emoji_symbol" data-clipboard-text="🐁">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%82'
                           class='emoji_font'>🐂</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%82'
                           class='emoji_name truncate'>公牛</a>
                        <a class="emoji_symbol" data-clipboard-text="🐂">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%83'
                           class='emoji_font'>🐃</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%83'
                           class='emoji_name truncate'>水牛</a>
                        <a class="emoji_symbol" data-clipboard-text="🐃">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%84'
                           class='emoji_font'>🐄</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%84'
                           class='emoji_name truncate'>奶牛</a>
                        <a class="emoji_symbol" data-clipboard-text="🐄">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%85'
                           class='emoji_font'>🐅</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%85'
                           class='emoji_name truncate'>老虎</a>
                        <a class="emoji_symbol" data-clipboard-text="🐅">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%86'
                           class='emoji_font'>🐆</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%86'
                           class='emoji_name truncate'>豹子</a>
                        <a class="emoji_symbol" data-clipboard-text="🐆">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%87'
                           class='emoji_font'>🐇</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%87'
                           class='emoji_name truncate'>兔子</a>
                        <a class="emoji_symbol" data-clipboard-text="🐇">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%88'
                           class='emoji_font'>🐈</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%88'
                           class='emoji_name truncate'>猫</a>
                        <a class="emoji_symbol" data-clipboard-text="🐈">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%88%E2%80%8D%E2%AC%9B'
                           class='emoji_font'>🐈‍⬛</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%88%E2%80%8D%E2%AC%9B'
                           class='emoji_name truncate'>黑猫</a>
                        <a class="emoji_symbol" data-clipboard-text="🐈‍⬛">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%8E'
                           class='emoji_font'>🐎</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%8E'
                           class='emoji_name truncate'>马</a>
                        <a class="emoji_symbol" data-clipboard-text="🐎">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%8F'
                           class='emoji_font'>🐏</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%8F'
                           class='emoji_name truncate'>公羊</a>
                        <a class="emoji_symbol" data-clipboard-text="🐏">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%90'
                           class='emoji_font'>🐐</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%90'
                           class='emoji_name truncate'>山羊</a>
                        <a class="emoji_symbol" data-clipboard-text="🐐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%91'
                           class='emoji_font'>🐑</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%91'
                           class='emoji_name truncate'>母羊</a>
                        <a class="emoji_symbol" data-clipboard-text="🐑">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%92'
                           class='emoji_font'>🐒</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%92'
                           class='emoji_name truncate'>猴子</a>
                        <a class="emoji_symbol" data-clipboard-text="🐒">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%95'
                           class='emoji_font'>🐕</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%95'
                           class='emoji_name truncate'>狗</a>
                        <a class="emoji_symbol" data-clipboard-text="🐕">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%95%E2%80%8D%F0%9F%A6%BA'
                           class='emoji_font'>🐕‍🦺</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%95%E2%80%8D%F0%9F%A6%BA'
                           class='emoji_name truncate'>服务犬</a>
                        <a class="emoji_symbol" data-clipboard-text="🐕‍🦺">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%96'
                           class='emoji_font'>🐖</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%96'
                           class='emoji_name truncate'>猪</a>
                        <a class="emoji_symbol" data-clipboard-text="🐖">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%97'
                           class='emoji_font'>🐗</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%97'
                           class='emoji_name truncate'>野猪</a>
                        <a class="emoji_symbol" data-clipboard-text="🐗">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%98'
                           class='emoji_font'>🐘</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%98'
                           class='emoji_name truncate'>大象</a>
                        <a class="emoji_symbol" data-clipboard-text="🐘">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A8'
                           class='emoji_font'>🐨</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A8'
                           class='emoji_name truncate'>考拉</a>
                        <a class="emoji_symbol" data-clipboard-text="🐨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A9'
                           class='emoji_font'>🐩</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A9'
                           class='emoji_name truncate'>贵宾犬</a>
                        <a class="emoji_symbol" data-clipboard-text="🐩">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%AA'
                           class='emoji_font'>🐪</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%AA'
                           class='emoji_name truncate'>骆驼</a>
                        <a class="emoji_symbol" data-clipboard-text="🐪">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%AB'
                           class='emoji_font'>🐫</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%AB'
                           class='emoji_name truncate'>双峰骆驼</a>
                        <a class="emoji_symbol" data-clipboard-text="🐫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%AD'
                           class='emoji_font'>🐭</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%AD'
                           class='emoji_name truncate'>老鼠头</a>
                        <a class="emoji_symbol" data-clipboard-text="🐭">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%AE'
                           class='emoji_font'>🐮</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%AE'
                           class='emoji_name truncate'>奶牛头</a>
                        <a class="emoji_symbol" data-clipboard-text="🐮">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%AF'
                           class='emoji_font'>🐯</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%AF'
                           class='emoji_name truncate'>老虎头</a>
                        <a class="emoji_symbol" data-clipboard-text="🐯">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B0'
                           class='emoji_font'>🐰</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B0'
                           class='emoji_name truncate'>兔子头</a>
                        <a class="emoji_symbol" data-clipboard-text="🐰">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B1'
                           class='emoji_font'>🐱</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B1'
                           class='emoji_name truncate'>猫脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🐱">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B4'
                           class='emoji_font'>🐴</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B4'
                           class='emoji_name truncate'>马头</a>
                        <a class="emoji_symbol" data-clipboard-text="🐴">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B5'
                           class='emoji_font'>🐵</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B5'
                           class='emoji_name truncate'>猴头</a>
                        <a class="emoji_symbol" data-clipboard-text="🐵">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B6'
                           class='emoji_font'>🐶</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B6'
                           class='emoji_name truncate'>狗脸</a>
                        <a class="emoji_symbol" data-clipboard-text="🐶">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B7'
                           class='emoji_font'>🐷</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B7'
                           class='emoji_name truncate'>猪头</a>
                        <a class="emoji_symbol" data-clipboard-text="🐷">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B9'
                           class='emoji_font'>🐹</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B9'
                           class='emoji_name truncate'>仓鼠</a>
                        <a class="emoji_symbol" data-clipboard-text="🐹">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%BA'
                           class='emoji_font'>🐺</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%BA'
                           class='emoji_name truncate'>狼</a>
                        <a class="emoji_symbol" data-clipboard-text="🐺">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%BB'
                           class='emoji_font'>🐻</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%BB'
                           class='emoji_name truncate'>熊</a>
                        <a class="emoji_symbol" data-clipboard-text="🐻">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%BB%E2%80%8D%E2%9D%84%EF%B8%8F'
                           class='emoji_font'>🐻‍❄️</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%BB%E2%80%8D%E2%9D%84%EF%B8%8F'
                           class='emoji_name truncate'>北极熊</a>
                        <a class="emoji_symbol" data-clipboard-text="🐻‍❄️">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%BC'
                           class='emoji_font'>🐼</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%BC'
                           class='emoji_name truncate'>熊猫</a>
                        <a class="emoji_symbol" data-clipboard-text="🐼">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%BD'
                           class='emoji_font'>🐽</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%BD'
                           class='emoji_name truncate'>猪鼻子</a>
                        <a class="emoji_symbol" data-clipboard-text="🐽">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%BE'
                           class='emoji_font'>🐾</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%BE'
                           class='emoji_name truncate'>爪印</a>
                        <a class="emoji_symbol" data-clipboard-text="🐾">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%BF'
                           class='emoji_font'>🐿</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%BF'
                           class='emoji_name truncate'>松鼠</a>
                        <a class="emoji_symbol" data-clipboard-text="🐿">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%81'
                           class='emoji_font'>🦁</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%81'
                           class='emoji_name truncate'>狮子</a>
                        <a class="emoji_symbol" data-clipboard-text="🦁">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%84'
                           class='emoji_font'>🦄</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%84'
                           class='emoji_name truncate'>独角兽</a>
                        <a class="emoji_symbol" data-clipboard-text="🦄">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%87'
                           class='emoji_font'>🦇</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%87'
                           class='emoji_name truncate'>蝙蝠</a>
                        <a class="emoji_symbol" data-clipboard-text="🦇">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%8A'
                           class='emoji_font'>🦊</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%8A'
                           class='emoji_name truncate'>狐狸</a>
                        <a class="emoji_symbol" data-clipboard-text="🦊">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%8C'
                           class='emoji_font'>🦌</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%8C'
                           class='emoji_name truncate'>鹿</a>
                        <a class="emoji_symbol" data-clipboard-text="🦌">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%8D'
                           class='emoji_font'>🦍</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%8D'
                           class='emoji_name truncate'>大猩猩</a>
                        <a class="emoji_symbol" data-clipboard-text="🦍">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%8F'
                           class='emoji_font'>🦏</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%8F'
                           class='emoji_name truncate'>犀牛</a>
                        <a class="emoji_symbol" data-clipboard-text="🦏">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%92'
                           class='emoji_font'>🦒</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%92'
                           class='emoji_name truncate'>长颈鹿</a>
                        <a class="emoji_symbol" data-clipboard-text="🦒">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%93'
                           class='emoji_font'>🦓</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%93'
                           class='emoji_name truncate'>斑马</a>
                        <a class="emoji_symbol" data-clipboard-text="🦓">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%94'
                           class='emoji_font'>🦔</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%94'
                           class='emoji_name truncate'>刺猬</a>
                        <a class="emoji_symbol" data-clipboard-text="🦔">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%98'
                           class='emoji_font'>🦘</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%98'
                           class='emoji_name truncate'>袋鼠</a>
                        <a class="emoji_symbol" data-clipboard-text="🦘">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%99'
                           class='emoji_font'>🦙</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%99'
                           class='emoji_name truncate'>美洲鸵</a>
                        <a class="emoji_symbol" data-clipboard-text="🦙">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%9B'
                           class='emoji_font'>🦛</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%9B'
                           class='emoji_name truncate'>河马</a>
                        <a class="emoji_symbol" data-clipboard-text="🦛">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%9D'
                           class='emoji_font'>🦝</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%9D'
                           class='emoji_name truncate'>浣熊</a>
                        <a class="emoji_symbol" data-clipboard-text="🦝">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A1'
                           class='emoji_font'>🦡</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A1'
                           class='emoji_name truncate'>獾</a>
                        <a class="emoji_symbol" data-clipboard-text="🦡">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A3'
                           class='emoji_font'>🦣</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A3'
                           class='emoji_name truncate'>猛犸</a>
                        <a class="emoji_symbol" data-clipboard-text="🦣">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A5'
                           class='emoji_font'>🦥</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A5'
                           class='emoji_name truncate'>树懒</a>
                        <a class="emoji_symbol" data-clipboard-text="🦥">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A6'
                           class='emoji_font'>🦦</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A6'
                           class='emoji_name truncate'>水獭</a>
                        <a class="emoji_symbol" data-clipboard-text="🦦">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A7'
                           class='emoji_font'>🦧</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A7'
                           class='emoji_name truncate'>红毛猩猩</a>
                        <a class="emoji_symbol" data-clipboard-text="🦧">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A8'
                           class='emoji_font'>🦨</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A8'
                           class='emoji_name truncate'>臭鼬</a>
                        <a class="emoji_symbol" data-clipboard-text="🦨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%AB'
                           class='emoji_font'>🦫</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%AB'
                           class='emoji_name truncate'>海狸</a>
                        <a class="emoji_symbol" data-clipboard-text="🦫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%AC'
                           class='emoji_font'>🦬</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%AC'
                           class='emoji_name truncate'>大野牛</a>
                        <a class="emoji_symbol" data-clipboard-text="🦬">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%AE'
                           class='emoji_font'>🦮</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%AE'
                           class='emoji_name truncate'>导盲犬</a>
                        <a class="emoji_symbol" data-clipboard-text="🦮">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/D2'><span class="emoji_font line">🐓</span>鸟类<sup>18</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%93'
                           class='emoji_font'>🐓</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%93'
                           class='emoji_name truncate'>公鸡</a>
                        <a class="emoji_symbol" data-clipboard-text="🐓">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%94'
                           class='emoji_font'>🐔</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%94'
                           class='emoji_name truncate'>鸡</a>
                        <a class="emoji_symbol" data-clipboard-text="🐔">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A3'
                           class='emoji_font'>🐣</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A3'
                           class='emoji_name truncate'>小鸡破壳</a>
                        <a class="emoji_symbol" data-clipboard-text="🐣">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A4'
                           class='emoji_font'>🐤</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A4'
                           class='emoji_name truncate'>小鸡</a>
                        <a class="emoji_symbol" data-clipboard-text="🐤">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A5'
                           class='emoji_font'>🐥</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A5'
                           class='emoji_name truncate'>正面朝向的小鸡</a>
                        <a class="emoji_symbol" data-clipboard-text="🐥">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A6'
                           class='emoji_font'>🐦</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A6'
                           class='emoji_name truncate'>鸟</a>
                        <a class="emoji_symbol" data-clipboard-text="🐦">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A7'
                           class='emoji_font'>🐧</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A7'
                           class='emoji_name truncate'>企鹅</a>
                        <a class="emoji_symbol" data-clipboard-text="🐧">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%95%8A'
                           class='emoji_font'>🕊</a>
                        <a href='/zh-hans/emoji/%F0%9F%95%8A'
                           class='emoji_name truncate'>鸽</a>
                        <a class="emoji_symbol" data-clipboard-text="🕊">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%83'
                           class='emoji_font'>🦃</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%83'
                           class='emoji_name truncate'>火鸡</a>
                        <a class="emoji_symbol" data-clipboard-text="🦃">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%85'
                           class='emoji_font'>🦅</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%85'
                           class='emoji_name truncate'>鹰</a>
                        <a class="emoji_symbol" data-clipboard-text="🦅">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%86'
                           class='emoji_font'>🦆</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%86'
                           class='emoji_name truncate'>鸭子</a>
                        <a class="emoji_symbol" data-clipboard-text="🦆">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%89'
                           class='emoji_font'>🦉</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%89'
                           class='emoji_name truncate'>猫头鹰</a>
                        <a class="emoji_symbol" data-clipboard-text="🦉">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%9A'
                           class='emoji_font'>🦚</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%9A'
                           class='emoji_name truncate'>孔雀</a>
                        <a class="emoji_symbol" data-clipboard-text="🦚">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%9C'
                           class='emoji_font'>🦜</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%9C'
                           class='emoji_name truncate'>鹦鹉</a>
                        <a class="emoji_symbol" data-clipboard-text="🦜">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A2'
                           class='emoji_font'>🦢</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A2'
                           class='emoji_name truncate'>天鹅</a>
                        <a class="emoji_symbol" data-clipboard-text="🦢">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A4'
                           class='emoji_font'>🦤</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A4'
                           class='emoji_name truncate'>渡渡鸟</a>
                        <a class="emoji_symbol" data-clipboard-text="🦤">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%A9'
                           class='emoji_font'>🦩</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%A9'
                           class='emoji_name truncate'>火烈鸟</a>
                        <a class="emoji_symbol" data-clipboard-text="🦩">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AA%B6'
                           class='emoji_font'>🪶</a>
                        <a href='/zh-hans/emoji/%F0%9F%AA%B6'
                           class='emoji_name truncate'>羽毛</a>
                        <a class="emoji_symbol" data-clipboard-text="🪶">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/D3'><span class="emoji_font line">🐸</span>两栖动物<sup>1</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B8'
                           class='emoji_font'>🐸</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B8'
                           class='emoji_name truncate'>青蛙</a>
                        <a class="emoji_symbol" data-clipboard-text="🐸">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/D5'><span class="emoji_font line">🐟</span>海洋动物<sup>10</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%8B'
                           class='emoji_font'>🐋</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%8B'
                           class='emoji_name truncate'>鲸鱼</a>
                        <a class="emoji_symbol" data-clipboard-text="🐋">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%99'
                           class='emoji_font'>🐙</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%99'
                           class='emoji_name truncate'>章鱼</a>
                        <a class="emoji_symbol" data-clipboard-text="🐙">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%9A'
                           class='emoji_font'>🐚</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%9A'
                           class='emoji_name truncate'>海螺</a>
                        <a class="emoji_symbol" data-clipboard-text="🐚">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%9F'
                           class='emoji_font'>🐟</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%9F'
                           class='emoji_name truncate'>鱼</a>
                        <a class="emoji_symbol" data-clipboard-text="🐟">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A0'
                           class='emoji_font'>🐠</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A0'
                           class='emoji_name truncate'>热带鱼</a>
                        <a class="emoji_symbol" data-clipboard-text="🐠">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%A1'
                           class='emoji_font'>🐡</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%A1'
                           class='emoji_name truncate'>河豚</a>
                        <a class="emoji_symbol" data-clipboard-text="🐡">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%AC'
                           class='emoji_font'>🐬</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%AC'
                           class='emoji_name truncate'>海豚</a>
                        <a class="emoji_symbol" data-clipboard-text="🐬">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%90%B3'
                           class='emoji_font'>🐳</a>
                        <a href='/zh-hans/emoji/%F0%9F%90%B3'
                           class='emoji_name truncate'>喷水的鲸</a>
                        <a class="emoji_symbol" data-clipboard-text="🐳">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%88'
                           class='emoji_font'>🦈</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%88'
                           class='emoji_name truncate'>鲨鱼</a>
                        <a class="emoji_symbol" data-clipboard-text="🦈">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%AD'
                           class='emoji_font'>🦭</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%AD'
                           class='emoji_name truncate'>海豹</a>
                        <a class="emoji_symbol" data-clipboard-text="🦭">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/D7'><span class="emoji_font line">🌹</span>花朵<sup>10</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B7'
                           class='emoji_font'>🌷</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B7'
                           class='emoji_name truncate'>郁金香</a>
                        <a class="emoji_symbol" data-clipboard-text="🌷">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B8'
                           class='emoji_font'>🌸</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B8'
                           class='emoji_name truncate'>樱花</a>
                        <a class="emoji_symbol" data-clipboard-text="🌸">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B9'
                           class='emoji_font'>🌹</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B9'
                           class='emoji_name truncate'>玫瑰</a>
                        <a class="emoji_symbol" data-clipboard-text="🌹">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%BA'
                           class='emoji_font'>🌺</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%BA'
                           class='emoji_name truncate'>芙蓉</a>
                        <a class="emoji_symbol" data-clipboard-text="🌺">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%BB'
                           class='emoji_font'>🌻</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%BB'
                           class='emoji_name truncate'>向日葵</a>
                        <a class="emoji_symbol" data-clipboard-text="🌻">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%BC'
                           class='emoji_font'>🌼</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%BC'
                           class='emoji_name truncate'>开花</a>
                        <a class="emoji_symbol" data-clipboard-text="🌼">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8F%B5'
                           class='emoji_font'>🏵</a>
                        <a href='/zh-hans/emoji/%F0%9F%8F%B5'
                           class='emoji_name truncate'>圆形花饰</a>
                        <a class="emoji_symbol" data-clipboard-text="🏵">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%90'
                           class='emoji_font'>💐</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%90'
                           class='emoji_name truncate'>花束</a>
                        <a class="emoji_symbol" data-clipboard-text="💐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%92%AE'
                           class='emoji_font'>💮</a>
                        <a href='/zh-hans/emoji/%F0%9F%92%AE'
                           class='emoji_name truncate'>白花</a>
                        <a class="emoji_symbol" data-clipboard-text="💮">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%80'
                           class='emoji_font'>🥀</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%80'
                           class='emoji_name truncate'>枯萎的花</a>
                        <a class="emoji_symbol" data-clipboard-text="🥀">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/D8'><span class="emoji_font line">🌴</span>其他植物<sup>13</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%98%98' class='emoji_font'>☘</a>
                        <a href='/zh-hans/emoji/%E2%98%98'
                           class='emoji_name truncate'>三叶草</a>
                        <a class="emoji_symbol" data-clipboard-text="☘">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B1'
                           class='emoji_font'>🌱</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B1'
                           class='emoji_name truncate'>幼苗</a>
                        <a class="emoji_symbol" data-clipboard-text="🌱">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B2'
                           class='emoji_font'>🌲</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B2'
                           class='emoji_name truncate'>松树</a>
                        <a class="emoji_symbol" data-clipboard-text="🌲">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B3'
                           class='emoji_font'>🌳</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B3'
                           class='emoji_name truncate'>落叶树</a>
                        <a class="emoji_symbol" data-clipboard-text="🌳">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B4'
                           class='emoji_font'>🌴</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B4'
                           class='emoji_name truncate'>棕榈树</a>
                        <a class="emoji_symbol" data-clipboard-text="🌴">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B5'
                           class='emoji_font'>🌵</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B5'
                           class='emoji_name truncate'>仙人掌</a>
                        <a class="emoji_symbol" data-clipboard-text="🌵">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%BE'
                           class='emoji_font'>🌾</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%BE'
                           class='emoji_name truncate'>稻子</a>
                        <a class="emoji_symbol" data-clipboard-text="🌾">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%BF'
                           class='emoji_font'>🌿</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%BF'
                           class='emoji_name truncate'>药草</a>
                        <a class="emoji_symbol" data-clipboard-text="🌿">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%80'
                           class='emoji_font'>🍀</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%80'
                           class='emoji_name truncate'>四叶草</a>
                        <a class="emoji_symbol" data-clipboard-text="🍀">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%81'
                           class='emoji_font'>🍁</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%81'
                           class='emoji_name truncate'>枫叶</a>
                        <a class="emoji_symbol" data-clipboard-text="🍁">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%82'
                           class='emoji_font'>🍂</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%82'
                           class='emoji_name truncate'>落叶</a>
                        <a class="emoji_symbol" data-clipboard-text="🍂">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%83'
                           class='emoji_font'>🍃</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%83'
                           class='emoji_name truncate'>风吹叶落</a>
                        <a class="emoji_symbol" data-clipboard-text="🍃">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AA%B4'
                           class='emoji_font'>🪴</a>
                        <a href='/zh-hans/emoji/%F0%9F%AA%B4'
                           class='emoji_name truncate'>盆栽植物</a>
                        <a class="emoji_symbol" data-clipboard-text="🪴">复制</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="emoji_card_list">
            <h2><a href='/zh-hans/categories/E'><span
                    class="emoji_font line">🍓</span>食物和饮料<sup>129</sup></a><i
                    id='categories-E'></i></h2>
            <h3><a href='/zh-hans/sub-categories/E1'><span class="emoji_font line">🍅</span>水果<sup>19</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%85'
                           class='emoji_font'>🍅</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%85'
                           class='emoji_name truncate'>西红柿</a>
                        <a class="emoji_symbol" data-clipboard-text="🍅">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%87'
                           class='emoji_font'>🍇</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%87'
                           class='emoji_name truncate'>葡萄</a>
                        <a class="emoji_symbol" data-clipboard-text="🍇">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%88'
                           class='emoji_font'>🍈</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%88'
                           class='emoji_name truncate'>甜瓜</a>
                        <a class="emoji_symbol" data-clipboard-text="🍈">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%89'
                           class='emoji_font'>🍉</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%89'
                           class='emoji_name truncate'>西瓜</a>
                        <a class="emoji_symbol" data-clipboard-text="🍉">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%8A'
                           class='emoji_font'>🍊</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%8A'
                           class='emoji_name truncate'>橘子</a>
                        <a class="emoji_symbol" data-clipboard-text="🍊">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%8B'
                           class='emoji_font'>🍋</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%8B'
                           class='emoji_name truncate'>柠檬</a>
                        <a class="emoji_symbol" data-clipboard-text="🍋">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%8C'
                           class='emoji_font'>🍌</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%8C'
                           class='emoji_name truncate'>香蕉</a>
                        <a class="emoji_symbol" data-clipboard-text="🍌">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%8D'
                           class='emoji_font'>🍍</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%8D'
                           class='emoji_name truncate'>菠萝</a>
                        <a class="emoji_symbol" data-clipboard-text="🍍">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%8E'
                           class='emoji_font'>🍎</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%8E'
                           class='emoji_name truncate'>红苹果</a>
                        <a class="emoji_symbol" data-clipboard-text="🍎">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%8F'
                           class='emoji_font'>🍏</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%8F'
                           class='emoji_name truncate'>青苹果</a>
                        <a class="emoji_symbol" data-clipboard-text="🍏">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%90'
                           class='emoji_font'>🍐</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%90'
                           class='emoji_name truncate'>梨</a>
                        <a class="emoji_symbol" data-clipboard-text="🍐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%91'
                           class='emoji_font'>🍑</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%91'
                           class='emoji_name truncate'>桃</a>
                        <a class="emoji_symbol" data-clipboard-text="🍑">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%92'
                           class='emoji_font'>🍒</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%92'
                           class='emoji_name truncate'>樱桃</a>
                        <a class="emoji_symbol" data-clipboard-text="🍒">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%93'
                           class='emoji_font'>🍓</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%93'
                           class='emoji_name truncate'>草莓</a>
                        <a class="emoji_symbol" data-clipboard-text="🍓">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%9D'
                           class='emoji_font'>🥝</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%9D'
                           class='emoji_name truncate'>猕猴桃</a>
                        <a class="emoji_symbol" data-clipboard-text="🥝">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A5'
                           class='emoji_font'>🥥</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A5'
                           class='emoji_name truncate'>椰子</a>
                        <a class="emoji_symbol" data-clipboard-text="🥥">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%AD'
                           class='emoji_font'>🥭</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%AD'
                           class='emoji_name truncate'>芒果</a>
                        <a class="emoji_symbol" data-clipboard-text="🥭">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AB%90'
                           class='emoji_font'>🫐</a>
                        <a href='/zh-hans/emoji/%F0%9F%AB%90'
                           class='emoji_name truncate'>蓝莓</a>
                        <a class="emoji_symbol" data-clipboard-text="🫐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AB%92'
                           class='emoji_font'>🫒</a>
                        <a href='/zh-hans/emoji/%F0%9F%AB%92'
                           class='emoji_name truncate'>橄榄</a>
                        <a class="emoji_symbol" data-clipboard-text="🫒">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/E2'><span class="emoji_font line">🍄</span>蔬菜<sup>15</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B0'
                           class='emoji_font'>🌰</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B0'
                           class='emoji_name truncate'>栗子</a>
                        <a class="emoji_symbol" data-clipboard-text="🌰">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%B6'
                           class='emoji_font'>🌶</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%B6'
                           class='emoji_name truncate'>红辣椒</a>
                        <a class="emoji_symbol" data-clipboard-text="🌶">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%BD'
                           class='emoji_font'>🌽</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%BD'
                           class='emoji_name truncate'>玉米</a>
                        <a class="emoji_symbol" data-clipboard-text="🌽">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%84'
                           class='emoji_font'>🍄</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%84'
                           class='emoji_name truncate'>蘑菇</a>
                        <a class="emoji_symbol" data-clipboard-text="🍄">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%86'
                           class='emoji_font'>🍆</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%86'
                           class='emoji_name truncate'>茄子</a>
                        <a class="emoji_symbol" data-clipboard-text="🍆">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%91'
                           class='emoji_font'>🥑</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%91'
                           class='emoji_name truncate'>鳄梨</a>
                        <a class="emoji_symbol" data-clipboard-text="🥑">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%92'
                           class='emoji_font'>🥒</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%92'
                           class='emoji_name truncate'>黄瓜</a>
                        <a class="emoji_symbol" data-clipboard-text="🥒">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%94'
                           class='emoji_font'>🥔</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%94'
                           class='emoji_name truncate'>土豆</a>
                        <a class="emoji_symbol" data-clipboard-text="🥔">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%95'
                           class='emoji_font'>🥕</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%95'
                           class='emoji_name truncate'>胡萝卜</a>
                        <a class="emoji_symbol" data-clipboard-text="🥕">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%9C'
                           class='emoji_font'>🥜</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%9C'
                           class='emoji_name truncate'>花生</a>
                        <a class="emoji_symbol" data-clipboard-text="🥜">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A6'
                           class='emoji_font'>🥦</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A6'
                           class='emoji_name truncate'>西兰花</a>
                        <a class="emoji_symbol" data-clipboard-text="🥦">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%AC'
                           class='emoji_font'>🥬</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%AC'
                           class='emoji_name truncate'>绿叶蔬菜</a>
                        <a class="emoji_symbol" data-clipboard-text="🥬">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%84'
                           class='emoji_font'>🧄</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%84'
                           class='emoji_name truncate'>蒜</a>
                        <a class="emoji_symbol" data-clipboard-text="🧄">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%85'
                           class='emoji_font'>🧅</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%85'
                           class='emoji_name truncate'>洋葱</a>
                        <a class="emoji_symbol" data-clipboard-text="🧅">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AB%91'
                           class='emoji_font'>🫑</a>
                        <a href='/zh-hans/emoji/%F0%9F%AB%91'
                           class='emoji_name truncate'>灯笼椒</a>
                        <a class="emoji_symbol" data-clipboard-text="🫑">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/E3'><span class="emoji_font line">🍕</span>熟食<sup>34</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%AD'
                           class='emoji_font'>🌭</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%AD'
                           class='emoji_name truncate'>热狗</a>
                        <a class="emoji_symbol" data-clipboard-text="🌭">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%AE'
                           class='emoji_font'>🌮</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%AE'
                           class='emoji_name truncate'>墨西哥卷饼</a>
                        <a class="emoji_symbol" data-clipboard-text="🌮">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8C%AF'
                           class='emoji_font'>🌯</a>
                        <a href='/zh-hans/emoji/%F0%9F%8C%AF'
                           class='emoji_name truncate'>墨西哥玉米煎饼</a>
                        <a class="emoji_symbol" data-clipboard-text="🌯">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%94'
                           class='emoji_font'>🍔</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%94'
                           class='emoji_name truncate'>汉堡</a>
                        <a class="emoji_symbol" data-clipboard-text="🍔">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%95'
                           class='emoji_font'>🍕</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%95'
                           class='emoji_name truncate'>披萨</a>
                        <a class="emoji_symbol" data-clipboard-text="🍕">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%96'
                           class='emoji_font'>🍖</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%96'
                           class='emoji_name truncate'>排骨</a>
                        <a class="emoji_symbol" data-clipboard-text="🍖">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%97'
                           class='emoji_font'>🍗</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%97'
                           class='emoji_name truncate'>家禽的腿</a>
                        <a class="emoji_symbol" data-clipboard-text="🍗">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%9E'
                           class='emoji_font'>🍞</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%9E'
                           class='emoji_name truncate'>面包</a>
                        <a class="emoji_symbol" data-clipboard-text="🍞">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%9F'
                           class='emoji_font'>🍟</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%9F'
                           class='emoji_name truncate'>薯条</a>
                        <a class="emoji_symbol" data-clipboard-text="🍟">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B2'
                           class='emoji_font'>🍲</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B2'
                           class='emoji_name truncate'>一锅食物</a>
                        <a class="emoji_symbol" data-clipboard-text="🍲">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B3'
                           class='emoji_font'>🍳</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B3'
                           class='emoji_name truncate'>煎蛋</a>
                        <a class="emoji_symbol" data-clipboard-text="🍳">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%BF'
                           class='emoji_font'>🍿</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%BF'
                           class='emoji_name truncate'>爆米花</a>
                        <a class="emoji_symbol" data-clipboard-text="🍿">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%90'
                           class='emoji_font'>🥐</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%90'
                           class='emoji_name truncate'>羊角面包</a>
                        <a class="emoji_symbol" data-clipboard-text="🥐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%93'
                           class='emoji_font'>🥓</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%93'
                           class='emoji_name truncate'>培根</a>
                        <a class="emoji_symbol" data-clipboard-text="🥓">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%96'
                           class='emoji_font'>🥖</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%96'
                           class='emoji_name truncate'>法式长棍面包</a>
                        <a class="emoji_symbol" data-clipboard-text="🥖">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%97'
                           class='emoji_font'>🥗</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%97'
                           class='emoji_name truncate'>绿色沙拉</a>
                        <a class="emoji_symbol" data-clipboard-text="🥗">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%98'
                           class='emoji_font'>🥘</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%98'
                           class='emoji_name truncate'>装有食物的浅底锅</a>
                        <a class="emoji_symbol" data-clipboard-text="🥘">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%99'
                           class='emoji_font'>🥙</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%99'
                           class='emoji_name truncate'>夹心饼</a>
                        <a class="emoji_symbol" data-clipboard-text="🥙">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%9A'
                           class='emoji_font'>🥚</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%9A'
                           class='emoji_name truncate'>蛋</a>
                        <a class="emoji_symbol" data-clipboard-text="🥚">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%9E'
                           class='emoji_font'>🥞</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%9E'
                           class='emoji_name truncate'>烙饼</a>
                        <a class="emoji_symbol" data-clipboard-text="🥞">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A3'
                           class='emoji_font'>🥣</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A3'
                           class='emoji_name truncate'>碗勺</a>
                        <a class="emoji_symbol" data-clipboard-text="🥣">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A8'
                           class='emoji_font'>🥨</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A8'
                           class='emoji_name truncate'>椒盐卷饼</a>
                        <a class="emoji_symbol" data-clipboard-text="🥨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A9'
                           class='emoji_font'>🥩</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A9'
                           class='emoji_name truncate'>肉块</a>
                        <a class="emoji_symbol" data-clipboard-text="🥩">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%AA'
                           class='emoji_font'>🥪</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%AA'
                           class='emoji_name truncate'>三明治</a>
                        <a class="emoji_symbol" data-clipboard-text="🥪">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%AB'
                           class='emoji_font'>🥫</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%AB'
                           class='emoji_name truncate'>罐头食品</a>
                        <a class="emoji_symbol" data-clipboard-text="🥫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%AF'
                           class='emoji_font'>🥯</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%AF'
                           class='emoji_name truncate'>面包圈</a>
                        <a class="emoji_symbol" data-clipboard-text="🥯">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%80'
                           class='emoji_font'>🧀</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%80'
                           class='emoji_name truncate'>芝士</a>
                        <a class="emoji_symbol" data-clipboard-text="🧀">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%82'
                           class='emoji_font'>🧂</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%82'
                           class='emoji_name truncate'>盐</a>
                        <a class="emoji_symbol" data-clipboard-text="🧂">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%86'
                           class='emoji_font'>🧆</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%86'
                           class='emoji_name truncate'>炸豆丸子</a>
                        <a class="emoji_symbol" data-clipboard-text="🧆">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%87'
                           class='emoji_font'>🧇</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%87'
                           class='emoji_name truncate'>华夫饼</a>
                        <a class="emoji_symbol" data-clipboard-text="🧇">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%88'
                           class='emoji_font'>🧈</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%88'
                           class='emoji_name truncate'>黄油</a>
                        <a class="emoji_symbol" data-clipboard-text="🧈">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AB%93'
                           class='emoji_font'>🫓</a>
                        <a href='/zh-hans/emoji/%F0%9F%AB%93'
                           class='emoji_name truncate'>扁面包</a>
                        <a class="emoji_symbol" data-clipboard-text="🫓">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AB%94'
                           class='emoji_font'>🫔</a>
                        <a href='/zh-hans/emoji/%F0%9F%AB%94'
                           class='emoji_name truncate'>墨西哥粽子</a>
                        <a class="emoji_symbol" data-clipboard-text="🫔">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AB%95'
                           class='emoji_font'>🫕</a>
                        <a href='/zh-hans/emoji/%F0%9F%AB%95'
                           class='emoji_name truncate'>奶酪火锅</a>
                        <a class="emoji_symbol" data-clipboard-text="🫕">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/E4'><span class="emoji_font line">🍚</span>亚洲食物<sup>17</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%98'
                           class='emoji_font'>🍘</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%98'
                           class='emoji_name truncate'>米饼</a>
                        <a class="emoji_symbol" data-clipboard-text="🍘">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%99'
                           class='emoji_font'>🍙</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%99'
                           class='emoji_name truncate'>饭团</a>
                        <a class="emoji_symbol" data-clipboard-text="🍙">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%9A'
                           class='emoji_font'>🍚</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%9A'
                           class='emoji_name truncate'>米饭</a>
                        <a class="emoji_symbol" data-clipboard-text="🍚">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%9B'
                           class='emoji_font'>🍛</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%9B'
                           class='emoji_name truncate'>咖喱饭</a>
                        <a class="emoji_symbol" data-clipboard-text="🍛">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%9C'
                           class='emoji_font'>🍜</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%9C'
                           class='emoji_name truncate'>面条</a>
                        <a class="emoji_symbol" data-clipboard-text="🍜">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%9D'
                           class='emoji_font'>🍝</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%9D'
                           class='emoji_name truncate'>意粉</a>
                        <a class="emoji_symbol" data-clipboard-text="🍝">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A0'
                           class='emoji_font'>🍠</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A0'
                           class='emoji_name truncate'>烤红薯</a>
                        <a class="emoji_symbol" data-clipboard-text="🍠">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A1'
                           class='emoji_font'>🍡</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A1'
                           class='emoji_name truncate'>团子</a>
                        <a class="emoji_symbol" data-clipboard-text="🍡">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A2'
                           class='emoji_font'>🍢</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A2'
                           class='emoji_name truncate'>关东煮</a>
                        <a class="emoji_symbol" data-clipboard-text="🍢">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A3'
                           class='emoji_font'>🍣</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A3'
                           class='emoji_name truncate'>寿司</a>
                        <a class="emoji_symbol" data-clipboard-text="🍣">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A4'
                           class='emoji_font'>🍤</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A4'
                           class='emoji_name truncate'>天妇罗</a>
                        <a class="emoji_symbol" data-clipboard-text="🍤">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A5'
                           class='emoji_font'>🍥</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A5'
                           class='emoji_name truncate'>鱼板</a>
                        <a class="emoji_symbol" data-clipboard-text="🍥">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B1'
                           class='emoji_font'>🍱</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B1'
                           class='emoji_name truncate'>盒饭</a>
                        <a class="emoji_symbol" data-clipboard-text="🍱">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%9F'
                           class='emoji_font'>🥟</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%9F'
                           class='emoji_name truncate'>饺子</a>
                        <a class="emoji_symbol" data-clipboard-text="🥟">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A0'
                           class='emoji_font'>🥠</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A0'
                           class='emoji_name truncate'>幸运饼干</a>
                        <a class="emoji_symbol" data-clipboard-text="🥠">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A1'
                           class='emoji_font'>🥡</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A1'
                           class='emoji_name truncate'>外卖盒</a>
                        <a class="emoji_symbol" data-clipboard-text="🥡">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%AE'
                           class='emoji_font'>🥮</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%AE'
                           class='emoji_name truncate'>月饼</a>
                        <a class="emoji_symbol" data-clipboard-text="🥮">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/E5'><span class="emoji_font line">🦀</span>海产<sup>5</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%80'
                           class='emoji_font'>🦀</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%80'
                           class='emoji_name truncate'>蟹</a>
                        <a class="emoji_symbol" data-clipboard-text="🦀">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%90'
                           class='emoji_font'>🦐</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%90'
                           class='emoji_name truncate'>虾</a>
                        <a class="emoji_symbol" data-clipboard-text="🦐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%91'
                           class='emoji_font'>🦑</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%91'
                           class='emoji_name truncate'>乌贼</a>
                        <a class="emoji_symbol" data-clipboard-text="🦑">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%9E'
                           class='emoji_font'>🦞</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%9E'
                           class='emoji_name truncate'>龙虾</a>
                        <a class="emoji_symbol" data-clipboard-text="🦞">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A6%AA'
                           class='emoji_font'>🦪</a>
                        <a href='/zh-hans/emoji/%F0%9F%A6%AA'
                           class='emoji_name truncate'>牡蛎</a>
                        <a class="emoji_symbol" data-clipboard-text="🦪">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/E6'><span class="emoji_font line">🍦</span>甜食<sup>14</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A6'
                           class='emoji_font'>🍦</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A6'
                           class='emoji_name truncate'>圆筒冰激凌</a>
                        <a class="emoji_symbol" data-clipboard-text="🍦">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A7'
                           class='emoji_font'>🍧</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A7'
                           class='emoji_name truncate'>刨冰</a>
                        <a class="emoji_symbol" data-clipboard-text="🍧">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A8'
                           class='emoji_font'>🍨</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A8'
                           class='emoji_name truncate'>冰淇淋</a>
                        <a class="emoji_symbol" data-clipboard-text="🍨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%A9'
                           class='emoji_font'>🍩</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%A9'
                           class='emoji_name truncate'>甜甜圈</a>
                        <a class="emoji_symbol" data-clipboard-text="🍩">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%AA'
                           class='emoji_font'>🍪</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%AA'
                           class='emoji_name truncate'>饼干</a>
                        <a class="emoji_symbol" data-clipboard-text="🍪">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%AB'
                           class='emoji_font'>🍫</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%AB'
                           class='emoji_name truncate'>巧克力</a>
                        <a class="emoji_symbol" data-clipboard-text="🍫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%AC'
                           class='emoji_font'>🍬</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%AC'
                           class='emoji_name truncate'>糖</a>
                        <a class="emoji_symbol" data-clipboard-text="🍬">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%AD'
                           class='emoji_font'>🍭</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%AD'
                           class='emoji_name truncate'>棒棒糖</a>
                        <a class="emoji_symbol" data-clipboard-text="🍭">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%AE'
                           class='emoji_font'>🍮</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%AE'
                           class='emoji_name truncate'>奶黄</a>
                        <a class="emoji_symbol" data-clipboard-text="🍮">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%AF'
                           class='emoji_font'>🍯</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%AF'
                           class='emoji_name truncate'>蜂蜜</a>
                        <a class="emoji_symbol" data-clipboard-text="🍯">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B0'
                           class='emoji_font'>🍰</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B0'
                           class='emoji_name truncate'>水果蛋糕</a>
                        <a class="emoji_symbol" data-clipboard-text="🍰">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%82'
                           class='emoji_font'>🎂</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%82'
                           class='emoji_name truncate'>生日蛋糕</a>
                        <a class="emoji_symbol" data-clipboard-text="🎂">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A7'
                           class='emoji_font'>🥧</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A7'
                           class='emoji_name truncate'>派</a>
                        <a class="emoji_symbol" data-clipboard-text="🥧">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%81'
                           class='emoji_font'>🧁</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%81'
                           class='emoji_name truncate'>纸杯蛋糕</a>
                        <a class="emoji_symbol" data-clipboard-text="🧁">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/E7'><span
                    class="emoji_font line">☕</span>饮料<sup>19</sup></a></h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%98%95' class='emoji_font'>☕</a>
                        <a href='/zh-hans/emoji/%E2%98%95'
                           class='emoji_name truncate'>热饮</a>
                        <a class="emoji_symbol" data-clipboard-text="☕">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B5'
                           class='emoji_font'>🍵</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B5'
                           class='emoji_name truncate'>热茶</a>
                        <a class="emoji_symbol" data-clipboard-text="🍵">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B6'
                           class='emoji_font'>🍶</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B6'
                           class='emoji_name truncate'>清酒</a>
                        <a class="emoji_symbol" data-clipboard-text="🍶">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B7'
                           class='emoji_font'>🍷</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B7'
                           class='emoji_name truncate'>葡萄酒</a>
                        <a class="emoji_symbol" data-clipboard-text="🍷">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B8'
                           class='emoji_font'>🍸</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B8'
                           class='emoji_name truncate'>鸡尾酒</a>
                        <a class="emoji_symbol" data-clipboard-text="🍸">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%B9'
                           class='emoji_font'>🍹</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%B9'
                           class='emoji_name truncate'>热带水果饮料</a>
                        <a class="emoji_symbol" data-clipboard-text="🍹">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%BA'
                           class='emoji_font'>🍺</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%BA'
                           class='emoji_name truncate'>啤酒</a>
                        <a class="emoji_symbol" data-clipboard-text="🍺">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%BB'
                           class='emoji_font'>🍻</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%BB'
                           class='emoji_name truncate'>干杯</a>
                        <a class="emoji_symbol" data-clipboard-text="🍻">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%BC'
                           class='emoji_font'>🍼</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%BC'
                           class='emoji_name truncate'>奶瓶</a>
                        <a class="emoji_symbol" data-clipboard-text="🍼">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8D%BE'
                           class='emoji_font'>🍾</a>
                        <a href='/zh-hans/emoji/%F0%9F%8D%BE'
                           class='emoji_name truncate'>开香槟</a>
                        <a class="emoji_symbol" data-clipboard-text="🍾">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%82'
                           class='emoji_font'>🥂</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%82'
                           class='emoji_name truncate'>碰杯</a>
                        <a class="emoji_symbol" data-clipboard-text="🥂">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%83'
                           class='emoji_font'>🥃</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%83'
                           class='emoji_name truncate'>平底杯</a>
                        <a class="emoji_symbol" data-clipboard-text="🥃">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%9B'
                           class='emoji_font'>🥛</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%9B'
                           class='emoji_name truncate'>一杯奶</a>
                        <a class="emoji_symbol" data-clipboard-text="🥛">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A5%A4'
                           class='emoji_font'>🥤</a>
                        <a href='/zh-hans/emoji/%F0%9F%A5%A4'
                           class='emoji_name truncate'>带吸管杯</a>
                        <a class="emoji_symbol" data-clipboard-text="🥤">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%83'
                           class='emoji_font'>🧃</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%83'
                           class='emoji_name truncate'>饮料盒</a>
                        <a class="emoji_symbol" data-clipboard-text="🧃">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%89'
                           class='emoji_font'>🧉</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%89'
                           class='emoji_name truncate'>马黛茶</a>
                        <a class="emoji_symbol" data-clipboard-text="🧉">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%8A'
                           class='emoji_font'>🧊</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%8A'
                           class='emoji_name truncate'>冰块</a>
                        <a class="emoji_symbol" data-clipboard-text="🧊">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%8B'
                           class='emoji_font'>🧋</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%8B'
                           class='emoji_name truncate'>珍珠奶茶</a>
                        <a class="emoji_symbol" data-clipboard-text="🧋">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AB%96'
                           class='emoji_font'>🫖</a>
                        <a href='/zh-hans/emoji/%F0%9F%AB%96'
                           class='emoji_name truncate'>茶壶</a>
                        <a class="emoji_symbol" data-clipboard-text="🫖">复制</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="emoji_card_list">
            <h2><a href='/zh-hans/categories/G'><span
                    class="emoji_font line">⚽</span>活动<sup>84</sup></a><i
                    id='categories-G'></i></h2>
            <h3><a href='/zh-hans/sub-categories/G1'><span class="emoji_font line">🎈</span>事件<sup>21</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%E2%9C%A8' class='emoji_font'>✨</a>
                        <a href='/zh-hans/emoji/%E2%9C%A8'
                           class='emoji_name truncate'>闪亮</a>
                        <a class="emoji_symbol" data-clipboard-text="✨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%80'
                           class='emoji_font'>🎀</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%80'
                           class='emoji_name truncate'>蝴蝶结</a>
                        <a class="emoji_symbol" data-clipboard-text="🎀">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%81'
                           class='emoji_font'>🎁</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%81'
                           class='emoji_name truncate'>礼物</a>
                        <a class="emoji_symbol" data-clipboard-text="🎁">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%83'
                           class='emoji_font'>🎃</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%83'
                           class='emoji_name truncate'>南瓜灯</a>
                        <a class="emoji_symbol" data-clipboard-text="🎃">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%84'
                           class='emoji_font'>🎄</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%84'
                           class='emoji_name truncate'>圣诞树</a>
                        <a class="emoji_symbol" data-clipboard-text="🎄">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%86'
                           class='emoji_font'>🎆</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%86'
                           class='emoji_name truncate'>焰火</a>
                        <a class="emoji_symbol" data-clipboard-text="🎆">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%87'
                           class='emoji_font'>🎇</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%87'
                           class='emoji_name truncate'>烟花</a>
                        <a class="emoji_symbol" data-clipboard-text="🎇">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%88'
                           class='emoji_font'>🎈</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%88'
                           class='emoji_name truncate'>气球</a>
                        <a class="emoji_symbol" data-clipboard-text="🎈">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%89'
                           class='emoji_font'>🎉</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%89'
                           class='emoji_name truncate'>拉炮彩带</a>
                        <a class="emoji_symbol" data-clipboard-text="🎉">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%8A'
                           class='emoji_font'>🎊</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%8A'
                           class='emoji_name truncate'>五彩纸屑球</a>
                        <a class="emoji_symbol" data-clipboard-text="🎊">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%8B'
                           class='emoji_font'>🎋</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%8B'
                           class='emoji_name truncate'>七夕树</a>
                        <a class="emoji_symbol" data-clipboard-text="🎋">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%8D'
                           class='emoji_font'>🎍</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%8D'
                           class='emoji_name truncate'>门松</a>
                        <a class="emoji_symbol" data-clipboard-text="🎍">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%8E'
                           class='emoji_font'>🎎</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%8E'
                           class='emoji_name truncate'>日本人形</a>
                        <a class="emoji_symbol" data-clipboard-text="🎎">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%8F'
                           class='emoji_font'>🎏</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%8F'
                           class='emoji_name truncate'>鲤鱼旗</a>
                        <a class="emoji_symbol" data-clipboard-text="🎏">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%90'
                           class='emoji_font'>🎐</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%90'
                           class='emoji_name truncate'>风铃</a>
                        <a class="emoji_symbol" data-clipboard-text="🎐">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%91'
                           class='emoji_font'>🎑</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%91'
                           class='emoji_name truncate'>赏月</a>
                        <a class="emoji_symbol" data-clipboard-text="🎑">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%97'
                           class='emoji_font'>🎗</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%97'
                           class='emoji_name truncate'>提示丝带</a>
                        <a class="emoji_symbol" data-clipboard-text="🎗">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%9F'
                           class='emoji_font'>🎟</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%9F'
                           class='emoji_name truncate'>入场券</a>
                        <a class="emoji_symbol" data-clipboard-text="🎟">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%AB'
                           class='emoji_font'>🎫</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%AB'
                           class='emoji_name truncate'>票</a>
                        <a class="emoji_symbol" data-clipboard-text="🎫">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%A7'
                           class='emoji_font'>🧧</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%A7'
                           class='emoji_name truncate'>红包</a>
                        <a class="emoji_symbol" data-clipboard-text="🧧">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%A8'
                           class='emoji_font'>🧨</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%A8'
                           class='emoji_name truncate'>爆竹</a>
                        <a class="emoji_symbol" data-clipboard-text="🧨">复制</a>
                    </div>
                </div>
            </div>
            <h3><a href='/zh-hans/sub-categories/G5'><span class="emoji_font line">🎨</span>艺术和工艺<sup>7</sup></a>
            </h3>
            <div class="row row-cols-lg-5 row-cols-4 no-gutters list_table">
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%A8'
                           class='emoji_font'>🎨</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%A8'
                           class='emoji_name truncate'>调色盘</a>
                        <a class="emoji_symbol" data-clipboard-text="🎨">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%8E%AD'
                           class='emoji_font'>🎭</a>
                        <a href='/zh-hans/emoji/%F0%9F%8E%AD'
                           class='emoji_name truncate'>表演艺术</a>
                        <a class="emoji_symbol" data-clipboard-text="🎭">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%96%BC'
                           class='emoji_font'>🖼</a>
                        <a href='/zh-hans/emoji/%F0%9F%96%BC'
                           class='emoji_name truncate'>带框的画</a>
                        <a class="emoji_symbol" data-clipboard-text="🖼">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%B5'
                           class='emoji_font'>🧵</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%B5'
                           class='emoji_name truncate'>线</a>
                        <a class="emoji_symbol" data-clipboard-text="🧵">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%A7%B6'
                           class='emoji_font'>🧶</a>
                        <a href='/zh-hans/emoji/%F0%9F%A7%B6'
                           class='emoji_name truncate'>毛线</a>
                        <a class="emoji_symbol" data-clipboard-text="🧶">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AA%A1'
                           class='emoji_font'>🪡</a>
                        <a href='/zh-hans/emoji/%F0%9F%AA%A1'
                           class='emoji_name truncate'>缝合针</a>
                        <a class="emoji_symbol" data-clipboard-text="🪡">复制</a>
                    </div>
                </div>
                <div class="col">
                    <div class="emoji_card">
                        <a href='/zh-hans/emoji/%F0%9F%AA%A2'
                           class='emoji_font'>🪢</a>
                        <a href='/zh-hans/emoji/%F0%9F%AA%A2'
                           class='emoji_name truncate'>结</a>
                        <a class="emoji_symbol" data-clipboard-text="🪢">复制</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
STR;
    }


    private function getOriginEmojiData()
    {
        $htmlStr = $this->readHtmlData();
        $pattern = '#data-clipboard-text="(.*?)">#i';
        preg_match_all($pattern, $htmlStr, $match);
        if (empty($match) || count($match) < 2) {
            throw new Exception("fatal error preg match not find");
        }
        $d = $match[1];

        $result = array_map(function ($item) {
            $len = mb_strlen($item, 'gb2312');
            if ($len > 3) {
                return false;
            }
            return $item;
        }, $d);
        return array_values(array_filter($result));
    }
}
