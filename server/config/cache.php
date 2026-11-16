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
// | 缓存设置（ThinkPHP 6：扁平格式改为 default + stores；
// | 各驱动键名不变。默认仍为 File，与 5.1 本地行为一致）
// +----------------------------------------------------------------------
use think\facade\Env;
return [
    // 默认缓存驱动
    'default' => strtolower(Env::get('cache.type', 'File')),
    // 缓存驱动配置
    'stores'  => [
        'file'  => [
            // 驱动方式
            'type'   => 'file',
            // 缓存保存目录
            'path'   => '',
            // 缓存前缀
            'prefix' => 'likeshopb2c-' . Env::get('database.database'),
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
        ],
        'redis' => [
            // 驱动方式
            'type'     => 'redis',
            'host'     => Env::get('cache.host', 'like-redis'),
            'port'     => Env::get('cache.port', '6379'),
            'password' => Env::get('cache.password', ''),
            'select'   => Env::get('cache.select', 0),
            'timeout'  => Env::get('cache.timeout', 0),
            // 缓存前缀
            'prefix'   => 'likeshopb2c-' . Env::get('database.database'),
            // 缓存有效期 0表示永久缓存
            'expire'   => 0,
        ],
    ],
];
