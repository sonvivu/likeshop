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
namespace app\api\validate;
use think\facade\Db;
use think\Validate;

class GetCoupon extends Validate{
    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);
        foreach ($this->message as $key => $value) {
            $this->message[$key] = lang($value);
        }
        foreach ($this->rule as $key => $rule) {
            if (strpos($key, '|') !== false) {
                list($attr, $title) = explode('|', $key, 2);
                $newKey = $attr . '|' . lang($title);
                if ($newKey !== $key) {
                    $this->rule[$newKey] = $rule;
                    unset($this->rule[$key]);
                }
            }
        }
    }

    protected $rule = [
        'id'            => 'require|checkCoupon',
    ];
    protected $message = [
        'id.require'    => '请选择优惠券',
    ];
    public function checkCoupon($value,$rule,$data){

        $coupon = Db::name('coupon')->where(['id'=>$value,'status'=>1,'del'=>0,'get_type'=>1])->find();
        if(!$coupon){
            return lang('优惠券已下架');
        }
        $now = time();
        if($coupon['send_time_start'] > $now || $coupon['send_time_end'] < $now){
            return lang('未到领取时间');
        }

        //限量发放，验证是否达到数量
        if($coupon['send_total_type'] == 2){
            $total_coupon = Db::name('coupon_list')->where(['coupon_id'=>$value,'del'=>0])->count();
            if($total_coupon >= $coupon['send_total']){
                return lang('优惠券已领完');
            }
        }
        //限制每人领取次数
        if($coupon['get_num_type'] == 2){

            $total_coupon = Db::name('coupon_list')->where(['user_id'=>$data['user_id'],'coupon_id'=>$value,'del'=>0])->count();
            if($total_coupon >= $coupon['get_num']) {
                return lang('您已达到领取次数了');
            }

        }
        if($coupon['get_num_type'] == 3){
            $today = date("Y-m-d");
            $tomorrow = date("Y-m-d",strtotime("+1 day"));
            $total_coupon = Db::name('coupon_list')->where(['user_id'=>$data['user_id'],'coupon_id'=>$value,'del'=>0])
                ->whereTime('create_time', 'between',[$today,$tomorrow])
                ->count();

            if($total_coupon >= $coupon['get_num']) {
                return lang('您已达到领取次数了');
            }
        }

        return true;

    }
}