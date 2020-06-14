<?php
//
// Description
// -----------
// This method will add a new message for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Message to.
//
// Returns
// -------
//
function qruqsp_ntst_messageAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'participant_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Participant'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Message Number'),
        'precedence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Precedence'),
        'hx'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Handling'),
        'station_of_origin'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Station of Origin'),
        'check_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Check'),
        'place_of_origin'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Place of Origin'),
        'time_filed'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Time Filed'),
        'date_filed'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Date Filed'),
        'to_name_address'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name/Address'),
        'phone_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Phone Number'),
        'email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email'),
        'message'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        'spoken'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Spoken'),
        'signature'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Signature'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'private', 'checkAccess');
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.messageAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Load the net details for the participant
    //
    $strsql = "SELECT participants.callsign, "
        . "participants.name, "
        . "participants.flags, "
        . "participants.place_of_origin, "
        . "participants.phone, "
        . "participants.email, "
        . "nets.id AS net_id, "
        . "nets.name, "
        . "nets.message_sources "
        . "FROM qruqsp_ntst_participants AS participants "
        . "INNER JOIN qruqsp_ntst_nets AS nets ON ("
            . "participants.net_id = nets.id "
            . "AND nets.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE participants.id = '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' "
        . "AND participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.ntst', 'participant');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.10', 'msg'=>'Unable to load participant', 'err'=>$rc['err']));
    }
    if( !isset($rc['participant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.11', 'msg'=>'Unable to find requested participant'));
    }
    $participant = $rc['participant'];
    $args['net_id'] = $rc['participant']['net_id'];

    $dt = new DateTime('now', new DateTimezone('UTC'));
    if( !isset($args['time_filed']) || $args['time_filed'] == '' ) {
        $args['time_filed'] = $dt->format('Hi') . 'Z';
    }
    if( !isset($args['date_filed']) || $args['date_filed'] == '' ) {
        $args['date_filed'] = $dt->format('M d');
    }
    $dt->sub(new DateInterval('P3M'));

    //
    // Check for next number if one was not passed
    //
    if( !isset($args['number']) || $args['number'] == '' ) {
        $strsql = "SELECT MAX(messages.number) AS num "
            . "FROM qruqsp_ntst_participants AS participants "
            . "INNER JOIN qruqsp_ntst_messages AS messages ON ("
                . "participants.id = messages.participant_id "
                . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND messages.date_added > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
                . ") "
                . "";
//        $strsql .= "WHERE participants.callsign = '" . ciniki_core_dbQuote($ciniki, $participant['callsign']) . "' ";
        $strsql .= "WHERE participants.net_id = '" . ciniki_core_dbQuote($ciniki, $participant['net_id']) . "' ";
        $strsql .= "AND participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.ntst', 'last');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.26', 'msg'=>'Unable to load number', 'err'=>$rc['err']));
        }
        if( isset($rc['last']['num']) && $rc['last']['num'] != '' ) {
            $args['number'] = sprintf("%03d", ($rc['last']['num'] + 1));
        } else {
            $args['number'] = '001';
        }
    }

    if( !isset($args['precedence']) || $args['precedence'] == '' ) {
        $args['precedence'] = 'R';
    }

    if( !isset($args['station_of_origin']) || $args['station_of_origin'] == '' ) {
        $args['station_of_origin'] = $participant['callsign'];
    }
    if( !isset($args['place_of_origin']) || $args['place_of_origin'] == '' ) {
        $args['place_of_origin'] = $participant['place_of_origin'];
    }

    //
    // Load a message if one is not supplied
    //
/*    if( !isset($args['message']) || $args['message'] == '' ) {
        //
        // Get the last 3 months or 100 messages MD5's to compare with our message file
        //
        $strsql = "SELECT md5(message) AS m "
            . "FROM qruqsp_ntst_messages "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY date_added DESC "
            . "LIMIT 100 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'qruqsp.ntst', 'messages', 'm');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.24', 'msg'=>'Unable to load the list of messages', 'err'=>$rc['err']));
        }
        $existing_messages = isset($rc['messages']) ? $rc['messages'] : '';

        //
        // Load the messages file specified for th net
        //
        $message_source = 'quotes.csv';
        if( isset($participant['message_sources']) ) {
            //
            // FIXME: Add lookup of source
            //
        }

        //
        // Open the messages file, and generate MD5 array
        //
        $filename = $ciniki['config']['ciniki.core']['root_dir'] . '/qruqsp-mods/ntst/messages/' . $message_source;
        $message_file = file($filename);
        $messages = array();
        foreach($message_file as $line) {
            $pieces = explode("::", $line);
            if( is_array($pieces) && count($pieces) > 1 ) {
                $messages[md5($pieces[0])] = array(
                    'message' => preg_replace("/\[\[(.*)||.*\]\]/g", "$1", $pieces[0]),
                    'spoken' => preg_replace("/\[\[.*||(.*)\]\]/g", "$1", $pieces[0]),
                    'signature' => $pieces[1],
                    );
            }
        }
        if( count($messages) < 2 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.25', 'msg'=>'Message file is empty.'));
        }

        //
        // Find the last message sent from this file, so we know what's the next
        // message to send.  Go through the list of existing md5 hashes in 
        // descending order from the SQL above to find last one used.
        //
        foreach($existing_messages as $hash) {
            if( isset($messages[$hash]) ) {
                //
                // MD5 exists, now find position in array
                //
                $next = 'no';
                foreach($messages as $k => $v) {
                    if( $next == 'yes' ) {
                        $next_message = $v;
                        break;
                    } 
                    if( $k == $hash ) {
                        $next = 'yes';
//                        $next_message = next($messages);
//                        if( $next_message === false ) {
//                            $next_message = reset($messages);
//                        }
//                        break;
                    }
                }
                
                break;
            }
        }
        if( !isset($next_message) ) {
            $next_message = reset($messages);
        }

        //
        // Setup the args with the next message
        //
        $args['message'] = $next_message['message'];
        $args['spoken'] = $next_message['spoken'];
        $args['signature'] = $next_message['signature'];
    }
*/
    if( !isset($args['check_number']) || $args['check_number'] == '' || $args['check_number'] == 0 ) {
        $args['check_number'] = str_word_count($args['message']);
    }

    if( !isset($args['to_name_address']) || $args['to_name_address'] == '' ) {
        $args['to_name_address'] = 'All Stations';
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.ntst');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the message to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'qruqsp.ntst.message', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.ntst');
        return $rc;
    }
    $message_id = $rc['id'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.ntst');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Email the message
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'private', 'messageSend');
    $rc = qruqsp_ntst_messageSend($ciniki, $args['tnid'], $message_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.27', 'msg'=>'', 'err'=>$rc['err']));
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', 'ntst');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.ntst.message', 'object_id'=>$message_id));

    //
    // Return the details for the net
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'public', 'netGet');
    $ciniki['request']['args']['net_id'] = $args['net_id'];
    return qruqsp_ntst_netGet($ciniki);
}
?>
