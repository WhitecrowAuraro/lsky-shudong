<?php

/**
 * User: WispX
 * Date: 2017/10/26 0026
 * Time: 9:02
 * Link: http://gitee.com/wispx
 */

//error_reporting(0);

define('LSKY', 'LSKY');

// 数据库配置
require './config/db.php';

// 其他配置
$config = require './config/config.php';

// 静态操作类
require './vendor/Operate.php';

// 分页类
require './vendor/Page.php';

// 工具方法
require './vendor/Tool.php';

// 数据库操作类
require './vendor/Query.php';

// 实例化数据库
$db = new Query(DB_NAME);

$action = Operate::param('action');
$post_type = Operate::param('type', 'POST', 'index');
$get_type = Operate::param('type', 'GET');
$page = Operate::param('page', 'GET', 1);

// 每页显示数量
$page_size = 12;

// limit
$page_now = ($page - 1) * $page_size;

switch ($post_type) {
    case 'index':
        $total = $db->table('article')
            ->findNumRows();
        $list = $db->table('article')
            ->order('send_time desc')
            ->limit("{$page_now}, {$page_size}")
            ->select();
        break;
    case 'real_name':
        $where = "is_anonymous = 0";
        $total = $db->table('article')
            ->where($where)
            ->findNumRows();
        $list = $db->table('article')
            ->where($where)
            ->order('send_time desc')
            ->limit("{$page_now}, {$page_size}")
            ->select();
        break;
    case 'anonymous':
        $where = "is_anonymous = 1";
        $total = $db->table('article')
            ->where($where)
            ->findNumRows();
        $list = $db->table('article')
            ->where($where)
            ->order('send_time desc')
            ->limit("{$page_now}, {$page_size}")
            ->select();
        break;
    default:
        break;
}

$pageno = new Page($total, 3, $page, $page_size);

if($get_type) {
    if($get_type == 'send') {
        $ip = Operate::getIp(0, true);
        $time = time();
        $count = count($db->table('article')->where("ip = '{$ip}' AND " . 'send_time > ' . strtotime(date('Y-m-d', $time)))->select());
        if($count >= 3) {
            return Operate::json(2, '每天只能发表3个悄悄话哦！明天再来吧！');
        }
        $data = Operate::trimArray($_POST);
        // 处理数据  过滤字符串、防注入
        foreach ($data as $item => &$value) {
            $data[$item] = addslashes($value);
            // 判断是否有字段为空，否则直接退出
            if(empty($data[$item])) {
                break;
                return Operate::json(0, '数据异常');
            }
        }
        if(mb_strlen($data['name']) <= 20) {
            if(is_numeric($data['qq']) && mb_strlen($data['qq']) <= 10) {
                $data['ip'] = $ip;
                $data['send_time'] = $time;
                if($db->add($data, 'article')) {
                    return Operate::json(1, '发布成功');
                }
                return Operate::json(0, '发布失败');
            }
            return Operate::json(0, 'QQ格式不正确');
        }
        return Operate::json(0, '数据异常');
    }
}

// 加载视图
require "./view/". ($action ? "page/{$action}" : "index") . ".php";