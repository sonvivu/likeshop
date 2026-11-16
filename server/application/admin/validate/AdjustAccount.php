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
namespace app\admin\validate;

use think\facade\Db;
use think\Validate;

class AdjustAccount extends Validate
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
        'id' => 'require',
        'type' => 'require|checkData',
        'money_remark' => 'max:100',
        'integral_remark' => 'max:100',
        'growth_remark' => 'max:100',
        'earings_remark' => 'max:100',
    ];
    protected $message = [
        'id.require' => '请选择用户',
        'type.require' => '调整类型参数缺失',
        'money_remark.max' => '余额备注不能超过100字',
        'integral_remark.max' => '积分备注不能超过100字',
        'growth_remark.max' => '成长值备注不能超过100字',
        'earings_remark.max' => '佣金备注不能超过100字',
    ];


    protected function checkData($value, $rule, $data)
    {
        $user = Db::name('user')->where(['del' => 0, 'id' => $data['id']])->find();
        if (empty($user)) {
            return lang('该用户不存在');
        }
        if (!isset($data['money_handle']) && !isset($data['integral_handle'])
            && !isset($data['growth_handle']) && !isset($data['earnings_handle'])) {
            return lang('请选择调整的类型');
        }

        switch ($value) {
            case 'money':
                $result = $this->checkMoney($data, $user);
                break;
            case 'integral':
                $result = $this->checkIntegral($data, $user);
                break;
            case 'growth':
                $result = $this->checkGrowth($data, $user);
                break;
            case 'earnings':
                $result = $this->checkEarnings($data, $user);
                break;
            default:
                $result = lang('账户调整类型错误');
        }
        return $result;
    }

    /**
     * @notes 验证金额
     * @param $data
     * @param $user
     * @return bool|string
     * @author 段誉
     * @date 2022/3/22 16:47
     */
    protected function checkMoney($data, $user)
    {
        if (empty($data['money'])) {
            return lang('请输入大于0的调整余额');
        }
        if ($data['money'] < 0) {
            return lang('调整余额必须大于零');
        }
        //验证扣减余额操作
        if ($data['money_handle'] == 0) {
            //用户余额不足
            if ($data['money'] > $user['user_money']) {
                return lang('用户余额仅剩下'). $user['user_money']. ' '. lang('元');
            }
        }
        if (empty($data['money_remark'])) {
            return lang('请输入调整余额备注');
        }
        return true;
    }

    /**
     * @notes 验证积分
     * @param $data
     * @param $user
     * @return bool|string
     * @author 段誉
     * @date 2022/3/22 16:48
     */
    protected function checkIntegral($data, $user)
    {
        if (empty($data['integral'])) {
            return lang('请输入大于0的调整积分');
        }
        if ($data['integral'] < 0) {
            return lang('调整积分必须大于零');
        }
        //验证扣减积分操作
        if ($data['integral_handle'] == 0) {
            //用户积分不足
            if ($data['integral'] > $user['user_integral']) {
                return lang('用户积分仅剩下'). $user['user_integral']. ' '. lang('分');
            }
        }

        if (empty($data['integral_remark'])) {
            return lang('请输入调整积分备注');
        }
        return true;
    }

    /**
     * @notes 验证成长值
     * @param $data
     * @param $user
     * @return bool|string
     * @author 段誉
     * @date 2022/3/22 16:49
     */
    protected function checkGrowth($data, $user)
    {
        if (empty($data['growth'])) {
            return lang('请输入大于0的调整成长值');
        }
        if ($data['growth'] < 0) {
            return lang('成长值必须大于零');
        }
        //验证扣减成长值操作
        if ($data['growth_handle'] == 0) {
            //用户成长值不足
            if ($data['growth'] > $user['user_growth']) {
                return lang('用户成长值仅剩下') . $user['user_growth'];
            }
        }
        if (empty($data['growth_remark'])) {
            return lang('请输入调整成长值备注');
        }
        return true;
    }

    /**
     * @notes 验证佣金
     * @param $data
     * @param $user
     * @return bool|string
     * @author 段誉
     * @date 2022/3/22 16:50
     */
    protected function checkEarnings($data, $user)
    {
        if (empty($data['earnings'])) {
            return lang('请输入大于0的调整佣金');
        }
        if ($data['earnings'] < 0) {
            return lang('调整佣金必须大于零');
        }
        if (empty($user['earnings'])) {
            $user['earnings'] = 0;
        }
        //验证扣减余额操作
        if ($data['earnings_handle'] == 0) {
            if ($data['earnings'] > $user['earnings']) {
                return lang('用户佣金仅剩下'). $user['earnings']. ' '. lang('元');
            }
        }
        if (empty($data['earnings_remark'])) {
            return lang('请输入调整余额备注');
        }
        return true;
    }
}