<?php


class Calc
{
    public function calcSalary($actualHour, $otHour,$otOut8Count)
    {
        $baseSalary = 10000;
        // 设置时区（根据需要设置）
        date_default_timezone_set('Asia/Shanghai');

        // 获取当前月份的第一天和最后一天
        $firstDay = new DateTime('first day of this month');
        $lastDay = new DateTime('last day of this month');

        // 计算工作日
        $workDays = 0;
        $currentDay = clone $firstDay; // 克隆对象以避免修改原始对象

        while ($currentDay <= $lastDay) {
            // 检查是否为周一至周五
            if ($currentDay->format('N') >= 1 && $currentDay->format('N') <= 5) {
                $workDays++;
            }
            // 增加一天
            $currentDay->modify('+1 day');
        }

        echo "本月工作日数量为：" . $workDays . "天";
        // 如果需要计算每日8小时的工作小时数
        $workHours = $workDays * 8;
        echo "本月总工作小时数为：" . $workHours . "小时";

    }
}

$calc = new Calc();
$calc->calcSalary(177.5,25.5,6);