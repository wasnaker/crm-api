<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spine\Services\FileService;
use Tests\TestCase;

/**
 * The extension allow-list only inspects the name the client chose. These
 * tests cover the content-type cross-check that closes the gap for images.
 */
class FileServiceImageMimeTest extends TestCase
{
    protected FileService $service;

    /** @var array<int, string> */
    protected array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FileService();

        // Every test gets a fake disk. Without this a test whose expectation
        // fails can fall through into storeUpload() and write a real file into
        // storage/app/private, which then blocks the web server from creating
        // anything under a 700 directory it does not own.
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    /**
     * A real UploadedFile over a real temp file.
     *
     * UploadedFile::fake() cannot be used here: its getMimeType() falls back
     * to the *filename extension* rather than sniffing the contents, so every
     * fixture would report image/jpeg no matter what bytes it held and the
     * cross-check would never be exercised. The production code path runs
     * finfo, so the test has to as well.
     */
    protected function upload(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'spine-upload-');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    /** 1x1 transparent PNG. */
    protected function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk'
            .'YPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        );
    }

    /** Minimal JPEG header, enough for finfo to report image/jpeg. */
    protected function jpegBytes(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00"
            . "\xFF\xDB\x00C\x00" . str_repeat("\x08", 64) . "\xFF\xD9";
    }

    public function test_a_real_png_passes(): void
    {
        $file = $this->upload('avatar.png', $this->pngBytes());

        $this->assertSame('image/png', $file->getMimeType());

        $this->service->assertUploadAllowed($file);

        $this->assertTrue(true);
    }

    public function test_a_real_jpeg_passes(): void
    {
        $file = $this->upload('photo.jpg', $this->jpegBytes());

        $this->service->assertUploadAllowed($file);

        $this->assertTrue(true);
    }

    public function test_html_content_named_as_a_jpeg_is_refused(): void
    {
        $file = $this->upload(
            'avatar.jpg',
            '<html><body><script>alert(document.cookie)</script></body></html>'
        );

        $this->assertSame('text/html', $file->getMimeType(), 'fixture must sniff as HTML for this test to mean anything');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/is not a valid JPG image/');

        $this->service->assertUploadAllowed($file);
    }

    public function test_svg_content_named_as_a_png_is_refused(): void
    {
        $file = $this->upload(
            'avatar.png',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $this->expectException(ValidationException::class);

        $this->service->assertUploadAllowed($file);
    }

    public function test_a_php_payload_named_as_a_png_is_refused(): void
    {
        $file = $this->upload(
            'shell.png',
            "<?php system(\$_GET['c']); ?>"
        );

        $this->expectException(ValidationException::class);

        $this->service->assertUploadAllowed($file);
    }

    /**
     * Documents are deliberately exempt: their sniffed type legitimately
     * varies by producer, so asserting it would reject valid files.
     */
    public function test_a_csv_is_not_mime_checked(): void
    {
        $file = $this->upload(
            'export.csv',
            "a,b,c\n1,2,3\n"
        );

        $this->service->assertUploadAllowed($file);

        $this->assertContains(
            $file->getMimeType(),
            ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
            'fixture stopped sniffing like a spreadsheet; the exemption still holds either way'
        );
    }

    public function test_a_zip_is_not_mime_checked(): void
    {
        $file = $this->upload('bundle.zip', "PK\x03\x04fake-zip-bytes");

        $this->service->assertUploadAllowed($file);

        $this->assertTrue(true);
    }

    public function test_the_check_can_be_switched_off(): void
    {
        config()->set('spine.files.verify_image_mime', false);

        $file = $this->upload(
            'avatar.jpg',
            '<html><script>alert(1)</script></html>'
        );

        $this->service->assertUploadAllowed($file);

        $this->assertTrue(true, 'verify_image_mime=false must skip the content check');
    }

    public function test_the_refusal_happens_before_the_event_is_dispatched(): void
    {
        $dispatched = false;
        \Illuminate\Support\Facades\Event::listen(\Spine\Events\FileUploading::class, function () use (&$dispatched) {
            $dispatched = true;
        });

        try {
            $this->service->storeUpload(
                $this->upload('avatar.png', '<svg onload=alert(1)>'),
                'avatar',
                1
            );
            $this->fail('storeUpload should have refused SVG content named as a PNG');
        } catch (ValidationException) {
            $this->assertFalse($dispatched);
        }
    }
}
