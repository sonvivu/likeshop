<?php
// +----------------------------------------------------------------------
// | likeshop开源商城系统
// +----------------------------------------------------------------------
// | ThinkPHP 6 升级：替代 ThinkPHP 5.1 的 think\Controller + Jump。
// | 5.1 的 Controller/Jump 已在 6.x 删除，这里按 5.1 语义重新实现，
// | 供 AdminBase/ApiBase/Index 继承。行为与 5.1 保持一致：
// | validate 成功返回 true，失败默认返回错误串（failException 时抛 ValidateException）；
// | success/error/result/redirect 抛 HttpResponseException；
// | HTML 类型跳转改为直接渲染 dispatch 模板（6.x 已删除 'jump' 响应类型）。
// +----------------------------------------------------------------------

namespace app;

use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\View;
use think\Response;

class BaseController
{
    protected $app;
    protected $request;
    protected $failException = false;
    protected $batchValidate = false;

    public function __construct()
    {
        $this->app = app();
        $this->request = app('request');
        $this->initialize();
    }

    protected function initialize()
    {
    }

    protected function assign($name, $value = '')
    {
        return View::assign($name, $value);
    }

    protected function fetch($template = '', $vars = [], $config = [])
    {
        return View::fetch($template, $vars);
    }

    protected function display($content = '', $vars = [], $config = [])
    {
        return View::display($content, $vars);
    }

    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;
        return $this;
    }

    /**
     * 按 5.1 Controller::validate 的语义校验：
     * $validate 可为规则数组、验证器类名、或“类名.场景”；成功返回 true，
     * 失败默认返回错误串，failException 为 true 时抛 ValidateException。
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        $v = validate($validate, $message, $batch || $this->batchValidate);
        $v->failException($this->failException);
        if ($callback && is_callable($callback)) {
            $callback($v, $data);
        }
        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }
            return $v->getError();
        }
        return true;
    }

    protected function getResponseType()
    {
        return $this->request->isAjax()
            ? Config::get('app.default_ajax_return')
            : Config::get('app.default_return_type');
    }

    /**
     * ThinkPHP 6 已删除 'jump' 响应类型：HTML 跳转改为直接渲染 dispatch 模板。
     */
    protected function jumpResponse(array $result, $template)
    {
        $content = View::fetch($template, $result);
        return Response::create($content, 'html');
    }

    protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        } elseif ('' !== $url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string) url($url);
        }

        $result = [
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        $type = $this->getResponseType();
        if ('html' == strtolower($type)) {
            $response = $this->jumpResponse($result, Config::get('app.dispatch_success_tmpl'));
        } else {
            $response = Response::create($result, $type);
        }

        $response->header($header);
        throw new HttpResponseException($response);
    }

    protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $type = $this->getResponseType();
        if (is_null($url)) {
            $url = $this->request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ('' !== $url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string) url($url);
        }

        $result = [
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        if ('html' == strtolower($type)) {
            $response = $this->jumpResponse($result, Config::get('app.dispatch_error_tmpl'));
        } else {
            $response = Response::create($result, $type);
        }

        $response->header($header);
        throw new HttpResponseException($response);
    }

    protected function result($data, $code = 0, $msg = '', $type = '', array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => time(),
            'data' => $data,
        ];

        $type     = $type ?: $this->getResponseType();
        $response = Response::create($result, $type)->header($header);

        throw new HttpResponseException($response);
    }

    protected function redirect($url, $params = [], $code = 302, $with = [])
    {
        if (is_integer($params)) {
            $code   = $params;
            $params = [];
        }
        if ($params) {
            $url = (string) url($url, $params);
        }

        $response = redirect($url, $code);
        if ($with) {
            $response->with($with);
        }

        throw new HttpResponseException($response);
    }
}
