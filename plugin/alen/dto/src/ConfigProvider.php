<?php
namespace Plugin\Alen\Dto;

class ConfigProvider
{
    public function __invoke()
    {
        // Initial configuration
        return [
            // 合并到  config/autoload/annotations.php 文件
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'dto',
                    'description' => 'Dto 插件相关配置文件', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/../publish/dto.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/config/autoload/dto.php', // 复制为这个路径下的该文件
                ],
            ],
        ];
    }
}