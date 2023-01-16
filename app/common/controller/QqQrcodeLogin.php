<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/11/23 23:22:45
 * 
 */

namespace app\common\controller;

/**
 * Class QqQrcodeLogin
 * @package app\common\controller
 */
class QqQrcodeLogin
{
    /**
     * 获取二维码地址
     * @access public
     * @return array
     */
    public function getQrcodeUrl()
    {
        $url = 'https://ssl.ptlogin2.qq.com/ptqrshow?appid=549000912&e=2&l=M&s=3&d=72&v=4&t=0.5409099' . time() . '&daid=5';
        $arr = $this->curl($url, 0, 0, 0, 1, 0, 0, 1);
        $arr['header'];

        preg_match('/qrsig=(.*?);/', $arr['header'], $match);
        if ($qrsig = $match[1]) {
            return [true, ['img' => base64_encode($arr['body']), 'qrsig' => $qrsig]];
        } else {
            return [false, '二维码获取失败'];
        }

    }

    /**
     * 获取登录状态
     * @access public
     * @param $qrsig
     * @return array
     */
    public function queryStatus($qrsig)
    {
        $url = 'https://ssl.ptlogin2.qq.com/ptqrlogin?u1=http%3A%2F%2Fqzs.qq.com%2Fqzone%2Fv5%2Floginsucc.html%3Fpara%3Dizone&ptqrtoken=' . $this->getQrToken($qrsig) . '&ptredirect=0&h=1&t=1&g=1&from_ui=1&ptlang=2052&action=0-0-' . time() . '0000&js_ver=10194&js_type=1&pt_uistyle=40&aid=549000912&daid=5&';
        $ret = $this->curl($url, 0, $url, 'qrsig=' . $qrsig . '; ', 1, 0, 0, 1);
        $ret = $ret['body'];
        if (preg_match("/ptuiCB\('(.*?)'\)/", $ret, $arr)) {
            $data = explode("','", str_replace("', '", "','", $arr[1]));
            if ($data[0] == 0) {
                preg_match('/uin=(.*?)&/', $data[2], $uin);
                $uin = $this->getUin($uin[1]);
                return [true, ['code' => 3, 'msg' => $uin]];
            } elseif ($data[0] == 66) {
                return [true, ['code' => 1, 'msg' => '二维码未失效。']];
            } elseif ($data[0] == 67) {
                return [true, ['code' => 2, 'msg' => '正在验证二维码。']];
            } else {
                return [true, ['code' => -1, 'msg' => '二维码已失效。']];
            }
        } else {
            return [false, '查询失败'];
        }
    }

    private function getUin($uin)
    {
        for ($i = 0; $i < strlen($uin); $i++) {
            if ($uin[$i] == 'o' || $uin[$i] == '0') {
                continue;
            } else {
                break;
            }

        }
        return substr($uin, $i);
    }

    private function getQrToken($qrsig)
    {
        $len = strlen($qrsig);
        $hash = 0;
        for ($i = 0; $i < $len; $i++) {
            $hash += (($hash << 5) & 2147483647) + ord($qrsig[$i]) & 2147483647;
            $hash &= 2147483647;
        }
        return $hash & 2147483647;
    }

    private function curl($url, $post = 0, $referer = 0, $cookie = 0, $header = 0, $ua = 0, $nobaody = 0, $split = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $httpheader[] = "Accept:application/json";
        $httpheader[] = "Accept-Encoding:gzip,deflate,sdch";
        $httpheader[] = "Accept-Language:zh-CN,zh;q=0.8";
        $httpheader[] = "Connection:close";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if ($header) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        if ($ua) {
            curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.152 Safari/537.36');
        }
        if ($nobaody) {
            curl_setopt($ch, CURLOPT_NOBODY, 1);

        }
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        if ($split) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($ret, 0, $headerSize);
            $body = substr($ret, $headerSize);
            $ret = array();
            $ret['header'] = $header;
            $ret['body'] = $body;
        }
        curl_close($ch);
        return $ret;
    }
}