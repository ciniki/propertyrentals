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
function ciniki_propertyrentals_objects($ciniki) {
	
	$objects = array();
	$objects['property'] = array(
		'name'=>'Property',
		'sync'=>'yes',
		'table'=>'ciniki_propertyrentals',
		'fields'=>array(
			'title'=>array(),
			'permalink'=>array(),
			'status'=>array('default'=>'10'),
			'flags'=>array('default'=>'0'),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'address1'=>array('default'=>''),
			'address2'=>array('default'=>''),
			'city'=>array('default'=>''),
			'province'=>array('default'=>''),
			'postal'=>array('default'=>''),
			'latitude'=>array('default'=>''),
			'longitude'=>array('default'=>''),
			'synopsis'=>array('default'=>''),
			'descriptions'=>array('default'=>''),
			'notes'=>array('default'=>''),
			),
		'history_table'=>'ciniki_propertyrental_history',
		);
	$objects['image'] = array(
		'name'=>'Image',
		'sync'=>'yes',
		'table'=>'ciniki_propertyrental_images',
		'fields'=>array(
			'property_id'=>array('ref'=>'ciniki.propertyrentals.property'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array('default'=>'0'),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array('default'=>''),
			),
		'history_table'=>'ciniki_propertyrental_history',
		);
	$objects['tag'] = array(
		'name'=>'Tag',
		'sync'=>'yes',
		'table'=>'ciniki_propertyrental_tags',
		'fields'=>array(
			'property_id'=>array('ref'=>'ciniki.propertyrentals.property'),
			'tag_type'=>array(),
			'tag_name'=>array(),
			'permalink'=>array(),
			),
		'history_table'=>'ciniki_propertyrental_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
