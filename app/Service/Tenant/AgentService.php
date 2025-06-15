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
use App\Model\Tenant\Agent;
use App\Model\Tenant\AgentUser;
use App\Model\Tenant\Tenant;
use App\Repository\Tenant\AgentRepository;
use App\Service\IService;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;
use Hyperf\Paginator\LengthAwarePaginator;

class AgentService extends IService
{

    public function __construct(protected readonly AgentRepository $repository){}
    /**
     * 获取代理信息
     */
    public function getInfo(mixed $id): ?Agent
    {
        return $this->repository->findById($id);
    }

    /**
     * 获取代理列表
     */
    public function getList(array $paras): Collection
    {
        return $this->repository->list($paras);
    }

    /**
     * 根据编码查找代理
     */
    public function findByCode(string $code): ?Agent
    {
        return $this->repository->findByCode($code);
    }

    /**
     * 获取代理树形结构
     */
    public function getTree(int $parentId = null): Collection
    {
        $agents = $this->repository->getQuery()->where('parent_id', $parentId)
            ->orderBy('created_at')
            ->get();

        return $agents->map(function (Agent $agent) {
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'code' => $agent->code,
                'level' => $agent->level,
                'status' => $agent->status,
                'children' => $this->getTree($agent->id),
            ];
        });
    }

    public function create(array $data): mixed
    {
        return Db::transaction(function () use ($data) {
            // 生成代理编码
            if (empty($data['code'])) {
                $data['code'] = $this->generateAgentCode();
            }

            // 验证上级代理
            if (!empty($data['parent_id'])) {
                $parentAgent = $this->repository->findById($data['parent_id']);
                if (!$parentAgent || !$parentAgent->isAvailable()) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '指定的上级代理不存在或不可用');
                }

                // 检查层级限制
                if ($parentAgent->level >= 5) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '代理层级不能超过5级');
                }

                $data['level'] = $parentAgent->level + 1;
                $data['path'] = $parentAgent->path . ',' . $parentAgent->id;
            } else {
                $data['level'] = 1;
                $data['path'] = '';
            }

            /** @var Agent $agent */
            $agent = parent::create($data);
            $this->handleWith($agent, $data);
            return $agent;
        });
    }

    public function updateById(mixed $id, array $data): mixed
    {
        return Db::transaction(function () use ($id, $data) {
            /** @var null|Agent $agent */
            $agent = $this->repository->findById($id);
            if (empty($agent)) {
                throw new BusinessException(ResultCode::NOT_FOUND);
            }

            // 验证上级代理
            if (!empty($data['parent_id']) && $data['parent_id'] !== $agent->parent_id) {
                if ($data['parent_id'] == $id) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '不能设置自己为上级代理');
                }

                $parentAgent = $this->repository->findById($data['parent_id']);
                if (!$parentAgent || !$parentAgent->isAvailable()) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '指定的上级代理不存在或不可用');
                }

                // 检查是否会形成循环引用
                if ($parentAgent->isDescendantOf($agent)) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '不能设置下级代理为上级代理');
                }

                // 检查层级限制
                if ($parentAgent->level >= 5) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '代理层级不能超过5级');
                }
            }

            $agent->fill($data)->save();
            $this->handleWith($agent, $data);
            return $agent;
        });
    }

    /**
     * 处理关联关系
     */
    private function handleWith(Agent $agent, array $data): void
    {
        // 创建默认管理员用户
        if (!empty($data['admin_user'])) {
            $this->createAdminUser($agent, $data['admin_user']);
        }

        // 如果上级代理发生变化，更新路径
        if (!empty($data['parent_id']) && $data['parent_id'] !== $agent->getOriginal('parent_id')) {
            $agent->updatePath();
            $agent->updateChildrenPath();
        }
    }

    public function deleteById(mixed $id): int
    {
        /** @var null|Agent $agent */
        $agent = $this->repository->findById($id);
        if (empty($agent)) {
            throw new BusinessException(ResultCode::NOT_FOUND);
        }
        
        // 检查是否有下级代理
        if ($agent->children()->exists()) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '该代理存在下级代理，无法删除');
        }

        // 检查是否有关联的租户
        if ($agent->tenants()->exists()) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '该代理存在关联的租户，无法删除');
        }

        return $this->repository->deleteById($id);
    }

    /**
     * 获取代理详情
     */
    public function getDetail(mixed $id): ?Agent
    {
        /** @var null|Agent $agent */
        $agent = $this->repository->findById($id);
        if ($agent) {
            $agent->load(['parent', 'children', 'tenants']);
        }
        return $agent;
    }

    /**
     * 重置代理配额
     */
    public function resetQuota(int $id, float $newLimit = null): bool
    {
        $agent = $this->repository->findById($id);
        if (!$agent) {
            throw new BusinessException(ResultCode::NOT_FOUND);
        }
        
        $updateData = ['quota_used' => 0];
        if ($newLimit !== null) {
            $updateData['quota_limit'] = $newLimit;
        }
        
        return $agent->update($updateData);
    }

    /**
     * 调整代理配额
     */
    public function adjustQuota(int $id, float $amount): bool
    {
        $agent = $this->repository->findById($id);
        if (!$agent) {
            throw new BusinessException(ResultCode::NOT_FOUND);
        }
        
        $newLimit = $agent->quota_limit + $amount;
        if ($newLimit < 0) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '配额不能为负数');
        }
        
        return $agent->update(['quota_limit' => $newLimit]);
    }

    /**
     * 获取代理统计信息
     */
    public function getStatistics(int $parentId): array
    {
        $query = $this->repository->getQuery()->query();
        
        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }
        
        $total = $query->count();
        $active = $query->where('status', Status::NORMAL)->count();
        $expired = $query->where('expire_at', '<', time())->count();
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
     * 生成代理编码
     */
    private function generateAgentCode(): string
    {
        do {
            $code = 'A' . date('Ymd') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while ($this->repository->getQuery()->where('code', $code)->exists());
        
        return $code;
    }

    /**
     * 创建管理员用户
     */
    private function createAdminUser(Agent $agent, array $userData): AgentUser
    {
        return AgentUser::create([
            'agent_id' => $agent->id,
            'username' => $userData['username'],
            'password' => $userData['password'] ?? '123456',
            'nickname' => $userData['nickname'] ?? $userData['username'],
            'phone' => $userData['phone'] ?? null,
            'email' => $userData['email'] ?? null,
            'user_type' => UserType::ADMIN,
            'status' => Status::NORMAL,
            'created_by' => $userData['created_by'] ?? 0,
        ]);
    }

    /**
     * 更新代理层级结构
     */
    private function updateAgentHierarchy(Agent $agent): void
    {
        // 更新当前代理路径
        $agent->updatePath();
        
        // 递归更新所有下级代理的层级和路径
        $this->updateChildrenHierarchy($agent);
    }

    /**
     * 递归更新下级代理层级
     */
    private function updateChildrenHierarchy(Agent $parent): void
    {
        $children = $parent->children;
        
        foreach ($children as $child) {
            $child->level = $parent->level + 1;
            $child->updatePath();
            
            // 递归更新下级
            $this->updateChildrenHierarchy($child);
        }
    }

    /**
     * 批量更新代理状态
     */
    public function batchUpdateStatus(array $ids, Status $status): int
    {
        return $this->repository->getQuery()->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * 获取即将过期的代理
     */
    public function getExpiringAgents(int $days = 7): Collection
    {
        return $this->repository->getQuery()->where('status', Status::NORMAL)
            ->whereBetween('expire_at', [Carbon::now(), Carbon::now()->addDays($days)])
            ->get();
    }

    /**
     * 获取配额使用率高的代理
     */
    public function getHighQuotaUsageAgents(float $threshold = 0.8): Collection
    {
        return $this->repository->getQuery()->where('status', Status::NORMAL)
            ->whereRaw('quota_used / quota_limit >= ?', [$threshold])
            ->get();
    }

    /**
     * 获取代理的管理范围
     */
    public function getManagementScope(int $agentId): array
    {
        $agent = $this->repository->findById($agentId);
        if (!$agent) {
            throw new BusinessException(ResultCode::NOT_FOUND);
        }
        
        $descendants = $agent->getDescendants();
        $tenantIds = $agent->tenants->pluck('id')->toArray();
        
        // 包含下级代理的租户
        foreach ($descendants as $descendant) {
            $tenantIds = array_merge($tenantIds, $descendant->tenants->pluck('id')->toArray());
        }
        
        return [
            'agent_id' => $agentId,
            'sub_agent_ids' => $descendants->pluck('id')->toArray(),
            'tenant_ids' => array_unique($tenantIds),
        ];
    }
}