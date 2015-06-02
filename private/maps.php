<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_propertyrentals_maps($ciniki, $modules) {
	$maps = array();
	$maps['property'] = array(
		'status'=>array(
			'10'=>'Available',
			'20'=>'Rented',
			'50'=>'Archived',
			'60'=>'Deleted',
			),
		'flags'=>array(
			0x01=>'Visible',
			),
		);

	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
