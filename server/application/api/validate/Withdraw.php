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

use app\api\logic\DistributionLogic;
use app\common\server\ConfigServer;
use think\Db;
use think\Validate;

class Withdraw extends Validate
{
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
        'id' => 'require', //参数缺失
        'type' => 'require|in:1,2,3,4,5',//提现类型
        'money' => 'require|checkMoney',//提现佣金
        'account' => 'requireIf:type,3|requireIf:type,4|requireIf:type,5',//账户类型
        'real_name' => 'requireIf:type,3|requireIf:type,4|requireIf:type,5|chs',//真实姓名
        'money_qr_code' => 'requireIf:type,3|requireIf:type,4',//收款码
        'bank' => 'requireIf:type,5', // 提现银行
        'subbank' => 'requireIf:type,5', // 银行支行
    ];

    protected $message = [
        'id.require' => '参数缺失',
        'type.require' => '参数错误',
        'type.in' => '提现类型错误',
        'money.require' => '参数错误',
        'account.requireIf' => '请填写账号',
        'real_name.requireIf' => '请填写真实姓名',
        'real_name.chs' => '请填写真实姓名',
        'money_qr_code.requireIf' => '请上传收款码',
        'bank.requireIf' => '请填写提现银行',
        'subbank.requireIf' => '请填写银行支行',
    ];

    //申请提现
    public function sceneApply()
    {
        return $this->only(['type', 'money', 'account', 'real_name', 'money_qr_code', 'bank', 'subbank']);
    }

    //申请详情
    public function sceneInfo()
    {
        return $this->only(['id']);
    }


    //提现佣金验证
    protected function checkMoney($value, $rule, $data = [])
    {
        if (!is_numeric($value) || $value <= 0) {
            return lang('提现金额不正确');
        }
        $able_withdraw = Db::name('user')->where('id', $data['user_id'])->value('earnings');
        if ($value > $able_withdraw){
            return lang('可提现金额不足');
        }

        //1.最低提现金额
        $min_withdraw = ConfigServer::get('withdraw', 'min_withdraw', 0);
        if (!is_numeric($min_withdraw) || $min_withdraw <= 0) {
            return lang('最低提现金额未设置');
        }
        if($value < $min_withdraw){
            return lang('最低提现'). ' '. $min_withdraw. ' '. lang('元');
        }

        //2,最高提现金额
        $max_withdraw = ConfigServer::get('withdraw', 'max_withdraw', 0);
        if ($value > $max_withdraw){
            return lang('最高提现'). ' '. $max_withdraw. ' '. lang('元');
        }
        return true;
    }
}
