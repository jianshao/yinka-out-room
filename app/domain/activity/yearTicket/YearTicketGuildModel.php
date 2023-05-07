<?php

namespace app\domain\activity\yearTicket;

use app\common\RedisCommon;
use app\domain\queue\job\Redis;

class YearTicketGuildModel
{
    public $guildId=0;
    public $nickname="";
    public $avatar="";
    public $rank=0;
    public $levelMap=[]; //<levelId:score>

    public function totalScore(){
        $score=0;
        foreach($this->levelMap as $key=>$selfScore){
            $score+=$selfScore;
        }
        return $score;
    }
}