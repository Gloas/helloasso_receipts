<?php
require_once __DIR__.'/../lib/DownloadReceipts.php';

use PHPUnit\Framework\TestCase;

class DownloadReceiptsTest extends TestCase
{
    private $testFilePath = 'test_export.csv';
    private $testHeadersPath = 'test_headers.txt';
    private $testFolder = 'test_receipts/';

    protected function setUp(): void
    {
        // Créer des fichiers de test
        file_put_contents($this->testFilePath, "id;name;amount\n123;Test1;10\n456;Test2;20");
        file_put_contents($this->testHeadersPath, "Authorization: Bearer test\nContent-Type: application/json\n");
        
        if (!file_exists($this->testFolder)) {
            mkdir($this->testFolder);
        }
    }

    protected function tearDown(): void
    {
        // Nettoyer après les tests
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        if (file_exists($this->testHeadersPath)) {
            unlink($this->testHeadersPath);
        }
        if (file_exists($this->testFolder)) {
            array_map('unlink', glob($this->testFolder . '*'));
            rmdir($this->testFolder);
        }
    }

    public function testConstructorInitializesCorrectly()
    {
        $mock = $this->getMockBuilder(DownloadReceipts::class)
            ->setConstructorArgs(['test_sale'])
            ->onlyMethods(['_download'])
            ->getMock();

        $mock->expects($this->exactly(2))
            ->method('_download');

        $mock->__construct('test_sale');
    }

    public function testDownloadMethodCreatesFile()
    {
        $mock = $this->getMockBuilder(DownloadReceipts::class)
            ->setConstructorArgs(['test_sale'])
            ->onlyMethods(['_download'])
            ->getMock();

        $mock->expects($this->once())
            ->method('_download')
            ->willReturnCallback(function($row, $sell_name, $headers) {
                file_put_contents($this->testConfig['folder_path'] . 'TestFile_123.pdf', 'test content');
                return true;
            });

        $mock->__construct('test_sale');
        $this->assertFileExists($this->testConfig['folder_path'] . 'TestFile_123.pdf');
    }

    public function testEmptyHeadersFile()
    {
        file_put_contents($this->testConfig['headers_path'], '');
        file_put_contents('cookies.txt', '');

        ob_start();
        $instance = new DownloadReceipts('test_sale');
        $output = ob_get_clean();

        $this->assertEquals("Fichiers headers.txt et cookie.txt vides.\n", $output);
    }

    public function testCurlErrorHandling()
    {
        $mock = $this->getMockBuilder(DownloadReceipts::class)
            ->setConstructorArgs(['test_sale'])
            ->onlyMethods(['_download'])
            ->getMock();

        $mock->expects($this->once())
            ->method('_download')
            ->willReturnCallback(function() {
                echo 'Erreur cURL : test error';
                return false;
            });

        ob_start();
        $mock->__construct('test_sale');
        $output = ob_get_clean();

        $this->assertStringContainsString('Erreur cURL :', $output);
    }
}
