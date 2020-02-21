<?php
//
// Description
// ===========
// This method will return all the information about an net.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the net is attached to.
// net_id:          The ID of the net to get the details for.
//
// Returns
// -------
//
function qruqsp_ntst_netGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'net_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Net'),
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
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.netGet');
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'private', 'maps');
    $rc = qruqsp_ntst_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Return default for new Net
    //
    if( $args['net_id'] == 0 ) {
        $net = array('id'=>0,
            'name'=>'',
            'status'=>'10',
            'start_utc'=>'',
            'end_utc'=>'',
        );
    }

    //
    // Get the details for an existing Net
    //
    else {
        $strsql = "SELECT qruqsp_ntst_nets.id, "
            . "qruqsp_ntst_nets.name, "
            . "qruqsp_ntst_nets.status, "
            . "qruqsp_ntst_nets.status AS status_text, "
            . "qruqsp_ntst_nets.start_utc AS start_utc_date, "
            . "qruqsp_ntst_nets.start_utc AS start_utc_time, "
            . "qruqsp_ntst_nets.end_utc AS end_utc_date, "
            . "qruqsp_ntst_nets.end_utc AS end_utc_time "
            . "FROM qruqsp_ntst_nets "
            . "WHERE qruqsp_ntst_nets.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_ntst_nets.id = '" . ciniki_core_dbQuote($ciniki, $args['net_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
            array('container'=>'nets', 'fname'=>'id', 
                'fields'=>array('name', 'status', 'status_text', 'start_utc_date', 'start_utc_time', 'end_utc_date', 'end_utc_time',),
                'maps'=>array('status_text'=>$maps['net']['status']),
                'utctotz'=>array(
                    'start_utc_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'start_utc_time'=>array('timezone'=>'UTC', 'format'=>$time_format),
                    'end_utc_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'end_utc_time'=>array('timezone'=>'UTC', 'format'=>$time_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.32', 'msg'=>'Net not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['nets'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.33', 'msg'=>'Unable to find Net'));
        }
        $net = $rc['nets'][0];

        //
        // Get the list of participants
        //
        $strsql = "SELECT qruqsp_ntst_participants.id, "
            . "qruqsp_ntst_participants.net_id, "
            . "qruqsp_ntst_participants.callsign, "
            . "qruqsp_ntst_participants.flags, "
            . "qruqsp_ntst_participants.flags AS options, "
            . "qruqsp_ntst_participants.name, "
            . "qruqsp_ntst_participants.place_of_origin, "
            . "qruqsp_ntst_participants.address, "
            . "qruqsp_ntst_participants.phone, "
            . "qruqsp_ntst_participants.email "
            . "FROM qruqsp_ntst_participants "
            . "WHERE qruqsp_ntst_participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_ntst_participants.net_id = '" . ciniki_core_dbQuote($ciniki, $args['net_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
            array('container'=>'participants', 'fname'=>'id', 
                'fields'=>array('id', 'net_id', 'callsign', 'flags', 'options', 'name', 
                    'place_of_origin', 'address', 'phone', 'email'),
                'flags'=>array('options'=>$maps['participant']['flags']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $net['participants'] = isset($rc['participants']) ? $rc['participants'] : array();

        $participant_ids = array();
        foreach($net['participants'] as $p) {
            $participant_ids[] = $p['id'];
        }

        //
        // Get the list of messages
        //
        if( count($participant_ids) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
            $strsql = "SELECT qruqsp_ntst_messages.id, "
                . "qruqsp_ntst_messages.participant_id, "
                . "qruqsp_ntst_participants.callsign, "
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
                . "LEFT JOIN qruqsp_ntst_participants ON ("
                    . "qruqsp_ntst_messages.participant_id = qruqsp_ntst_participants.id "
                    . "AND qruqsp_ntst_participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE qruqsp_ntst_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND qruqsp_ntst_messages.participant_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $participant_ids) . ") "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
                array('container'=>'messages', 'fname'=>'id', 
                    'fields'=>array('id', 'participant_id', 'callsign', 'status', 'number', 'precedence', 'hx', 
                        'station_of_origin', 'check_number', 'place_of_origin', 'time_filed', 'date_filed', 
                        'to_name_address', 'phone_number', 'email', 'message', 'signature')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $net['messages'] = isset($rc['messages']) ? $rc['messages'] : array();
        } else {
            $net['messages'] = array();
        }
    }

    return array('stat'=>'ok', 'net'=>$net);
}
?>
