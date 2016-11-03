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
function ciniki_propertyrentals_web_propertyDetails($ciniki, $settings, $business_id, $permalink) {

    
//  print "<pre>" . print_r($ciniki, true) . "</pre>";
    //
    // Load INTL settings
    //
/*  ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];
*/
    $strsql = "SELECT ciniki_propertyrentals.id, "
        . "ciniki_propertyrentals.title, "
        . "ciniki_propertyrentals.permalink, "
        . "ciniki_propertyrentals.primary_image_id, "
        . "ciniki_propertyrentals.sqft, "
        . "ciniki_propertyrentals.owner, "
        . "ciniki_propertyrentals.synopsis, "
        . "ciniki_propertyrentals.description, "
        . "ciniki_propertyrental_images.image_id, "
        . "ciniki_propertyrental_images.name AS image_name, "
        . "ciniki_propertyrental_images.permalink AS image_permalink, "
        . "ciniki_propertyrental_images.description AS image_description, "
        . "UNIX_TIMESTAMP(ciniki_propertyrental_images.last_updated) AS image_last_updated "
        . "FROM ciniki_propertyrentals "
        . "LEFT JOIN ciniki_propertyrental_images ON ("
            . "ciniki_propertyrentals.id = ciniki_propertyrental_images.property_id "
            . "AND ciniki_propertyrental_images.image_id > 0 "
            . "AND (ciniki_propertyrental_images.webflags&0x01) = 0 "
            . ") "
        . "WHERE ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_propertyrentals.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "AND (ciniki_propertyrentals.flags&0x01) = 1 "    // Visible on website
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artclub', array(
        array('container'=>'properties', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id', 
            'sqft', 'owner', 'synopsis', 'description')),
        array('container'=>'images', 'fname'=>'image_id', 
            'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
                'description'=>'image_description', 'last_updated'=>'image_last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['properties']) || count($rc['properties']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.propertyrentals.18', 'msg'=>"I'm sorry, but we can't find the property you requested."));
    }
    $property = array_pop($rc['properties']);

    //
    // Check if any files are attached to the property
    //
/*  $strsql = "SELECT id, name, extension, permalink, description "
        . "FROM ciniki_propertyrental_files "
        . "WHERE ciniki_propertyrental_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_propertyrental_files.property_id = '" . ciniki_core_dbQuote($ciniki, $property['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
        array('container'=>'files', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'extension', 'permalink', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['files']) ) {
        $property['files'] = $rc['files'];
    } */

    return array('stat'=>'ok', 'property'=>$property);
}
?>
