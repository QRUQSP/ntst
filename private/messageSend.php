<?php
//
// Description
// -----------
// Send a message to a participant
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_ntst_messageSend(&$ciniki, $tnid, $message_id) {

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
        . "qruqsp_ntst_messages.email AS to_email, "
        . "qruqsp_ntst_messages.message, "
        . "qruqsp_ntst_messages.signature, "
        . "participants.callsign, "
        . "participants.name, "
        . "participants.email, "
        . "nets.name AS net_name "
        . "FROM qruqsp_ntst_messages "
        . "INNER JOIN qruqsp_ntst_participants AS participants ON ("
            . "qruqsp_ntst_messages.participant_id = participants.id "
            . "AND participants.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN qruqsp_ntst_nets AS nets ON ("
            . "participants.net_id = nets.id "
            . "AND nets.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE qruqsp_ntst_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND qruqsp_ntst_messages.id = '" . ciniki_core_dbQuote($ciniki, $message_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('id', 'participant_id', 'status', 'callsign', 'name', 'email', 'net_name',
                'number', 'precedence', 'hx', 'station_of_origin', 'check_number', 
                'place_of_origin', 'time_filed', 'date_filed', 
                'to_name_address', 'phone_number', 'to_email', 
                'message', 'signature'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.29', 'msg'=>'Message not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['messages'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.30', 'msg'=>'Unable to find Message'));
    }
    $message = $rc['messages'][0];

    //
    // Send the message
    //
    $words = preg_split('/\s+/', $message['message']);
    $content = "Number: " . $message['number'] . "\n"
        . "Precedence: " . $message['precedence'] . "\n"
        . "Handling: " . $message['hx'] . "\n"
        . "Station of Origin: " . $message['station_of_origin'] . "\n"
        . "Check: " . $message['check_number'] . "\n"
        . "Place of Origin: " . $message['place_of_origin'] . "\n"
        . "Time Filed: " . $message['time_filed'] . "\n"
        . "Date Filed: " . $message['date_filed'] . "\n"
        . "To: " . $message['to_name_address'] . "\n"
        . "Phone: " . $message['phone_number'] . "\n"
        . "Email: " . $message['to_email'] . "\n"
        . "\n";
    $c = 0;
    foreach($words as $word) {
        $content .= $word;
        if( ($c%5) < 4 ) {
            $content .= " ";
        }
        $c++;
        if( ($c%5) == 0 ) {
            $content .= "\n";
        }
    }
    $content .= "\n";
    $content .= "Signature: " . $message['signature'] . "\n";

    //
    // Add message to mail queue
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
    $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
        'object' => 'qruqsp.ntst.message',
        'object_id' => $message['id'],
        'customer_id' => 0,
        'customer_name' => $message['name'],
        'customer_email' => $message['email'],
        'subject' => 'Net: ' . $message['net_name'] . ' - Message Number ' . $message['number'],
        'html_content' => $content,
        'text_content' => $content,
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.28', 'msg'=>'Unable to add message to queue', 'err'=>$rc['err']));
    }
    $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid' => $tnid);

    return array('stat'=>'ok');
}
?>
