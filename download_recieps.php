<?php
class Download_Recieps {
    
    protected string $_file_path = '/home/ghislo/APE/helloasso/export-paiements.csv';
    protected string $_cookie_path = '/home/ghislo/APE/helloasso/cookie';
    protected string $_folder_path = '/home/ghislo/APE/helloasso/downloaded_paiements/';
    protected $_context;


    public function __construct(string $sell_name) {
        $csv = file_get_contents($this->_file_path);
        $csv_as_array = str_getcsv($csv, ';');
        $csv_as_array = array_filter($csv_as_array, fn($data) => 0 === strpos($data,
                                                                              sprintf('https://www.helloasso.com/associations/association-des-parents-d-eleves-de-valleiry/boutiques/%s/paiement-attestation/',
                                                                                      $sell_name)));
        @mkdir($this->_folder_path, 0777, true);

        $cookie = file_get_contents($this->_cookie_path);
        $context_params = ['http' => ['method' => 'GET',
                                      'header' => 'Cookie: ' . $cookie]];
        $this->_context = stream_context_create($context_params);
        array_map(fn($url) => $this->_download($url), $csv_as_array);
    }


    protected function _download(string $url) : void {
        $file_name = $this->_folder_path . basename($url);
        if ( !file_exists($file_name))
            file_put_contents($file_name, file_get_contents($url, false, $this->_context));
    }
}

new Download_Recieps($argv[1]);
?>
