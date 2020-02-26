<?php
//
// Description
// ===========
// This method will return the filled out radiogram for a message
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function qruqsp_ntst_radiogramPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        'download'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Message'),
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
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.radiogramPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Run the template
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'ntst', 'templates', 'radiogram');
    $rc = qruqsp_ntst_templates_radiogram($ciniki, $args['tnid'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Return the pdf
    //
    if( isset($rc['pdf']) ) {
        if( isset($args['download']) && $args['download'] == 'no' ) {
            $rc['pdf']->Output($rc['filename'], 'I');
        } else {
            $rc['pdf']->Output($rc['filename'], 'D');
        }
    }

    return array('stat'=>'exit');
}
?>
