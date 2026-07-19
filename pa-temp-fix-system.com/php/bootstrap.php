<?php
/**
 * PA Shell Bootstrap
 * 通过 composer autoload 自动加载所有项目类
 * 此文件仅负责：加载 autoload + 初始化全局配置
 */

// 加载 Composer 自动加载器（含 classmap 项目类）
require_once dirname(__FILE__) . '/../vendor/autoload.php';

// 设置默认时区
date_default_timezone_set('Asia/Shanghai');

// 加载常量定义（向后兼容）
require_once dirname(__FILE__) . '/constant/Constant.php';
