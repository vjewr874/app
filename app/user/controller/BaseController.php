<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/14 14:20:16
 * 
 */

namespace app\user\controller;

use app\BaseController as AppBaseController;

class BaseController extends AppBaseController
{
    protected $middleware = [
        'app\middleware\UserAuth',
    ];
}