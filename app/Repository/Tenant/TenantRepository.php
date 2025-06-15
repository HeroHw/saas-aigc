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

namespace App\Repository\Tenant;

use App\Model\Tenant\Tenant;
use App\Repository\IRepository;
use Carbon\Carbon;
use Hyperf\Collection\Arr;
use Hyperf\Database\Model\Builder;

/**
 * Class TenantRepository.
 * @extends IRepository<Tenant>
 */
final class TenantRepository extends IRepository
{
    public function __construct(protected readonly Tenant $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(Arr::get($params, 'agent_id'), static function (Builder $query, $agentId) {
                $query->where('parent_agent_id', $agentId);
            })
            ->when(Arr::get($params, 'status'), static function (Builder $query, $status) {
                $query->where('status', $status);
            })
            ->when(Arr::get($params, 'name'), static function (Builder $query, $name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->when(Arr::get($params, 'code'), static function (Builder $query, $code) {
                $query->where('code', 'like', '%' . $code . '%');
            })
            ->when(Arr::get($params, 'contact_name'), static function (Builder $query, $contactName) {
                $query->where('contact_name', 'like', '%' . $contactName . '%');
            })
            ->when(Arr::get($params, 'contact_phone'), static function (Builder $query, $contactPhone) {
                $query->where('contact_phone', $contactPhone);
            })
            ->when(Arr::get($params, 'contact_email'), static function (Builder $query, $contactEmail) {
                $query->where('contact_email', $contactEmail);
            })
            ->when(isset($params['is_expired']), static function (Builder $query) use ($params) {
                if ($params['is_expired']) {
                    $query->where('expire_at', '<', Carbon::now());
                } else {
                    $query->where(function (Builder $q) {
                        $q->whereNull('expire_at')->orWhere('expire_at', '>=', Carbon::now());
                    });
                }
            })
            ->when(isset($params['quota_exceeded']), static function (Builder $query) use ($params) {
                if ($params['quota_exceeded']) {
                    $query->whereRaw('quota_used >= quota_limit');
                } else {
                    $query->whereRaw('quota_used < quota_limit');
                }
            });
    }

    public function findByCode(string $code): ?Tenant
    {
        return $this->model->newQuery()
            ->where('code', $code)
            ->first();
    }

    public function getExpiringTenants(int $days = 7): \Hyperf\Collection\Collection
    {
        return $this->model->newQuery()
            ->where('expire_at', '>', Carbon::now())
            ->where('expire_at', '<=', Carbon::now()->addDays($days))
            ->get();
    }

    public function getHighQuotaUsageTenants(float $threshold = 0.8): \Hyperf\Collection\Collection
    {
        return $this->model->newQuery()
            ->whereRaw('quota_used / quota_limit >= ?', [$threshold])
            ->where('quota_limit', '>', 0)
            ->get();
    }
}