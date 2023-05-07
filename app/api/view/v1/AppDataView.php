<?php


namespace app\api\view\v1;


use app\domain\exceptions\FQException;
use app\domain\init\model\AppConfigModel;
use app\domain\reddot\model\RedDotItemModel;
use app\domain\reddot\RedDotModel;

class AppDataView
{
    public static function authVersionLite(AppConfigModel $model)
    {
        return [
            'md5Version' => $model->version,
            'checkSum' => $model->md5,
            'url' => self::initUrl($model->path)
        ];
    }


    /**
     * @info init url
     */
    private static function initUrl($filePath)
    {
        if (empty($filePath)) {
            throw new FQException("filepath error");
        }
        $filePath = ltrim($filePath, '/');
        $baseUrl = rtrim(config('config.APP_URL_image'), '/');
        return sprintf("%s/%s", $baseUrl, $filePath);
    }


    /**
     * @param RedDotModel $model
     * @return array
     */
    public static function viewReddot(RedDotItemModel $model)
    {
        return [
            'count' => $model->count,
//            'type' => $model->type,
        ];

    }

    /**
     * @param $number
     * @return array
     */
    private static function getReddotItemCount($number)
    {
        $data = [
            'count' => $number
        ];
        return $data;
    }

}