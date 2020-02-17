<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_ntst_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();

    $objects['net'] = array(
        'name' => 'Net',
        'sync' => 'yes',
        'o_name' => 'net',
        'o_container' => 'nets',
        'table' => 'qruqsp_ntst_nets',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'start_utc' => array('name'=>'Start Date Time', 'default'=>''),
            'end_utc' => array('name'=>'End Date Time', 'default'=>''),
            'message_sources' => array('name'=>'Message Source Files', 'default'=>''),
            ),
        'history_table' => 'qruqsp_ntst_history',
        );
    $objects['participant'] = array(
        'name' => 'Participant',
        'sync' => 'yes',
        'o_name' => 'participant',
        'o_container' => 'participants',
        'table' => 'qruqsp_ntst_participants',
        'fields' => array(
            'net_id' => array('name'=>'Net', 'ref'=>'qruqsp.ntst.net'),
            'callsign' => array('name'=>'Callsign'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'name' => array('name'=>'Name', 'default'=>''),
            'place_of_origin' => array('name'=>'Place of Origin', 'default'=>''),
            'address' => array('name'=>'Address', 'default'=>''),
            'phone' => array('name'=>'Phone', 'default'=>''),
            'email' => array('name'=>'Email', 'default'=>''),
            ),
        'history_table' => 'qruqsp_ntst_history',
        );
    $objects['message'] = array(
        'name' => 'Message',
        'sync' => 'yes',
        'o_name' => 'message',
        'o_container' => 'messages',
        'table' => 'qruqsp_ntst_messages',
        'fields' => array(
            'participant_id' => array('name'=>'Participant', 'ref'=>'qruqsp.ntst.participant'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'number' => array('name'=>'Message Number', 'default'=>''),
            'precedence' => array('name'=>'Precedence', 'default'=>''),
            'hx' => array('name'=>'Handling', 'default'=>''),
            'station_of_origin' => array('name'=>'Station of Origin', 'default'=>''),
            'check_number' => array('name'=>'Check', 'default'=>''),
            'place_of_origin' => array('name'=>'Place of Origin', 'default'=>''),
            'time_filed' => array('name'=>'Time Filed', 'default'=>''),
            'date_filed' => array('name'=>'Date Filed', 'default'=>''),
            'to_name_address' => array('name'=>'Name/Address', 'default'=>''),
            'phone_number' => array('name'=>'Phone Number', 'default'=>''),
            'email' => array('name'=>'Email', 'default'=>''),
            'message' => array('name'=>'Message', 'default'=>''),
            'signature' => array('name'=>'Signature', 'default'=>''),
            ),
        'history_table' => 'qruqsp_ntst_history',
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
