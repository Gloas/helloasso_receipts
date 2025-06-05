<?php
require_once 'lib/DownloadReceipts.php';

use App\Receipts\DownloadReceipts;

new DownloadReceipts($argv[1]);
