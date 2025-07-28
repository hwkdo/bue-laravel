<?php

namespace Hwkdo\BueLaravel\Commands;

use Illuminate\Console\Command;

class BueLaravelCommand extends Command
{
    public $signature = 'bue-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
