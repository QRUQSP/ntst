<?php
//
// Description
// -----------
// This method will return the list of Participants for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Participant for.
//
// Returns
// -------
//
function qruqsp_ntst_participantList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'private', 'checkAccess');
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.participantList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of participants
    //
    $strsql = "SELECT qruqsp_ntst_participants.id, "
        . "qruqsp_ntst_participants.net_id, "
        . "qruqsp_ntst_participants.callsign, "
        . "qruqsp_ntst_participants.flags, "
        . "qruqsp_ntst_participants.name, "
        . "qruqsp_ntst_participants.place_of_origin, "
        . "qruqsp_ntst_participants.address, "
        . "qruqsp_ntst_participants.phone, "
        . "qruqsp_ntst_participants.email "
        . "FROM qruqsp_ntst_participants "
        . "WHERE qruqsp_ntst_participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
        array('container'=>'participants', 'fname'=>'id', 
            'fields'=>array('id', 'net_id', 'callsign', 'flags', 'name', 
                'place_of_origin', 'address', 'phone', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['participants']) ) {
        $participants = $rc['participants'];
        $participant_ids = array();
        foreach($participants as $iid => $participant) {
            $participant_ids[] = $participant['id'];
        }
    } else {
        $participants = array();
        $participant_ids = array();
    }

    return array('stat'=>'ok', 'participants'=>$participants, 'nplist'=>$participant_ids);
}
?>
