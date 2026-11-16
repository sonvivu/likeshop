<?php

namespace app\common\helper;

/**
 * 日期范围助手：移植自 ThinkPHP 5.1 的 think\helper\Time。
 *
 * ThinkPHP 6 的 think-helper 3.x 已删除 Time 类（仅剩 Str/Arr），而后台统计、
 * 日志、提现、签到、抽奖等 12 个文件仍在用它；直接引用会在运行时报
 * Class "think\helper\Time" not found。
 *
 * 只移植代码库实际用到的 4 个方法，语义与 5.1 原版逐行一致
 * （vendor/topthink/think-helper/src/Time.php，v3.5.1 时期）：
 * - today()：[今天 00:00:00, 今天 23:59:59]
 * - yesterday()：[昨天 00:00:00, 昨天 23:59:59]
 * - month()：[本月 1 日 00:00:00, 本月最后一天 23:59:59]
 * - dayToNow($day, $now)：[$day 天前 00:00:00, $now ? 当前时间 : 昨天 23:59:59]
 */
class Time
{
    public static function today()
    {
        list($y, $m, $d) = explode('-', date('Y-m-d'));
        return [
            mktime(0, 0, 0, $m, $d, $y),
            mktime(23, 59, 59, $m, $d, $y),
        ];
    }

    public static function yesterday()
    {
        $yesterday = date('d') - 1;
        return [
            mktime(0, 0, 0, date('m'), $yesterday, date('Y')),
            mktime(23, 59, 59, date('m'), $yesterday, date('Y')),
        ];
    }

    public static function month($everyDay = false)
    {
        list($y, $m, $t) = explode('-', date('Y-m-t'));
        return [
            mktime(0, 0, 0, $m, 1, $y),
            mktime(23, 59, 59, $m, $t, $y),
        ];
    }

    public static function dayToNow($day = 1, $now = true)
    {
        $end = time();
        if (!$now) {
            list($foo, $end) = self::yesterday();
        }

        return [
            mktime(0, 0, 0, date('m'), date('d') - $day, date('Y')),
            $end,
        ];
    }
}
