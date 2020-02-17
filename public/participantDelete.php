<?php
//
// Description
// -----------
// This method will delete an participant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the participant is attached to.
// participant_id:            The ID of the participant to be removed.
//
// Returns
// -------
//
function qruqsp_ntst_participantDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'participant_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Participant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'private', 'checkAccess');
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'ciniki.ntst.participantDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the participant
    //
    $strsql = "SELECT id, uuid "
        . "FROM qruqsp_ntst_participants "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.ntst', 'participant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['participant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.13', 'msg'=>'Participant does not exist.'));
    }
    $participant = $rc['participant'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'qruqsp.ntst.participant', $args['participant_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.14', 'msg'=>'Unable to check if the participant is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.15', 'msg'=>'The participant is still in use. ' . $rc['msg']));
    }

    //
    // Get the list of messages for this participant
    //
    $strsql = "SELECT id, uuid "
        . "FROM qruqsp_ntst_messages "
        . "WHERE participant_id = '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.ntst', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.23', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $messages = isset($rc['rows']) ? $rc['rows'] : array();
    

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.ntst');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the messages
    //
    foreach($messages as $message) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.ntst.message',
            $message['id'], $message['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.ntst');
            return $rc;
        }
    }

    //
    // Remove the participant
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.ntst.participant',
        $args['participant_id'], $participant['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.ntst');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.ntst');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', 'ntst');

    //
    // Return the details for the net
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'public', 'netGet');
    return qruqsp_ntst_netGet($ciniki);
}
?>
