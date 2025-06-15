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

namespace App\Model\Enums\Tenant;

enum UserType: int
{
    case NORMAL = 1;     // 普通用户
    case ADMIN = 2;      // 管理员

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => '普通用户',
            self::ADMIN => '管理员',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NORMAL => 'primary',
            self::ADMIN => 'warning',
        };
    }

    public static function options(): array
    {
        return [
            ['label' => self::NORMAL->label(), 'value' => self::NORMAL->value, 'color' => self::NORMAL->color()],
            ['label' => self::ADMIN->label(), 'value' => self::ADMIN->value, 'color' => self::ADMIN->color()],
        ];
    }
}