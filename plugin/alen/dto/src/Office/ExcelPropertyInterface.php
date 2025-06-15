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

namespace Plugin\Alen\Dto\Office;

use Hyperf\DbConnection\Model\Model;
use Hyperf\HttpServer\Contract\ResponseInterface;

interface ExcelPropertyInterface
{
    public function import(Model $model, ?\Closure $closure = null): mixed;

    public function export(string $filename, array|\Closure $closure): ResponseInterface;
}
