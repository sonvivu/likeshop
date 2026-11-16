<?php
// +----------------------------------------------------------------------
// | likeshop开源商城系统
// +----------------------------------------------------------------------
// | 多语言中间件（ThinkPHP 6 升级新增）
// | 代替框架自带的 think\middleware\LoadLangPack，原因：
// | 1. 框架的 switchLangSet 只加载框架语言包 + 模块语言包，不加载应用级
// |    语言包（application/lang/{vi,zh-cn}.php，1550 key）；
// | 2. Lang::load() 是后加载者胜：应用级必须在模块级之前加载，模块级
// |    才能在 219 个重叠 key 上优先（5.1 的行为）；
// | 3. MultiApp::loadApp() 会把语言重置为默认语言，所以侦测必须在
// |    MultiApp 之后运行——本中间件注册在各模块 middleware.php（'app' 型），
// |    不注册为全局中间件。
// | 侦测顺序与 5.1/框架一致：?lang= > think-lang 请求头 > think_var Cookie
// | > Accept-Language；只接受 vi / zh-cn。
// +----------------------------------------------------------------------

namespace app\common\http\middleware;

use Closure;
use think\facade\Lang;
use think\Request;

class LangPack
{
    public function handle($request, Closure $next)
    {
        $langset = $this->detect($request);

        // Nạp theo thứ tự: framework -> app -> module (module thắng khi trùng key)
        Lang::load([app()->getThinkPath() . 'lang' . DIRECTORY_SEPARATOR . $langset . '.php']);
        Lang::load([app()->getRootPath() . 'application' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $langset . '.php']);
        $moduleLang = app()->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . $langset . '.php';
        if (is_file($moduleLang)) {
            Lang::load([$moduleLang]);
        }
        Lang::setLangSet($langset);

        $config = Lang::getConfig();
        if (!empty($config['use_cookie']) && $request->cookie($config['cookie_var']) !== $langset) {
            cookie($config['cookie_var'], $langset, 86400 * 30);
        }

        return $next($request);
    }

    /**
     * 侦测当前语言（逻辑同 think\middleware\LoadLangPack::detect）。
     */
    protected function detect(Request $request): string
    {
        $config = Lang::getConfig();

        $langSet = '';
        if ($request->get($config['detect_var'])) {
            $langSet = $request->get($config['detect_var']);
        } elseif ($request->header($config['header_var'])) {
            $langSet = $request->header($config['header_var']);
        } elseif ($request->cookie($config['cookie_var'])) {
            $langSet = $request->cookie($config['cookie_var']);
        } elseif ($request->server('HTTP_ACCEPT_LANGUAGE')) {
            $langSet = $request->server('HTTP_ACCEPT_LANGUAGE');
        }

        if (preg_match('/^([a-z\d\-]+)/i', $langSet, $matches)) {
            $langSet = strtolower($matches[1]);
            if (isset($config['accept_language'][$langSet])) {
                $langSet = $config['accept_language'][$langSet];
            }
        } else {
            $langSet = Lang::getLangSet();
        }

        if (!empty($config['allow_lang_list']) && !in_array($langSet, $config['allow_lang_list'])) {
            $langSet = Lang::getLangSet();
        }
        if (!in_array($langSet, ['vi', 'zh-cn'])) {
            $langSet = $config['default_lang'];
        }

        return $langSet;
    }
}
