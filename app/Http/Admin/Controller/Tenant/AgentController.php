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
use App\Http\Admin\Request\Tenant\AgentRequest;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Http\CurrentUser;
use App\Model\Enums\Tenant\Status;
use App\Service\Tenant\AgentService;
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
final class AgentController extends AbstractController
{
    public function __construct(
        private readonly AgentService $agentService,
        private readonly CurrentUser $currentUser
    ) {}

    #[Get(
        path: '/admin/agent/list',
        operationId: 'agentList',
        summary: '代理列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:index')]
    #[PageResponse(instance: \App\Schema\AgentSchema::class)]
    public function pageList(): Result
    {
        return $this->success(
            $this->agentService->page(
                $this->getRequestData(),
                $this->getCurrentPage(),
                $this->getPageSize()
            )
        );
    }

    #[Get(
        path: '/admin/agent-list/tree',
        operationId: 'agentTree',
        summary: '获取代理树形结构',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:tree')]
    #[ResultResponse(new Result())]
    public function tree(): Result
    {
        $parentId = $this->getRequest()->input('parent_id');
        $tree = $this->agentService->getTree($parentId);
        
        return $this->success($tree);
    }

    #[Post(
        path: '/admin/agent',
        operationId: 'agentCreate',
        summary: '创建代理',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:save')]
    #[RequestBody(content: new JsonContent(ref: AgentRequest::class, title: '创建代理'))]
    #[ResultResponse(new Result())]
    public function create(AgentRequest $request): Result
    {
        $this->agentService->create(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        return $this->success();
    }

    #[Get(
        path: '/admin/agent/{id}',
        operationId: 'agentDetail',
        summary: '获取代理详情',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:read')]
    #[ResultResponse(new Result())]
    public function show(int $id): Result
    {
        $agent = $this->agentService->getDetail($id);
        
        return $this->success($agent);
    }

    #[Put(
        path: '/admin/agent/{id}',
        operationId: 'agentUpdate',
        summary: '更新代理',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:save')]
    #[RequestBody(content: new JsonContent(ref: AgentRequest::class, title: '更新代理'))]
    #[ResultResponse(new Result())]
    public function updateInfo(int $id, AgentRequest $request): Result
    {
        $this->agentService->updateById($id, array_merge($request->validated(), [
            'updated_by' => $this->currentUser->id(),
        ]));
        
        return $this->success();
    }

    #[Delete(
        path: '/admin/agent/{id}',
        operationId: 'agentDelete',
        summary: '删除代理',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:delete')]
    #[ResultResponse(new Result())]
    public function delete(int $id): Result
    {
        $this->agentService->deleteById($id);
        
        return $this->success();
    }

    #[Post(
        path: '/admin/agent/{id}/reset-quota',
        operationId: 'agentResetQuota',
        summary: '重置代理配额',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:quota')]
    #[ResultResponse(new Result())]
    public function resetQuota(int $id): Result
    {
        $newLimit = $this->getRequest()->input('quota_limit');
        $this->agentService->resetQuota($id, $newLimit);
        
        return $this->success();
    }

    #[Post(
        path: '/admin/agent/{id}/adjust-quota',
        operationId: 'agentAdjustQuota',
        summary: '调整代理配额',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:quota')]
    #[ResultResponse(new Result())]
    public function adjustQuota(int $id): Result
    {
        $amount = (float) $this->getRequest()->input('amount', 0);
        $this->agentService->adjustQuota($id, $amount);
        
        return $this->success();
    }

    #[Post(
        path: '/admin/agent/batch-status',
        operationId: 'agentBatchStatus',
        summary: '批量更新代理状态',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:save')]
    #[ResultResponse(new Result())]
    public function batchUpdateStatus(): Result
    {
        $ids = $this->getRequest()->input('ids', []);
        $status = Status::from($this->getRequest()->input('status'));
        
        $count = $this->agentService->batchUpdateStatus($ids, $status);
        
        return $this->success($count);
    }

    #[Get(
        path: '/admin/agent-list/statistics',
        operationId: 'agentStatistics',
        summary: '获取代理统计信息',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:read')]
    #[ResultResponse(new Result())]
    public function statistics(): Result
    {
        $parentId = $this->getRequest()->input('parent_id');
        $statistics = $this->agentService->getStatistics($parentId);
        
        return $this->success($statistics);
    }

    #[Get(
        path: '/admin/agent-list/expiring',
        operationId: 'agentExpiring',
        summary: '获取即将过期的代理',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:read')]
    #[ResultResponse(new Result())]
    public function getExpiringAgents(): Result
    {
        $days = (int) $this->getRequest()->input('days', 7);
        $agents = $this->agentService->getExpiringAgents($days);
        
        return $this->success($agents);
    }

    #[Get(
        path: '/admin/agent-list/high-quota-usage',
        operationId: 'agentHighQuotaUsage',
        summary: '获取配额使用率高的代理',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:read')]
    #[ResultResponse(new Result())]
    public function getHighQuotaUsageAgents(): Result
    {
        $threshold = (float) $this->getRequest()->input('threshold', 0.8);
        $agents = $this->agentService->getHighQuotaUsageAgents($threshold);
        
        return $this->success($agents);
    }

    #[Get(
        path: '/admin/agent/{id}/management-scope',
        operationId: 'agentManagementScope',
        summary: '获取代理管理范围',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:read')]
    #[ResultResponse(new Result())]
    public function getManagementScope(int $id): Result
    {
        $scope = $this->agentService->getManagementScope($id);
        
        return $this->success($scope);
    }

    #[Get(
        path: '/admin/agent-list/status-options',
        operationId: 'agentStatusOptions',
        summary: '获取状态选项',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['代理管理']
    )]
    #[Permission(code: 'tenant:agent:read')]
    #[ResultResponse(new Result())]
    public function getStatusOptions(): Result
    {
        return $this->success(Status::options());
    }
}