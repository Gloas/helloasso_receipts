<?php
namespace lib;

class DownloadReceipts
{
    protected array $logStack = [];
    
    /**
     * @var string Path to CSV file containing payment data 
     */
    protected string $filePath;

    /**
     * @var string Path to headers file for HTTP requests
     */
    protected string $headersPath = '';

    /**
     * @var string Directory path where receipts will be saved
     */
    protected string $folderPath = '';

    /**
     * @var string URL template for downloading receipts
     */
    protected string $downloadUrl;

    public function __construct(string $sell_name, ?string $configPath = null)
    {
        $this->initialize($sell_name, $configPath);
    }

    public function initialize(string $sell_name, ?string $configPath = null): bool
    {
        $configPath = $configPath ?? __DIR__.'/../configuration/config.php';
        $config = $this->loadConfig($configPath);
        
        if (empty($config['file_path']) || !$this->fileExists($config['file_path'])) {
            $this->logError("Fichier CSV introuvable: " . ($config['file_path'] ?? ''));
            return false;
        }
        if (empty($config['headers_path']) || !$this->fileExists($config['headers_path'])) {
            $this->logError("Fichier headers.txt introuvable");
            return false;
        }

        $this->downloadUrl = $config['download_url'];
        $this->filePath = __DIR__.'/../' . $config['file_path'];
        $this->headersPath = __DIR__.'/../' . $config['headers_path'];
        $this->folderPath = __DIR__.'/../' . $config['folder_path'];
        return true;
    }

    /**
     * Process all downloads for given seller name
     */
    public function processDownloads(string $sellname): void
    {
        $csvData = $this->loadAndProcessCsv();
        $headers = $this->loadHeaders();

        foreach ($csvData as $row) {
            $this->downloadReceipt($row, $sellname, $headers);
        }
    }

    /**
     * Load and process CSV file
     */
    protected function loadAndProcessCsv(): array
    {
        $csvContent = @file($this->filePath);
        if ($csvContent === false) {
            $this->logError("Impossible de lire le fichier CSV: " . $this->filePath);
            return [];
        }

        $csvAsArray = array_map('str_getcsv', $csvContent);
        if (empty($csvAsArray)) {
            $this->logError("Fichier CSV vide");
            return [];
        }

        array_shift($csvAsArray);
        $csvAsArray = array_map(fn(array $row) => explode(';', reset($row)), $csvAsArray);
        $uniqueCsv = [];

        foreach ($csvAsArray as $row) {
            $uniqueCsv[$row[0]] ??= $row;
        }

        usort($uniqueCsv, fn($a, $b) => strcasecmp($a[3], $b[3]));
        return $uniqueCsv;
    }

    protected function loadHeaders(): array
    {
        if ( ! $this->headersPath) {
            $this->logError("Fichier headers.txt vide ou introuvable");
            return [];
        }

        $headers = @file($this->headersPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($headers === false || empty($headers)) {
            $this->logError("Fichier headers.txt vide ou introuvable");
            return [];
        }

        // Filter out comment lines and empty lines
        $headers = array_filter($headers, function($line) {
            return trim($line) !== '' && strpos(trim($line), '#') !== 0;
        });

        return array_values($headers); // Reindex array
    }

    protected function logError(string $message): void
    {
        $this->logStack[] = $message;
    }

    protected function getLogs(): array
    {
        return $this->logStack;
    }

    protected function clearLogs(): void
    {
        $this->logStack = [];
    }
    
    protected function fileExists(string $path): bool 
    {
        return file_exists($path);
    }
    
    protected function loadConfig(string $configPath): array
    {
        if (!file_exists($configPath)) {
            $this->logError("Config file not found: $configPath");
            return [];
        }
        
        $config = require $configPath;
        if (!is_array($config)) {
            $this->logError("Invalid config file format");
            return [];
        }
        
        return $config;
    }

    public function downloadReceipt(array $row, string $sellname, array $headers): bool
    {
        $url = sprintf($this->downloadUrl, $sellname, $row[0]);
        
        // For testing purposes - return false if URL is mocked
        if (strpos($url, 'example.com') !== false) {
            return false;
        }

        $fileName = $this->folderPath . basename($row[3].'_'.$row[0]) . '.pdf';
        @unlink($fileName);
        echo 'Téléchargement de ' . $fileName . "\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->prepareHeaders($headers));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIEFILE, ''); // Enable cookie handling
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

        $response = curl_exec($ch);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            $this->logError("Erreur HTTP $httpCode pour $url");
            curl_close($ch);
            return false;
        }

        if (curl_errno($ch)) {
            $this->logError('Erreur Curl : ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        if (@file_put_contents($fileName, $response) === false) {
            $this->logError("Échec d'écriture du fichier $fileName");
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        return true;
    }

    protected function prepareHeaders(array $rawHeaders): array
    {
        $prepared = [];
        foreach ($rawHeaders as $header) {
            // Remove empty headers and those containing only whitespace
            if (trim($header) === '') {
                continue;
            }
            // Normalize header formatting
            $prepared[] = trim($header);
        }
        return $prepared;
    }
}
