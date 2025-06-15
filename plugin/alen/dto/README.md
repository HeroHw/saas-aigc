## 输入导入导出插件

MineAdmin 3.0 数据导入导出插件,方便后端导入导出数据

## 下载插件

- 后台应用市场下载插件
- 命令安装，在后端根目录下执行命令：

```bash
php bin/hyperf.php mine-extension:download alen/dto
```
## 安装插件

- 后台应用商店安装插件
- 命令安装，在后端根目录下执行命令：

```bash
php bin/hyperf.php mine-extension:install alen/dto --yes
```

## 使用方法

### 定义导入导出数据模型
在app目录下创建一个Dto文件夹,然后定义一个数据模型继承MineModelExcel

#### 示例
```php
/**
* TestDto
*
* 导出数据结构
* @author Alen
* @date 2025/3/4 14:26
  */
#[ExcelData]
class TestDto implements MineModelExcel
{
    // trade_date
    #[ExcelProperty(value: '交易时间', index: 1)]
    public string $trade_date;
    // country
    #[ExcelProperty(value: '国家', index: 2)]
    public string $country;

}
```
### 后端
后端提供了一个静态类,可以方便实现数据导入导出操作,在controller中调用即可
```php
return Dto::instance()->export(TestDto::class,'测试数据导出',$data,function ($data) use ($params){
            return $data;
        });
```

```
