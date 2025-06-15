<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */
return [
    // 代理相关
    'agent' => [
        'name' => '代理名称',
        'code' => '代理编码',
        'parent_id' => '上级代理',
        'contact_name' => '联系人姓名',
        'contact_phone' => '联系人电话',
        'contact_email' => '联系人邮箱',
        'status' => '状态',
        'commission_rate' => '佣金比例',
        'ai_config' => 'AI配置',
        'quota_limit' => '配额限制',
        'expire_at' => '过期时间',
        'remark' => '备注',
        'admin_user' => '管理员用户',
        'admin_user.username' => '管理员用户名',
        'admin_user.password' => '管理员密码',
        'admin_user.nickname' => '管理员昵称',
        'admin_user.phone' => '管理员电话',
        'admin_user.email' => '管理员邮箱',
    ],
    
    // 租户相关
    'tenant' => [
        'name' => '租户名称',
        'code' => '租户编码',
        'parent_agent_id' => '所属代理',
        'contact_name' => '联系人姓名',
        'contact_phone' => '联系人电话',
        'contact_email' => '联系人邮箱',
        'status' => '状态',
        'ai_config' => 'AI配置',
        'quota_limit' => '配额限制',
        'expire_at' => '过期时间',
        'remark' => '备注',
        'admin_user' => '管理员用户',
        'admin_user.username' => '管理员用户名',
        'admin_user.password' => '管理员密码',
        'admin_user.nickname' => '管理员昵称',
        'admin_user.phone' => '管理员电话',
        'admin_user.email' => '管理员邮箱',
        'admin_user.quota_limit' => '管理员配额限制',
    ],
];