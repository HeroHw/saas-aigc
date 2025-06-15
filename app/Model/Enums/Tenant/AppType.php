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

enum AppType: string
{
    case CHAT = 'chat';           // 聊天应用
    case IMAGE = 'image';         // 图像生成
    case AUDIO = 'audio';         // 音频处理
    case VIDEO = 'video';         // 视频处理
    case EMBEDDING = 'embedding'; // 向量嵌入
    case COMPLETION = 'completion'; // 文本补全

    public function label(): string
    {
        return match ($this) {
            self::CHAT => '聊天应用',
            self::IMAGE => '图像生成',
            self::AUDIO => '音频处理',
            self::VIDEO => '视频处理',
            self::EMBEDDING => '向量嵌入',
            self::COMPLETION => '文本补全',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CHAT => 'i-material-symbols:chat',
            self::IMAGE => 'i-material-symbols:image',
            self::AUDIO => 'i-material-symbols:audio-file',
            self::VIDEO => 'i-material-symbols:video-file',
            self::EMBEDDING => 'i-material-symbols:vector',
            self::COMPLETION => 'i-material-symbols:text-fields',
        };
    }

    public static function options(): array
    {
        return [
            ['label' => self::CHAT->label(), 'value' => self::CHAT->value, 'icon' => self::CHAT->icon()],
            ['label' => self::IMAGE->label(), 'value' => self::IMAGE->value, 'icon' => self::IMAGE->icon()],
            ['label' => self::AUDIO->label(), 'value' => self::AUDIO->value, 'icon' => self::AUDIO->icon()],
            ['label' => self::VIDEO->label(), 'value' => self::VIDEO->value, 'icon' => self::VIDEO->icon()],
            ['label' => self::EMBEDDING->label(), 'value' => self::EMBEDDING->value, 'icon' => self::EMBEDDING->icon()],
            ['label' => self::COMPLETION->label(), 'value' => self::COMPLETION->value, 'icon' => self::COMPLETION->icon()],
        ];
    }
}