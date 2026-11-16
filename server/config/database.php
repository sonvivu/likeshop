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
// | 数据库设置（ThinkPHP 6：扁平格式改为 default + connections；
// | 连接内键名不变）
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // 默认数据库连接
    'default'     => 'mysql',
    // ThinkPHP 6：ModelService 只读顶层 database.auto_timestamp /
    // database.datetime_format（不读 connections.mysql 里的同名键），
    // 缺省 auto_timestamp='timestamp' 会把 int 时间戳当 datetime 字符串
    // 读（new DateTime(int) 在 PHP 8 报错）。TP5.1 读的是连接内配置，
    // 值为 false，所以这里必须补顶层键 false 才能保持旧行为。
    // ThinkPHP 8 / ThinkORM 4：DbManager::modelMaker 仍读这两项；且
    // 读时间字段时只要 getDateFormat() 非 false 就把 int 转成格式化
    // 字符串（Attribute::getAttr 的 isTimeAttr 分支），导致
    // date('Y-m-d H:i:s', $model['create_time']) 在 PHP 8.1+ 抛
    // TypeError。datetime_format=false 恢复 TP6 的行为：
    // create_time/update_time 读出保持 int。
    'auto_timestamp' => false,
    'datetime_format' => false,
    // 数据库连接配置
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type'            => 'mysql',
            // 服务器地址
            'hostname'        => Env::get('database.hostname','likeshop-mysql'),
            // 数据库名
            'database'        => Env::get('database.database','localhost_likeshop'),
            // 用户名
            'username'        => Env::get('database.username','root'),
            // 密码
            'password'        => Env::get('database.password','root'),
            // 端口
            'hostport'        => Env::get('database.hostport','3306'),
            // 连接dsn
            'dsn'             => '',
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8
            'charset'         => 'utf8mb4',
            // 数据库表前缀
            'prefix'          => Env::get('database.prefix','ls_'),
            // 数据库调试模式
            'debug'           => true,
            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 自动读取主库数据
            'read_master'     => false,
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 数据集返回类型
            'resultset_type'  => 'array',
            // 自动写入时间戳字段
            'auto_timestamp'  => false,
            // 时间字段取出后的默认时间格式
            'datetime_format' => 'Y-m-d H:i:s',
            // 是否需要进行SQL性能分析
            'sql_explain'     => false,
            // Builder类
            'builder'         => '',
            // Query类
            'query'           => '\\think\\db\\Query',
            // 是否需要断线重连
            'break_reconnect' => false,
            // 断线标识字符串
            'break_match_str' => [],
        ],
    ],
];
