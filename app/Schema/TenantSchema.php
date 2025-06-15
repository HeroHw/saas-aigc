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

namespace App\Schema;

use App\Model\Enums\Tenant\Status;
use Carbon\Carbon;
use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;

#[Schema(title: 'TenantSchema')]
final class TenantSchema implements \JsonSerializable
{
    #[Property(property: 'id', title: '租户ID，主键', type: 'int')]
    public ?int $id;

    #[Property(property: 'name', title: '租户名称', type: 'string')]
    public ?string $name;

    #[Property(property: 'code', title: '租户编码', type: 'string')]
    public ?string $code;

    #[Property(property: 'parent_agent_id', title: '所属代理ID', type: 'int')]
    public ?int $parentAgentId;

    #[Property(property: 'contact_name', title: '联系人姓名', type: 'string')]
    public ?string $contactName;

    #[Property(property: 'contact_phone', title: '联系人电话', type: 'string')]
    public ?string $contactPhone;

    #[Property(property: 'contact_email', title: '联系人邮箱', type: 'string')]
    public ?string $contactEmail;

    #[Property(property: 'status', title: '状态 (1正常 2停用 3待审核)', type: 'int')]
    public ?Status $status;

    #[Property(property: 'ai_config', title: 'AI配置信息', type: 'object')]
    public ?array $aiConfig;

    #[Property(property: 'quota_limit', title: '配额限制', type: 'number', format: 'float')]
    public ?float $quotaLimit;

    #[Property(property: 'quota_used', title: '已使用配额', type: 'number', format: 'float')]
    public ?float $quotaUsed;

    #[Property(property: 'quota_remaining', title: '剩余配额', type: 'number', format: 'float')]
    public ?float $quotaRemaining;

    #[Property(property: 'quota_usage_rate', title: '配额使用率', type: 'number', format: 'float')]
    public ?float $quotaUsageRate;

    #[Property(property: 'expire_at', title: '过期时间', type: 'string', format: 'date-time')]
    public ?Carbon $expireAt;

    #[Property(property: 'is_expired', title: '是否已过期', type: 'boolean')]
    public ?bool $isExpired;

    #[Property(property: 'days_until_expiry', title: '距离过期天数', type: 'int')]
    public ?int $daysUntilExpiry;

    #[Property(property: 'created_by', title: '创建者', type: 'int')]
    public ?int $createdBy;

    #[Property(property: 'updated_by', title: '更新者', type: 'int')]
    public ?int $updatedBy;

    #[Property(property: 'created_at', title: '创建时间', type: 'string', format: 'date-time')]
    public ?Carbon $createdAt;

    #[Property(property: 'updated_at', title: '更新时间', type: 'string', format: 'date-time')]
    public ?Carbon $updatedAt;

    #[Property(property: 'remark', title: '备注', type: 'string')]
    public ?string $remark;

    // 关联数据
    #[Property(property: 'parent_agent', title: '所属代理信息', type: 'object')]
    public ?AgentSchema $parentAgent;

    #[Property(property: 'users_count', title: '用户数量', type: 'int')]
    public ?int $usersCount;

    #[Property(property: 'app_configs_count', title: '应用配置数量', type: 'int')]
    public ?int $appConfigsCount;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'parent_agent_id' => $this->parentAgentId,
            'contact_name' => $this->contactName,
            'contact_phone' => $this->contactPhone,
            'contact_email' => $this->contactEmail,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'ai_config' => $this->aiConfig,
            'quota_limit' => $this->quotaLimit,
            'quota_used' => $this->quotaUsed,
            'quota_remaining' => $this->quotaRemaining,
            'quota_usage_rate' => $this->quotaUsageRate,
            'expire_at' => $this->expireAt?->toDateTimeString(),
            'is_expired' => $this->isExpired,
            'days_until_expiry' => $this->daysUntilExpiry,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'created_at' => $this->createdAt?->toDateTimeString(),
            'updated_at' => $this->updatedAt?->toDateTimeString(),
            'remark' => $this->remark,
            'parent_agent' => $this->parentAgent,
            'users_count' => $this->usersCount,
            'app_configs_count' => $this->appConfigsCount,
        ];
    }
}