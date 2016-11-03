<?php
//
// Description
// ===========
// This method will return all the information about an property.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the property is attached to.
// property_id:     The ID of the property to get the details for.
// 
// Returns
// -------
//
function ciniki_propertyrentals_propertyGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'property_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Property'), 
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
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
    $rc = ciniki_propertyrentals_checkAccess($ciniki, $args['business_id'], 'ciniki.propertyrentals.propertyGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // Load the business intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load property rental maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'propertyrentals', 'private', 'maps');
    $rc = ciniki_propertyrentals_maps($ciniki, $modules);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    if( $args['property_id'] == 0 ) {
        $property = array('id'=>0,
            'title'=>'',
            'permalink'=>'',
            'status'=>'10',
            'flags'=>'1',
            'primary_image_id'=>'0',
            'address1'=>'',
            'address2'=>'',
            'city'=>'',
            'province'=>'',
            'postal'=>'',
            'latitude'=>'',
            'longitude'=>'',
            'synopsis'=>'',
            'description'=>'',
            'sqft'=>'',
            'ammenities'=>'',
            'owner'=>'',
            'notes'=>'',
            'images'=>array(),
            );
    } else {
        $strsql = "SELECT ciniki_propertyrentals.id, "
            . "ciniki_propertyrentals.title, "
            . "ciniki_propertyrentals.permalink, "
            . "ciniki_propertyrentals.status, "
            . "ciniki_propertyrentals.status AS status_text, "
            . "ciniki_propertyrentals.flags, "
            . "ciniki_propertyrentals.flags AS flags_text, "
            . "ciniki_propertyrentals.primary_image_id, "
            . "ciniki_propertyrentals.address1, "
            . "ciniki_propertyrentals.address2, "
            . "ciniki_propertyrentals.city, "
            . "ciniki_propertyrentals.province, "
            . "ciniki_propertyrentals.postal, "
            . "ciniki_propertyrentals.latitude, "
            . "ciniki_propertyrentals.longitude, "
            . "ciniki_propertyrentals.synopsis, "
            . "ciniki_propertyrentals.description, "
            . "ciniki_propertyrentals.sqft, "
            . "ciniki_propertyrentals.ammenities, "
            . "ciniki_propertyrentals.owner, "
            . "ciniki_propertyrentals.notes "
            . "";
        if( isset($args['images']) && $args['images'] == 'yes' ) {
            $strsql .= ", "
                . "ciniki_propertyrental_images.id AS img_id, "
                . "ciniki_propertyrental_images.name AS image_name, "
                . "ciniki_propertyrental_images.webflags AS image_webflags, "
                . "ciniki_propertyrental_images.image_id, "
                . "ciniki_propertyrental_images.description AS image_description "
                . "";
        }
        $strsql .= "FROM ciniki_propertyrentals ";
        if( isset($args['images']) && $args['images'] == 'yes' ) {
            $strsql .= "LEFT JOIN ciniki_propertyrental_images ON (ciniki_propertyrentals.id = ciniki_propertyrental_images.property_id "
                . "AND ciniki_propertyrental_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") ";
        }
        $strsql .= "WHERE ciniki_propertyrentals.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_propertyrentals.id = '" . ciniki_core_dbQuote($ciniki, $args['property_id']) . "' "
            . "";
        
        if( isset($args['images']) && $args['images'] == 'yes' ) {
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
                array('container'=>'propertyrentals', 'fname'=>'id', 'name'=>'property',
                    'fields'=>array('id', 'title', 'permalink', 'status', 'status_text', 'flags', 'flags_text', 'primary_image_id', 
                        'address1', 'address2', 'city', 'province', 'postal', 'latitude', 'longitude',
                        'synopsis', 'description', 'sqft', 'ammenities', 'owner', 'notes'),
                    'maps'=>array('status_text'=>$maps['property']['status'],
                        'flags_text'=>$maps['property']['flags'],
                        )),
                array('container'=>'images', 'fname'=>'img_id', 'name'=>'image',
                    'fields'=>array('id'=>'img_id', 'name'=>'image_name', 'webflags'=>'image_webflags',
                        'image_id', 'description'=>'image_description')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['propertyrentals']) || !isset($rc['propertyrentals'][0]) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.propertyrentals.6', 'msg'=>'Unable to find property'));
            }
            $property = $rc['propertyrentals'][0]['property'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            if( isset($property['images']) ) {
                foreach($property['images'] as $img_id => $img) {
                    if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                        $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $img['image']['image_id'], 75);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $property['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                    }
                }
            }
        } else {
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
                array('container'=>'propertyrentals', 'fname'=>'id', 'name'=>'property',
                    'fields'=>array('id', 'title', 'permalink', 'status', 'flags', 'primary_image_id', 
                        'address1', 'address2', 'city', 'province', 'postal', 'latitude', 'longitude',
                        'synopsis', 'description', 'sqft', 'ammenities', 'owner', 'notes')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['propertyrentals']) || !isset($rc['propertyrentals'][0]) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.propertyrentals.7', 'msg'=>'Unable to find property'));
            }
            $property = $rc['propertyrentals'][0]['property'];
        }

        //
        // Get the categories and tags for the post
        //
        if( ($ciniki['business']['modules']['ciniki.propertyrentals']['flags']&0x10) > 0 ) {
            $strsql = "SELECT tag_type, tag_name AS lists "
                . "FROM ciniki_propertyrental_tags "
                . "WHERE property_id = '" . ciniki_core_dbQuote($ciniki, $args['property_id']) . "' "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "ORDER BY tag_type, tag_name "
                . "";
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.propertyrentals', array(
                array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                    'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['tags']) ) {
                foreach($rc['tags'] as $tags) {
                    if( $tags['tags']['tag_type'] == 10 ) {
                        $property['categories'] = $tags['tags']['lists'];
                    }
                }
            }
        }
    }

    if( $property['latitude'] == 0 ) {
        $property['latitude'] = '';
    }
    if( $property['longitude'] == 0 ) {
        $property['longitude'] = '';
    }

    $rsp = array('stat'=>'ok', 'property'=>$property);

    //
    // Check if all tags should be returned
    //
    $rsp['categories'] = array();
    if( ($ciniki['business']['modules']['ciniki.propertyrentals']['flags']&0x10) > 0
        && isset($args['categories']) && $args['categories'] == 'yes' 
        ) {
        //
        // Get the available tags
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.propertyrentals', $args['business_id'], 
            'ciniki_propertyrental_tags', 10);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.propertyrentals.8', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['categories'] = $rc['tags'];
        }
    }

    return $rsp;
}
?>
