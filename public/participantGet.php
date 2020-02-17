<?php
//
// Description
// ===========
// This method will return all the information about an participant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the participant is attached to.
// participant_id:          The ID of the participant to get the details for.
//
// Returns
// -------
//
function qruqsp_ntst_participantGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'participant_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Participant'),
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
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.participantGet');
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
    // Return default for new Participant
    //
    if( $args['participant_id'] == 0 ) {
        $participant = array('id'=>0,
            'net_id'=>'',
            'callsign'=>'',
            'flags'=>'0',
            'name'=>'',
            'email'=>'',
        );
    }

    //
    // Get the details for an existing Participant
    //
    else {
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
            . "AND qruqsp_ntst_participants.id = '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
            array('container'=>'participants', 'fname'=>'id', 
                'fields'=>array('net_id', 'callsign', 'flags', 'name', 
                    'place_of_origin', 'address', 'phone', 'email'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.16', 'msg'=>'Participant not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['participants'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.17', 'msg'=>'Unable to find Participant'));
        }
        $participant = $rc['participants'][0];
    }

    return array('stat'=>'ok', 'participant'=>$participant);
}
?>
