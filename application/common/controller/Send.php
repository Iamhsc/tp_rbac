<?php

namespace app\common\controller;


trait Send
{
    /**
     * 返回信息
     * @param int $code
     * @param string $message
     * @param array $data
     * @param array $header
     */
    public static function returnMsg($code = 200, $message = '', $data = [], $header = [])
    {
        http_response_code($code);    //设置返回头部
        $return['code'] = (int)$code;
        $return['msg'] = $message;
        $return['data'] = $data;
        // 发送头部信息
        foreach ($header as $name => $val) {
            if (is_null($val)) {
                header($name);
            } else {
                header($name . ':' . $val);
            }
        }
        exit(json_encode($return, JSON_UNESCAPED_UNICODE));
    }
}

