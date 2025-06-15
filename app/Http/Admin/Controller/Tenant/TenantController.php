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

namespace App\Http\Admin\Controller\Tenant;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Admin\Request\Tenant\TenantRequest;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Http\CurrentUser;
use App\Model\Enums\Tenant\Status;
use App\Service\Tenant\TenantService;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\JsonContent;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\PageResponse;
use Mine\Swagger\Attributes\ResultResponse;
use OpenApi\Attributes\RequestBody;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class TenantController extends AbstractController
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly CurrentUser $currentUser
    ) {}

    #[Get(
        path: '/admin/tenant/list',
        operationId: 'tenantList',
        summary: '租户列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:index')]
    #[PageResponse(instance: \App\Schema\TenantSchema::class)]
    public function pageList(): Result
    {
        return $this->success(
            $this->tenantService->page(
                $this->getRequestData(),
                $this->getCurrentPage(),
                $this->getPageSize()
            )
        );
    }

    #[Post(
        path: '/admin/tenant',
        operationId: 'tenantCreate',
        summary: '创建租户',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:save')]
    #[RequestBody(content: new JsonContent(ref: TenantRequest::class, title: '创建租户'))]
    #[ResultResponse(new Result())]
    public function create(TenantRequest $request): Result
    {
        $this->tenantService->create(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        return $this->success();
    }

    #[Get(
        path: '/admin/tenant/{id}',
        operationId: 'tenantDetail',
        summary: '获取租户详情',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:read')]
    #[ResultResponse(new Result())]
    public function show(int $id): Result
    {
        $tenant = $this->tenantService->getDetail($id);
        
        return $this->success($tenant);
    }

    #[Put(
        path: '/admin/tenant/{id}',
        operationId: 'tenantUpdate',
        summary: '更新租户',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:save')]
    #[RequestBody(content: new JsonContent(ref: TenantRequest::class, title: '更新租户'))]
    #[ResultResponse(new Result())]
    public function updateInfo(int $id, TenantRequest $request): Result
    {
        $this->tenantService->updateById($id, array_merge($request->validated(), [
            'updated_by' => $this->currentUser->id(),
        ]));
        
        return $this->success();
    }

    #[Delete(
        path: '/admin/tenant/{id}',
        operationId: 'tenantDelete',
        summary: '删除租户',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:delete')]
    #[ResultResponse(new Result())]
    public function delete(int $id): Result
    {
        $this->tenantService->deleteById($id);
        
        return $this->success();
    }

    #[Post(
        path: '/admin/tenant/{id}/reset-quota',
        operationId: 'tenantResetQuota',
        summary: '重置租户配额',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:quota')]
    #[ResultResponse(new Result())]
    public function resetQuota(int $id): Result
    {
        $newLimit = $this->getRequest()->input('quota_limit');
        $this->tenantService->resetQuota($id, $newLimit);
        
        return $this->success();
    }

    #[Post(
        path: '/admin/tenant/{id}/adjust-quota',
        operationId: 'tenantAdjustQuota',
        summary: '调整租户配额',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:quota')]
    #[ResultResponse(new Result())]
    public function adjustQuota(int $id): Result
    {
        $amount = (float) $this->getRequest()->input('amount', 0);
        $this->tenantService->adjustQuota($id, $amount);
        
        return $this->success();
    }

    #[Post(
        path: '/admin/tenant/batch-status',
        operationId: 'tenantBatchStatus',
        summary: '批量更新租户状态',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:save')]
    #[ResultResponse(new Result())]
    public function batchUpdateStatus(): Result
    {
        $ids = $this->getRequest()->input('ids', []);
        $status = Status::from($this->getRequest()->input('status'));
        
        $count = $this->tenantService->batchUpdateStatus($ids, $status);
        
        return $this->success($count);
    }

    #[Get(
        path: '/admin/tenant-list/statistics',
        operationId: 'tenantStatistics',
        summary: '获取租户统计信息',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:read')]
    #[ResultResponse(new Result())]
    public function statistics(): Result
    {
        $agentId = $this->getRequest()->input('agent_id');
        $statistics = $this->tenantService->getStatistics($agentId);
        
        return $this->success($statistics);
    }

    #[Get(
        path: '/admin/tenant-list/expiring',
        operationId: 'tenantExpiring',
        summary: '获取即将过期的租户',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:read')]
    #[ResultResponse(new Result())]
    public function getExpiringTenants(): Result
    {
        $days = (int) $this->getRequest()->input('days', 7);
        $tenants = $this->tenantService->getExpiringTenants($days);
        
        return $this->success($tenants);
    }

    #[Get(
        path: '/admin/tenant-list/high-quota-usage',
        operationId: 'tenantHighQuotaUsage',
        summary: '获取配额使用率高的租户',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:read')]
    #[ResultResponse(new Result())]
    public function getHighQuotaUsageTenants(): Result
    {
        $threshold = (float) $this->getRequest()->input('threshold', 0.8);
        $tenants = $this->tenantService->getHighQuotaUsageTenants($threshold);
        
        return $this->success($tenants);
    }

    #[Get(
        path: '/admin/tenant-list/status-options',
        operationId: 'tenantStatusOptions',
        summary: '获取状态选项',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['租户管理']
    )]
    #[Permission(code: 'tenant:tenant:read')]
    #[ResultResponse(new Result())]
    public function getStatusOptions(): Result
    {
        return $this->success(Status::options());
    }
}