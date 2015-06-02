<?php
//
// Description
// -----------
// This method will return the list of propertyrentals for a business.  It is restricted
// to business owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get propertyrentals for.
//
// Returns
// -------
//
function ciniki_propertyrentals_propertyList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
		'tag_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
		'tag_permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'), 
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'propertyrentals', 'private', 'checkAccess');
    $rc = ciniki_propertyrentals_checkAccess($ciniki, $args['business_id'], 'ciniki.propertyrentals.propertyList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	$rsp = array('stat'=>'ok');



	//
	// If categories are also to be returned
	//
	if( isset($args['categories']) && $args['categories'] == 'yes' ) {
		$rsp['tag_name'] = 'Uncategorized';

		//
		// Get the distinct list of tags
		//
		$strsql = "SELECT ciniki_propertyrental_tags.tag_name, "
			. "ciniki_propertyrental_tags.permalink, "
			. "COUNT(ciniki_propertyrentals.id) AS num_properties "
			. "FROM ciniki_propertyrental_tags "
			. "LEFT JOIN ciniki_propertyrentals ON ("
				. "ciniki_propertyrental_tags.property_id = ciniki_propertyrentals.id "
				. "AND ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_propertyrental_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_propertyrental_tags.tag_type = '10' "
			. "GROUP BY ciniki_propertyrental_tags.permalink "
			. "ORDER BY ciniki_propertyrental_tags.tag_name COLLATE latin1_general_cs "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
			array('container'=>'tags', 'fname'=>'tag_name', 'name'=>'tag',
				'fields'=>array('tag_name', 'permalink', 'num_properties')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			$rsp['categories'] = $rc['tags'];
		}
		if( isset($args['tag_permalink']) && $args['tag_permalink'] != '' ) {
			foreach($rsp['categories'] as $cid => $cat) {
				if( $cat['tag']['permalink'] == $args['tag_permalink'] ) {
					$rsp['tag_name'] = $cat['tag']['tag_name'];
				}
			}
		}

		//
		// Check for any uncategorized property rentals
		//
		$strsql = "SELECT COUNT(ciniki_propertyrentals.id) AS num_properties, ciniki_propertyrental_tags.tag_name "
			. "FROM ciniki_propertyrentals "
			. "LEFT JOIN ciniki_propertyrental_tags ON ("
				. "ciniki_propertyrentals.id = ciniki_propertyrental_tags.property_id "
				. "AND ciniki_propertyrental_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_propertyrental_tags.tag_type = '10' "
				. ") "
			. "WHERE ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ISNULL(tag_name) "
			. "GROUP BY tag_name "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.propertyrentals', 'uncategorized');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['uncategorized']) ) {
			$rsp['categories'][] = array('tag'=>array('tag_name'=>'Uncategorized', 'permalink'=>'', 'num_properties'=>$rc['uncategorized']['num_properties']));
		}
		// No uncategorized property rentals, show the first category
		if( (!isset($rc['uncategorized']['num_properties']) || $rc['uncategorized']['num_properties'] == 0) 
			&& count($rsp['categories']) > 0 
			&& (!isset($args['tag_permalink']) || $args['tag_permalink'] == '') 
			) {
			$args['tag_permalink'] = $rsp['categories'][0]['tag']['permalink'];
			$rsp['tag_name'] = $rsp['categories'][0]['tag']['tag_name'];
		}

		$rsp['tag_permalink'] = $args['tag_permalink'];
	}
	
	//
	// Load the property rentals
	//
	if( isset($args['tag_type']) && $args['tag_type'] != '' 
		&& isset($args['tag_permalink']) && $args['tag_permalink'] != '' 
		) {
		$strsql = "SELECT ciniki_propertyrentals.id, "
			. "ciniki_propertyrentals.title, "
			. "ciniki_propertyrentals.status, "
			. "ciniki_propertyrentals.synopsis, "
			. "ciniki_propertyrentals.description, "
			. "FROM ciniki_propertyrental_tags, ciniki_propertyrentals "
			. "WHERE ciniki_propertyrental_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_propertyrental_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
			. "AND ciniki_propertyrental_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
			. "AND ciniki_propertyrental_tags.property_id = ciniki_propertyrentals.id "
			. "AND ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		if( isset($args['status']) && $args['status'] != '' ) {
			$strsql .= "AND ciniki_propertyrentals.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
		}
		$strsql .= "ORDER BY ciniki_propertyrentals.title ASC "
			. "";
	} elseif( isset($args['tag_type']) && $args['tag_type'] != '' 
		&& isset($args['tag_permalink']) && $args['tag_permalink'] == '' 
		) {
		$strsql = "SELECT ciniki_propertyrentals.id, "
			. "ciniki_propertyrentals.title, "
			. "ciniki_propertyrentals.status, "
			. "ciniki_propertyrentals.synopsis, "
			. "ciniki_propertyrentals.description, "
			. "ciniki_propertyrental_tags.tag_name "
			. "FROM ciniki_propertyrentals "
			. "LEFT JOIN ciniki_propertyrental_tags ON ("
				. "ciniki_propertyrentals.id = ciniki_propertyrental_tags.property_id "
				. "AND ciniki_propertyrental_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
				. "AND ciniki_propertyrental_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		if( isset($args['status']) && $args['status'] != '' ) {
			$strsql .= "AND ciniki_propertyrentals.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
		}
		$strsql .= "AND ISNULL(tag_name) "
			. "ORDER BY ciniki_propertyrentals.title ASC "
			. "";
	} else {
		$strsql = "SELECT ciniki_propertyrentals.id, "
			. "ciniki_propertyrentals.title, "
			. "ciniki_propertyrentals.status, "
			. "ciniki_propertyrentals.synopsis, "
			. "ciniki_propertyrentals.description "
			. "FROM ciniki_propertyrentals "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		if( isset($args['status']) && $args['status'] != '' ) {
			$strsql .= "AND ciniki_propertyrentals.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
		}
		$strsql .= "ORDER BY ciniki_propertyrentals.title ASC "
			. "";
	}

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
		array('container'=>'properties', 'fname'=>'id', 'name'=>'property',
			'fields'=>array('id', 'title', 'status', 'synopsis', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['properties']) ) {
		$rsp['properties'] = $rc['properties'];
	} else {
		$rsp['properties'] = array();
	}
	
	return $rsp;
}
?>
