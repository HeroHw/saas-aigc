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

enum Status: int
{
    case NORMAL = 1;     // 正常
    case DISABLED = 2;   // 停用
    case PENDING = 3;    // 待审核

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => '正常',
            self::DISABLED => '停用',
            self::PENDING => '待审核',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NORMAL => 'success',
            self::DISABLED => 'danger',
            self::PENDING => 'warning',
        };
    }

    public static function options(): array
    {
        return [
            ['label' => self::NORMAL->label(), 'value' => self::NORMAL->value, 'color' => self::NORMAL->color()],
            ['label' => self::DISABLED->label(), 'value' => self::DISABLED->value, 'color' => self::DISABLED->color()],
            ['label' => self::PENDING->label(), 'value' => self::PENDING->value, 'color' => self::PENDING->color()],
        ];
    }
}