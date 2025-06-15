<?php

namespace App\Dto;

use Plugin\Alen\Dto\Office\Annotation\ExcelProperty;
use Plugin\Alen\Dto\Office\Annotation\ExcelData;
use Plugin\Alen\Dto\Office\Interfaces\MineModelExcel;

#[ExcelData]
class TestDto implements MineModelExcel
{
    #[ExcelProperty(value: '用户名', index: 0)]
    public string $username;

    #[ExcelProperty(value: '邮箱', index: 1)]
    public string $email;

    #[ExcelProperty(value: '创建时间', index: 2)]
    public string $created_at;
}