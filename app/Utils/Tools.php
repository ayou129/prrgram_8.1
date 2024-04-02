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

namespace App\Utils;

use Exception;
use Hyperf\Logger\Logger;
use Hyperf\ModelCache\Config;
use Hyperf\Utils\ApplicationContext;
use Illuminate\Support\Facades\Log;
use LogicException;

class Tools
{
    public static function getRedis()
    {
        $container = ApplicationContext::getContainer();
        return $container->get(\Hyperf\Redis\Redis::class);
    }

    /**
     * @param $plaintext | 加密字符串
     * @param mixed $cipher
     */
    public static function encrypt($plaintext, $cipher = 'aes-128-ecb')
    {
        if (! in_array($cipher, openssl_get_cipher_methods())) {
            throw new LogicException('error openssl method');
        }
        // $key以前应该以加密安全的方式生成，例如openssl_random_pseudo_bytes
        // $key should have been previously generated in a cryptographically safe way, like openssl_random_pseudo_bytes
        $key = config('main.encrypt.default.key');
        $key = hex2bin((string) $key);
        // var_dump($key);
        // $iv = config('main.encrypt.iv');
        try {
            return openssl_encrypt($plaintext, $cipher, $key);
        } catch (Exception $e) {
            Logger::error('解密失败：' . $e->getMessage());
            return false;
        }
    }

    public static function decrypt($ciphertext, $cipher = 'aes-128-ecb')
    {
        if (! in_array($cipher, openssl_get_cipher_methods())) {
            throw new LogicException('error openssl method');
        }
        try {
            $key = config('main.encrypt.default.key');
            $key = hex2bin($key);
            return openssl_decrypt($ciphertext, $cipher, $key);
        } catch (Exception $e) {
            Log::error('解密失败：' . $e->getMessage());
            return false;
        }
    }

    // 生成随机密码

    public static function generateRandomPassword($length = 80)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';

        for ($i = 0; $i < $length; ++$i) {
            $index = random_int(0, strlen($characters) - 1);
            $password .= $characters[$index];
        }

