<?php

use App\Console\Commands\ServeFastCommand;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

uses(Tests\TestCase::class);

class PortableServeFastCommand extends ServeFastCommand
{
    public ?array $testOptions = null;

    public int $optionChecks = 0;

    public BufferedOutput $buffer;

    public function prepare(string $host = '127.0.0.1', int $port = 8765): void
    {
        $this->input = new ArrayInput(['--host' => $host, '--port' => $port], $this->getDefinition());
        $this->buffer = new BufferedOutput;
        $this->output = new OutputStyle($this->input, $this->buffer);
        $this->components = new Factory($this->output);
    }

    public function command(): array
    {
        return $this->serverCommand();
    }

    public function nextPort(): void
    {
        $this->portOffset++;
    }

    public function extensionPath(string $directory, string $binary, string|false $iniFile = false): ?string
    {
        return $this->findOpcacheExtension($directory, $binary, $iniFile);
    }

    public function probe(array $options): bool
    {
        return $this->canUseOpcache(PHP_BINARY, $options);
    }

    protected function opcacheOptions(string $phpBinary): array
    {
        $this->optionChecks++;

        return $this->testOptions ?? parent::opcacheOptions($phpBinary);
    }
}

beforeEach(function () {
    $this->extensionFixture = sys_get_temp_dir().DIRECTORY_SEPARATOR.'inventory opcache '.bin2hex(random_bytes(6));
    mkdir($this->extensionFixture.DIRECTORY_SEPARATOR.'ext', 0777, true);
    $this->extensionFilename = PHP_OS_FAMILY === 'Windows' ? 'php_opcache.dll' : 'opcache.so';
    $this->extensionFile = $this->extensionFixture.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$this->extensionFilename;
    file_put_contents($this->extensionFile, 'An incompatible extension fixture.');
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->extensionFixture);
});

test('standard server fallback keeps Laravel host port and router and warns only once', function () {
    $command = new PortableServeFastCommand;
    $command->testOptions = [];
    $command->prepare('[::1]', 8765);

    $first = $command->command();
    $command->nextPort();
    $second = $command->command();

    expect($first[1])->toBe('-S')
        ->and($first[2])->toBe('[::1]:8765')
        ->and($second[2])->toBe('[::1]:8766')
        ->and($first[3])->toBeFile()
        ->and($second[3])->toBe($first[3])
        ->and($command->optionChecks)->toBe(1)
        ->and(substr_count($command->buffer->fetch(), 'OPcache is unavailable'))->toBe(1);
});

test('fast server inserts PHP settings while preserving the parent server arguments', function () {
    $command = new PortableServeFastCommand;
    $command->testOptions = ['-d', 'opcache.enable_cli=1', '-d', 'opcache.revalidate_freq=0'];
    $command->prepare('0.0.0.0', 8765);

    $arguments = $command->command();

    expect(array_slice($arguments, 1, 4))->toBe($command->testOptions)
        ->and(array_slice($arguments, 5, 2))->toBe(['-S', '0.0.0.0:8765'])
        ->and($arguments[7])->toBeFile()
        ->and($command->buffer->fetch())->toBe('');
});

test('relative extension directories resolve beside the PHP binary even with spaces', function () {
    $command = new PortableServeFastCommand;

    expect($command->extensionPath('ext', $this->extensionFixture.DIRECTORY_SEPARATOR.'php'))
        ->toBe(realpath($this->extensionFile));
});

test('relative extension directories also resolve beside a custom php ini', function () {
    $command = new PortableServeFastCommand;

    expect($command->extensionPath(
        'ext',
        $this->extensionFixture.DIRECTORY_SEPARATOR.'other'.DIRECTORY_SEPARATOR.'php',
        $this->extensionFixture.DIRECTORY_SEPARATOR.'php.ini',
    ))->toBe(realpath($this->extensionFile));
});

test('absolute and missing extension directories are handled without machine paths', function () {
    $command = new PortableServeFastCommand;

    expect($command->extensionPath($this->extensionFixture.DIRECTORY_SEPARATOR.'ext', PHP_BINARY))
        ->toBe(realpath($this->extensionFile))
        ->and($command->extensionPath($this->extensionFixture.DIRECTORY_SEPARATOR.'missing', PHP_BINARY))->toBeNull()
        ->and($command->extensionPath('', PHP_BINARY))->toBeNull();
});

test('OPcache probe rejects an installed but incompatible extension without stopping artisan', function () {
    $command = new PortableServeFastCommand;

    expect($command->probe([
        '-n', '-d', 'zend_extension='.$this->extensionFile,
        '-d', 'opcache.enable_cli=1',
    ]))->toBeFalse();
});

test('OPcache probe rejects a disabled cache so the server can fall back safely', function () {
    $command = new PortableServeFastCommand;

    expect($command->probe(['-d', 'opcache.enable_cli=0']))->toBeFalse();
});
