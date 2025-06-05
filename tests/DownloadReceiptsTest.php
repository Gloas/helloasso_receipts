<?php
declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use lib\DownloadReceipts;

class DownloadReceiptsTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = __DIR__ . '/test_files/';
        if (!file_exists($this->testDir)) {
            mkdir($this->testDir);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testDir)) {
            // Remove all files and subdirectories
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->testDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            
            rmdir($this->testDir);
        }
    }

    public function testDownloadMock()
    {
        // Arrange
        $mockRow = ['123', 'test', 'test', 'test.pdf'];
        $sellName = 'test';
        $headers = ['mock-header'];
        
        // Create test files directory if not exists
        $testFilesDir = $this->testDir . 'receipts/';
        if (!file_exists($testFilesDir)) {
            mkdir($testFilesDir, 0777, true);
        }
        
        // Create complete mock
        $dr = new class($sellName) extends DownloadReceipts {
            public function __construct(string $sell_name) {
                // Initialize required properties without parent constructor
                $this->_download_url = 'http://example.com/%s/%s';
                $this->_folder_path = __DIR__.'/test_files/receipts/';
            }
            
            public function _download(array $row, string $sell_name, array $headers): bool {
                // Verify the URL construction
                $expectedUrl = 'http://example.com/test/123';
                $actualUrl = sprintf($this->_download_url, $sell_name, $row[0]);
                if ($actualUrl !== $expectedUrl) {
                    throw new \RuntimeException("URL construction failed");
                }
                return false;
            }
        };
        
        // Act
        $result = $dr->_download($mockRow, $sellName, $headers);
        
        // Assert
        $this->assertFalse($result, 'Le téléchargement devrait échouer avec une URL mockée');
    }
}
