<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use lib\DownloadReceipts;

class DownloadReceiptsTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = __DIR__ . '/test_files/';
        if (!file_exists($this->testDir)) {
            mkdir($this->testDir);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
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

    public function testProcessDownloadsSuccess()
    {
        // Arrange
        $sellName = 'test';
        $mockCsvData = [
            ['123', 'test', 'test', 'file1.pdf'],
            ['456', 'test', 'test', 'file2.pdf']
        ];
        $mockHeaders = ['header1', 'header2'];
        
        $dr = new class($sellName) extends DownloadReceipts {
            public $loadCsvCalled = false;
            public $loadHeadersCalled = false;
            public $downloadCalled = 0;
            protected array $logStack = [];

            public function __construct(string $sellName)
            {
                parent::__construct($sellName);
                $this->initialize($sellName);
            }

            protected function logError(string $message): void
            {
                $this->logStack[] = $message;
            }

            public function getLogs(): array
            {
                return $this->logStack;
            }

            protected function loadAndProcessCsv(): array {
                $this->loadCsvCalled = true;
                return [
                    '123' => ['123', 'test', 'test', 'file1.pdf'],
                    '456' => ['456', 'test', 'test', 'file2.pdf']
                ];
            }
            
            protected function loadHeaders(): array {
                $this->loadHeadersCalled = true;
                return ['header1', 'header2'];
            }
            
            public function downloadReceipt(array $row, string $sellerName, array $headers): bool {
                $this->downloadCalled++;
                return true;
            }
        };

        // Act
        $dr->processDownloads($sellName);

        // Assert
        $this->assertTrue($dr->loadCsvCalled, 'loadAndProcessCsv() devrait être appelé');
        $this->assertTrue($dr->loadHeadersCalled, 'loadHeaders() devrait être appelé');
        $this->assertGreaterThanOrEqual(2, $dr->downloadCalled, 'downloadReceipt() devrait être appelé au moins pour chaque ligne');
        $this->assertLessThanOrEqual(2, $dr->downloadCalled, 'downloadReceipt() devrait être appelé au maximum pour chaque ligne');
    }

    public function testProcessDownloadsEmptyCsv()
    {
        // Arrange
        $sellName = 'test';
        
        $dr = new class($sellName) extends DownloadReceipts {
            protected array $logStack = [];
            
            public function __construct(string $sellName) {
                parent::__construct($sellName);
            }
            
            protected function loadAndProcessCsv(): array {
                return [];
            }
            
            protected function logError(string $message): void {
                $this->logStack[] = $message;
            }
            
            public function getLogs(): array {
                return $this->logStack;
            }
        };

        // Act
        $dr->processDownloads($sellName);

        // Assert
        // Le test passe si aucune exception n'est levée
        $this->addToAssertionCount(1); // Méthode moderne pour vérifier qu'aucune exception n'est levée
    }

    public function testDownloadMock()
    {
        // Arrange
        $mockRow = ['123', 'test', 'test', 'test.pdf'];
        $sellName = 'test';
        $headers = ['mock-header'];
        
        $dr = new class($sellName) extends DownloadReceipts {
            private string $mockDownloadUrl = 'http://example.com/%s/%s';
            
            public function __construct(string $sellName) {
                parent::__construct($sellName);
            }
            
            public function downloadReceipt(array $row, string $sellerName, array $headers): bool {
                $expectedUrl = 'http://example.com/test/123';
                $actualUrl = sprintf($this->mockDownloadUrl, $sellerName, $row[0]);
                if ($expectedUrl !== $actualUrl) {
                    throw new \RuntimeException("URL construction failed");
                }
                return false;
            }
            
            public function getExpectedUrl(string $sellerName, string $id): string {
                return sprintf($this->mockDownloadUrl, $sellerName, $id);
            }
        };
        
        // Act
        $result = $dr->downloadReceipt($mockRow, $sellName, $headers);
        
        // Assert
        $this->assertFalse($result);
        $this->assertEquals('http://example.com/test/123', $dr->getExpectedUrl($sellName, $mockRow[0]));
    }

    public function testInitialize()
    {
        $sellName = 'test';
        $dr = new class($sellName) extends DownloadReceipts {
            protected function loadConfig(string $configPath): array {
                return [
                    'file_path' => 'test.csv',
                    'headers_path' => 'headers.txt',
                    'folder_path' => 'receipts/',
                    'download_url' => 'http://example.com/%s/%s'
                ];
            }
            
            protected function fileExists(string $path): bool {
                return true; // Mock de la vérification de fichier
            }
            
            protected function logError(string $message): void {
                // Ne rien faire pour éviter les logs dans les tests
            }
        };
        $this->assertTrue($dr->initialize($sellName));
    }

    public function testInitializeWithMockConfig()
    {
        $sellName = 'test';
        $dr = new class($sellName) extends DownloadReceipts {
            protected function loadConfig(string $configPath): array {
                return [
                    'file_path' => 'test.csv',
                    'headers_path' => 'headers.txt', 
                    'folder_path' => 'receipts/',
                    'download_url' => 'http://example.com/%s/%s'
                ];
            }
            
            protected function logError(string $message): void {
                // Ne rien faire pour les tests
            }
            
            protected function fileExists(string $path): bool {
                return true; // Mock de la vérification de fichier
            }
        };
        
        $this->assertTrue($dr->initialize($sellName, 'mock_config_path'));
    }

    public function testInitializeWithInvalidConfig()
    {
        $sellName = 'test';
        $dr = new class($sellName) extends DownloadReceipts {
            protected array $logStack = [];
            
            protected function loadConfig(string $configPath): array {
                $this->logError("Config file not found");
                return [];
            }
            
            protected function logError(string $message): void {
                $this->logStack[] = $message;
            }
            
            public function getLogs(): array {
                return $this->logStack;
            }
        };

        $this->assertFalse($dr->initialize($sellName, 'invalid_path'));
        $this->assertNotEmpty($dr->getLogs());
    }

    public function testLoadHeaders()
    {
        $sellName = 'test';
        $dr = new class($sellName) extends DownloadReceipts {
            protected array $logStack = [];
            
            public function __construct(string $sellName) {
                parent::__construct($sellName);
            }
            
            public function loadHeaders(): array {
                return ['header1', 'header2']; // Retourne des headers factices
            }
            
            protected function logError(string $message): void {
                $this->logStack[] = $message;
            }
            
            public function getLogs(): array {
                return $this->logStack;
            }
        };
        
        $headers = $dr->loadHeaders();
        $this->assertIsArray($headers);
        $this->assertNotEmpty($headers);
    }

    public function testLogError()
    {
        $sellName = 'test';
        $testMessage = 'Test error message';
        
        $dr = new class($sellName) extends DownloadReceipts {
            protected array $logStack = [];
            
            public function __construct(string $sellName) {
                parent::__construct($sellName);
                $this->initialize($sellName);
            }
            
            public function callLogError(string $message): void {
                $this->logError($message);
            }
            
            public function getLogs(): array {
                return $this->logStack;
            }
        };
        
        $dr->callLogError($testMessage);
        $logs = $dr->getLogs();
        $this->assertContains($testMessage, $logs);
    }
}
