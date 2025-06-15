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

namespace App\Service\Tenant;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Model\Enums\Tenant\Status;
use App\Model\Enums\Tenant\UserType;
use App\Model\Tenant\Tenant;
use App\Model\Tenant\TenantUser;
use App\Model\Tenant\Agent;
use App\Repository\Tenant\TenantRepository;
use App\Repository\Tenant\AgentRepository;
use App\Service\IService;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\DbConnection\Db;

/**
 * @extends IService<Tenant>
 */
final class TenantService extends IService
{
    public function __construct(
        protected readonly TenantRepository $repository,
        protected readonly AgentRepository $agentRepository
    ) {}

    public function getInfo(int $id): ?Tenant
    {
        return $this->repository->findById($id);
    }

    public function getDetail(int $id): Tenant
    {
        $tenant = $this->repository->findById($id);
        if (!$tenant) {
            throw new BusinessException(ResultCode::NOT_FOUND);
        }
        return $tenant->load(['parentAgent', 'users', 'appConfigs']);
    }

    public function findByCode(string $code): ?Tenant
    {
        return $this->repository->findByCode($code);
    }

    public function create(array $data): mixed
    {
        return Db::transaction(function () use ($data) {
            // 生成租户编码
            if (empty($data['code'])) {
                $data['code'] = $this->generateTenantCode();
            }

            // 验证代理是否存在且可用
            if (!empty($data['parent_agent_id'])) {
                $agent = $this->agentRepository->findById($data['parent_agent_id']);
                if (!$agent || !$agent->isAvailable()) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '指定的代理不存在或不可用');
                }
            }

            /** @var Tenant $tenant */
            $tenant = parent::create($data);
            $this->handleWith($tenant, $data);
            return $tenant;
        });
    }

    public function updateById(mixed $id, array $data): mixed
    {
        return Db::transaction(function () use ($id, $data) {
            /** @var null|Tenant $tenant */
            $tenant = $this->repository->findById($id);
            if (empty($tenant)) {
                throw new BusinessException(ResultCode::NOT_FOUND);
            }

            // 验证代理是否存在且可用
            if (!empty($data['parent_agent_id']) && $data['parent_agent_id'] !== $tenant->parent_agent_id) {
                $agent = $this->agentRepository->findById($data['parent_agent_id']);
                if (!$agent || !$agent->isAvailable()) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '指定的代理不存在或不可用');
                }
            }

            $tenant->fill($data)->save();
            $this->handleWith($tenant, $data);
            return $tenant;
        });
    }

    /**
     * 处理关联关系
     */
    private function handleWith(Tenant $tenant, array $data): void
    {
        // 创建默认管理员用户
        if (!empty($data['admin_user'])) {
            $this->createAdminUser($tenant, $data['admin_user']);
        }
    }

    public function deleteById(mixed $id): int
    {
        /** @var null|Tenant $tenant */
        $tenant = $this->repository->findById($id);
        if (empty($tenant)) {
            throw new BusinessException(ResultCode::NOT_FOUND);
        }
        
        // 检查是否有关联的使用记录
        if ($tenant->usageRecords()->exists()) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '该租户存在使用记录，无法删除');
        }

        return $this->repository->deleteById($id);
    }

    /**
     * 重置租户配额
     */
    public function resetQuota(int $id, float $newLimit = null): bool
    {
        $tenant = $this->repository->findById($id);
        if (!$tenant) {
            throw new BusinessException(ResultCode::NOT_FOUND);
        }
        
        $updateData = ['quota_used' => 0];
        if ($newLimit !== null) {
            $updateData['quota_limit'] = $newLimit;
        }
        
        return $tenant->update($updateData);
    }

    /**
     * 调整租户配额
     */
    public function adjustQuota(int $id, float $amount): bool
    {
        $tenant = $this->repository->findById($id);
        if (!$tenant) {
            throw new BusinessException(ResultCode::NOT_FOUND);
        }
        
        $newLimit = $tenant->quota_limit + $amount;
        if ($newLimit < 0) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '配额不能为负数');
        }
        
        return $tenant->update(['quota_limit' => $newLimit]);
    }

    /**
     * 获取租户统计信息
     */
    public function getStatistics(int $agentId): array
    {
        $query = $this->repository->getQuery();
        
        if ($agentId) {
            $query->where('parent_agent_id', $agentId);
        }
        
        $total = $query->count();
        $active = $query->where('status', Status::NORMAL)->count();
        $expired = $query->where('expire_at', '<', Carbon::now())->count();
        $quotaExceeded = $query->whereRaw('quota_used >= quota_limit')->count();
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'expired' => $expired,
            'quota_exceeded' => $quotaExceeded,
            'available' => $active - $expired - $quotaExceeded,
        ];
    }

    /**
     * 生成租户编码
     */
    private function generateTenantCode(): string
    {
        do {
            $code = 'T' . date('Ymd') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while ($this->repository->getQuery()->where('code', $code)->exists());
        
        return $code;
    }

    /**
     * 创建管理员用户
     */
    private function createAdminUser(Tenant $tenant, array $userData): TenantUser
    {
        return TenantUser::create([
            'tenant_id' => $tenant->id,
            'username' => $userData['username'],
            'password' => $userData['password'] ?? '123456',
            'nickname' => $userData['nickname'] ?? $userData['username'],
            'phone' => $userData['phone'] ?? null,
            'email' => $userData['email'] ?? null,
            'user_type' => UserType::ADMIN,
            'status' => Status::NORMAL,
            'quota_limit' => $userData['quota_limit'] ?? $tenant->quota_limit,
            'quota_used' => 0,
            'created_by' => $userData['created_by'] ?? 0,
        ]);
    }

    /**
     * 批量更新租户状态
     */
    public function batchUpdateStatus(array $ids, Status $status): int
    {
        return $this->repository->getQuery()->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * 获取即将过期的租户
     */
    public function getExpiringTenants(int $days = 7): Collection
    {
        return $this->repository->getQuery()->where('status', Status::NORMAL)
            ->whereBetween('expire_at', [Carbon::now(), Carbon::now()->addDays($days)])
            ->get();
    }

    /**
     * 获取配额使用率高的租户
     */
    public function getHighQuotaUsageTenants(float $threshold = 0.8): Collection
    {
        return $this->repository->getQuery()->where('status', Status::NORMAL)
            ->whereRaw('quota_used / quota_limit >= ?', [$threshold])
            ->get();
    }
}