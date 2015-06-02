//
// The app to add/edit property rentals images
//
function ciniki_propertyrentals_images() {
	this.webFlags = {
		'1':{'name':'Hidden'},
		};
	this.init = function() {
		//
		// The panel to display the edit form
		//
		this.edit = new M.panel('Edit Image',
			'ciniki_propertyrentals_images', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.propertyrentals.images.edit');
		this.edit.default_data = {};
		this.edit.data = {};
		this.edit.property_id = 0;
		this.edit.sections = {
			'_image':{'label':'Image', 'fields':{
				'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'info':{'label':'Information', 'type':'simpleform', 'fields':{
				'name':{'label':'Title', 'type':'text'},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
				}},
			'_description':{'label':'Description', 'type':'simpleform', 'fields':{
				'description':{'label':'', 'type':'textarea', 'size':'medium', 'hidelabel':'yes'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_propertyrentals_images.saveImage();'},
				'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_propertyrentals_images.deleteImage();'},
				}},
		};
		this.edit.fieldValue = function(s, i, d) { 
			if( this.data[i] != null ) {
				return this.data[i]; 
			} 
			return ''; 
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.propertyrentals.propertyImageHistory', 'args':{'business_id':M.curBusinessID, 
				'property_image_id':this.property_image_id, 'field':i}};
		};
		this.edit.addDropImage = function(iid) {
			M.ciniki_propertyrentals_images.edit.setFieldValue('image_id', iid, null, null);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_propertyrentals_images.saveImage();');
		this.edit.addClose('Cancel');
	};

	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create container
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_propertyrentals_images', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}

		if( args.add != null && args.add == 'yes' ) {
			this.showEdit(cb, 0, args.property_id);
		} else if( args.property_image_id != null && args.property_image_id > 0 ) {
			this.showEdit(cb, args.property_image_id);
		}
		return false;
	}

	this.showEdit = function(cb, iid, eid) {
		if( iid != null ) {
			this.edit.property_image_id = iid;
		}
		if( eid != null ) {
			this.edit.property_id = eid;
		}
		if( this.edit.property_image_id > 0 ) {
			this.edit.reset();
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.propertyrentals.propertyImageGet', 
				{'business_id':M.curBusinessID, 'property_image_id':this.edit.property_image_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_propertyrentals_images.edit.data = rsp.image;
					M.ciniki_propertyrentals_images.edit.refresh();
					M.ciniki_propertyrentals_images.edit.show(cb);
				});
		} else {
			this.edit.reset();
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.data = {};
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveImage = function() {
		if( this.edit.property_image_id > 0 ) {
			var c = this.edit.serializeFormData('no');
			if( c != '' ) {
				var rsp = M.api.postJSONFormData('ciniki.propertyrentals.propertyImageUpdate', 
					{'business_id':M.curBusinessID, 
					'property_image_id':this.edit.property_image_id}, c,
						function(rsp) {
							if( rsp.stat != 'ok' ) {
								M.api.err(rsp);
								return false;
							} else {
								M.ciniki_propertyrentals_images.edit.close();
							}
						});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeFormData('yes');
			var rsp = M.api.postJSONFormData('ciniki.propertyrentals.propertyImageAdd', 
				{'business_id':M.curBusinessID, 'property_id':this.edit.property_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} else {
							M.ciniki_propertyrentals_images.edit.close();
						}
					});
		}
	};

	this.deleteImage = function() {
		if( confirm('Are you sure you want to delete this image?') ) {
			var rsp = M.api.getJSONCb('ciniki.propertyrentals.propertyImageDelete', {'business_id':M.curBusinessID, 
				'property_image_id':this.edit.property_image_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_propertyrentals_images.edit.close();
				});
		}
	};
}
