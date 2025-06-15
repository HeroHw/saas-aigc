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
    // 代理相關
    'agent' => [
        'name' => '代理名稱',
        'code' => '代理編碼',
        'parent_id' => '上級代理',
        'contact_name' => '聯絡人姓名',
        'contact_phone' => '聯絡人電話',
        'contact_email' => '聯絡人郵箱',
        'status' => '狀態',
        'commission_rate' => '佣金比例',
        'ai_config' => 'AI配置',
        'quota_limit' => '配額限制',
        'expire_at' => '過期時間',
        'remark' => '備註',
        'admin_user' => '管理員用戶',
        'admin_user.username' => '管理員用戶名',
        'admin_user.password' => '管理員密碼',
        'admin_user.nickname' => '管理員暱稱',
        'admin_user.phone' => '管理員電話',
        'admin_user.email' => '管理員郵箱',
    ],
    
    // 租戶相關
    'tenant' => [
        'name' => '租戶名稱',
        'code' => '租戶編碼',
        'parent_agent_id' => '所屬代理',
        'contact_name' => '聯絡人姓名',
        'contact_phone' => '聯絡人電話',
        'contact_email' => '聯絡人郵箱',
        'status' => '狀態',
        'ai_config' => 'AI配置',
        'quota_limit' => '配額限制',
        'expire_at' => '過期時間',
        'remark' => '備註',
        'admin_user' => '管理員用戶',
        'admin_user.username' => '管理員用戶名',
        'admin_user.password' => '管理員密碼',
        'admin_user.nickname' => '管理員暱稱',
        'admin_user.phone' => '管理員電話',
        'admin_user.email' => '管理員郵箱',
        'admin_user.quota_limit' => '管理員配額限制',
    ],
];