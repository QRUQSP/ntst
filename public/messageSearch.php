<?php
//
// Description
// -----------
// This method searchs for a Messages for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Message for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function qruqsp_ntst_messageSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'private', 'checkAccess');
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.messageSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of messages
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
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('id', 'participant_id', 'status', 'number', 'precedence', 'hx', 'station_of_origin', 'check_number', 'place_of_origin', 'time_filed', 'date_filed', 'to_name_address', 'phone_number', 'email', 'message', 'spoken', 'signature')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['messages']) ) {
        $messages = $rc['messages'];
        $message_ids = array();
        foreach($messages as $iid => $message) {
            $message_ids[] = $message['id'];
        }
    } else {
        $messages = array();
        $message_ids = array();
    }

    return array('stat'=>'ok', 'messages'=>$messages, 'nplist'=>$message_ids);
}
?>
