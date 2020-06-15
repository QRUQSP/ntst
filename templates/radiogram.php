<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// NOTE: the background required should be opened in Preview on Mac, and Exported to PNG 300 dpi (NO ALPHA)!!!
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function qruqsp_ntst_templates_radiogram(&$ciniki, $tnid, $args) {

/*    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }
*/
    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the message
    //
    $strsql = "SELECT qruqsp_ntst_messages.id, "
        . "qruqsp_ntst_messages.participant_id, "
        . "qruqsp_ntst_messages.status, "
        . "qruqsp_ntst_messages.number, "
        . "qruqsp_ntst_messages.precedence, "
        . "qruqsp_ntst_messages.hx, "
        . "qruqsp_ntst_messages.station_of_origin, "
        . "qruqsp_ntst_messages.check_number, "
        . "qruqsp_ntst_messages.place_of_origin, "
        . "qruqsp_ntst_messages.time_filed, "
        . "qruqsp_ntst_messages.date_filed, "
        . "qruqsp_ntst_messages.to_name_address, "
        . "qruqsp_ntst_messages.phone_number, "
        . "qruqsp_ntst_messages.email, "
        . "qruqsp_ntst_messages.message, "
        . "qruqsp_ntst_messages.spoken, "
        . "qruqsp_ntst_messages.signature "
        . "FROM qruqsp_ntst_messages "
        . "WHERE qruqsp_ntst_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND qruqsp_ntst_messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('participant_id', 'status', 'number', 'precedence', 'hx', 'station_of_origin', 'check_number', 'place_of_origin', 'time_filed', 'date_filed', 'to_name_address', 'phone_number', 'email', 'message', 'spoken', 'signature'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.21', 'msg'=>'Message not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['messages'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.22', 'msg'=>'Unable to find Message'));
    }
    $message = $rc['messages'][0];

    //
    // Setup the PDF
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/tcpdf/tcpdf.php');
    class MYPDF extends TCPDF {
        public function Header() { }
        public function Footer() { }
    }
    $pdf = new TCPDF('P', PDF_UNIT, 'LETTER', true, 'ISO-8859-1', false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($message['station_of_origin']);
    $pdf->SetTitle($message['station_of_origin'] . ' - '. $message['number']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    $filename = preg_replace('/[^A-Za-z0-9\-]/', '_', $message['station_of_origin'] . '-'. $message['number']);

    $pdf->AddPage();
    $pdf->SetCellPaddings(1, 0, 1, 0);
    $pdf->Image($ciniki['config']['qruqsp.core']['modules_dir'] . '/ntst/templates/radiogram.png', 15, 15, 186, '', '', '', '', false, 300, '', false, false, 0);

    $pdf->setFont('helvetica', '', 12);
    $pdf->setXY(25, 44);
    $pdf->Cell(14, 6, $message['number'], 0, false, 'C');
    $pdf->setXY(39, 44);
    $pdf->Cell(21, 6, $message['precedence'], 0, false, 'C');
    $pdf->setXY(60, 44);
    $pdf->Cell(6.5, 6, $message['hx'], 0, false, 'C');
    $pdf->setXY(66.5, 44);
    $pdf->Cell(29, 6, $message['station_of_origin'], 0, false, 'C');
    $pdf->setXY(95.5, 44);
    $pdf->Cell(17.5, 6, $message['check_number'], 0, false, 'C');
    $pdf->setXY(113, 44);
    $pdf->Cell(40.5, 6, $message['place_of_origin'], 0, false, 'C');
    $pdf->setXY(154, 44);
    $pdf->Cell(20, 6, $message['time_filed'], 0, false, 'C');
    $pdf->setXY(175, 44);
    $pdf->Cell(20, 6, $message['date_filed'], 0, false, 'C');

    $pdf->setXY(30, 50);
    $pdf->MultiCell(65, 15, $message['to_name_address'], 0, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', true);

    $pdf->setXY(45, 64);
    $pdf->MultiCell(50, 6, $message['phone_number'], 0, 'L', 0, 0, '', '', true, 0, false, true, 0, 'M', true);
    $pdf->setXY(34, 69);
    $pdf->MultiCell(60, 6, $message['email'], 0, 'L', 0, 0, '', '', true, 0, false, true, 0, 'M', true);

    //
    // Split the message into words
    //
    $words = preg_split('/\s+/', $message['message']);

    $row = 0;
    $col = 0;
/*    $words = array('one', 'two', 'three', 'four', 'five',
        'six', 'seven', 'eight', 'nine', 'ten',
        'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen',
        'sixteen', 'seventeen', 'eighteen', 'nighteen', 'twenty',
        'twentyone', 'twentytwo', 'twentythree', 'twentyfour', 'twentyfive',
        ); */
    foreach($words as $word) {
        $pdf->setXY(25 + ($col*34), 74 + ($row*6.5));
        $pdf->MultiCell(29, 6, $word, 0, 'C', 0, 0, '', '', true, 0, false, true, 0, 'B', true);
        $col++;
        if( $col > 4 ) {
            $col = 0;
            $row++;
        }
    }

    $pdf->setFont('helvetica', '', 11);
    $pdf->setXY(25, 105.5);
    $pdf->MultiCell(160, 6, $message['signature'], 0, 'L', 0, 0, '', '', true, 0, false, true, 0, 'M', true);
    $pdf->setFont('helvetica', '', 12);

    //
    // Output spoken if different
    //
    if( $message['spoken'] != $message['message'] ) {
        $pdf->setXY(25, 145);
        $pdf->setFont('helvetica', '', 14);
        $pdf->Cell(20, 6, 'Spoken Message:', 0, false, 'L');
        $pdf->setFont('helvetica', '', 12);
        $pdf->setXY(25, 152);
        $pdf->MultiCell(160, 25, $message['spoken'], 0, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', true);
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
