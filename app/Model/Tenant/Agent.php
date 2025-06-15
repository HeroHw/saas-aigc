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
 * @property int $id 代理ID，主键
 * @property string $name 代理名称
 * @property string $code 代理编码
 * @property int $parent_id 上级代理ID
 * @property int $level 代理层级
 * @property string $path 代理路径
 * @property string $contact_name 联系人姓名
 * @property string $contact_phone 联系人电话
 * @property string $contact_email 联系人邮箱
 * @property Status $status 状态
 * @property float $commission_rate 佣金比例
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
 * @property Agent $parent 上级代理
 * @property Collection|Agent[] $children 下级代理
 * @property Collection|AgentUser[] $users 代理用户
 * @property Collection|Tenant[] $tenants 下属租户
 */
final class Agent extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'agent';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'name',
        'code',
        'parent_id',
        'level',
        'path',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'commission_rate',
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
        'parent_id' => 'integer',
        'level' => 'integer',
        'status' => Status::class,
        'commission_rate' => 'decimal:4',
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
     * 上级代理
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'parent_id');
    }

    /**
     * 下级代理
     */
    public function children(): HasMany
    {
        return $this->hasMany(Agent::class, 'parent_id');
    }

    /**
     * 代理用户
     */
    public function users(): HasMany
    {
        return $this->hasMany(AgentUser::class, 'agent_id');
    }

    /**
     * 下属租户
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'parent_agent_id');
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
     * 检查代理是否可用
     */
    public function isAvailable(): bool
    {
        return $this->status === Status::NORMAL 
            && !$this->isExpired() 
            && !$this->isQuotaExceeded();
    }

    /**
     * 获取所有祖先代理
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;
        
        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }
        
        return $ancestors;
    }

    /**
     * 获取所有后代代理
     */
    public function getDescendants(): Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }
        
        return $descendants;
    }

    /**
     * 更新代理路径
     */
    public function updatePath(): void
    {
        if ($this->parent_id) {
            $this->path = $this->parent->path . ',' . $this->id;
        } else {
            $this->path = (string) $this->id;
        }
        $this->save();
    }

    /**
     * 是否为顶级代理
     */
    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }
}