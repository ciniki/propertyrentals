<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_propertyrentals_web_categories($ciniki, $settings, $business_id, $args) {

	//
	// Get the list of category names
	//
	$rsp = array('stat'=>'ok');
	$strsql = "SELECT DISTINCT ciniki_propertyrental_tags.tag_name AS name, "
		. "ciniki_propertyrental_tags.permalink "
		. "FROM ciniki_propertyrental_tags, ciniki_propertyrentals "
		. "WHERE ciniki_propertyrental_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_propertyrental_tags.tag_type = '10' "
		. "AND ciniki_propertyrental_tags.property_id = ciniki_propertyrentals.id "
		. "AND ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_propertyrentals.flags&0x01) = 1 "
		. "ORDER BY tag_name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
		array('container'=>'categories', 'fname'=>'permalink',
			'fields'=>array('permalink', 'name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['categories']) ) {
		$rsp['categories'] = $rc['categories'];
	}

	return $rsp;
}
?>
