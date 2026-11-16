<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ] ThinkPHP 6 + 多应用（application/ 为模块目录）
namespace think;

require __DIR__ . '/../vendor/autoload.php';

// ThinkPHP 6 不再自动加载应用公共文件，这里显式加载
// （is_mobile/check_url_safety/download_file 等 48 个全局函数）。
require __DIR__ . '/../application/common.php';

// 支持事先使用静态方法设置Request对象和Config对象

if (!file_exists(__DIR__ . '/../config/install.lock')) {
    header("location:/install/install.php");
    exit;
}

define('ROOT_PATH', __DIR__);

// 执行应用并响应
$app = new App(__DIR__ . '/../');
$http = $app->http;
// 多应用入口：模块目录是 application/，不是 6.x 默认的 app/。
// 注意不能用 $http->path(application/)：MultiApp::setApp() 会把 path() 的值
// 当作“当前模块自己的目录”，于是所有模块的 appPath 都变成 application/，
// 导致各模块的 config/、middleware.php、event.php、lang/ 全都加载不到
// （症状：api 的 default_return_type=json 不生效、Login/LangPack 中间件不跑、
// 事件不触发）。正确做法是让 getBasePath()（rootPath.'app/'）指向 application/，
// 这里用符号链接 server/app -> application 实现，框架按默认布局工作。
// 全局中间件：6.x 只自动加载 app/middleware.php（不存在），手动导入
// （middleware 服务挂在 App 上，不是 Http 上）
$app->middleware->import(require __DIR__ . '/../application/middleware.php');

$response = $http->run();
$response->send();
$http->end($response);
