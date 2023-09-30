<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Utils\Im;

use LogicException;

class WangyiIM
{
    /**
     * https://dev.yunxin.163.com/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%AC%AC%E4%B8%89%E6%96%B9%E5%9B%9E%E8%B0%83.
     */
    # P2P消息
    public const EVENT_TYPE_P2P = 1;

    # 群组消息
    public const EVENT_TYPE_GROUP_MESSAGE = 2;

    # 用户资料变更
    public const EVENT_TYPE_USER_CHANGE_INFO = 3;

    # 添加好友
    public const EVENT_TYPE_USER_FRIEND_ADD = 4;

    # 删除好友
    public const EVENT_TYPE_USER_FRIEND_DELETE = 5;

    # 聊天室消息
    public const EVENT_TYPE_CHATROOM_MESSAGE = 6;

    # 创建群
    public const EVENT_TYPE_GROUP_CREATE = 7;

    # 解散群
    public const EVENT_TYPE_GROUP_DISBAND = 8;

    # 群邀请
    public const EVENT_TYPE_GROUP_INVITATION = 9;

    # 退群
    public const EVENT_TYPE_GROUP_OUT = 10;

    # 增加群管理员
    public const EVENT_TYPE_GROUP_ADMIN_ADD = 11;

    # 取消群管理员
    public const EVENT_TYPE_GROUP_ADMIN_CANCEL = 12;

    # 转让群
    public const EVENT_TYPE_GROUP_TRANSFER = 13;

    # 踢出群
    public const EVENT_TYPE_GROUP_KICK_OUT = 14;

    # 更新群信息
    public const EVENT_TYPE_GROUP_INFO_UPDATE = 15;

    # 更新群成员信息
    public const EVENT_TYPE_GROUP_USER_INFO_UPDATE = 16;

    # 更新其他人的群成员信息
    public const EVENT_TYPE_GROUP_OTHER_USER_INFO_UPDATE = 17;

    # 禁言群成员
    public const EVENT_TYPE_GROUP_SPEAK_BAN = 18;

    # 申请入群
    public const EVENT_TYPE_GROUP_USER_ADD_REQUEST = 19;

    # 音视频呼叫
    public const EVENT_TYPE_AUDIO_VIDEO_PASSIVE_CALL = 20;

    # 音视频会议创建
    public const EVENT_TYPE_AUDIO_VIDEO_CREATE = 21;

    # 超大群消息
    public const EVENT_TYPE_GROUP_BIG_MESSAGE = 22;

    # 超大群群邀请
    public const EVENT_TYPE_GROUP_BIG_INVITATION = 23;

    # 超大群踢人出群
    public const EVENT_TYPE_GROUP_BIG_KICK_OUT = 24;

    # 超大群退群
    public const EVENT_TYPE_GROUP_BIG_OUT = 25;

    # 更新超大群群信息
    public const EVENT_TYPE_GROUP_BIG_INFO_UPDATE = 26;

    # 更新超大群群成员信息
    public const EVENT_TYPE_GROUP_BIG_USER_INFO_UPDATE = 27;

    # 超大群申请入群
    public const EVENT_TYPE_GROUP_BIG_USER_ADD_REQUEST = 28;

    # 增加超大群管理员
    public const EVENT_TYPE_GROUP_BIG_ADMIN_ADD = 29;

    # 取消超大群管理员
    public const EVENT_TYPE_GROUP_BIG_ADMIN_CANCEL = 30;

    # 禁言超大群
    public const EVENT_TYPE_GROUP_BIG_SPEAK_BAN = 31;

    # 禁言超大群群成员
    public const EVENT_TYPE_GROUP_BIG_USER_SPEAK_BAN = 32;

    # 更新超大群里其他人的群成员信息
    public const EVENT_TYPE_GROUP_BIG_USER_CHANGE_INFO = 33;

    # 转让超大群
    public const EVENT_TYPE_GROUP_BIG_TRANSFER = 34;

    # 消息撤回(点对点||群消息)
    public const EVENT_TYPE_MESSAGE_WITHDRAW = 35;

    # 用户登录
    public const EVENT_TYPE_USER_LOGIN = 36;

    public const HEX_DIGITS = '0123456789abcdef';

    public const SUCCESS = 0;

    public const FAIL = 1;

    public $app_key;                // 开发者平台分配的app_key

    public $app_secret;             // 开发者平台分配的app_secret,可刷新

    public $request_type;             // 开发者平台分配的app_secret,可刷新

    public $nonce;                    // 随机数（最大长度128个字符）

    public $cur_time;                 // 当前UTC时间戳，从1970年1月1日0点0 分0 秒开始到现在的秒数(String)

    public $check_sum;                // SHA1(app_secret + nonce + cur_time),三个参数拼接的字符串，进行SHA1哈希计算，转化成16进制字符(String，小写)

    /**
     * 参数初始化.
     */
    public function __construct()
    {
        $this->app_key = config('main.wangyi.im.app_key');
        $this->app_secret = config('main.wangyi.im.app_secret');
        $this->request_type = config('main.wangyi.im.request_type');
    }

