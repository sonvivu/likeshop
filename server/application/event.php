<?php
// +----------------------------------------------------------------------
// | likeshop开源商城系统
// +----------------------------------------------------------------------
// | 事件定义（ThinkPHP 6：5.1 的 tags.php 改为本文件 application/event.php）
// | 本文件由 App::load() 在初始化时加载（getAppPath() 经符号链接 server/app
// | 指向 application/）。注意不要放回 config/event.php：config 目录的文件
// | 只会被当作配置项加载，事件监听永远不会注册。
// | 5.1 的 tags.php：application/tags.php（业务事件）、
// | application/admin/tags.php（menu_auth）、application/api/tags.php（app_end）。
// | 注意：
// | - wx_message_send 已删除：类 app\common\behavior\WxMessageSend 不存在，
// |   且没有任何代码触发它（5.1 下触发也会 fatal）；
// | - 5.1 的 action_begin（SystemLog）/ app_end（AppEnd）在 6.x 没有对应的
// |   框架事件，已改为在 AdminBase/ApiBase 构造中直接调用，见对应文件注释。
// +----------------------------------------------------------------------

return [
    'bind'      => [],
    'listen'    => [
        //发送短信
        'sms_send' => [
            'app\\common\\behavior\\SmsSend',
        ],
        //消息通知
        'notice' => [
            'app\\common\\behavior\\Notice',
        ],
        //足迹记录
        'footprint' => [
            'app\\common\\behavior\\Footprint',
        ],
        //取消订单后操作
        'cancel_order' => [
            'app\\common\\behavior\\CancelOrder',
        ],
        //打印
        'printer' => [
            'app\\common\\behavior\\Printer',
        ],
        //赠送积分或成长值
        'give_reward' => [
            'app\\common\\behavior\\GiveReward',
        ],
        //商品下架或删除清除收藏夹
        'update_collect' => [
            'app\\common\\behavior\\UpdateCollect',
        ],
        //清除菜单权限缓存（原 application/admin/tags.php）
        'menu_auth' => [
            'app\\admin\\behavior\\ClearMenuAuthCache',
        ],
    ],
    'subscribe' => [],
];
