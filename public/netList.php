<?php
//
// Description
// -----------
// This method will return the list of Nets for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Net for.
//
// Returns
// -------
//
function qruqsp_ntst_netList($ciniki) {
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
    $rc = qruqsp_ntst_checkAccess($ciniki, $args['tnid'], 'qruqsp.ntst.netList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the date format strings for the user
    //
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
    // Get the list of nets
    //
    $strsql = "SELECT qruqsp_ntst_nets.id, "
        . "qruqsp_ntst_nets.name, "
        . "qruqsp_ntst_nets.status, "
        . "qruqsp_ntst_nets.status AS status_text, "
        . "qruqsp_ntst_nets.start_utc, "
        . "qruqsp_ntst_nets.start_utc AS start_utc_text, "
        . "qruqsp_ntst_nets.start_utc AS start_time_text, "
        . "qruqsp_ntst_nets.end_utc "
        . "FROM qruqsp_ntst_nets "
        . "WHERE qruqsp_ntst_nets.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY start_utc DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.ntst', array(
        array('container'=>'nets', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'status_text', 'start_utc', 'start_utc_text', 'end_utc'),
            'maps'=>array('status_text'=>$maps['net']['status']),
            'utctotz'=>array(
                'start_utc_text'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'start_time_text'=>array('timezone'=>'UTC', 'format'=>$time_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['nets']) ) {
        $nets = $rc['nets'];
        $net_ids = array();
        foreach($nets as $iid => $net) {
            $net_ids[] = $net['id'];
        }
    } else {
        $nets = array();
        $net_ids = array();
    }

    return array('stat'=>'ok', 'nets'=>$nets, 'nplist'=>$net_ids);
}
?>