    /**
     * 获取消息类枚举.
     * @return int[]
     */
    public function getMessageConst(): array
    {
        return [
            self::EVENT_TYPE_P2P,
            self::EVENT_TYPE_GROUP_MESSAGE,
            self::EVENT_TYPE_CHATROOM_MESSAGE,
            self::EVENT_TYPE_GROUP_BIG_MESSAGE,
        ];
    }

    public function success($modify_response = [], $callback_ext = ''): array
    {
        return [
            'errCode' => self::SUCCESS,
            'responseCode' => 20000,
            'modifyResponse' => $modify_response,
            'callbackExt' => $callback_ext,
        ];
    }

    /**
     * @param array $modify_response ['body、attach、ext']
     * @param mixed $response_code
     */
    public function fail($response_code, array $modify_response = [], string $callback_ext = ''): array
    {
        if (20000 > $response_code || $response_code < 20099) {
            throw new LogicException('faild $response_code值有误');
        }
        return [
            'errCode' => self::FAIL,
            'responseCode' => $response_code,
            'modifyResponse' => $modify_response,
            'callbackExt' => $callback_ext,
        ];
    }

    /**
     * 创建云信id.
     * @param $accid 网易云通信ID
     * @param $name 云通信ID昵称
     */
    public function createAccid($accid, $name)
    {
        $url = 'https://api.netease.im/nimserver/user/create.action';
        $data = [
            'accid' => $accid,
            'name' => $name,
        ];
        if ($this->request_type == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 发送普通消息.
     * @param $from 发送者accid
     * @param $ope 0：点对点个人消息，1：群消息（高级群），其他返回414
     * @param $to ope==0是表示accid即用户id，ope==1表示tid即群id
     * @param mixed $type
     * @param mixed $body
     * @param mixed $option
     * @param mixed $pushcontent
     * @param mixed $payload
     * @param mixed $ext
     * @return $type 消息类型
     * @return $body 最大长度5000字符，JSON格式
     * @return $option 发消息时特殊指定的行为选项,JSON格式
     * @return $pushcontent 推送文案,最长500个字符
     * @return $payload 必须是JSON,不能超过2k字符
     * @return $ext 开发者扩展字段，长度限制1024字符
     */
    public function sendMessage($from, $ope, $to, $type, $body, $option, $pushcontent, $payload, $ext)
    {
        $url = 'https://api.netease.im/nimserver/msg/sendMsg.action';
        $data = [
            'from' => $from,
            'ope' => $ope,
            'to' => $to,
            'type' => $type,
            'body' => json_encode($body),
            'option' => json_encode($option),
            'pushcontent' => $pushcontent,
            'payload' => json_decode($payload),
            'ext' => json_decode($ext),
        ];
        if ($this->request_type == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 批量发送
     * @param $fromAccid 发送者accid
     * @param $toAccids ["aaa","bbb"]（JSONArray对应的accid，如果解析出错，会报414错误），限500人
     * @param mixed $type
     * @param mixed $body
     * @param mixed $option
     * @param mixed $pushcontent
     * @param mixed $payload
     * @param mixed $ext
     * @return $type 消息类型
     * @return $body 最大长度5000字符，JSON格式
     * @return $option 发消息时特殊指定的行为选项,JSON格式
     * @return $pushcontent 推送文案,最长500个字符
     * @return $payload 必须是JSON,不能超过2k字符
     * @return $ext 开发者扩展字段，长度限制1024字符
     */
    public function sendBatchMessage($fromAccid, $toAccids, $type, $body, $option, $pushcontent, $payload, $ext)
    {
        $url = 'https://api.netease.im/nimserver/msg/sendBatchMsg.action';
        $data = [
            'fromAccid' => $fromAccid,
            'toAccids' => json_encode($toAccids),
            'type' => $type,
            'body' => json_encode($body),
            'option' => json_encode($option),
            'pushcontent' => $pushcontent,
            'payload' => json_decode($payload),
            'ext' => json_decode($ext),
        ];
        if ($this->request_type == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 发送短信验证码
     * @param $templateid [模板编号(由客服配置之后告知开发者)]
     * @param $mobile [目标手机号]
     * @param $deviceId [目标设备号，可选参数]
     * @param mixed $codeLen
     * @return $codeLen [验证码长度,范围4～10，默认为4]
     */
    public function sendSmsCode($templateid, $mobile, $deviceId, $codeLen): array
    {
        $url = 'https://api.netease.im/sms/sendcode.action';
        $data = [
            'templateid' => $templateid,
            'mobile' => $mobile,
            'deviceId' => $deviceId,
            'codeLen' => $codeLen,
        ];
        if ($this->request_type == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 发送模板短信
     * @param $templateid [模板编号(由客服配置之后告知开发者)]
     * @param $mobiles [验证码]
     * @param $params [短信参数列表，用于依次填充模板，JSONArray格式，如["xxx","yyy"];对于不包含变量的模板，不填此参数表示模板即短信全文内容]
     * @return $result [返回array数组对象]
     */
    public function sendSMSTemplate($templateid, $mobiles = [], $params = '')
    {
        $url = 'https://api.netease.im/sms/sendtemplate.action';
        $data = [
            'templateid' => $templateid,
            'mobiles' => json_encode($mobiles),
            'params' => json_encode($params),
        ];
        if ($this->request_type == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 校验参数.
     * @param mixed $app_key
     * @param mixed $cur_time
     * @param mixed $md5
     * @param mixed $check_sum
     * @param mixed $body
     */
    public function checkAppid($app_key, $cur_time, $md5, $check_sum, $body): bool
    {
        if (md5($body) !== $md5) {
            return false;
        }
        return true;
    }

    /**
     * API check_sum校验生成.
     * @param void
     * @return $check_sum(对象私有属性)
     */
    protected function check_sumBuilder()
    {
        // 此部分生成随机字符串
        $hex_digits = self::HEX_DIGITS;
        // $this->nonce;
        for ($i = 0; $i < 128; ++$i) {            // 随机字符串最大128个字符，也可以小于该数
            $this->nonce .= $hex_digits[rand(0, 15)];
        }
        $this->cur_time = (string) time();    // 当前时间戳，以秒为单位

        $join_string = $this->app_secret . $this->nonce . $this->cur_time;
        $this->check_sum = sha1($join_string);
        // print_r($this->check_sum);
    }

    /**
     * 将json字符串转化成php数组.
     * @param mixed $json_str
     * @return $json_arr
     */
    protected function json_to_array($json_str)
    {
        if (is_array($json_str) || is_object($json_str)) {
            $json_str = $json_str;
        } else {
            if (is_null(json_decode($json_str))) {
                $json_str = $json_str;
            } else {
                $json_str = strval($json_str);
                $json_str = json_decode($json_str, true);
            }
        }
        $json_arr = [];
        foreach ($json_str as $k => $w) {
            if (is_object($w)) {
                $json_arr[$k] = $this->json_to_array($w); // 判断类型是不是object
            } else {
                if (is_array($w)) {
                    $json_arr[$k] = $this->json_to_array($w);
                } else {
                    $json_arr[$k] = $w;
                }
            }
        }
        return $json_arr;
    }

    /**
     * 使用CURL方式发送post请求
     * @param $url [请求地址]
     * @param $data [array格式数据]
     * @return $请求返回结果(array)
     */
    protected function postDataCurl($url, $data)
    {
        $this->check_sumBuilder();       // 发送请求前需先生成check_sum

        $timeout = 5000;
        $http_header = [
            'AppKey:' . $this->app_key,
            'Nonce:' . $this->nonce,
            'CurTime:' . $this->cur_time,
            'CheckSum:' . $this->check_sum,
            'Content-Type:application/x-www-form-urlencoded;charset=utf-8',
        ];
        // print_r($http_header);

        // $postdata = '';
        $postdataArray = [];
        foreach ($data as $key => $value) {
            array_push($postdataArray, $key . '=' . urlencode($value));
        }
        $postdata = join('&', $postdataArray);

        // var_dump($http_header, $postdata);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 处理http证书问题
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if ($result === false) {
            $result = curl_errno($ch);
        }
        curl_close($ch);
        return $this->json_to_array($result);
    }

    /**
     * 使用FSOCKOPEN方式发送post请求
     * @param $url [请求地址]
     * @param $data [array格式数据]
     * @return $请求返回结果(array)
     */
    protected function postDataFsockopen($url, $data)
    {
        $this->check_sumBuilder(); // 发送请求前需先生成check_sum

        $postdata = '';
        foreach ($data as $key => $value) {
            $postdata .= ($key . '=' . urlencode($value) . '&');
        }
        // building POST-request:
        $url_info = parse_url($url);
        if (! isset($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $request = '';
        $request .= 'POST ' . $url_info['path'] . " HTTP/1.1\r\n";
        $request .= 'Host:' . $url_info['host'] . "\r\n";
        $request .= "Content-type: application/x-www-form-urlencoded;charset=utf-8\r\n";
        $request .= 'Content-length: ' . strlen($postdata) . "\r\n";
        $request .= "Connection: close\r\n";
        $request .= 'AppKey: ' . $this->app_key . "\r\n";
        $request .= 'Nonce: ' . $this->nonce . "\r\n";
        $request .= 'CurTime: ' . $this->cur_time . "\r\n";
        $request .= 'CheckSum: ' . $this->check_sum . "\r\n";
        $request .= "\r\n";
        $request .= $postdata . "\r\n";

        print_r($request);
        $fp = fsockopen($url_info['host'], $url_info['port']);
        fputs($fp, $request);
        $result = '';
        while (! feof($fp)) {
            $result .= fgets($fp, 128);
        }
        fclose($fp);

        $str_s = strpos($result, '{');
        $str_e = strrpos($result, '}');
        $str = substr($result, $str_s, $str_e - $str_s + 1);
        print_r($result);
        return $this->json_to_array($str);
    }
}
