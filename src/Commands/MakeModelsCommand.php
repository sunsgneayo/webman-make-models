<?php

declare(strict_types=1);

namespace Sunsgne\WebmanMakeModels\Commands;


use Sunsgne\WebmanMakeModels\SyncResult;
use Sunsgne\WebmanMakeModels\MakeModels;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModelsCommand extends Command
{
    /** @var string  命令前缀 */
    protected static $defaultName = 'make:models';

    /** @var string 描述 */
    protected static $defaultDescription = '根据表名生成数据库模型';

    protected function configure()
    {
        $this->addArgument(
            'table_name',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'database.php 中配置的数据库'
        );
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tableName = $input->getArgument('table_name')[0] ?? '';
        if (empty($tableName)){
            $output->writeln('table_name 不能为空');
        }

        $analyser = new MakeModels();
        $analyser->onProcess(function (SyncResult $result) use ($output): void {
            $output->write($result->class . ' ');
            if ($result->exception) {
                $output->writeln($result->exception->getMessage());
            } else {
                $output->writeln('同步成功');
            }
        });
        $analyser->run($tableName);

        return self::SUCCESS;
    }
}
