<?php
// Désactiver les warnings de session pour les tests
if (PHP_SAPI === 'cli') {
    ini_set('session.sid_length', '32');
    ini_set('session.sid_bits_per_character', '5');
}

return [
    'download_url' => 'https://www.helloasso.com/associations/association-des-parents-d-eleves-de-valleiry/boutiques/%s/paiement-attestation/%s',
    'file_path' => 'export-paiements.csv',
    'headers_path' => 'headers.txt',
    'folder_path' => 'receipts/'
];
