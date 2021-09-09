<?php


$heap = new SplMinHeap();
$heap->insert(38);
$heap->insert(1);
$heap->insert(5);
$heap->insert(3);
$heap->insert(7);
$heap->insert(14);
$heap->insert(36);

echo $heap->extract();
echo $heap->extract();
foreach ($heap as $value)
{
    var_dump($value);
}



echo "ALTER TABLE `thj_order_record` 
ADD COLUMN `is_delete` tinyint(2) NOT NULL DEFAULT 0 COMMENT '0-未删除 10-已删除' AFTER `update_time`;";