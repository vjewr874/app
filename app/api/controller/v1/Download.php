<?php
declare (strict_types=1);

namespace app\api\controller\v1;

use app\common\controller\Helper;
use app\common\controller\Json;
use app\UserError;

class Download extends BaseController
{
    /**
     * 下载文件
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function file()
    {

        // 授权ID
        $authId = $this->body['auth_id'];
        // 授权内容
        $authContent = $this->body['data']['auth_content'] ?? null;
        // 版本ID
        $id = $this->body['data']['number'] ?? null;

        // 日志名称
        $this->log['name'] = '文件下载';
        // 日志查询内容
        $this->log['auth_content'] = $authContent;

        try {
            // 查询授权信息
            $auth = $this->getAuthFind($authId, $authContent, '*');

            // 获取更新文件
            list($status, $info) = Helper::downloadAuthFile($auth, 'update_file', $id);

            // 获取更新文件失败
            if (!$status) {
                ue($info);
            }

            // 写日志
            $this->writeAuthLog();

            return download(Helper::readfileAndDel($info), basename($info), true);
        } catch (UserError $e) {
            return $this->result(Json::error($e->getMessage()));
        }
    }
}
