<?php


namespace app\domain\feedback\service;


use app\domain\feedback\dao\FeedbackModelDao;
use app\domain\feedback\model\FeedbackModel;
use think\facade\Log;

class FeedbackService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new FeedbackService();
        }
        return self::$instance;
    }

    public function addFeedback($userId, $content) {
        FeedbackModelDao::getInstance()->addFeedback(new FeedbackModel($userId, $content, time()));
        Log::info(sprintf('FeedbackService::addFeedback userId=%d content=%s',
            $userId, $content));
    }
}