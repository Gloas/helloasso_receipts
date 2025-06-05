<?php
require_once __DIR__.'/../lib/DownloadReceipts.php';

use PHPUnit\Framework\TestCase;

class DownloadReceiptsTest extends TestCase
{
    private $testFilePath = 'test_export.csv';
    private $testHeadersPath = 'test_headers.txt';
    private $testFolder = 'test_receipts/';
    private $testConfig = [
        'download_url' => 'https://test.com/%s/%s',
        'file_path' => 'test_export.csv',
        'headers_path' => 'test_headers.txt',
        'folder_path' => 'test_receipts/'
    ];

    protected function setUp(): void
    {
        // Créer un fichier CSV mocké avec 2 entrées seulement
        $csvContent = <<<CSV
Référence commande;Date de la commande;Statut de la commande;Nom payeur;Prénom payeur
123456;01/01/2025;Validé;Doe;John
789012;02/01/2025;Validé;Smith;Jane
CSV;
        file_put_contents($this->testFilePath, $csvContent);
        file_put_contents($this->testHeadersPath, "Authorization: Bearer test\nContent-Type: application/json\n");
        
        // Créer un fichier config.php mocké pour les tests
        $configContent = '<?php return ' . var_export($this->testConfig, true) . ';';
        file_put_contents('test_config.php', $configContent);
        
        if (!file_exists($this->testFolder)) {
            mkdir($this->testFolder);
        }
    }

    protected function tearDown(): void
    {
        // Nettoyer tous les fichiers de test possibles
        $filesToClean = [
            $this->testFilePath,
            $this->testHeadersPath,
            'test_config.php',
            'test_empty_config.php',
            'test_empty_headers.txt',
            'empty_headers.txt'
        ];
        
        foreach ($filesToClean as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        if (file_exists($this->testFolder)) {
            array_map('unlink', glob($this->testFolder . '*'));
            rmdir($this->testFolder);
        }
    }

    public function testConstructorInitializesCorrectly()
    {
        $mock = $this->getMockBuilder(DownloadReceipts::class)
            ->setConstructorArgs(['test_sale', 'test_config.php'])
            ->onlyMethods(['_download'])
            ->getMock();

        $mock->expects($this->exactly(2))
            ->method('_download')
            ->with(
                $this->isType('array'),
                $this->equalTo('test_sale'),
                $this->isType('array')
            )
            ->willReturn(true);

        $instance = $mock->__construct('test_sale', 'test_config.php');
        $this->assertInstanceOf(DownloadReceipts::class, $mock);
    }

    public function testDownloadMethodCreatesFile()
    {
        $mock = $this->getMockBuilder(DownloadReceipts::class)
            ->setConstructorArgs(['test_sale', 'test_config.php'])
            ->onlyMethods(['_download'])
            ->getMock();

        $mock->expects($this->exactly(2))
            ->method('_download')
            ->willReturnCallback(function($row, $sell_name, $headers) {
                $filename = $this->testFolder . $row[3] . '_' . $row[0] . '.pdf';
                file_put_contents($filename, 'test content');
                return true;
            });

        $mock->__construct('test_sale', 'test_config.php');
        $this->assertFileExists($this->testFolder . 'Doe_123456.pdf');
        $this->assertFileExists($this->testFolder . 'Smith_789012.pdf');
    }

    public function testEmptyHeadersFile()
    {
        // Créer un fichier headers vide spécifique pour ce test
        $emptyHeadersPath = 'test_empty_headers.txt';
        file_put_contents($emptyHeadersPath, '');
        
        // Créer une config spécifique pour ce test
        $emptyConfig = $this->testConfig;
        $emptyConfig['headers_path'] = $emptyHeadersPath;
        file_put_contents('test_empty_config.php', '<?php return ' . var_export($emptyConfig, true) . ';');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Fichiers headers.txt et cookie.txt vides");

        $instance = new DownloadReceipts('test_sale', 'test_empty_config.php');
    }

    public function testCurlErrorHandling()
    {
        $mock = $this->getMockBuilder(DownloadReceipts::class)
            ->setConstructorArgs(['test_sale', 'test_config.php'])
            ->onlyMethods(['_download'])
            ->getMock();

        $mock->expects($this->exactly(2)) // On s'attend à 2 appels pour nos 2 entrées de test
            ->method('_download')
            ->willReturnOnConsecutiveCalls(
                true,
                $this->throwException(new RuntimeException('Erreur cURL : test error'))
            );

        // Attraper la sortie
        ob_start();
        $mock->__construct('test_sale', 'test_config.php');
        $output = ob_get_clean();

        $this->assertStringContainsString('Erreur cURL : test error', $output);
    }
}
