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

namespace Plugin\Alen\Dto\Office\Excel;

use Hyperf\Collection\Arr;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Model\Model;
use Hyperf\HttpServer\Contract\RequestInterface;
use Plugin\Alen\Dto\Office\ExcelPropertyInterface;
use Plugin\Alen\Dto\Office\Exception\MineException;
use Plugin\Alen\Dto\Office\MineExcel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PhpOffice extends MineExcel implements ExcelPropertyInterface
{
    /**
     * 导入.
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function import(Model $model, ?\Closure $closure = null): mixed
    {
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        $data = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $tempFileName = 'import_' . time() . '.' . $file->getExtension();
            $tempFilePath = BASE_PATH . '/runtime/' . $tempFileName;
            file_put_contents($tempFilePath, $file->getStream()->getContents());
            $reader = IOFactory::createReader(IOFactory::identify($tempFilePath));
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($tempFilePath);
            $endCell = isset($this->property) ? $this->getColumnIndex(count($this->property)) : null;
            try {
                if ($this->orderByIndex) {
                    $data = $this->getDataByIndex($sheet, $endCell);
                } else {
                    $data = $this->getDataByText($sheet, $endCell);
                }
                unlink($tempFilePath);
            } catch (\Throwable $e) {
                unlink($tempFilePath);
                throw new MineException($e->getMessage());
            }
        } else {
            return false;
        }
        if ($closure instanceof \Closure) {
            return $closure($model, $data);
        }

        foreach ($data as $datum) {
            $model::create($datum);
        }
        return true;
    }

    /**
     * 导出.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function export(string $filename, array|\Closure $closure, ?\Closure $callbackData = null): ResponseInterface
    {
        $spread = new Spreadsheet();
        $sheet = $spread->getActiveSheet();
        $filename .= '.xlsx';

        is_array($closure) ? $data = &$closure : $data = $closure();

        // 表头
        $titleStart = 0;
        foreach ($this->property as $item) {
            $headerColumn = $this->getColumnIndex($titleStart) . '1';
            $sheet->setCellValue($headerColumn, $item['value']);
            $style = $sheet->getStyle($headerColumn)->getFont()->setBold(true);
            $columnDimension = $sheet->getColumnDimension($headerColumn[0]);

            empty($item['width']) ? $columnDimension->setAutoSize(true) : $columnDimension->setWidth((float) $item['width']);

            empty($item['align']) || $sheet->getStyle($headerColumn)->getAlignment()->setHorizontal($item['align']);

            empty($item['headColor']) || $style->setColor(new Color(str_replace('#', '', $item['headColor'])));

            if (! empty($item['headBgColor'])) {
                $sheet->getStyle($headerColumn)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB(str_replace('#', '', $item['headBgColor']));
            }
            ++$titleStart;
        }

        $generate = $this->yieldExcelData($data);

        // 表体
        try {
            $row = 2;
            while ($generate->valid()) {
                $column = 0;
                $items = $generate->current();
                if ($callbackData instanceof \Closure) {
                    $items = $callbackData($items);
                }
                foreach ($items as $name => $value) {
                    $columnRow = $this->getColumnIndex($column) . $row;
                    $annotation = '';
                    foreach ($this->property as $item) {
                        if ($item['name'] == $name) {
                            $annotation = $item;
                            break;
                        }
                    }

                    if (! empty($annotation['dictName'])) {
                        $sheet->setCellValue($columnRow, $annotation['dictName'][$value]);
                    } elseif (! empty($annotation['path'])) {
                        $sheet->setCellValue($columnRow, Arr::get($items, $annotation['path']));
                    } elseif (! empty($annotation['dictData'])) {
                        $sheet->setCellValue($columnRow, $annotation['dictData'][$value]);
                    } elseif (! empty($this->dictData[$name])) {
                        $sheet->setCellValue($columnRow, $this->dictData[$name][$value] ?? '');
                    } else {
                        $sheet->setCellValue($columnRow, $value . "\t");
                    }

                    if (! empty($item['color'])) {
                        $sheet->getStyle($columnRow)->getFont()
                            ->setColor(new Color(str_replace('#', '', $annotation['color'])));
                    }

                    if (! empty($item['bgColor'])) {
                        $sheet->getStyle($columnRow)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB(str_replace('#', '', $annotation['bgColor']));
                    }
                    ++$column;
                }
                $generate->next();
                ++$row;
            }
        } catch (\RuntimeException $e) {
        }

        $writer = IOFactory::createWriter($spread, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $res = $this->downloadExcel($filename, ob_get_contents());
        ob_end_clean();
        $spread->disconnectWorksheets();

        return $res;
    }

    protected function yieldExcelData(array &$data): \Generator
    {
        foreach ($data as $dat) {
            $yield = [];
            foreach ($this->property as $item) {
                $yield[$item['name']] = $dat[$item['name']] ?? '';
            }
            yield $yield;
        }
    }

    private function getDataByIndex($sheet, $endCell): array
    {
        $data = [];
        foreach ($sheet->getActiveSheet()->getRowIterator(2) as $row) {
            $temp = [];
            foreach ($row->getCellIterator('A', $endCell) as $index => $item) {
                $propertyIndex = ord($index) - 65;
                if (isset($this->property[$propertyIndex])) {
                    $temp[$this->property[$propertyIndex]['name']] = $item->getFormattedValue();
                }
            }
            if (! empty($temp)) {
                $data[] = $temp;
            }
        }
        return $data;
    }

    private function getDataByText($sheet, $endCell): array
    {
        $data = [];
        // 获取展示名称到字段名的映射关系
        $fieldMap = [];
        foreach ($this->property as $item) {
            $fieldMap[trim($item['value'])] = $item['name'];
        }

        $headerMap = [];
        // 获取表头
        // 获取表头，假设表头在第一行
        $headerRow = $sheet->getActiveSheet()->getRowIterator(1, 1)->current();
        foreach ($headerRow->getCellIterator('A', $endCell) as $index => $item) {
            $propertyIndex = ord($index) - 65; // 获得列索引
            $value = trim($item->getFormattedValue());
            $headerMap[$propertyIndex] = $fieldMap[$value] ?? null; // 获取表头值
        }
        // 读取数据，从第二行开始
        foreach ($sheet->getActiveSheet()->getRowIterator(2) as $row) {
            $temp = [];
            foreach ($row->getCellIterator('A', $endCell) as $index => $item) {
                $propertyIndex = ord($index) - 65; // 获得列索引
                if (! empty($headerMap[$propertyIndex])) { // 确保列索引存在于表头数组中
                    $temp[$headerMap[$propertyIndex]] = trim($item->getFormattedValue()); // 映射表头标题到对应值
                }
            }
            $data[] = $temp;
        }
        return $data;
    }
}
