<?php

namespace app\utils;


/**
 * curl请求orm 类
 * Class RequestOrm
 *
 * @package app\utils
 */
class RequestOrm
{
    private $header;
    private $options;

    public function __construct($header = array(), $options = array())
    {
        $this->header = empty($header) ? $this->defaultHeader() : $header;
        $this->options = empty($options) ? $this->defaultOptions() : $options;
    }

    private function defaultHeader()
    {
        return [];
    }

    private function defaultOptions()
    {
        $result['timeout'] = 10;
        $result['connect_timeout'] = 10;
        return $result;
    }

    /**
     * @param $requestUrl
     * @param $paramData
     * @return string
     */
    public function post(string $requestUrl,$paramData): string
    {
        if (empty($requestUrl)) {
            return "";
        }
        $response = \Requests::post($requestUrl, $this->header, $paramData, $this->options);
        if (empty($response)) {
            return "";
        }
        return $response->body;
    }


    /**
     * @param $requestUrl
     * @return string
     */
    public function get(string $requestUrl): string
    {
        if (empty($requestUrl)) {
            return "";
        }
        $response = \Requests::get($requestUrl, $this->header, $this->options);
        if (empty($response)) {
            return '';
        }
        return $response->body;
    }


}
