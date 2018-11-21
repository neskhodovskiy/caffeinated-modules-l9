<?php

namespace Caffeinated\Modules\Console\Commands;

use Caffeinated\Modules\ModuleRepositoriesManager;
use Caffeinated\Modules\Traits\MigrationTrait;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ModuleMigrateRollbackCommand extends Command
{
    use MigrationTrait, ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate:rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migrations for a specific or all modules';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * @var ModuleRepositoriesManager
     */
    protected $module;

    /**
     * Create a new command instance.
     *
     * @param Migrator $migrator
     * @param ModuleRepositoriesManager  $module
     */
    public function __construct(Migrator $migrator, ModuleRepositoriesManager $module)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->module = $module;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->migrator->setConnection($this->option('database'));

        $paths = $this->getMigrationPaths();
        $this->migrator->setOutput($this->output)->rollback(
            $paths, ['pretend' => $this->option('pretend'), 'step' => (int) $this->option('step')]
        );
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [['slug', InputArgument::OPTIONAL, 'Module slug.']];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted.'],
            ['location', null, InputOption::VALUE_OPTIONAL, 'Which modules location to use.'],
        ];
    }

    /**
     * Get all of the migration paths.
     *
     * @return array
     */
    protected function getMigrationPaths()
    {
        $slug = $this->argument('slug');
        $paths = [];

        if ($slug) {
            $paths[] = module_path($slug, 'Database/Migrations');
        } else {
            foreach (modules($this->option('location'))->all() as $module) {
                $paths[] = module_path($module['slug'], 'Database/Migrations');
            }
        }

        return $paths;
    }
}
