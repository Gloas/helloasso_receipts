<?php
class Download_Recieps
{
    
o    protected string $_file_path = 'export-paiements.csv';
    protected string $_cookie_path = 'cookies.txt';
    protected string $_folder_path = 'recieps/';
    protected string $_download_url = 'https://www.helloasso.com/associations/association-des-parents-d-eleves-de-valleiry/boutiques/%s/paiement-attestation/%s';
    protected $_context;


    public function __construct(string $sell_name)
    {
        $csv_as_array = array_map('str_getcsv', file($this->_file_path));
        array_shift($csv_as_array);
        $csv_as_array = array_map(fn(array $row) => explode(';', reset($row)), $csv_as_array);
        $unique_csv = [];

        foreach ($csv_as_array as $row)
            $unique_csv[$row[0]] ??= $row;

        usort($unique_csv, fn($a, $b) => strcasecmp($a[3], $b[3]));

        @mkdir($this->_folder_path, 0777, true);

        $cookie = file_get_contents($this->_cookie_path);
        $context_params = ['http' => ['method' => 'GET',
                                      'header' => 'Cookie: ' . $cookie]];
        $this->_context = stream_context_create($context_params);
        array_map(fn($row) => $this->_download($row, $sell_name), $unique_csv);
    }


    protected function _download(array $row, string $sell_name): void
    {
        $url = sprintf($this->_download_url, $sell_name, $row[0]);
        $file_name = $this->_folder_path . basename($row[3].'_'.$row[0]) . '.pdf';
        @unlink($file_name);
        echo 'Téléchargement de ' . $file_name . "\n";
        @file_put_contents($file_name, file_get_contents($url, false, $this->_context));
    }
}

new Download_Recieps($argv[1]);
?>
