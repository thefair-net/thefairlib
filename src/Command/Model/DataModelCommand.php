<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Command\Model;

use TheFairLib\Command\Model\Ast\ModelRewriteConnectionVisitor;
use TheFairLib\Command\Model\Ast\ModelRewriteKeyTypeVisitor;
use TheFairLib\Command\Model\Ast\ModelRewriteShardingNumVisitor;
use TheFairLib\Command\Model\Ast\ModelUpdateVisitor;
use Hyperf\Command\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\Commands\ModelData;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Schema\MySqlBuilder;
use Hyperf\Utils\CodeGen\Project;
use Hyperf\Utils\Str;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @\Hyperf\Command\Annotation\Command
 * Class DataModelCommand
 * @package TheFairLib\Commands
 */
class DataModelCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Parser
     */
    protected $astParser;

    /**
     * @var PrettyPrinterAbstract
     */
    protected $printer;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
    protected $input;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('gen:dataModel');
        $this->container = $container;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->resolver = $this->container->get(ConnectionResolverInterface::class);
        $this->config = $this->container->get(ConfigInterface::class);
        $this->astParser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard();

        return parent::run($input, $output);
    }

    public function handle()
    {
        $table = $this->input->getArgument('table');
        $pool = $this->input->getOption('pool');
        $shardingNum = (int)$this->output->ask("sharding number", '0');
        $primaryKey = $this->output->ask("primary key", 'id');
        if ($pool == 'default') {
            $poolName = $this->config->get('databases');
            $pool = $this->output->choice('pool name', array_keys($poolName));
        }
        $option = new ModelOption();
        $option->setPool($pool)
            ->setPath($this->getOption('path', 'commands.gen:model.path', $pool, 'app/Model'))
            ->setPrefix($this->getOption('prefix', 'prefix', $pool, ''))
            ->setInheritance($this->getOption('inheritance', 'commands.gen:model.inheritance', $pool, 'DataModel'))
            ->setUses($this->getOption('uses', 'commands.gen:model.uses', $pool, 'TheFairLib\Model\DataModel'))
            ->setForceCasts($this->getOption('force-casts', 'commands.gen:model.force_casts', $pool, false))
            ->setRefreshFillable($this->getOption('refresh-fillable', 'commands.gen:model.refresh_fillable', $pool, false))
            ->setTableMapping($this->getOption('table-mapping', 'commands.gen:model.table_mapping', $pool, []))
            ->setIgnoreTables($this->getOption('ignore-tables', 'commands.gen:model.ignore_tables', $pool, []))
            ->setWithComments($this->getOption('with-comments', 'commands.gen:model.with_comments', $pool, false))
            ->setVisitors($this->getOption('visitors', 'commands.gen:model.visitors', $pool, []))
            ->setPropertyCase($this->getOption('property-case', 'commands.gen:model.property_case', $pool))
            ->setShardingNum($shardingNum ?? 0)
            ->setKeyName($primaryKey ?? 'id');

        if ($table) {
            $this->createModel($table, $option);
        } else {
            $this->createModels($option);
        }
    }

    protected function configure()
    {
        $this->addArgument('table', InputArgument::OPTIONAL, 'Which table you want to associated with the Model.');
        $this->addOption('pool', 'p', InputOption::VALUE_OPTIONAL, 'Which connection pool you want the Model use.', 'default');
        $this->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path that you want the Model file to be generated.');
        $this->addOption('force-casts', 'F', InputOption::VALUE_NONE, 'Whether force generate the casts for model.');
        $this->addOption('prefix', 'P', InputOption::VALUE_OPTIONAL, 'What prefix that you want the Model set.');
        $this->addOption('inheritance', 'i', InputOption::VALUE_OPTIONAL, 'The inheritance that you want the Model extends.');
        $this->addOption('uses', 'U', InputOption::VALUE_OPTIONAL, 'The default class uses of the Model.');
        $this->addOption('refresh-fillable', 'R', InputOption::VALUE_NONE, 'Whether generate fillable argement for model.');
        $this->addOption('table-mapping', 'M', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Table mappings for model.');
        $this->addOption('ignore-tables', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Ignore tables for creating models.');
        $this->addOption('with-comments', null, InputOption::VALUE_NONE, 'Whether generate the property comments for model.');
        $this->addOption('visitors', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Custom visitors for ast traverser.');
        $this->addOption('property-case', null, InputOption::VALUE_OPTIONAL, 'Which property case you want use, 0: snake case, 1: camel case.');
//        $this->addOption('sharding-num', null, InputOption::VALUE_OPTIONAL, ' table sharding num : user_info_10');
//        $this->addOption('primary-key', 'PK', InputOption::VALUE_OPTIONAL, ' primary key for the model', 'id');
    }

    protected function getSchemaBuilder(string $poolName): MySqlBuilder
    {
        $connection = $this->resolver->connection($poolName);
        return $connection->getSchemaBuilder();
    }

    protected function createModels(ModelOption $option)
    {
        $builder = $this->getSchemaBuilder($option->getPool());
        $tables = [];

        foreach ($builder->getAllTables() as $row) {
            $row = (array)$row;
            $table = reset($row);
            if (!$this->isIgnoreTable($table, $option)) {
                $tables[] = $table;
            }
        }

        foreach ($tables as $table) {
            $this->createModel($table, $option);
        }
    }

    protected function isIgnoreTable(string $table, ModelOption $option): bool
    {
        if (in_array($table, $option->getIgnoreTables())) {
            return true;
        }

        return $table === $this->config->get('databases.migrations', 'migrations');
    }

    protected function createModel(string $table, ModelOption $option)
    {
        $builder = $this->getSchemaBuilder($option->getPool());
        $tmpTable = $table = Str::replaceFirst($option->getPrefix(), '', $table);
        if ($option->getShardingNum() > 0) {
            $tmpTable = sprintf("%s_0", $table);
        }
        $columns = $this->formatColumns($builder->getColumnTypeListing($tmpTable));
        $this->checkPrimaryKey($columns, $option);
        $project = new Project();
        $class = $option->getTableMapping()[$table] ?? Str::studly(Str::singular($table));
        $class = $project->namespace($option->getPath()) . $class;
        $path = BASE_PATH . '/' . $project->path($class);

        if (!file_exists($path)) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            file_put_contents($path, $this->buildClass($table, $class, $option));
        }
        $columns = $this->getColumns($class, $columns, $option->isForceCasts());
        $stms = $this->astParser->parse(file_get_contents($path));
        $traverser = new NodeTraverser();
        $traverser->addVisitor(make(ModelUpdateVisitor::class, [
            'class' => $class,
            'columns' => $columns,
            'option' => $option,
        ]));
        $traverser->addVisitor(make(ModelRewriteConnectionVisitor::class, [$class, $option->getPool()]));
        $traverser->addVisitor(make(ModelRewriteShardingNumVisitor::class, [$class, $option->getShardingNum()]));
        $traverser->addVisitor(make(ModelRewriteKeyTypeVisitor::class, [$class, $option->getKeyType()]));
        foreach ($option->getVisitors() as $visitorClass) {
            $data = make(ModelData::class)->setClass($class)->setColumns($columns);
            $traverser->addVisitor(make($visitorClass, [$option, $data]));
        }
        $stms = $traverser->traverse($stms);
        $code = $this->printer->prettyPrintFile($stms);
        file_put_contents($path, $code);
        $this->output->writeln(sprintf('<info>Model %s was created.</info>', $class));
    }

    protected function checkPrimaryKey($columns, ModelOption &$option)
    {
        $status = false;
        foreach ($columns as $column) {
            if ($column['column_name'] == $option->getKeyName()) {
                $status = true;
                if (in_array($column['data_type'], ['varchar', 'char'])) {
                    $option->setKeyType('string');
                } else {
                    $option->setKeyType('int');
                }
            }
        }
        if (!$status) {
            $this->output->writeln("没有找到主键");
            exit;
        }
    }

    /**
     * Format column's key to lower case.
     * @param array $columns
     * @return array
     */
    protected function formatColumns(array $columns): array
    {
        return array_map(function ($item) {
            return array_change_key_case($item, CASE_LOWER);
        }, $columns);
    }

    protected function getColumns($className, $columns, $forceCasts): array
    {
        /** @var Model $model */
        $model = new $className();
        $dates = $model->getDates();
        $casts = [];
        if (!$forceCasts) {
            $casts = $model->getCasts();
        }

        foreach ($dates as $date) {
            if (!isset($casts[$date])) {
                $casts[$date] = 'datetime';
            }
        }

        foreach ($columns as $key => $value) {
            $columns[$key]['cast'] = $casts[$value['column_name']] ?? null;
        }

        return $columns;
    }

    protected function getOption(string $name, string $key, string $pool = 'default', $default = null)
    {
        $result = $this->input->getOption($name);
        $nonInput = null;
        if (in_array($name, ['force-casts', 'refresh-fillable', 'with-comments'])) {
            $nonInput = false;
        }
        if (in_array($name, ['table-mapping', 'ignore-tables', 'visitors'])) {
            $nonInput = [];
        }

        if ($result === $nonInput) {
            $result = $this->config->get("databases.{$pool}.{$key}", $default);
        }

        return $result;
    }

    /**
     * Build the class with the given name.
     * @param string $table
     * @param string $name
     * @param ModelOption $option
     * @return string
     */
    protected function buildClass(string $table, string $name, ModelOption $option): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Model.stub');
        return $this->replaceNamespace($stub, $name)
            ->replaceInheritance($stub, $option->getInheritance())
            ->replaceConnection($stub, $option->getPool())
            ->replaceUses($stub, $option->getUses())
            ->replaceClass($stub, $name)
            ->replaceShardingNum($stub, $option->getShardingNum())
            ->replacePrimaryKey($stub, $option->getKeyName())
            ->replaceKeyType($stub, $option->getKeyType())
            ->replaceTable($stub, $table);
    }

    /**
     * Replace the namespace for the given stub.
     * @param string $stub
     * @param string $name
     * @return DataModelCommand
     */
    protected function replaceNamespace(string &$stub, string $name): self
    {
        $stub = str_replace(
            ['%NAMESPACE%'],
            [$this->getNamespace($name)],
            $stub
        );

        return $this;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     * @param string $name
     * @return string
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    protected function replaceInheritance(string &$stub, string $inheritance): self
    {
        $stub = str_replace(
            ['%INHERITANCE%'],
            [$inheritance],
            $stub
        );

        return $this;
    }

    protected function replaceConnection(string &$stub, string $connection): self
    {
        $stub = str_replace(
            ['%CONNECTION%'],
            [$connection],
            $stub
        );

        return $this;
    }

    protected function replaceShardingNum(string &$stub, int $shardingNum): self
    {
        $stub = str_replace(
            ['%SHARDING_NUM%'],
            [$shardingNum],
            $stub
        );

        return $this;
    }

    protected function replacePrimaryKey(string &$stub, string $primaryKey): self
    {
        $stub = str_replace(
            ['%PRIMARY_KEY%'],
            [$primaryKey],
            $stub
        );

        return $this;
    }

    protected function replaceKeyType(string &$stub, string $keyType): self
    {
        $stub = str_replace(
            ['%KEY_TYPE%'],
            [$keyType],
            $stub
        );

        return $this;
    }

    protected function replaceUses(string &$stub, string $uses): self
    {
        $uses = $uses ? "use {$uses};" : '';
        $stub = str_replace(
            ['%USES%'],
            [$uses],
            $stub
        );

        return $this;
    }

    /**
     * Replace the class name for the given stub.
     * @param string $stub
     * @param string $name
     * @return DataModelCommand
     */
    protected function replaceClass(string &$stub, string $name): self
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $stub = str_replace('%CLASS%', $class, $stub);

        return $this;
    }

    /**
     * Replace the table name for the given stub.
     * @param string $stub
     * @param string $table
     * @return string
     */
    protected function replaceTable(string $stub, string $table): string
    {
        return str_replace('%TABLE%', $table, $stub);
    }

    /**
     * Get the destination class path.
     * @param string $name
     * @return string
     */
    protected function getPath(string $name): string
    {
        return BASE_PATH . '/' . str_replace('\\', '/', $name) . '.php';
    }
}
