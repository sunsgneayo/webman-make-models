<?php

declare(strict_types=1);
namespace Sunsgne\WebmanMakeModels;


use ReflectionClass;
use ReflectionException;
use support\Db;
use support\Model;
use Throwable;

/**
 * 模型分析器
 */
class MakeModels
{
    protected array $_callbacks = [];

    /**
     * 添加一个回调，当处理完成一个类时触发此回调
     * @param callable $callback
     * @return bool
     */
    public function onProcess(callable $callback): bool
    {
        if (!is_callable($callback)) {
            return false;
        }
        $this->_callbacks[] = $callback;
        return true;
    }

    /**
     * @var string[] 已经解析过的文件列表
     */
    protected array $_processedFiles = [];

    /**
     * @var string[] 已经解析过的类列表
     */
    protected array $_processedClasses = [];

    /** @var string 默认的 */
    protected string $_defaultNamespace = 'app\model';

    /**
     * @param string $tableName
     * @return void
     */
    public function run(string $tableName): void
    {
        $this->_processedFiles = [];
        $dir = $this->snake2camel($tableName, true);
        $file = app_path() . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . $dir . '.php';
        if (is_file($file)) {
            /** 文件存在，去获取表结构信息并写入 */
            $this->processFile(realpath($file));
            //当前文件存在
        } else {
            /** 文件不存在，去生成 */
            $this->makeFile($file, $tableName, $dir);
        }
    }

    /**
     * 扫描目录，处理目录中的文件
     * @param string $dir 目录路径
     * @return void
     */
    protected function scanDir(string $dir): void
    {
        $entries = scandir($dir);
        foreach ($entries as $entry) {
            if (str_starts_with($entry, '.')) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->scanDir($path);
            } elseif (is_file($path)) {
                $this->processFile(realpath($path));
            }
        }
    }

    /**
     * 蛇形转大驼峰
     * @param string $string
     * @param bool $ucFirst
     * @return string
     * @Time 2023/6/14 17:12
     * @author sunsgne
     */
    protected function snake2camel(string $string, bool $ucFirst = false): string
    {
        $camel = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $string);
        return $ucFirst ? ucfirst($camel) : $camel;
    }

    /**
     * @param string $file 文件路径
     * @param string $tableName 表名
     * @param string $fileName 文件名
     * @return void
     * @Time 2023/6/14 13:17
     * @author sunsgne
     */
    protected function makeFile(string $file, string $tableName, string $fileName): void
    {
        $info             = new TableInfo();
        $info->table      = $tableName;
        $info->connection = '';
        $info->config     = Db::connection($info->connection)->getConfig();
        $info->readTableComment();

        $info->readColumns();
        $commentLines = '/**' . PHP_EOL;
        if (!empty($info->comment)) {
            $commentLines .= " * {$info->table} {$info->comment}" . PHP_EOL;
        }
        foreach ($info->columns as $column) {
            $commentLines .= " * @property {$column->type} \${$column->name} " . ($column->comment ?? '') . PHP_EOL;
        }
        $commentLines .= ' */' . PHP_EOL;
        $table = '$table';
        $controller_content = <<<EOF
<?php

namespace $this->_defaultNamespace;

use support\Model;
$commentLines
class $fileName extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '$tableName';
}

EOF;
        file_put_contents($file, $controller_content);
        $this->report($file, $this->_defaultNamespace.DIRECTORY_SEPARATOR.$fileName);
    }

    /**
     * 处理文件
     * @param string $file 文件名
     * @return void
     */
    protected function processFile(string $file): void
    {
        if (in_array($file, $this->_processedFiles)) {
            return;
        }
        $this->_processedFiles[] = $file;

        $context = new FileContext($file);

        //将文件中的类倒序解析
        $classes = array_reverse($context->analysis->classes);
        foreach ($classes as $class) {
            if (in_array($class['name'], $this->_processedClasses)) {
                continue;
            }
            $this->_processedClasses[] = $class['name'];
            try {
                $tableInfo = $this->getTableInfoFromClass($class['name']);
            } catch (ReflectionException) {
                continue;
            } catch (MakeException $e) {
                $this->report($file, $class['name'], $e);
                continue;
            }

            if (!$tableInfo) {
                continue;
            }

            try {
                $tableInfo->readTableComment();
            } catch (MakeException $e) {
                $this->report($file, $class['name'], $e);
                continue;
            }
            $tableInfo->readColumns();

            //将列信息写入文件
            $commentLines = ['/**'];
            if (!empty($tableInfo->comment)) {
                $commentLines[] = " * {$tableInfo->table} {$tableInfo->comment}";
            }
            foreach ($tableInfo->columns as $column) {
                $commentLines[] = " * @property {$column->type} \${$column->name} " . ($column->comment ?? '');
            }
            $commentLines[] = ' */';
            if (isset($class['docComment'])) {
                //模型原本有注释
                array_splice(
                    $context->lines,
                    $class['docComment']['start'] - 1,
                    $class['docComment']['end'] - $class['docComment']['start'],
                    $commentLines
                );
            } else {
                //模型没有注释
                array_splice(
                    $context->lines,
                    $class['start'] - 1,
                    0,
                    $commentLines
                );
            }
            $context->content = implode("\r\n", $context->lines);
            $context->updated = true;
            $this->report($file, $class['name']);
        }

        if ($context->updated) {
            //回写文件内容
            file_put_contents($file, $context->content);
        }
    }

    /**
     * 从类中解析数据表信息
     * @param string $class 模型类名
     * @return TableInfo|null
     * @throws ReflectionException
     * @throws MakeException
     */
    protected function getTableInfoFromClass(string $class): TableInfo|null
    {
        $ref = new ReflectionClass($class);
        if ($ref->isAbstract() || !$ref->isSubclassOf(Model::class)) {
            return null;
        }

        $info             = new TableInfo();
        $instance         = $ref->newInstance();
        $info->table      = $ref->getProperty('table')->getValue($instance);
        $info->connection = $ref->getProperty('connection')->getValue($instance);
        $info->config     = Db::connection($info->connection)->getConfig();

        //只支持mysql和pgsql
        if (!in_array($info->config['driver'], ['mysql', 'pgsql'])) {
            throw new MakeException('不支持的数据库类型');
        }
        return $info;
    }

    /**
     * 报告模型同步结果
     * @param string $file 文件路径
     * @param string $class 类名
     * @param Throwable|null $exception 异常
     * @return void
     */
    protected function report(string $file, string $class, ?Throwable $exception = null): void
    {
        if (empty($this->_callbacks)) {
            return;
        }
        $result = new SyncResult($file, $class, $exception);
        foreach ($this->_callbacks as $callback) {
            try {
                $callback($result);
            } catch (Throwable) {

            }
        }
    }
}
