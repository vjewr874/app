<?php
declare (strict_types=1);

namespace app\api\controller\v1;

use app\common\controller\Json;
use app\common\model\Version;
use app\UserError;

class Query extends BaseController
{
    /**
     * 查询授权
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function auth()
    {
        // 授权ID
        $authId = $this->body['auth_id'];
        // 授权内容
        $authContent = $this->body['data']['auth_content'] ?? null;

        // 日志名称
        $this->log['name'] = '查询授权';
        // 日志查询内容
        $this->log['auth_content'] = $authContent;

        try {
            // 查询授权信息
            $res = $this->getAuthFind($authId, $authContent);

            return $this->result(Json::success()->data([
                'appid' => $res->appid,
                'auth_id' => $res->id,
                'auth_content' => $res->auth_content,
                'qq' => $res->qq,
                'expire_time' => $res->expire_time ? $res->expire_time : strtotime('2099-12-31'),
            ]));
        } catch (UserError $e) {
            return $this->result(Json::error($e->getMessage()));
        }
    }

    /**
     * 查询更新
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update()
    {
        // 授权ID
        $authId = $this->body['auth_id'];
        // 授权内容
        $authContent = $this->body['data']['auth_content'] ?? null;
        // 版本ID
        $number = $this->body['data']['number'] ?? null;

        // 日志名称
        $this->log['name'] = '查询更新';
        // 日志查询内容
        $this->log['auth_content'] = $authContent;

        try {
            // 查询授权信息
            $auth = $this->getAuthFind($authId, $authContent);

            // 查询版本列表
            $data = Version::field('id,appid,version,content,release_time')
                ->where('appid', $auth->appid)
                ->where('id', '>', (int)$number)
                ->select();

            return $this->result(Json::success()->data($data ?: []));
        } catch (UserError $e) {
            return $this->result(Json::error($e->getMessage()));
        }
    }
}
