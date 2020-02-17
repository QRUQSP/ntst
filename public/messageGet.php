<?php
//
// Description
// ===========
// This method will return all the information about an message.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the message is attached to.
// message_id:          The ID of the message to get the details for.
//
// Returns
// -------
//
function qruqsp_ntst_messageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'private', 'checkAccess');
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.messageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Message
    //
    if( $args['message_id'] == 0 ) {
        //
        // FIXME: Select a random receipient from participants list
        //

        //
        // FIXME: Select a random message
        //

        //
        // Setup the default message
        //
        $message = array('id'=>0,
            'participant_id'=>'',
            'status'=>'10',
            'number'=>'',
            'precedence'=>'',
            'hx'=>'',
            'station_of_origin'=>'',
            'check_number'=>'',
            'place_of_origin'=>'',
            'time_filed'=>'',
            'date_filed'=>'',
            'to_name_address'=>'',
            'phone_number'=>'',
            'email'=>'',
            'message'=>'',
            'signature'=>'',
        );
    }

    //
    // Get the details for an existing Message
    //
    else {
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
            . "qruqsp_ntst_messages.signature "
            . "FROM qruqsp_ntst_messages "
            . "WHERE qruqsp_ntst_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_ntst_messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
            array('container'=>'messages', 'fname'=>'id', 
                'fields'=>array('participant_id', 'status', 'number', 'precedence', 'hx', 'station_of_origin', 'check_number', 'place_of_origin', 'time_filed', 'date_filed', 'to_name_address', 'phone_number', 'email', 'message', 'signature'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.21', 'msg'=>'Message not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['messages'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.22', 'msg'=>'Unable to find Message'));
        }
        $message = $rc['messages'][0];
    }

    return array('stat'=>'ok', 'message'=>$message);
}
?>
