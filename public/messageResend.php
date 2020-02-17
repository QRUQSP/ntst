<?php
//
// Description
// ===========
// This method sends or resend a message to a participants
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
function qruqsp_ntst_messageResend($ciniki) {
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
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.messageResend');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Email the message
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'private', 'messageSend');
    $rc = qruqsp_ntst_messageSend($ciniki, $args['tnid'], $args['message_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.ntst.31', 'msg'=>'', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok', 'message'=>$message);
}
?>
