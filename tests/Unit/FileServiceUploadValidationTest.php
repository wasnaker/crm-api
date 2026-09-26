<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spine\Services\FileService;
use Tests\TestCase;

class FileServiceUploadValidationTest extends TestCase
{
    protected FileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FileService();
    }

    public function test_it_allows_an_extension_on_the_allow_list(): void
    {
        $file = UploadedFile::fake()->create('invoice.pdf', 12, 'application/pdf');

        $this->service->assertUploadAllowed($file);

        $this->assertTrue(true, 'no exception thrown');
    }

    #[DataProvider('allowedProvider')]
    public function test_it_allows_documented_types(string $filename): void
    {
        $this->service->assertUploadAllowed(UploadedFile::fake()->create($filename));

        $this->assertTrue(true);
    }

    public static function allowedProvider(): array
    {
        return [
            'pdf' => ['contract.pdf'],
            'xlsx' => ['report.xlsx'],
            'csv' => ['export.csv'],
            'png' => ['screenshot.png'],
            'zip' => ['bundle.zip'],
            'uppercase is normalised' => ['CONTRACT.PDF'],
            'multiple suffixes' => ['notes.backup.pdf'],
        ];
    }

    #[DataProvider('blockedProvider')]
    public function test_it_refuses_a_blocked_extension(string $filename, string $expectedInMessage): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->service->assertUploadAllowed(UploadedFile::fake()->create($filename));
        } catch (ValidationException $e) {
            $this->assertStringContainsString($expectedInMessage, $e->errors()['file'][0]);

            throw $e;
        }
    }

    public static function blockedProvider(): array
    {
        return [
            'php' => ['shell.php', 'php'],
            'phtml' => ['shell.phtml', 'phtml'],
            'phar' => ['archive.phar', 'phar'],
            'double extension' => ['invoice.php.pdf', 'php'],
            'leading dot' => ['.htaccess', 'htaccess'],
            'server config' => ['site.ini', 'ini'],
            'windows binary' => ['tool.exe', 'exe'],
            'native binary' => ['lib.so', 'so'],
            'archive script' => ['run.sh', 'sh'],
        ];
    }

    public function test_it_refuses_an_extension_that_is_not_on_the_allow_list(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/are not allowed/');

        $this->service->assertUploadAllowed(UploadedFile::fake()->create('clip.mov'));
    }

    public function test_it_refuses_a_file_without_an_extension(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/must have a file extension/');

        $this->service->assertUploadAllowed(UploadedFile::fake()->create('README'));
    }

    public function test_blocked_list_wins_over_the_allow_list(): void
    {
        config()->set('spine.files.allowed_extensions', ['php', 'pdf']);

        $this->expectException(ValidationException::class);

        $this->service->assertUploadAllowed(UploadedFile::fake()->create('shell.php'));
    }

    public function test_validation_runs_before_the_file_uploading_event(): void
    {
        config()->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => storage_path('app'),
        ]);

        $dispatched = false;
        \Illuminate\Support\Facades\Event::listen(\Spine\Events\FileUploading::class, function () use (&$dispatched) {
            $dispatched = true;
        });

        try {
            $this->service->storeUpload(
                UploadedFile::fake()->create('shell.php'),
                'invoice',
                1
            );
            $this->fail('storeUpload should have refused a .php upload');
        } catch (ValidationException) {
            $this->assertFalse($dispatched, 'FileUploading must not be dispatched for a refused upload');
        }
    }

    public function test_store_upload_writes_an_allowed_file(): void
    {
        Storage::fake('local');

        $path = $this->service->storeUpload(
            UploadedFile::fake()->create('invoice.pdf', 8, 'application/pdf'),
            'invoice',
            42,
            7,
            'local'
        );

        Storage::disk('local')->assertExists($path);

        $this->assertStringStartsWith('tenants/7/invoice/42/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function test_allowed_and_blocked_extensions_are_lowercased_and_deduplicated(): void
    {
        config()->set('spine.files.allowed_extensions', ['PDF', '.pdf', 'Zip', 123, 'xlsx']);
        config()->set('spine.files.blocked_extensions', ['.PHP', 'php', 'PhP']);

        $this->assertSame(['pdf', 'zip', 'xlsx'], $this->service->allowedExtensions());
        $this->assertSame(['php'], $this->service->blockedExtensions());
    }

    public function test_extension_segments_reads_every_suffix(): void
    {
        $this->assertSame(['php', 'pdf'], $this->service->extensionSegments('invoice.php.pdf'));
        $this->assertSame(['htaccess'], $this->service->extensionSegments('.htaccess'));
        $this->assertSame([], $this->service->extensionSegments('README'));
        $this->assertSame(['pdf'], $this->service->extensionSegments('/tmp/dir/Report.PDF'));
    }

    public function test_error_message_does_not_reflect_a_verbatim_client_name(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->service->assertUploadAllowed(
                UploadedFile::fake()->create("we'ird\"na<me>.ph<p>")
            );
            $this->fail('a client-supplied suffix must not be echoed back verbatim');
        } catch (ValidationException $e) {
            $message = $e->errors()['file'][0];

            // The quoted extension comes from our own template, so only the
            // client-controlled characters are asserted absent.
            foreach (["<", ">", "'", "\\"] as $hostile) {
                $this->assertStringNotContainsString($hostile, $message);
            }

            $this->assertStringNotContainsString('na<me', $message);
            $this->assertMatchesRegularExpression('/^Files of type "[a-z0-9]*" are not allowed\.$/', $message);

            throw $e;
        }
    }
}
