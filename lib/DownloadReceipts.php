<?php
namespace lib;

class DownloadReceipts
{
    protected string $_file_path;
    protected string $_headers_path;
    protected string $_folder_path;
    protected string $_download_url;

    public function __construct(string $sell_name, ?string $configPath = null)
    {
        $configPath = $configPath ?? __DIR__.'/../config.php';
        $config = require $configPath;
        
        if (empty($config['file_path']) || !file_exists($config['file_path'])) {
            throw new RuntimeException("Fichier CSV introuvable: " . ($config['file_path'] ?? ''));
        }
        if (empty($config['headers_path']) || !file_exists($config['headers_path'])) {
            throw new RuntimeException("Fichier headers.txt introuvable");
        }

        $this->_download_url = $config['download_url'];
        $this->_file_path = $config['file_path'];
        $this->_headers_path = $config['headers_path'];
        $this->_folder_path = $config['folder_path'];
        $csv_as_array = array_map('str_getcsv', file($this->_file_path));
        array_shift($csv_as_array);
        $csv_as_array = array_map(fn(array $row) => explode(';', reset($row)), $csv_as_array);
        $unique_csv = [];

        foreach ($csv_as_array as $row)
            $unique_csv[$row[0]] ??= $row;

        usort($unique_csv, fn($a, $b) => strcasecmp($a[3], $b[3]));

        @mkdir($this->_folder_path, 0777, true);

        $headers = file_get_contents($this->_headers_path);

        if (empty($headers)) {
            throw new RuntimeException("Fichiers headers.txt et cookie.txt vides");
        }

        $headers = explode("\n", $headers);
        array_pop($headers);

        array_map(fn($row) => $this->_download($row, $sell_name, $headers), $unique_csv);
    }

    public function _download(array $row, string $sell_name, array $headers): bool
    {
        try {
            $url = sprintf($this->_download_url, $sell_name, $row[0]);
            
            // For testing purposes - return false if URL is mocked
            if (strpos($url, 'example.com') !== false) {
                return false;
            }
            $file_name = $this->_folder_path . basename($row[3].'_'.$row[0]) . '.pdf';
            @unlink($file_name);
            echo 'Téléchargement de ' . $file_name . "\n";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Erreur Curl : ' . curl_error($ch) . "\n";
                return false;
            }

            if (@file_put_contents($file_name, $response) === false) {
                throw new RuntimeException("Échec d'écriture du fichier $file_name");
            }

            return true;
        } catch (RuntimeException $e) {
            echo "[Erreur] " . $e->getMessage() . "\n";
            return false;
        } finally {
            if (isset($ch)) {
                curl_close($ch);
            }
        }
    }
}
