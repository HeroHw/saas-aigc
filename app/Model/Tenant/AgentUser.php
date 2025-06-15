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
use App\Model\Enums\Tenant\UserType;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Stringable\Str;

/**
 * @property int $id 用户ID，主键
 * @property int $agent_id 所属代理ID
 * @property string $username 用户名
 * @property string $password 密码
 * @property string $nickname 用户昵称
 * @property string $phone 手机
 * @property string $email 用户邮箱
 * @property string $avatar 用户头像
 * @property UserType $user_type 用户类型
 * @property Status $status 状态
 * @property string $login_ip 最后登陆IP
 * @property Carbon $login_time 最后登陆时间
 * @property array $user_setting 用户设置数据
 * @property int $created_by 创建者
 * @property int $updated_by 更新者
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property Carbon $deleted_at 删除时间
 * @property string $remark 备注
 * @property Agent $agent 所属代理
 */
final class AgentUser extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'agent_user';

    /**
     * 隐藏的字段列表.
     */
    protected array $hidden = ['password'];

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'agent_id',
        'username',
        'password',
        'nickname',
        'phone',
        'email',
        'avatar',
        'user_type',
        'status',
        'login_ip',
        'login_time',
        'user_setting',
        'created_by',
        'updated_by',
        'remark',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'integer',
        'agent_id' => 'integer',
        'user_type' => UserType::class,
        'status' => Status::class,
        'login_time' => 'datetime',
        'user_setting' => 'json',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 所属代理
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * 设置密码
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * 验证密码
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * 重置密码为默认密码
     */
    public function resetPassword(string $defaultPassword = '123456'): void
    {
        $this->password = $defaultPassword;
    }

    /**
     * 检查用户是否可用
     */
    public function isAvailable(): bool
    {
        return $this->status === Status::NORMAL && $this->agent->isAvailable();
    }

    /**
     * 是否为管理员
     */
    public function isAdmin(): bool
    {
        return $this->user_type === UserType::ADMIN;
    }

    /**
     * 更新登录信息
     */
    public function updateLoginInfo(string $ip): void
    {
        $this->login_ip = $ip;
        $this->login_time = Carbon::now();
        $this->save();
    }

    /**
     * 生成API Token
     */
    public function generateApiToken(): string
    {
        return Str::random(64);
    }

    /**
     * 获取管理权限范围
     */
    public function getManagementScope(): array
    {
        if (!$this->isAdmin()) {
            return [];
        }

        // 管理员可以管理所属代理及其下级代理和租户
        $scope = [
            'agent_id' => $this->agent_id,
            'sub_agents' => $this->agent->getDescendants()->pluck('id')->toArray(),
            'tenants' => $this->agent->tenants->pluck('id')->toArray(),
        ];

        // 包含下级代理的租户
        foreach ($this->agent->getDescendants() as $subAgent) {
            $scope['tenants'] = array_merge($scope['tenants'], $subAgent->tenants->pluck('id')->toArray());
        }

        return $scope;
    }
}