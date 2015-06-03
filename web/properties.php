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
function ciniki_propertyrentals_web_properties($ciniki, $settings, $business_id, $args) {

	$strsql = "SELECT ciniki_propertyrentals.id, "
		. "ciniki_propertyrentals.title, "
		. "ciniki_propertyrentals.permalink, "
		. "ciniki_propertyrentals.synopsis, "
		. "'yes' AS is_details, "
		. "ciniki_propertyrentals.primary_image_id "
		. "";
	if( isset($args['tag_type']) && $args['tag_type'] != '' 
		&& isset($args['tag_permalink']) && $args['tag_permalink'] != '' 
		) {
		$strsql .= "FROM ciniki_propertyrental_tags "
			. "INNER JOIN ciniki_propertyrentals ON ("
				. "ciniki_propertyrental_tags.propertyrental_id = ciniki_propertyrentals.id "
				. "AND ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_propertyrental_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_propertyrental_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
			. "AND ciniki_propertyrental_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
			. "";
	} else {
		$strsql .= "FROM ciniki_propertyrentals "
			. "WHERE ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
	}
	$strsql .= "AND (ciniki_propertyrentals.flags&0x01) = 1 ";		// Visible on website
	if( isset($args['status']) && $args['status'] != '' ) {
		$strsql .= "AND ciniki_propertyrentals.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
	}
	$strsql .= "ORDER BY ciniki_propertyrentals.title ";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 && is_int($args['limit']) ) {
		$strsql .= "LIMIT " . $args['limit'] . " ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
		array('container'=>'list', 'fname'=>'id', 
			'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id', 'is_details', 'description'=>'synopsis')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['list']) ) {
		return array('stat'=>'ok', 'list'=>array());
	}
	return array('stat'=>'ok', 'list'=>$rc['list']);
}
?>
