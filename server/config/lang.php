<?php
// +----------------------------------------------------------------------
// | 语言设置（ThinkPHP 6）
// +----------------------------------------------------------------------
// | i18n 分支 vi：默认语言 vi，另支持 zh-cn。语言侦测顺序与 5.1 一致：
// | ?lang= 参数 > think-lang 请求头 > think_var Cookie > Accept-Language。
// | Accept-Language 'vi-vn' 映射为 'vi'（浏览器越南语常发 vi-VN）。
// +----------------------------------------------------------------------

return [
    // 默认语言
    'default_lang'    => 'vi',
    // 允许的语言列表
    'allow_lang_list' => ['vi', 'zh-cn'],
    // 是否使用Cookie记录
    'use_cookie'      => true,
    // 多语言cookie变量（5.1 沿用 think_var）
    'cookie_var'      => 'think_var',
    // 多语言header变量
    'header_var'      => 'think-lang',
    // 多语言自动侦测变量名
    'detect_var'      => 'lang',
    // Accept-Language转义为对应语言包名称
    'accept_language' => [
        'zh-hans-cn' => 'zh-cn',
        'zh-tw'      => 'zh-cn',
        'zh-hk'      => 'zh-cn',
        'vi-vn'      => 'vi',
    ],
];
