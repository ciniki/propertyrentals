<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an property. 
// This method is typically used by the UI to display a list of changes that have occured 
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// property_id:			The ID of the property to get the history for.
// field:				The field to get the history for. This can be any of the elements 
//						returned by the ciniki.propertyrentals.get method.
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Property Title" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_propertyrentals_propertyHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'property_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Property'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'propertyrentals', 'private', 'checkAccess');
	$rc = ciniki_propertyrentals_checkAccess($ciniki, $args['business_id'], 'ciniki.propertyrentals.propertyHistory');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.propertyrentals', 'ciniki_propertyrentals_history', $args['business_id'], 'ciniki_propertyrentals', $args['property_id'], $args['field']);
}
?>
