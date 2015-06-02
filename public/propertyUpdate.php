<?php
//
// Description
// ===========
// This method will update an property in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_propertyrentals_propertyUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'property_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Property'), 
		'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'), 
		'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
		'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
		'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
		'address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address 1'), 
		'address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address 2'), 
		'city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'City'), 
		'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Province'), 
		'postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Postal'), 
		'latitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Latitude'), 
		'longitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Longitude'), 
		'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'), 
		'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
		'sqft'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Square Footage'), 
		'owner'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Owner'), 
		'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'propertyrentals', 'private', 'checkAccess');
    $rc = ciniki_propertyrentals_checkAccess($ciniki, $args['business_id'], 'ciniki.propertyrentals.propertyUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the existing property details
	//
	$strsql = "SELECT uuid FROM ciniki_propertyrentals "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['property_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.propertyrentals', 'property');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['property']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2425', 'msg'=>'Property not found'));
	}
	$property = $rc['property'];

	if( isset($args['title']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
		//
		// Make sure the permalink is unique
		//
		$strsql = "SELECT id, name, permalink "
			. "FROM ciniki_propertyrentals "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['property_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.propertyrentals', 'property');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2424', 'msg'=>'You already have an property with this name, please choose another name'));
		}
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.propertyrentals');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Update the property in the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.propertyrentals.property', $args['property_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.propertyrentals');
		return $rc;
	}

	//
	// Update the categories
	//
	if( isset($args['categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.propertyrentals', 'tag', $args['business_id'],
			'ciniki_propertyrental_tags', 'ciniki_propertyrental_history',
			'property_id', $args['property_id'], 10, $args['categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.propertyrentals');
			return $rc;
		}
	}

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.propertyrentals');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'propertyrentals');

	return array('stat'=>'ok');
}
?>
