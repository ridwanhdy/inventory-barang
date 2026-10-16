<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand;
use Symfony\Component\Process\Process;
use Throwable;

class ServeFastCommand extends ServeCommand
{
    protected $name = 'serve:fast';

    protected $description = 'Serve the application with OPcache enabled for this development server';

    protected ?array $serverOpcacheOptions = null;

    protected function serverCommand()
    {
        $command = parent::serverCommand();

        if ($this->serverOpcacheOptions === null) {
            $this->serverOpcacheOptions = $this->opcacheOptions($command[0]);

            if ($this->serverOpcacheOptions === []) {
                $this->components->warn('OPcache is unavailable. Using the standard development server.');
            }
        }

        array_splice($command, 1, 0, $this->serverOpcacheOptions);

        return $command;
    }

    protected function opcacheOptions(string $phpBinary): array
    {
        $options = [];

        if (! extension_loaded('Zend OPcache')) {
            $extension = $this->findOpcacheExtension(
                (string) ini_get('extension_dir'),
                $phpBinary,
                php_ini_loaded_file(),
            );

            if ($extension === null) {
                return [];
            }

            $options = ['-d', 'zend_extension='.$extension];
        }

        // Keep source changes visible on every request while caching compiled PHP.
        array_push($options,
            '-d', 'opcache.enable=1',
            '-d', 'opcache.enable_cli=1',
            '-d', 'opcache.validate_timestamps=1',
            '-d', 'opcache.revalidate_freq=0',
        );

        // An installed library can still be incompatible with this PHP version.
        return $this->canUseOpcache($phpBinary, $options) ? $options : [];
    }

    protected function findOpcacheExtension(string $extensionDirectory, string $phpBinary, string|false $iniFile): ?string
    {
        if ($extensionDirectory === '') {
            return null;
        }

        $directories = [$extensionDirectory];

        if (! preg_match('~^(?:[A-Za-z]:[\\\\/]|[\\\\/])~', $extensionDirectory)) {
            // Windows PHP distributions commonly configure extension_dir = "ext".
            $directories = [dirname($phpBinary).DIRECTORY_SEPARATOR.$extensionDirectory];

            if ($iniFile !== false) {
                $directories[] = dirname($iniFile).DIRECTORY_SEPARATOR.$extensionDirectory;
            }

            $directories[] = $extensionDirectory;
        }

        $filename = PHP_OS_FAMILY === 'Windows' ? 'php_opcache.dll' : 'opcache.so';

        foreach (array_unique($directories) as $directory) {
            $extension = realpath(rtrim($directory, '/\\').DIRECTORY_SEPARATOR.$filename);

            if ($extension !== false && is_file($extension)) {
                // Laravel starts its child server from public/, so use an absolute path.
                return $extension;
            }
        }

        return null;
    }

    protected function canUseOpcache(string $phpBinary, array $options): bool
    {
        try {
            $process = new Process([
                $phpBinary,
                ...$options,
                '-r',
                'exit(function_exists("opcache_get_status") && opcache_get_status(false) !== false ? 0 : 1);',
            ], public_path(), timeout: 5);

            return $process->run() === 0;
        } catch (Throwable) {
            return false;
        }
    }
}
