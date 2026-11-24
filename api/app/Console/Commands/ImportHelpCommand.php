<?php

namespace App\Console\Commands;

use Domain\HelpCenter\Support\HelpLibrary;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ImportHelpCommand extends Command
{
    protected $signature = 'help:import
        {--path= : Folder that contains the help library}
        {--replace : Clear the help center when the library has no articles}';

    protected $description = 'Load the help center from the repository, including screenshots. Articles that are no longer in the library are removed.';

    public function handle(HelpLibrary $library): int
    {
        $path = $this->folder();

        try {
            $result = $library->import($path, (bool) $this->option('replace'));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Imported {$result['articles']} articles and {$result['images']} screenshots.");
        if ($result['removed'] > 0) {
            $this->line("Removed {$result['removed']} articles that are no longer in the library.");
        }

        return self::SUCCESS;
    }

    private function folder(): string
    {
        $path = $this->option('path');

        return is_string($path) && $path !== '' ? $path : database_path('content/help');
    }
}
