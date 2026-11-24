<?php

namespace App\Console\Commands;

use Domain\HelpCenter\Support\HelpLibrary;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ExportHelpCommand extends Command
{
    protected $signature = 'help:export {--path= : Folder that receives the help library}';

    protected $description = 'Write the help center, including screenshots, into the repository so it can be loaded again after a wipe.';

    public function handle(HelpLibrary $library): int
    {
        $path = $this->folder();

        try {
            $result = $library->export($path);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Exported {$result['articles']} articles and {$result['images']} screenshots to {$this->display($path)}.");
        $this->line('Commit this folder, then run php artisan help:import after the next wipe.');

        return self::SUCCESS;
    }

    private function folder(): string
    {
        $path = $this->option('path');

        return is_string($path) && $path !== '' ? $path : database_path('content/help');
    }

    private function display(string $path): string
    {
        $root = rtrim(base_path(), '/').'/';

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
