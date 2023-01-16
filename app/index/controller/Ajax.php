<?php
declare (strict_types=1);

namespace app\index\controller;

use app\admin\validate\AuthCreate;
use app\BaseController;
use app\common\controller\Helper;
use app\common\controller\Json;
use app\common\model\App as AppModel;
use app\common\model\Auth as AuthModel;
use app\common\model\Card;
use app\common\model\Log;
use app\common\model\User;
use app\common\model\Version as VersionModel;
use app\Request;
use app\UserError;
use think\exception\ValidateException;
use Exception;

class Ajax extends BaseController
{
    /**
     * 获取APP列表
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAppList(Request $request)
    {
        $type = $request->param('type/s', 'detail');

        if ($type === 'detail') {
            // 获取APP详情列表
            $data = AppModel::field('id,name,author,team_name,qq,url')->order('id', 'desc')->select();
            foreach ($data ?: [] as $key => &$value) {
                $value->get_version = VersionModel::field('version,release_time,content')->where('appid', $value->id)->order('id', 'desc')->find();
            }
        } else {
            // 获取APP列表
            $data = AppModel::field('id,name')->select();
        }

        if ($data) {
            return Json::success()->data($data)->res();
        }

        return Json::error()->res();
    }

    /**
     * 查询授权
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryAuth(Request $request)
    {
        // 应用ID
        $appid = $request->param('appid/i');
        // 授权信息
        $authContent = $request->param('auth_content');
        // 授权ID
        $authId = $request->param('auth_id');

        // 返回信息
        $resData = ['name' => '信息'];

        // 查询条件
        $where = ['appid' => $appid];

        if (empty($appid)) {
            return Json::error('请选择查询的应用')->res();
        }

        if (notEmpty($authId)) {
            $resData['name'] = '授权ID';
            $resData['value'] = $authId;
            $where['auth_id'] = $authId;
        } elseif (notEmpty($authContent)) {
            $resData['value'] = $authContent;
            $where['auth_content'] = $authContent;
        } else {
            return Json::error('请输入查询信息')->res();
        }

        $res = AuthModel::field('id,expire_time')->where($where)->find();

        if ($res) {
            $resData['expire_time'] = $res->expire_time ? date('Y-m-d H:i:s', $res->expire_time) : '永久';
            return Json::success()->status($res->expire_time != 0 && $res->expire_time < time() ? -1 : 1)->data($resData)->res();
        }

        return Json::error('未查询到相关信息')->res();
    }

    /**
     * 创建授权
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createAuth(Request $request)
    {
        // 应用ID
        $appid = $request->param('appid/i');
        // 授权信息
        $authContent = trim($request->param('auth_content/s', ''));
        // 授权QQ
        $qq = trim($request->param('qq/s', ''));
        // 授权卡密
        $cardNumber = trim($request->param('card_number/s', ''));

        try {
            // 验证
            validate(AuthCreate::class)->scene('OnlineAuth')->check([
                'appid' => $appid,
                'auth_content' => $authContent,
                'card_number' => $cardNumber,
                'qq' => $qq,
            ]);

            // 获取卡密信息
            $card = Card::where('card_number', $cardNumber)->where('appid', $appid)->where('status', 1)->find();

            // 查询授权信息
            $auth = AuthModel::where('appid', $appid)
                ->where('auth_content', $authContent)
                ->find();

            // 当前时间戳
            $time = time();

            // 续费
            if ($auth) {
                // 永久授权检测
                if ($auth->expire_time === 0) {
                    ue('授权信息[' . $authContent . ']已为永久授权, 无需重复授权');
                }

                // 计时起点时间戳
                $expireTime = $auth->expire_time < $time ? $time : $auth->expire_time;

            } else {
                // 新开
                $auth = new AuthModel();
                $expireTime = $time;
            }

            if ($card->day === 0) {
                // 永久授权
                $expireTime = 0;
            } else {
                // 计算最后到期时间
                $expireTime += $card->day * 86400;
            }

            // 开启事务
            AuthModel::startTrans();

            // 写入数据库
            $auth->save([
                'qq' => $qq,
                'appid' => $appid,
                'auth_content' => $authContent,
                'create_name' => $card->create_name,
                'create_user' => $card->create_user,
                'expire_time' => $expireTime,
            ]);

            // 设置卡密状态 (重复查询是为了避免卡密重复使用)
            Card::where('card_number', $cardNumber)
                ->where('appid', $appid)
                ->where('status', 1)
                ->find()
                ->save(['usage_time' => $time, 'status' => 0]);

            // 写授权创建日志
            Log::write([
                'type' => 'create_auth',
                'content' => '自助授权,应用ID: ' . $appid . ';授权信息: '
                    . $authContent . ';授权QQ: ' . $qq . ';使用卡密: ' . $cardNumber
                    . '到期时间: ' . ($expireTime ? date('Y-m-d H:i:s', $expireTime) : '永久')
            ]);

            // 提交事务
            AuthModel::commit();

            return Json::success()->data(['expire_time' => $expireTime ? date('Y-m-d H:i:s', $expireTime) : '永久'])->res();
        } catch (ValidateException $e) {
            return Json::error($e->getMessage())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        } catch (Exception $e) {
            // 回滚事务
            AuthModel::rollback();
            return Json::error('创建授权失败' . $e->getMessage())->res();
        }
    }

    /**
     * 代理查询
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agentQuery(Request $request)
    {
        // 代理用户名
        $agentUserName = trim($request->param('username/s', ''));
        // 代理QQ
        $agentQq = trim($request->param('qq/s', ''));

        $resArr = ['name' => '用户名'];

        if (notEmpty($agentQq)) {
            $resArr['name'] = 'QQ';
            $resArr['value'] = $agentQq;
            $where = ['qq' => $agentQq];
        } else if (notEmpty($agentUserName)) {
            $resArr['value'] = $agentUserName;
            $where = ['username' => $agentUserName];
        } else {
            return Json::error('请输入查询信息')->res();
        }

        $res = User::where($where)->find();

        return Json::success()->status($res ? 1 : -1)->data($resArr)->res();
    }

    /**
     * 获取应用更新历史
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function getUpdateHistory(Request $request)
    {
        // 应用ID
        $appid = $request->param('appid/i');

        $v = new VersionModel();

        try {
            $res = $v->field('a2.name,a2.author,a2.team_name,a2.qq,a2.url,a1.version,a1.release_time,a1.content')
                ->alias('a1')
                ->join('app a2', 'a2.id = a1.appid')
                ->where('a1.appid', $appid)
                ->order('a1.id', 'desc')
                ->select();
            return Json::success()->data($res)->res();
        } catch (Exception $e) {
            return Json::error('查询失败, 请稍后再试')->res();
        }
    }

    /**
     * 获取QQ登录二维码
     * @access public
     * @param Request $request
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQqQrcode(Request $request)
    {
        // 应用ID
        $appid = $request->param('appid/i');

        $app = AppModel::find($appid);

        if (!$app) {
            return Json::error('应用ID不存在')->res();
        }

        // 获取QQ扫码二维码
        list($status, $info) = Helper::getQrcodeUrl();

        if (!$status) {
            return Json::error($info)->res();
        }

        // 记录下载参数
        session('download_params', ['appid' => $app->id, 'qrsig' => $info['qrsig']]);

        return Json::success()->data($info)->res();
    }

    /**
     * 获取QQ二维码扫描状态
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQqQrcodeStatus()
    {
        // 获取记录的参数
        $params = session('download_params');

        if (!($params && isset($params['appid']) && isset($params['qrsig']))) {
            return Json::error('请先获取QQ登录二维码')->res();
        }

        // 获取二维码扫描状态
        list($status, $info) = Helper::getQrcodeStatus($params['qrsig']);

        if (!$status) {
            return Json::error($info)->res();
        }

        // 扫码成功
        if ($info['code'] === 3) {
            // 获取授权列表
            $auth = AuthModel::field('id,auth_content,auth_id')
                ->where('appid', $params['appid'])
                ->where('qq', $info['msg'])
                ->where('expire_time = 0 or expire_time > ' . time())
                ->select();

            if ($auth->isEmpty()) {
                $info['code'] = 4;
            } else {
                $info['auth'] = $auth;
                // 有效时间10分钟
                $params['time'] = time() + 60 * 10;
                // QQ记录
                $params['qq'] = $info['msg'];
                session('auth_download', $params);
                session('download_params', null);
            }
        }

        return Json::success()->data($info)->res();
    }

    /**
     * 下载源码
     * @access public
     * @param Request $request
     * @return \think\response\File
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function downloadSourceCode(Request $request)
    {
        // 授权ID
        $authId = $request->param('id');
        // 下载类型
        $type = $request->param('type', 'complete');
        // 获取记录的参数
        $params = session('auth_download');

        if (!($params && isset($params['appid']) && isset($params['qrsig']) && isset($params['time']) && isset($params['qq']))) {
            return Json::error('请重新扫码后再试')->res();
        }

        // 下载超时验证
        if ($params['time'] < time()) {
            return Json::error('下载链接失效, 请重新扫码')->res();
        }

        // 获取授权信息
        $auth = AuthModel::where('appid', $params['appid'])
            ->where('qq', $params['qq'])
            ->where('expire_time = 0 or expire_time > ' . time())
            ->find($authId);

        if (!$auth) {
            return Json::error('授权信息不存在或已过期')->res();
        }

        // 获取文件
        list($status, $info) = Helper::downloadAuthFile($auth, $type == 'complete' ? 'complete_file' : 'update_file');

        if (!$status) {
            return Json::error($info)->res();
        }

        return download(Helper::readfileAndDel($info), basename($info), true);
    }
}
