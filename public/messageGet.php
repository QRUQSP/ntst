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
        'participant_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Participant'),
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

        $dt = new DateTime('now', new DateTimezone('UTC'));
        $args['time_filed'] = $dt->format('Hi') . 'Z';
        $args['date_filed'] = $dt->format('M d');

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
            . "nets.name AS net_name, "
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
        $net_id = $rc['participant']['net_id'];

        //
        // Setup the default message
        //
        $message = array('id'=>0,
            'participant_id'=>(isset($args['participant_id']) ? $args['participant_id'] : ''),
            'participant_name'=>$participant['name'] . ' - ' . $participant['callsign'],
            'status'=>'10',
            'number'=>'',
            'precedence'=>'R',
            'hx'=>'',
            'station_of_origin'=>$participant['callsign'],
            'check_number'=>'',
            'place_of_origin'=>$participant['place_of_origin'],
            'time_filed'=>$dt->format('Hi') . 'Z',
            'date_filed'=>$dt->format('M d'),
            'to_name_address'=>'',
            'phone_number'=>'',
            'email'=>'',
            'message'=>'',
            'spoken'=>'',
            'signature'=>$participant['name'] . ' - ' . $participant['callsign'],
        );

        //
        // Select a random receipient from participants list
        // Check sent messages by email address
        //
        $strsql = "SELECT participants.id, "
            . "participants.net_id, "
            . "participants.callsign, "
            . "participants.name, "
            . "participants.place_of_origin, "
            . "participants.address, "
            . "participants.phone, "
            . "participants.email, "
            . "COUNT(messages.id) AS num_messages "
            . "FROM qruqsp_ntst_participants AS participants "
            . "LEFT JOIN qruqsp_ntst_messages AS messages ON ("
                . "participants.email = messages.email "
                . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (participants.flags&0x04) = 0x04 "
            . "";
        if( isset($args['participant_id']) && $args['participant_id'] != '' ) {
            $strsql .= "AND participants.id <> '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' ";
        }
        $strsql .= "GROUP BY participants.id "
            . "ORDER BY num_messages ASC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
            array('container'=>'participants', 'fname'=>'id', 
                'fields'=>array('id', 'net_id', 'num_messages', 'callsign', 'name', 'place_of_origin', 'address', 'phone', 'email')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.37', 'msg'=>'Unable to load participants', 'err'=>$rc['err']));
        }
        //
        // The first returned row will contain the participant 
        // with the fewest received messages. Then look for the other
        // participants with the same number of messages, and randomly
        // pick one to be the recepient.
        //
        $available_participants = array();
        if( isset($rc['participants']) && count($rc['participants']) > 0 ) {
            $num_messages = $rc['participants'][0]['num_messages'];
            foreach($rc['participants'] AS $p) {
                if( $p['num_messages'] == $num_messages ) {
                    $available_participants[] = $p;
                }
            }
        }
        if( count($available_participants) > 0 ) {
            $pid = rand(1, count($available_participants)) - 1;
            $message['to_name_address'] = $available_participants[$pid]['name'] . "\n" . $available_participants[$pid]['address'];
            $message['phone_number'] = $available_participants[$pid]['phone'];
            $message['email'] = $available_participants[$pid]['email'];
        }

        //
        // Get the next available message number
        //
        $strsql = "SELECT MAX(messages.number) AS num "
            . "FROM qruqsp_ntst_participants AS participants "
            . "INNER JOIN qruqsp_ntst_messages AS messages ON ("
                . "participants.id = messages.participant_id "
                . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE participants.net_id = '" . ciniki_core_dbQuote($ciniki, $participant['net_id']) . "' "
            . "AND participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND participants.id = '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.ntst', 'last');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.26', 'msg'=>'Unable to load number', 'err'=>$rc['err']));
        }
        if( isset($rc['last']['num']) && $rc['last']['num'] != '' ) {
            $message['number'] = sprintf("%03d", ($rc['last']['num'] + 1));
        } else {
            $message['number'] = '001';
        }

        //
        // Get the last 3 months or 100 messages MD5's to compare with our message file
        // This helps eliminate duplicate messages in the same net and also make sure
        // all messages are used before repeating in future nets.
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
        if( isset($participant['message_sources']) && $participant['message_sources'] != '' ) {
            $files = explode(',', $participant['message_sources']);

            //
            // Open the messages file, and generate MD5 array
            //
            $messages = array();
            foreach($files as $file) {
                error_log($file);
                // Make sure somebody can't submit filename with ../../.. to access file system, remove all not characters/numbers
                $file = preg_replace('/[^0-9A-Za-z_\-]/', '', $file);
                $filename = $ciniki['config']['ciniki.core']['root_dir'] . '/qruqsp-mods/ntst/messages/' . $file . '.csv';
                $message_file = file($filename);
                foreach($message_file as $line) {
                    $pieces = explode("::", trim($line));
                    if( is_array($pieces) && count($pieces) > 0 ) {
                        $messages[md5($pieces[0])] = array(
                            'message' => preg_replace("/\[\[(.*)\|\|.*\]\]/", "$1", $pieces[0]),
                            'spoken' => preg_replace("/\[\[.*\|\|(.*)\]\]/", "$1", $pieces[0]),
                            'signature' => (isset($pieces[1]) ? $pieces[1] : ''),
                            );
                    }
                }
                if( count($messages) < 1 ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.25', 'msg'=>'Message file is empty.'));
                }
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
            $message['message'] = $next_message['message'];
            $message['spoken'] = $next_message['spoken'];
            if( $next_message['signature'] != '' ) {
                $message['signature'] = $next_message['signature'];
            }
        }
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
    }

    //
    // Get the list of participants
    //
    $strsql = "SELECT participants.id, "
        . "CONCAT_WS(' - ', participants.callsign, participants.name) AS name "
        . "FROM qruqsp_ntst_participants AS participants "
        . "WHERE participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY participants.callsign, participants.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
        array('container'=>'participants', 'fname'=>'id', 
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.36', 'msg'=>'Unable to load participants', 'err'=>$rc['err']));
    }
    $participants = isset($rc['participants']) ? $rc['participants'] : array();

    return array('stat'=>'ok', 'message'=>$message, 'participants'=>$participants);
}
?>
