<?php

namespace App\Exceptions;

use Exception;

/**
 * Dilempar saat ada upaya mencatat transaksi di periode yang sudah dikunci (tutup buku).
 * Di-render jadi redirect back dengan pesan error (lihat bootstrap/app.php).
 */
class PeriodeTerkunciException extends Exception
{
}
