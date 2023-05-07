<?php


namespace app\domain\activity\duobao3;


class TableInfo
{
    public $order = null;
    public $lastWinnerIssue = 0;
    public $lastWinnerUserId = 0;
    public $lastWinnerIndex = 0;

    public function __construct($order, $lastWinnerIssue, $lastWinnerUserId, $lastWinnerIndex) {
        $this->order = $order;
        $this->lastWinnerIssue = $lastWinnerIssue;
        $this->lastWinnerUserId = $lastWinnerUserId;
        $this->lastWinnerIndex = $lastWinnerIndex;
    }
}