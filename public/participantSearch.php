<?php
//
// Description
// -----------
// This method searchs for a Participants for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Participant for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function qruqsp_ntst_participantSearch($ciniki) {
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
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.participantSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of participants
    //
    $strsql = "SELECT DISTINCT "
        . "qruqsp_ntst_participants.callsign, "
        . "qruqsp_ntst_participants.flags, "
        . "qruqsp_ntst_participants.name, "
        . "qruqsp_ntst_participants.place_of_origin, "
        . "qruqsp_ntst_participants.address, "
        . "qruqsp_ntst_participants.phone, "
        . "qruqsp_ntst_participants.email "
        . "FROM qruqsp_ntst_participants "
        . "WHERE qruqsp_ntst_participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "callsign LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR callsign LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR callsign REGEXP '.*[0-9]" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . ".*' "
            . "OR name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR email LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "ORDER BY callsign ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
        array('container'=>'participants', 'fname'=>'callsign', 
            'fields'=>array('callsign', 'flags', 'name', 'place_of_origin', 
                'address', 'phone', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $participants = isset($rc['participants']) ? $rc['participants'] : array();

    return array('stat'=>'ok', 'participants'=>$participants);
}
?>
