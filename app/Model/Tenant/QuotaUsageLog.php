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
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 记录ID，主键
 * @property int $tenant_id 租户ID
 * @property int $tenant_user_id 租户用户ID
 * @property AppType $app_type 应用类型
 * @property string $model_name 模型名称
 * @property int $input_tokens 输入token数
 * @property int $output_tokens 输出token数
 * @property int $total_tokens 总token数
 * @property float $cost 费用
 * @property string $request_id 请求ID
 * @property array $request_data 请求数据
 * @property array $response_data 响应数据
 * @property string $ip_address IP地址
 * @property string $user_agent 用户代理
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property string $remark 备注
 * @property Tenant $tenant 所属租户
 * @property TenantUser $tenantUser 租户用户
 */
final class QuotaUsageLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'quota_usage_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'tenant_id',
        'tenant_user_id',
        'app_type',
        'model_name',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'cost',
        'request_id',
        'request_data',
        'response_data',
        'ip_address',
        'user_agent',
        'remark',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'tenant_user_id' => 'integer',
        'app_type' => AppType::class,
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost' => 'decimal:6',
        'request_data' => 'json',
        'response_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 所属租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * 租户用户
     */
    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id');
    }

    /**
     * 获取请求数据中的特定字段
     */
    public function getRequestValue(string $key, $default = null)
    {
        return data_get($this->request_data, $key, $default);
    }

    /**
     * 获取响应数据中的特定字段
     */
    public function getResponseValue(string $key, $default = null)
    {
        return data_get($this->response_data, $key, $default);
    }

    /**
     * 计算token使用效率
     */
    public function getTokenEfficiency(): float
    {
        if ($this->input_tokens === 0) {
            return 0;
        }
        
        return round($this->output_tokens / $this->input_tokens, 2);
    }

    /**
     * 计算平均每token成本
     */
    public function getCostPerToken(): float
    {
        if ($this->total_tokens === 0) {
            return 0;
        }
        
        return round($this->cost / $this->total_tokens, 6);
    }

    /**
     * 获取使用时长（如果有响应时间）
     */
    public function getDuration(): ?int
    {
        $startTime = $this->getRequestValue('timestamp');
        $endTime = $this->getResponseValue('timestamp');
        
        if ($startTime && $endTime) {
            return $endTime - $startTime;
        }
        
        return null;
    }

    /**
     * 是否为成功的请求
     */
    public function isSuccessful(): bool
    {
        $status = $this->getResponseValue('status');
        return $status === 'success' || $status === 200;
    }

    /**
     * 获取错误信息
     */
    public function getErrorMessage(): ?string
    {
        if ($this->isSuccessful()) {
            return null;
        }
        
        return $this->getResponseValue('error.message') 
            ?? $this->getResponseValue('error') 
            ?? '未知错误';
    }

    /**
     * 创建使用记录
     */
    public static function createUsageLog(array $data): self
    {
        return self::create([
            'tenant_id' => $data['tenant_id'],
            'tenant_user_id' => $data['tenant_user_id'] ?? null,
            'app_type' => $data['app_type'],
            'model_name' => $data['model_name'],
            'input_tokens' => $data['input_tokens'] ?? 0,
            'output_tokens' => $data['output_tokens'] ?? 0,
            'total_tokens' => ($data['input_tokens'] ?? 0) + ($data['output_tokens'] ?? 0),
            'cost' => $data['cost'] ?? 0,
            'request_id' => $data['request_id'] ?? null,
            'request_data' => $data['request_data'] ?? [],
            'response_data' => $data['response_data'] ?? [],
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'remark' => $data['remark'] ?? null,
        ]);
    }
}