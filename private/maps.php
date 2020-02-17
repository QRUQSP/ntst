<?php
//
// Description
// -----------
// This function returns the int to text mappings for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_ntst_maps(&$ciniki) {
    //
    // Build the maps object
    //
    $maps = array();
    $maps['net'] = array(
        'status' => array(
            '10' => 'Pending',
            '50' => 'Running',
            '90' => 'Close',
        ),
    );

    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
