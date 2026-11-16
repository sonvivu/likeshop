<?php
// +----------------------------------------------------------------------
// | likeshop开源商城系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | gitee下载：https://gitee.com/likeshop_gitee
// | github下载：https://github.com/likeshop-github
// | 访问官网：https://www.likeshop.cn
// | 访问社区：https://home.likeshop.cn
// | 访问手册：http://doc.likeshop.cn
// | 微信公众号：likeshop技术社区
// | likeshop系列产品在gitee、github等公开渠道开源版本可免费商用，未经许可不能去除前后端官方版权标识
// |  likeshop系列产品收费版本务必购买商业授权，购买去版权授权后，方可去除前后端官方版权标识
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | likeshop团队版权所有并拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeshop.cn.team
// +----------------------------------------------------------------------

namespace app\api\http\middleware;


use app\api\cache\TokenCache;
use app\api\validate\Token as TokenValidate;
use app\common\server\ConsumeTokenService;
use app\common\server\PasswordCryptoService;

class Login
{
    /**
     * 登录验证
     * @param $request
     * @param \Closure $next
     * @return mixed|\think\response\Redirect
     */
    public function handle($request, \Closure $next)
    {

        //允许跨域调用
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Authorization, Sec-Fetch-Mode, DNT, X-Mx-ReqToken, Keep-Alive, User-Agent, If-Match, If-None-Match, If-Unmodified-Since, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Accept-Language, Origin, Accept-Encoding,Access-Token,token,X-Consume-Token");
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, post');
        header('Access-Control-Max-Age: 1728000');
        header('Access-Control-Allow-Credentials:true');
        if (strtoupper($request->method()) == "OPTIONS") {
            return response();
        }

        try {
            PasswordCryptoService::decryptRequest($request);
        } catch (\RuntimeException $e) {
            return json([
                'code' => 0,
                'msg' => $e->getMessage(),
                'data' => [],
                'show' => 1,
                'time' => 0,
            ]);
        }

        $token = $request->header('token');

        //无需登录
        if ($this->isNotNeedLogin($request) && empty($token)) {
            return $next($request);
        }

        //token验证
        $token_cache = new TokenCache($token, ['token' => $token]);
        //token缓存，直接通过
        if (!$token_cache->isEmpty()) {
            $request->user_info = $token_cache->get(600);
            if (!$this->isNotNeedLogin($request) && !ConsumeTokenService::validate($request, $token, $request->user_info['id'] ?? 0)) {
                return $this->consumeTokenError();
            }
            return $next($request);
        }

        //token验证，并生成缓存
        $token_validate = new TokenValidate();
        $result = $token_validate->check(['token' => $token]);
        if ($result === true) {
            $request->user_info = $token_cache->set(600);
            if (!$this->isNotNeedLogin($request) && !ConsumeTokenService::validate($request, $token, $request->user_info['id'] ?? 0)) {
                return $this->consumeTokenError();
            }
            return $next($request);
        }

        //无需要登录，带token的情况
        if ($this->isNotNeedLogin($request) && $token) {
            return $next($request);
        }

        //登录失败
        // ThinkPHP 6 已删除 action() 助手：dispatch_error 只是包一层 _error，
        // 这里直接返回同等结构的 JSON（code -1，show 1）。
        return json([
            'code' => -1,
            'msg'  => $token_validate->getError(),
            'data' => [],
            'show' => 1,
            'time' => number_format(microtime(true) - app()->getBeginTime(), 6),
        ]);

    }

    private function consumeTokenError()
    {
        return json([
            'code' => 0,
            'msg' => '',
            'data' => ['error' => 'consume_token_invalid'],
            'show' => 0,
            'time' => 0,
        ]);
    }


    /**
     * 是否免登录验证
     * @param $request
     * @return bool
     */
    private function isNotNeedLogin($request)
    {
        [$controller, $action] = $this->parseControllerAction($request);
        $class = app()->parseClass('controller', $controller);
        if (!class_exists($class)) {
            return false;
        }
        // 直接读类默认属性，不实例化控制器（实例化会跑 ApiBase 构造，导致访问统计重复计数）。
        $props = (new \ReflectionClass($class))->getDefaultProperties();
        $data = $props['like_not_need_login'] ?? [];
        if (empty($data)) {
            return false;
        }
        $data = array_map('strtolower', $data);
        if (!in_array(strtolower($action), $data)) {
            return false;
        }
        return true;
    }

    /**
     * 从 pathinfo 解析当前控制器/操作。
     * ThinkPHP 6：中间件运行时路由尚未分发，$request->controller()/$request->action()
     * 为空；改为从 pathinfo 解析（MultiApp 已去掉模块段，如 index/lists）。
     * @return array [控制器名（StudlyCase）, 操作名]
     */
    private function parseControllerAction($request)
    {
        $segments = explode('/', trim($request->pathinfo(), '/'));
        $controller = $segments[0] ?? '';
        $action = $segments[1] ?? '';
        $suffix = config('app.url_html_suffix');
        if ($suffix && str_ends_with($action, '.' . $suffix)) {
            $action = substr($action, 0, -strlen($suffix) - 1);
        }
        $controller = \think\helper\Str::studly($controller ?: config('app.default_controller'));
        $action = $action ?: config('app.default_action');
        return [$controller, $action];
    }
}
