<?php

namespace app\web\controller;

//define your token
use app\BaseController;
use app\core\mysql\Sharding;
use app\query\backsystem\dao\MarketChannelModelDao;
use app\query\backsystem\dao\PromoteRoomConfModelDao;
use app\web\model\OpenInstallModel;
use app\web\service\OpenInstallService;
use Exception;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\Validate;

class OpenInstallController extends BaseController
{

    /**
     * 落地页绑定设备以及推广信息
     *
     * @param Request $request post请求信息
     *
     * @return void
     */
    public function bindOpeninstall(Request $request)
    {
        try {
            if (Request::instance()->isPost()) {
                $inputs = Request::param();
                // 验证模板类型 模板名称

                $validate = new Validate;
                $validate->rule([
                    'token' => 'require',
                    'key' => 'require',
                    'params' => 'require',
                ]);

                if (!$validate->check($inputs)) {
                    //打印错误规则，并返回页面
                    return rjson([], 403, $validate->getError());
                }
                $ip = $this->getIp();

                //校验推广码是否合法
                $params_arr = json_decode($inputs['params'], true);


                $promote_code = $params_arr['promoteCode'];
                $promote_info = PromoteRoomConfModelDao::getInstance()->getOne([["id", '=', $promote_code], ["id", '>=', 800001]]);
                $invitcode_info = MarketChannelModelDao::getInstance()->getOne([['invitcode', '=', $promote_code], ['invitcode', '<', 800001]]);

                if (empty($promote_info) && empty($invitcode_info)) {
                    $params_arr['promoteCode'] = 0;
                }
                $params_json = json_encode($params_arr);
                $info = $this->getKey($inputs, $ip);
                Log::info('LandInfo:' . json_encode($info));
                $openinstall_info = OpenInstallModel::getInstance()->getDeviceOld(ip2long($ip), $inputs['key'], $info['encry_keys']);
                if ($openinstall_info) {
                    OpenInstallModel::getInstance()->updateOne(ip2long($ip), $inputs['key'], $info['encry_keys'], ['referee_info' => $params_json,
                        'sc_width' => $info['scWidth'],
                        'sc_height' => $info['scHeight'],
                        'pixel_ratio' => $info['pixelRatio'],
                        'version' => $info['version'],
                        'renderer' => $info['renderer']]);
                } else {
                    Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function () use ($inputs, $info, $ip, $params_json) {
                        OpenInstallModel::getInstance()->storeData(
                            [
                                'ip' => ip2long($ip),
                                'key' => $inputs['key'],
                                'encry_key' => $info['encry_keys'][0],
                                'device_info' => $info['device_info'],
                                'referee_info' => $params_json,
                                'sc_width' => $info['scWidth'],
                                'sc_height' => $info['scHeight'],
                                'pixel_ratio' => $info['pixelRatio'],
                                'version' => $info['version'],
                                'renderer' => $info['renderer'],
                            ]
                        );
                        OpenInstallModel::getInstance()->storeData(
                            [
                                'ip' => ip2long($ip),
                                'key' => $inputs['key'],
                                'encry_key' => $info['encry_keys'][1],
                                'device_info' => $info['device_info'],
                                'referee_info' => $params_json,
                                'sc_width' => $info['scWidth'],
                                'sc_height' => $info['scHeight'],
                                'pixel_ratio' => $info['pixelRatio'],
                                'version' => $info['version'],
                                'renderer' => $info['renderer'],
                            ]
                        );
                        OpenInstallModel::getInstance()->storeData(
                            [
                                'ip' => ip2long($ip),
                                'key' => $inputs['key'],
                                'encry_key' => $info['encry_keys'][2],
                                'device_info' => $info['device_info'],
                                'referee_info' => $params_json,
                                'sc_width' => $info['scWidth'],
                                'sc_height' => $info['scHeight'],
                                'pixel_ratio' => $info['pixelRatio'],
                                'version' => $info['version'],
                                'renderer' => $info['renderer'],
                            ]
                        );
                    }
                    );
                }
                $params = json_decode($params_json, true);
                return rjson($params, 200, 'success');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return rjson([], 426, $e->getMessage());
        }
    }

