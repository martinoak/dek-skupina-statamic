<?php

namespace Statamic\Addons\DekDev\Commands;

use Statamic\API\File;
use Statamic\Extend\Command;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dekdev:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes the DekDev migration file.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        File::put(
            database_path().'/migrations/'.date('Y_m_d_His') . '_create_dek_dev_table.php',
            File::get(__DIR__.'/../resources/stubs/create_dek_dev_table.php.stub')
        );
    }
}
