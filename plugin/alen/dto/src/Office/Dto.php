<?php
declare(strict_types=1);
/**
 * Dto
 *
 * @author Alen
 * @date 2025/3/4 11:18
 */

namespace Plugin\Alen\Dto\Office;
use Exception;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Model\Model;
use Plugin\Alen\Dto\Office\Excel\PhpOffice;
use Plugin\Alen\Dto\Office\Excel\XlsWriter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Dto
 * 数据导入导出
 * @author Alen
 * @date 2025/3/4 11:20
 */
class Dto
{
    /**
     * @return mixed|Dto
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @author Alen
     * @date 2025/3/4 11:20
     */
    public static function instance(){
        return ApplicationContext::getContainer()->get(self::class);
    }

    /**
     * 导出数据.
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function export(string $dto, string $filename, null|array|\Closure $closure = null, ?\Closure $callbackData = null): ResponseInterface
    {
        $excelDrive = config('dto.excel_drive');
        if ($excelDrive === 'auto') {
            $excel = extension_loaded('xlswriter') ? new XlsWriter($dto) : new PhpOffice($dto);
        } else {
            $excel = $excelDrive === 'xlsWriter' ? new XlsWriter($dto) : new PhpOffice($dto);
        }
        return $excel->export($filename, $closure, $callbackData);
    }

    /**
     * 数据导入.
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function import(string $dto, Model $model, ?\Closure $closure = null): mixed
    {
        $excelDrive = config('dto.excel_drive');
        if ($excelDrive === 'auto') {
            $excel = extension_loaded('xlswriter') ? new XlsWriter($dto) : new PhpOffice($dto);
        } else {
            $excel = $excelDrive === 'xlsWriter' ? new XlsWriter($dto) : new PhpOffice($dto);
        }
        return $excel->import($model, $closure);
    }

}