        return $password;
    }

    /**
     * 当前环境是否是正式环境.
     */
    public static function isProduct(): bool
    {
        return \Hyperf\Support\env('APP_ENV', 'dev') === 'production';
    }

    public static function paramsFilter(&$val)
    {
        switch (true) {
            case is_array($val):
                foreach ($val as &$value) {
                    self::paramsFilter($value);
                }
                break;
            case is_string($val):
                $val = trim($val);
                break;
            default:
        }
    }

    /**
     * 格式化js的时间戳 To PHP的时间戳格式.
     * @param mixed $paramsArray
     */
    public static function formatJSTimestampToPHPTimestamp(&$paramsArray)
    {
        foreach ($paramsArray as $key => &$value) {
            if (is_numeric($value) && substr((string) $key, -3) === '_at') {
                try {
                    // var_dump($paramsArray[$key], $key);
                    $value = substr((string) $value, 0, 10);
                    $value = date('Y-m-d H:i:s', (int) $value);
                } catch (Exception $e) {
                    var_dump($e->getMessage());
                    continue;
                }
            }
        }
    }

    /**
     * 格式化数组.
     * @param mixed $paramsArray
     */
    public static function paramsDetectJsonArrays(&$paramsArray)
    {
        foreach ($paramsArray as $key => &$value) {
            if (isset($value[0]) && $value[0] === '[' && $value[-1] === ']') {
                $v = json_decode($value, true);
                if ($v !== false && $v !== null) {
                    $paramsArray[$key] = $v;
                }
            }
        }
    }

    // 判断两天是否是同一天
    public static function isSameDays($last_date, $this_date)
    {
        try {
            $last_date_timestamp_getdate = getdate(strtotime($last_date));
            $this_date_timestamp_getdate = getdate(strtotime($this_date));
            if (($last_date_timestamp_getdate['year'] === $this_date_timestamp_getdate['year'])
                && ($last_date_timestamp_getdate['mon'] === $this_date_timestamp_getdate['mon'])
                && ($last_date_timestamp_getdate['mday'] === $this_date_timestamp_getdate['mday'])
            ) {
                return true;
            }
            // var_dump($last_date_timestamp_getdate['year'], $this_date_timestamp_getdate['year']);
            // var_dump($last_date_timestamp_getdate['mon'], $this_date_timestamp_getdate['mon']);
            // var_dump($last_date_timestamp_getdate['mday'], $this_date_timestamp_getdate['mday']);
        } catch (Exception $e) {
            throw $e;
        }
        return false;
    }

    public static function getNowDate(int $timestamp = 0)
    {
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        return date('Y-m-d H:i:s');
    }

    public static function add($number1, $number2, $scale = 2)
    {
        return (float) bcadd((string) $number1, (string) $number2, $scale);
    }

    public static function sub($number1, $number2, $scale = 2)
    {
        return (float) bcsub((string) $number1, (string) $number2, $scale);
    }

    public static function mul($number1, $number2, $scale = 2)
    {
        return (float) bcmul((string) $number1, (string) $number2, $scale);
    }

    public static function div($number1, $number2, $scale = 2)
    {
        return (float) bcdiv((string) $number1, (string) $number2, $scale);
    }

    public static function convertModelArrayToJsComponentOptions($model_array)
    {
        $result = [];
        foreach ($model_array as $key => $value) {
            $result[] = ['value' => $key, 'label' => $value];
        }
        return $result;
    }

    /**
     * 将字符串内的大写字母转换成下划线加小写字母.
     * @param string $str
     */
    public static function strToUnderLineSpacing($str): string
    {
        $tmp_array = [];

        for ($i = 0; $i < strlen($str); ++$i) {
            $ascii_code = ord($str[$i]);
            if ($ascii_code >= 65 && $ascii_code <= 90) {
                if ($i == 0) {
                    $tmp_array[] = chr($ascii_code + 32);
                } else {
                    $tmp_array[] = '_' . chr($ascii_code + 32);
                }
            } else {
                $tmp_array[] = $str[$i];
            }
        }

        return implode('', $tmp_array);
    }

    /**
     * 加密字符串中所有手机号.
     * @param mixed $str
     * @param mixed $character
     */
    public static function phoneEncrypt($str, $character = '*')
    {
        $pattern = '/(\d{3})\d{4}(\d{4})/';
        $preg_character = '';
        for ($i = 0; $i < 4; ++$i) {
            $preg_character .= $character;
        }
        $replace = '${1}' . $preg_character . '${2}';
        return preg_replace($pattern, $replace, $str);
    }

    /**
     * @param string $url get请求地址
     * @param int $httpCode 返回状态码
     * @return mixed
     */
    public function curlGet($url, &$httpCode = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // 不做证书校验,部署在linux环境下请改为true
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $file_contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $file_contents;
    }

    public function curlPost($url, $data = [], &$httpCode = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $output;
    }

    public static function genExcelColNameFromArrayIndex($array_index)
    {
        // 将数组索引值加上1，因为Excel列索引是从1开始（对应A）
        $excel_index = $array_index + 1;

        // 使用之前的逻辑生成Excel列名
        $result = '';
        while ($excel_index >= 0) {
            $char_index = intval($excel_index / 26);
            $remainder = $excel_index % 26;

            if ($remainder == 0) {
                --$char_index;
                $remainder = 26;
            }

            $result = chr(ord('A') + $remainder - 1) . $result;
            $excel_index = $char_index - 1;
        }

        return $result;
    }

    public static function formatUtf8($text)
    {
        return mb_convert_encoding($text, 'UTF-8', 'auto');
    }

    /**
     * 组合生成带有children的树.
     * @param mixed $departments
     * @param mixed $parentId
     */
    public static function reorganizeDepartments($departments, $parentId = 0)
    {
        $result = [];
        foreach ($departments as $department) {
            if ($department['pid'] == $parentId) {
                $children = self::reorganizeDepartments($departments, $department['id']);
                if (! empty($children)) {
                    $department['children'] = $children;
                }
                $result[] = $department;
            }
        }
        return $result;
    }
}
