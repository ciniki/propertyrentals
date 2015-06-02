<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the propertyrental to.
// property_image_id:	The ID of the propertyrental image to get.
//
// Returns
// -------
//
function ciniki_propertyrentals_propertyImageGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'property_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Property Image'),
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
    $rc = ciniki_propertyrentals_checkAccess($ciniki, $args['business_id'], 'ciniki.propertyrentals.propertyImageGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the main information
	//
	$strsql = "SELECT ciniki_propertyrental_images.id, "
		. "ciniki_propertyrental_images.name, "
		. "ciniki_propertyrental_images.permalink, "
		. "ciniki_propertyrental_images.webflags, "
		. "ciniki_propertyrental_images.image_id, "
		. "ciniki_propertyrental_images.description "
		. "FROM ciniki_propertyrental_images "
		. "WHERE ciniki_propertyrental_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_propertyrental_images.id = '" . ciniki_core_dbQuote($ciniki, $args['property_image_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
		array('container'=>'images', 'fname'=>'id', 'name'=>'image',
			'fields'=>array('id', 'name', 'permalink', 'webflags', 'image_id', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['images']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'2397', 'msg'=>'Unable to find image'));
	}
	$image = $rc['images'][0]['image'];
	
	return array('stat'=>'ok', 'image'=>$image);
}
?>
