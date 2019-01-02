<?php
/**
 * Created by PhpStorm.
 * User: Alex Kung
 * Date: 1/2/2019
 * Time: 3:50 PM
 */

namespace app\api\lib;

class LjSms
{
    private static $config = [
        'normalUrl'  => 'http://api.ljioe.cn/api/v1/sms',
        'tpUrl'      => 'http://api.ljioe.cn/api/v1/smsTp',
        'replayUrl'  => 'http://api.ljioe.cn/api/v1/reply',
        'accountNum' => 'http://api.ljioe.cn/api/v1/accountNum',
        'report'     => 'http://api.ljioe.cn/api/v1/report',

        'account' => '',
        'key'     => '',
        'code'    => '',

        'templates' => [
            'A' => [
                'template'   => '【xxx】xxxxxxxxxxxxxxxx{}xxxxx',
                'tmeplateId' => '1000'
            ],
            'B' => [
                'template'   => '',
                'tmeplateId' => ''
            ]
        ],

    ];

    // 自定义短信内容的时候满足联江的前缀要求，可在此定义，也可以直接在自定义消息内容里加上
    // private static $pre = '';
    private static $pre = '【xxxx】';

    /**
     * 普通信息单群发
     * 
     * @param string $content 自定义发送的短信内容
     * @param array  $mobiles 接收短信的手机号
     *
     * @return array
     */
    public static function normalSend(string $content, array $mobiles = [])
    {
        $content   = LjSms::$pre . $content;
        $mobile    = implode(',', $mobiles);
        $timestamp = time();

        $data = [
            'content'   => $content,
            'mobile'    => $mobile,
            'code'      => LjSms::$config['code'],
            'ext'       => '',
            'account'   => LjSms::$config['account'],
            'key'       => md5(LjSms::$config['key'] . md5($timestamp)),
            'timestamp' => $timestamp
        ];
        $data = json_encode($data, true);
        $res  = http_curl(LjSms::$config['normalUrl'], 'POST', $data, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ));
        $res  = json_decode($res, true);
        if (20000 == $res['code']) {
            return ['msg' => $res['desc'], 'err' => null];
        } else {
            return ['msg' => $res['desc'], 'err' => $res['code']];
        }
    }

    /**
     * 模板信息单群发
     * 
     * @param string $template 配置中的模板信息
     * @param string $mobile 接收短信的手机号
     * @param array  $data 应用于模板中的数据
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function templateSend(string $template = 'A', string $mobile = '', array $data = [])
    {
        if (!in_array($template, array_keys(LjSms::$config['templates']))) {
            throw new \Exception('请求模板不在配置中');
        }
        $timestamp = time();
        $list      = [
            [
                'mobile'  => $mobile,
                'content' => $data
            ]
        ];
        $data      = [
            'template'   => LjSms::$config['templates']['A']['template'],
            'templateId' => LjSms::$config['templates']['A']['templateId'],
            'code'       => LjSms::$config['code'],
            'ext'        => '',
            'account'    => LjSms::$config['account'],
            'key'        => md5(LjSms::$config['key'] . md5($timestamp)),
            'timestamp'  => $timestamp,
            'list'       => $list
        ];

        $data = json_encode($data, true);
        $res  = http_curl(LjSms::$config['normalUrl'], 'POST', $data, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ));
        if (20000 == $res['code']) {
            return ['msg' => $res['desc'], 'err' => null];
        } else {
            return ['msg' => $res['desc'], 'err' => $res['code']];
        }

    }

    /**
     * @param string $template 配置中的模板信息
     * @param array  $data 接收短信的每个手机号和填补模板信息的数据组合
     * [
     *  [
     *     'mobile' => '136xxxx6666',
     *     'content' => ['xxx','xxx']
     *  ],
     *  [
     *     'mobile' => '136xxxx8888',
     *     'content' => ['yyy','yyy']
     *  ]
     * ]
     *
     * @return array
     * @throws \Exception
     */
    public static function templateMultiSend(string $template = 'A', array $data = [])
    {
        $timestamp = time();

        $list = [];
        foreach ($data as $datum) {
            if (empty($datum['mobile']) || empty($datum['content'] || !is_array($datum['content']))) {
                throw new \Exception('数据格式不正确');
            }
            $list[] = [
                'mobile'  => $datum['mobile'],
                'content' => $datum['content']
            ];
        }
        $data = [
            'template'   => LjSms::$config['templates']['A']['template'],
            'templateId' => LjSms::$config['templates']['A']['templateId'],
            'code'       => LjSms::$config['code'],
            'ext'        => '',
            'account'    => LjSms::$config['account'],
            'key'        => md5(LjSms::$config['key'] . md5($timestamp)),
            'timestamp'  => $timestamp,
            'list'       => $list
        ];

        $data = json_encode($data, true);
        $res  = http_curl(LjSms::$config['normalUrl'], 'POST', $data, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ));

        if (20000 == $res['code']) {
            return ['msg' => $res['desc'], 'err' => null];
        } else {
            return ['msg' => $res['desc'], 'err' => $res['code']];
        }
    }

    /**
     * 余额查询
     *
     * @return array
     */
    public static function accountNum()
    {
        $timestamp = time();

        $data = [
            'account'   => LjSms::$config['account'],
            'key'       => md5(LjSms::$config['key'] . md5($timestamp)),
            'timestamp' => $timestamp
        ];
        $data = json_encode($data, true);
        $res  = http_curl(LjSms::$config['accountNum'], 'POST', $data, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ));
        $res  = json_decode($res, true);
        if (20000 == $res['code']) {
            return ['smsNum' => $res['smsNum'], 'msg' => $res['desc'], 'err' => null];
        } else {
            return ['msg' => $res['desc'], 'err' => $res['code']];
        }
    }

    /**
     * 状态报告
     *
     * @return array
     */
    public static function statusReports()
    {
        $timestamp = time();

        $data = [
            'account'   => LjSms::$config['account'],
            'key'       => md5(LjSms::$config['key'] . md5($timestamp)),
            'timestamp' => $timestamp
        ];
        $data = json_encode($data, true);
        $res  = http_curl(LjSms::$config['report'], 'POST', $data, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ));
        $res  = json_decode($res, true);
        if (20000 == $res['code']) {
            return ['data' => $res['list'], 'msg' => $res['desc'], 'err' => null];
        } else {
            return ['msg' => $res['desc'], 'err' => $res['code']];
        }
    }

    /**
     * 查看上行回复
     *
     * @return array
     */
    public static function replies()
    {
        $timestamp = time();

        $data = [
            'account'   => LjSms::$config['account'],
            'key'       => md5(LjSms::$config['key'] . md5($timestamp)),
            'timestamp' => $timestamp
        ];
        $data = json_encode($data, true);
        $res  = http_curl(LjSms::$config['replayUrl'], 'POST', $data, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ));
        $res  = json_decode($res, true);
        if (20000 == $res['code']) {
            return ['data' => $res['list'], 'msg' => $res['desc'], 'err' => null];
        } else {
            return ['msg' => $res['desc'], 'err' => $res['code']];
        }
    }
}
//--------------------------------------------------------------------------------------------
//------- LjSms SDK 中用到的HTTP请求函数
//--------------------------------------------------------------------------------------------

// 封装的请求接口 HTTP 请求函数
function http_curl($url, $method = 'GET', $data = null, $header = null)
{
    $ch = curl_init();
    if ( 'GET' == $method && !empty($data) ) {
        $url .= '?' . http_build_query($data);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $header = is_null($header) ? 
    ['User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:33.0) Gecko/20100101 Firefox/33.0'] : $header;
    curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ( in_array($method,['POST','PUT','DELETE']) ) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// 封装的请求接口 HTTPS 请求函数
function https_curl($url, $method = 'GET', $data = null, $header = null)
{
    $ch = curl_init();
    if ( 'GET' == $method && !empty($data) ) {
        $url .= '?' . http_build_query($data);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $header = is_null($header) ? 
    ['User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:33.0) Gecko/20100101 Firefox/33.0'] : $header;
    curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    if ( in_array($method,['POST','PUT','DELETE']) ) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
    