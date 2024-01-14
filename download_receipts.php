<?php
declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/lib/DownloadReceipts.php';

use lib\DownloadReceipts;

if (PHP_SAPI !== 'cli') {
    echo "Ce script doit être exécuté en ligne de commande\n";
    exit(1);
}

if (!isset($argv[1])) {
    echo "Usage: php download_receipts.php <sell_name>\n";
    exit(1);
}

$download = new DownloadReceipts($argv[1]);
if (!$download->initialize($argv[1])) {
    exit(1);
}

$result = $download->processDownloads($argv[1]);
if ($result) {
    echo "Téléchargement terminé avec succès\n";
    exit(0);
}
exit(1);

