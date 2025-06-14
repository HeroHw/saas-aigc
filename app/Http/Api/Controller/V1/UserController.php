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

namespace App\Http\Api\Controller\V1;

use App\Dto\TestDto;
use App\Http\Api\Request\V1\UserRequest;
use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Result;
use App\Service\PassportService;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Get;
use Plugin\Alen\Dto\Office\Dto;
use Psr\Http\Message\ResponseInterface;

#[HyperfServer(name: 'http')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly PassportService $passportService
    ) {}

    #[Post(
        path: '/api/v1/login',
        operationId: 'ApiV1Login',
        summary: '用户登录',
        tags: ['api'],
    )]
    public function login(UserRequest $request): Result
    {
        return $this->success(
            $this->passportService->login(
                $request->input('username'),
                $request->input('password')
            )
        );
    }

    #[Get(
        path: '/api/v1/export',
        operationId: 'ApiV1Export',
        summary: '导出数据',
        tags: ['api'],
    )]
    public function export(): ResponseInterface
    {
        $users = [
            ['username' => 'admin', 'email' => 'admin@example.com', 'created_at' => '2025-04-05'],
            ['username' => 'user1', 'email' => 'user1@example.com', 'created_at' => '2025-04-04'],
        ];

        return Dto::instance()->export(TestDto::class, '用户登录数据', function () use ($users) {
            return $users;
        });
    }
}
