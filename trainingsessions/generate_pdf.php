<?php
/**
 * Course trainingsessions report. Gives a transversal view of all courses for a user.
 * this script is used as inclusion of the index.php file.
 *
 * @package    report_trainingsessions
 * @category   report
 * @copyright UPMC 2017
 */

require __DIR__.'/html2pdf/vendor/autoload.php';

use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;

//echo $_POST['result'];

try {
    $html2pdf = new Html2Pdf();
    $html2pdf->writeHTML($_POST['result']);
    $html2pdf->output();
} catch (Html2PdfException $e) {
    $formatter = new ExceptionFormatter($e);
    echo $formatter->getHtmlMessage();
    echo $_POST['result'];
}

 ?>