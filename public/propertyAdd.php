<?php
//
// Description
// -----------
// This method will add a new property for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to add the property to.
// name:            The name of the property.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_propertyrentals_propertyAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Status'), 
        'flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Options'), 
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'), 
        'address1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address 1'), 
        'address2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address 2'), 
        'city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'City'), 
        'province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Province'), 
        'postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Postal'), 
        'latitude'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Latitude'), 
        'longitude'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Longitude'), 
        'synopsis'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Synopsis'), 
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
        'sqft'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Square Footage'), 
        'owner'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'propertyrentals', 'private', 'checkAccess');
    $rc = ciniki_propertyrentals_checkAccess($ciniki, $args['business_id'], 'ciniki.propertyrentals.propertyAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($args['permalink']) || $args['permalink'] == '' ) {  
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
    }

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id "
        . "FROM ciniki_propertyrentals "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.propertyrentals', 'property');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.propertyrentals.4', 'msg'=>'You already have an property with this name, please choose another name'));
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
    // Add the property to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.propertyrentals.property', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.propertyrentals');
        return $rc;
    }
    $property_id = $rc['id'];

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.propertyrentals', 'tag', $args['business_id'],
            'ciniki_propertyrental_tags', 'ciniki_propertyrental_history',
            'property_id', $property_id, 10, $args['categories']);
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

    return array('stat'=>'ok', 'id'=>$property_id);
}
?>
