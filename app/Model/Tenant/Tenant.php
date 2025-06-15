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

namespace App\Model\Tenant;

use App\Model\Enums\Tenant\Status;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 租户ID，主键
 * @property string $name 租户名称
 * @property string $code 租户编码
 * @property int $parent_agent_id 所属代理ID
 * @property string $contact_name 联系人姓名
 * @property string $contact_phone 联系人电话
 * @property string $contact_email 联系人邮箱
 * @property Status $status 状态
 * @property array $ai_config AI配置信息
 * @property float $quota_limit 配额限制
 * @property float $quota_used 已使用配额
 * @property Carbon $expire_at 过期时间
 * @property int $created_by 创建者
 * @property int $updated_by 更新者
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property Carbon $deleted_at 删除时间
 * @property string $remark 备注
 * @property Agent $parentAgent 所属代理
 * @property Collection|TenantUser[] $users 租户用户
 * @property Collection|TenantAppConfig[] $appConfigs 应用配置
 * @property Collection|QuotaUsageLog[] $quotaUsageLogs 配额使用记录
 */
final class Tenant extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'tenant';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'name',
        'code',
        'parent_agent_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'ai_config',
        'quota_limit',
        'quota_used',
        'expire_at',
        'created_by',
        'updated_by',
        'remark',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'integer',
        'parent_agent_id' => 'integer',
        'status' => Status::class,
        'ai_config' => 'json',
        'quota_limit' => 'decimal:2',
        'quota_used' => 'decimal:2',
        'expire_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 所属代理
     */
    public function parentAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'parent_agent_id');
    }

    /**
     * 租户用户
     */
    public function users(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'tenant_id');
    }

    /**
     * 应用配置
     */
    public function appConfigs(): HasMany
    {
        return $this->hasMany(TenantAppConfig::class, 'tenant_id');
    }

    /**
     * 配额使用记录
     */
    public function quotaUsageLogs(): HasMany
    {
        return $this->hasMany(QuotaUsageLog::class, 'tenant_id');
    }

    /**
     * 检查是否过期
     */
    public function isExpired(): bool
    {
        return $this->expire_at && $this->expire_at->isPast();
    }

    /**
     * 检查配额是否超限
     */
    public function isQuotaExceeded(): bool
    {
        return $this->quota_used >= $this->quota_limit;
    }

    /**
     * 获取剩余配额
     */
    public function getRemainingQuota(): float
    {
        return max(0, $this->quota_limit - $this->quota_used);
    }

    /**
     * 增加配额使用量
     */
    public function addQuotaUsage(float $amount): bool
    {
        if ($this->quota_used + $amount > $this->quota_limit) {
            return false;
        }
        
        $this->quota_used += $amount;
        return $this->save();
    }

    /**
     * 检查租户是否可用
     */
    public function isAvailable(): bool
    {
        return $this->status === Status::NORMAL 
            && !$this->isExpired() 
            && !$this->isQuotaExceeded();
    }
}