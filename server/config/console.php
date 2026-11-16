<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    'name'      => 'Think Console',
    'version'   => '0.1',
    'user'      => null,
    'auto_path' => env('app_path') . 'command' . DIRECTORY_SEPARATOR,
    // ThinkPHP 6：控制台命令须显式注册（5.1 会自动扫描 command 目录）。
    'commands'  => [
        'bargain_end'                    => 'app\common\command\BargainEnd',
        'crontab'                        => 'app\common\command\Crontab',
        'distribution_order'             => 'app\common\command\DistributionOrder',
        'order_close'                    => 'app\common\command\OrderClose',
        'order_finish'                   => 'app\common\command\OrderFinish',
        'password'                       => 'app\common\command\Password',
        'team_end'                       => 'app\common\command\TeamEnd',
        'update'                         => 'app\common\command\Update',
        'user_distribution'              => 'app\common\command\UserDistribution',
        'wechat_merchant_transfer'       => 'app\common\command\WechatMerchantTransfer',
        'wechat_mini_express_send_sync'  => 'app\common\command\WechatMiniExpressSendSync',
    ],
];
