<?php
// +----------------------------------------------------------------------
// | likeshop100%开源免费商用商城系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | 商业版本务必购买商业授权，以免引起法律纠纷
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | gitee下载：https://gitee.com/likeshop_gitee
// | github下载：https://github.com/likeshop-github
// | 访问官网：https://www.likeshop.cn
// | 访问社区：https://home.likeshop.cn
// | 访问手册：http://doc.likeshop.cn
// | 微信公众号：likeshop技术社区
// | likeshop团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeshopTeam
// +----------------------------------------------------------------------

namespace app\common\http\middleware;


/**
 * 基础中间件
 * Class BaseMiddleware
 * @package app\common\http\middleware
 */
class BaseMiddleware
{
    public function handle($request, \Closure $next)
    {
        //过滤前后空格
        $request->filter(['trim']);
        // i18n nhánh vi: chuẩn hóa langset về vi/zh-cn.
        // Trình duyệt Việt gửi Accept-Language: vi-VN,... → detect() ra 'vi-vn',
        // không có file ngôn ngữ tương ứng nên sẽ rơi về msgid (chữ Trung).
        // ?lang=zh-cn và cookie think_var=zh-cn vẫn được giữ nguyên.
        // Lưu ý: loadLangPack() đã chạy ở init với langset sai nên file ngôn ngữ
        // của module (vd application/admin/lang/vi.php) chưa được nạp — phải nạp
        // bù cả file app lẫn file module (module lấy từ đoạn đầu của path).
        $lang = \think\facade\Lang::range();
        if (!in_array($lang, ['vi', 'zh-cn'])) {
            \think\facade\Lang::range('vi');
            \think\facade\Lang::load([app()->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . 'vi.php']);
            $segments = explode('/', trim($request->path(), '/'));
            $moduleLang = app()->getAppPath() . $segments[0] . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'vi.php';
            if (is_file($moduleLang)) {
                \think\facade\Lang::load([$moduleLang]);
            }
        }
        return $next($request);
    }
}