    /**
     * 客户端获取玩家推广信息
     *
     * @param Request $request post请求信息
     *
     * @return void
     */

    public function getRefereeInfo(Request $request)
    {
        try {
            if (Request::instance()->isPost()) {
                $inputs = Request::param();
                Log::info('RefereeInfo:' . json_encode($inputs));
                $res = OpenInstallService::getInstance()->getRefereeInfo($inputs);
                Log::info('RefereeInfo:' . json_encode($res));
                return rjson($res, 200, 'success');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return rjson([], 410, 'fail');
        }
    }

    /**
     * 客户端获取玩家推广信息
     *
     * @param Request $request post请求信息
     *
     * @return void
     */

    public function getRefereeRoomInfo(Request $request)
    {
        try {
            if (Request::instance()->isPost()) {
                $inputs = Request::param();
                Log::info('RefereeInfo:' . json_encode($inputs));
                $res = OpenInstallService::getInstance()->getRefereeRoomInfo($inputs);
                Log::info('RefereeInfo:' . json_encode($res));
                return rjson($res, 200, 'success');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return rjson([], 410, 'fail');
        }
    }

    /**
     * 客户端获取玩家推广信息
     *
     * @param Request $request post请求信息
     *
     * @return void
     */

    public function getRefereeInfoOld(Request $request)
    {
        try {
            if (Request::instance()->isPost()) {
                $inputs = Request::param();
                // 验证模板类型 模板名称
                $validate = new Validate;
                $validate->rule([
                    'token' => 'require',
                    'key' => 'require',
                ]);

                if (!$validate->check($inputs)) {
                    //打印错误规则，并返回页面
                    return rjson([], 410, $validate->getError());
                }

                $ip = $this->getIp();
                $info = $this->getKey($inputs, $ip);
                Log::info('KeyInfo:' . json_encode($info));
                $openinstall_info = OpenInstallModel::getInstance()->getDevice(ip2long($ip), $inputs['key'], $info['encry_keys']);
                Log::info('RefereeInfo:' . json_encode(['params' => $openinstall_info['referee_info']]));
                return rjson(['params' => $openinstall_info['referee_info']], 200, 'success');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return rjson([], 410, 'fail');
        }
    }

    public function getKey($inputs, $ip)
    {
        // 对token进行解密
        $decrpt = $this->utilFun()[1]($inputs['token']);
        $decrpt_arr = explode(',', $decrpt);
        $scWidth = isset($decrpt_arr[0]) ? $decrpt_arr[0] : '';
        $scHeight = isset($decrpt_arr[1]) ? $decrpt_arr[1] : '';
        $pixelRatio = isset($decrpt_arr[2]) ? $decrpt_arr[2] : '';
        $version = isset($decrpt_arr[3]) ? $decrpt_arr[3] : '';
        $renderer = isset($decrpt_arr[4]) ? $decrpt_arr[4] : '';
        $device_info_arr = [
            'ip' => $ip,
            'scWidth' => $scWidth,
            'scHeight' => $scHeight,
            'pixelRatio' => $pixelRatio,
            'version' => $version,
            'renderer' => $renderer,
        ];
        $device_info = json_encode($device_info_arr);
        $encry_key_1 = md5("{$ip}:{$scWidth}:{$scHeight}:{$pixelRatio}");
        $encry_key_2 = md5("{$ip}:{$scWidth}:{$scHeight}:{$pixelRatio}:{$version}:{$renderer}");
        $encry_key_3 = md5("{$ip}");
        return [
            'device_info' => $device_info,
            'encry_keys' => [$encry_key_1, $encry_key_2, $encry_key_3],
            'scWidth' => $scWidth,
            'scHeight' => $scHeight,
            'pixelRatio' => $pixelRatio,
            'version' => $version,
            'renderer' => $renderer,
        ];
    }

    public function utilFun()
    {
        $ini = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_=";

        // function Encryt(e, n) {
        //     var t, r, o, a, c = -1,
        //         u = e.length,
        //         l = [0, 0, 0, 0];
        //     for (t = []; ++c < u; r = e[c]) {
        //         o = e[++c];
        //             l[0] = r >> 2;
        //             l[1] = (3 & r) << 4 | (o || 0) >> 4;
        //             c >= u ? l[2] = l[3] = 64 : (a = e[++c];
        //                 l[2] = (15 & o) << 2 | (a || 0) >> 6, l[3] = c >= u ? 64 : 63 & a),
        //             t.push(i.charAt(l[0]), i.charAt(l[1]), i.charAt(l[2]), i.charAt(l[3]));
        //     }
        //     var s = t.join("");
        //     return n ? s.replace(/=/g, "") : s
        // }

        function encryt($e, $n, $ini)
        {
            $t = $r = $o = $a = $c = -1;
            $u = count($e);
            $l = [0, 0, 0, 0];
            $t = [];

            for (; ++$c < $u;) {
                // echo "c=====>" . $c . PHP_EOL;
                $r = $e[$c];
                $o = $e[++$c];
                $l[0] = $r >> 2;
                // echo "l[0]=====>" . $l[0] . PHP_EOL;
                $l[1] = (3 & $r) << 4 | ($o || 0) >> 4;
                // echo "l[1]=====>" . $l[1] . PHP_EOL;

                // $c >= $u ? $l[2] = $l[3] = 64 : ($a = $e[++$c]; $l[2] = (15 & $o) << 2 | ($a || 0) >> 6, $l[3] = $c >= $u ? 64 : 63 & $a);

                if ($c >= $u) {
                    $l[2] = $l[3] = 64;
                } else {
                    $a = $e[$c];
                    $l[2] = (15 & $o) << 2 | ($a || 0) >> 6;
                    $l[3] = $c >= $u ? 64 : 63 & $a;
                }
                // echo "l[2]=====>" . $l[2] . PHP_EOL;
                // echo "l[3]=====>" . $l[3] . PHP_EOL;
                array_push($t, $ini[$l[0]], $ini[$l[1]], $ini[$l[2]], $ini[$l[3]]);
            }

            // var_dump($t);die;
            $s = implode("", $t);

            return $n ? str_replace('/=/g', "", $s) : $s;

        }

        // function ToChar(e) {
        //     var n, t = -1,
        //         r = e.length,
        //         i = [];
        //     if (/^[\x00-\x7f]*$/.test(e))
        //         for (; ++t < r;) i.push(e.charCodeAt(t));
        //     else
        //         for (; ++t < r;) n = e.charCodeAt(t), n < 128 ? i.push(n) : n < 2048 ? i.push(n >> 6 | 192, 63 & n | 128) : i.push(n >> 12 | 224, n >> 6 & 63 | 128, 63 & n | 128);
        //     return i
        // }

        function toChar($e)
        {
            $n = $t = -1;
            $r = strlen($e);
            $i = [];
            $res = preg_match("/^[\\x00-\\x7f]*$/", $e);
            if ($res) {
                for (; ++$t < $r;) {
                    echo "if:" . $t . PHP_EOL;
                    array_push($i, strpos($e, $e[$t]));
                }
            } else {
                for (; ++$t < $r;) {
                    echo "else:" . $t . PHP_EOL;
                    $n = strpos($e, $e[$t]);
                    $n < 128 ? array_push($i, $n) : ($n < 2048 ? array_push($i, $n >> 6 | 192, 63 & $n | 128) : array_push($i, $n >> 12 | 224, $n >> 6 & 63 | 128, 63 & $n | 128));
                }
            }
            return $i;
        }

        function decrpt($e, $i = '')
        {
            // echo 'i======>' . $i . "\n";
            // echo 'e======>' . $e . "\n";
            $n = $t = $r = $o = $a = $c = $u = $l = [];
            for ($s = 0; $s < strlen($e);) {
                if (isset($e{$s}) || $s == 0) {
                    $o = strpos($i, $e{$s++});
                    // echo 'ooo======>' . $s . '======' . $e{$s} . "\n";
                }

                if (isset($e{$s})) {
                    $a = strpos($i, $e{$s++});
                    // echo 'aaa======>' . $s . "\n";
                }

                if (isset($e{$s})) {
                    $c = strpos($i, $e{$s++});
                    // echo 'ccc======>' . $s . "\n";
                }

                if (isset($e{$s})) {
                    $u = strpos($i, $e{$s++});
                    // echo 'uuu======>' . $s . "\n";
                }

                $n = $o << 2 | $a >> 4;
                $t = (15 & $a) << 4 | $c >> 2;
                $r = (3 & $c) << 6 | $u;
                array_push($l, $n);
                64 != $c && array_push($l, $t);
                64 != $u && array_push($l, $r);
            }
            // echo 'l======>' . implode(',', $l) . "\n";
            return $l;
        }

        function toDigit(array $e)
        {
            $n = $t = $r = $i = [];
            $o = 0;
            for ($n = $t = $r = 0; $o < count($e);) {
                $n = $e[$o];
                if ($n < 128) {
                    array_push($i, utf8_encode(chr($n)));
                    $o++;
                } elseif ($n > 191 && $n < 224) {
                    if (isset($e[$o + 1])) {
                        $t = $e[$o + 1];
                    }
                    array_push($i, utf8_encode(chr((31 & $n) << 6 | 63 & $t)));
                    $o += 2;
                } else {
                    if (isset($e[$o + 1])) {
                        $t = $e[$o + 1];
                    }
                    if (isset($e[$o + 2])) {
                        $r = $e[$o + 2];
                    }
                    $char = chr((15 & $n) << 12 | (63 & $t) << 6 | 63 & $r);
                    preg_match('/[\x{4e00}-\x{9fa5}]/u', $char, $matchs);
                    if ($matchs) {
                        array_push($i, utf8_encode($char));
                    }
                    $o += 3;
                }
            }
            return implode('', $i);
        }

        $to_encryt = function ($n) use ($ini) {
            if (!$n) {
                return "";
            }
            $r = toChar($n);

            $i = count($r);
            for ($o = 0; $o < $i; $o++) {
                $r[$o] = 150 ^ $r[$o];
            }
            return encryt($r, !0, $ini);
        };

        $to_digit = function ($e) use ($ini) {
            if (!$e) {
                return "";
            }
            $t = decrpt($e, $ini);
            $o = count($t);
            for ($i = 0; $i < $o; $i++) {
                $t[$i] = 150 ^ $t[$i];
            }
            return toDigit($t);
        };

        $e_encryt = function ($n) use ($ini) {
            return $n ? encryt(toChar($n), !0, $ini) : "";
        };

        $e_digit = function ($e) {
            return $e ? toDigit(decrpt($e)) : "";
        };

        return [$to_encryt, $to_digit, $e_encryt, $e_digit];
    }

    protected function getIp()
    {
        $ip = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip_arr = explode(',', $ip);
        return $ip_arr[0];
    }


    public function xingTuCallBack()
    {
        //https://mybest.custom.com/click/?os=__OS__&ua=__UA__&ip=__IP__&ts=__TS__
        $os = Request::param("os", "", "trim");
        $ua = Request::param("ua", "", "trim");
        $ip = Request::param("ip", "", "trim");
        $client_time = Request::param("ts", "", "trim");
        $promote_code = Request::param("promote_code", "", "trim");
        $data = json_encode(Request::param());
        try {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $data = [
                    "os" => $os,
                    "ua" => $ua,
                    "ip" => $ip,
                    "promote_code" => $promote_code,
                    "click_time" => $client_time,
                    "create_time" => time(),
                    "data" => $data
                ];
                $dnName = Sharding::getInstance()->getDbName('commonMaster', 0);
                Db::connect($dnName)->table("zb_xingtu_callback")->insert($data);
            }
        } catch (\Throwable $e) {
            Log::INFO("xingTuCallBack:" . $e->getMessage());
        }
        return "success";
    }

}
