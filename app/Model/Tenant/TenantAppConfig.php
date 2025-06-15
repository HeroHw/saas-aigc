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

use App\Model\Enums\Tenant\AppType;
use App\Model\Enums\Tenant\Status;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 配置ID，主键
 * @property int $tenant_id 租户ID
 * @property AppType $app_type 应用类型
 * @property string $app_name 应用名称
 * @property array $config 配置信息
 * @property Status $status 状态
 * @property float $quota_limit 配额限制
 * @property float $quota_used 已使用配额
 * @property int $created_by 创建者
 * @property int $updated_by 更新者
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property Carbon $deleted_at 删除时间
 * @property string $remark 备注
 * @property Tenant $tenant 所属租户
 */
final class TenantAppConfig extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'tenant_app_config';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'tenant_id',
        'app_type',
        'app_name',
        'config',
        'status',
        'quota_limit',
        'quota_used',
        'created_by',
        'updated_by',
        'remark',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'app_type' => AppType::class,
        'config' => 'json',
        'status' => Status::class,
        'quota_limit' => 'decimal:2',
        'quota_used' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 所属租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
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
     * 检查应用配置是否可用
     */
    public function isAvailable(): bool
    {
        return $this->status === Status::NORMAL 
            && !$this->isQuotaExceeded()
            && $this->tenant->isAvailable();
    }

    /**
     * 获取配置值
     */
    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * 设置配置值
     */
    public function setConfigValue(string $key, $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
    }

    /**
     * 合并配置
     */
    public function mergeConfig(array $newConfig): void
    {
        $this->config = array_merge($this->config ?? [], $newConfig);
    }

    /**
     * 获取API密钥
     */
    public function getApiKey(): ?string
    {
        return $this->getConfigValue('api_key');
    }

    /**
     * 获取API端点
     */
    public function getApiEndpoint(): ?string
    {
        return $this->getConfigValue('api_endpoint');
    }

    /**
     * 获取模型配置
     */
    public function getModelConfig(): array
    {
        return $this->getConfigValue('models', []);
    }

    /**
     * 检查是否支持指定模型
     */
    public function supportsModel(string $model): bool
    {
        $models = $this->getModelConfig();
        return in_array($model, $models) || isset($models[$model]);
    }
}