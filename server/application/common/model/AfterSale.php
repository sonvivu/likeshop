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

namespace app\common\model;

use think\Model;

class AfterSale extends Model
{
    //售后状态
    const STATUS_APPLY_REFUND           = 0;//申请退款
    const STATUS_REFUSE_REFUND          = 1;//商家拒绝
    const STATUS_WAIT_RETURN_GOODS      = 2;//商品待退货
    const STATUS_WAIT_RECEIVE_GOODS     = 3;//商家待收货
    const STATUS_REFUSE_RECEIVE_GOODS   = 4;//商家拒收货
    const STATUS_WAIT_REFUND            = 5;//等待退款
    const STATUS_SUCCESS_REFUND         = 6;//退款成功

    //退款类型
    const TYPE_ONLY_REFUND      = 0;//仅退款
    const TYPE_REFUND_RETURN    = 1;//退款退货
    
    /**
     * @notes 不能核销的状态
     * @return array
     * @author lbzy
     * @datetime 2023-06-21 11:48:32
     */
    static function CanNotVerificationStatusArr() : array
    {
        return [
            self::STATUS_APPLY_REFUND,
            self::STATUS_WAIT_RETURN_GOODS,
            self::STATUS_WAIT_RECEIVE_GOODS,
            self::STATUS_WAIT_REFUND,
        ];
    }

    //售后状态描述
    public static function getStatusDesc($state)
    {
        $data = [
            self::STATUS_APPLY_REFUND           => lang('申请退款'),
            self::STATUS_REFUSE_REFUND          => lang('商家拒绝'),
            self::STATUS_WAIT_RETURN_GOODS      => lang('商品待退货'),
            self::STATUS_WAIT_RECEIVE_GOODS     => lang('商家待收货'),
            self::STATUS_REFUSE_RECEIVE_GOODS   => lang('商家拒收货'),
            self::STATUS_WAIT_REFUND            => lang('等待退款'),
            self::STATUS_SUCCESS_REFUND         => lang('退款成功'),
        ];
        if ($state === true) {
            return $data;
        }
        return $data[$state] ?? '';
    }
    

    //售后类型描述
    public static function getRefundTypeDesc($type)
    {
        $data = [
            self::TYPE_ONLY_REFUND      => lang('仅退款'),
            self::TYPE_REFUND_RETURN    => lang('退款退货'),
        ];
        if ($type === true) {
            return $data;
        }
        return $data[$type] ?? '';
    }


    //售后原因
    public static function getReasonLists()
    {
        $data = [
            lang('7天无理由退换货'),
            lang('大小尺寸与商品描述不符'),
            lang('颜色/图案/款式不符'),
            lang('做工粗糙/有瑕疵'),
            lang('质量问题'),
            lang('卖家发错货'),
            lang('少件（含缺少配件）'),
            lang('不喜欢/不想要'),
            lang('快递/物流一直未送到'),
            lang('空包裹'),
            lang('快递/物流无跟踪记录'),
            lang('货物破损已拒签'),
            lang('其他'),
        ];
        return $data;
    }
    
    function getConfirmTakeTimeAttr($fieldValue, $data)
    {
        return $fieldValue ? date('Y-m-d H:i:s', $fieldValue) : '';
    }



    public function orderGoods()
    {
        return $this->hasMany('order_goods', 'id', 'order_goods_id');
    }

    public function user()
    {
        return $this->hasOne('user', 'id', 'user_id')
            ->field('id,sn,nickname,avatar,mobile,sex,create_time');
    }

    public function order()
    {
        return $this->hasOne('order', 'id', 'order_id')
            ->field('id,order_sn,total_amount,order_amount,pay_way,order_status,delivery_type');
    }


    public function logs()
    {
        return $this->hasMany('after_sale_log', 'after_sale_id', 'id')->order('id desc');
    }
}