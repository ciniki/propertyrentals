//
// This app will handle the listing, additions and deletions of properties.  These are associated tenant.
//
function ciniki_propertyrentals_main() {
    //
    // Panels
    //
    this.regFlags = {
        '1':{'name':'Track Registrations'},
        '2':{'name':'Online Registrations'},
        };
    this.init = function() {
        //
        // properties panel
        //
        this.menu = new M.panel('Properties',
            'ciniki_propertyrentals_main', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.propertyrentals.main.menu');
        this.menu.status = 10;
        this.menu.sections = {
            'status':{'label':'', 'type':'paneltabs', 'selected':'10', 'tabs':{
                '10':{'label':'Available', 'fn':'M.ciniki_propertyrentals_main.showMenu(null,10);'},
                '20':{'label':'Rented', 'fn':'M.ciniki_propertyrentals_main.showMenu(null,20);'},
                '50':{'label':'Archived', 'fn':'M.ciniki_propertyrentals_main.showMenu(null,50);'},
                }},
            'properties':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':['multiline'],
                'sortable':'yes',
                'sortTypes':['text'],
                'noData':'No properties found',
                'addTxt':'Add Property',
                'addFn':'M.ciniki_propertyrentals_main.propertyEdit(\'M.ciniki_propertyrentals_main.showMenu();\',0);',
                },
            };
        this.menu.sectionData = function(s) { return this.data[s]; }
        this.menu.noData = function(s) { return this.sections[s].noData; }
        this.menu.cellValue = function(s, i, j, d) {
            if( j == 0 ) { return d.property.title; }
        };
        this.menu.rowFn = function(s, i, d) {
            return 'M.ciniki_propertyrentals_main.showProperty(\'M.ciniki_propertyrentals_main.showMenu();\',\'' + d.property.id + '\');';
        };
        this.menu.addButton('add', 'Add', 'M.ciniki_propertyrentals_main.propertyEdit(\'M.ciniki_propertyrentals_main.showMenu();\',0);');
        this.menu.addClose('Back');

        //
        // The property panel 
        //
        this.property = new M.panel('Property',
            'ciniki_propertyrentals_main', 'property',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.propertyrentals.main.property');
        this.property.data = {};
        this.property.property_id = 0;
        this.property.sections = {
            '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
                }},
            'info':{'label':'', 'aside':'yes', 'list':{
                'title':{'label':'Title'},
                'status_text':{'label':'Status'},
                'flags_text':{'label':'Website'},
                'sqft':{'label':'Square Footage'},
                'owner':{'label':'Owner'},
                'categories_text':{'label':'Categories', 'visible':'no'},
                }},
            'address':{'label':'', 'aside':'yes', 'list':{
                'address':{'label':'Address'},
                }},
            'synopsis':{'label':'Synopsis', 'type':'htmlcontent'},
            'description':{'label':'Description', 'type':'htmlcontent'},
            'notes':{'label':'Description', 'type':'htmlcontent'},
            'images':{'label':'Gallery', 'type':'simplethumbs'},
            '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'addTxt':'Add Additional Image',
                'addFn':'M.startApp(\'ciniki.propertyrentals.images\',null,\'M.ciniki_propertyrentals_main.showProperty();\',\'mc\',{\'property_id\':M.ciniki_propertyrentals_main.property.property_id,\'add\':\'yes\'});',
                },
            '_buttons':{'label':'', 'buttons':{
                'edit':{'label':'Edit', 'fn':'M.ciniki_propertyrentals_main.propertyEdit(\'M.ciniki_propertyrentals_main.showProperty();\',M.ciniki_propertyrentals_main.property.property_id);'},
                }},
        };
        this.property.addDropImage = function(iid) {
            var rsp = M.api.getJSON('ciniki.propertyrentals.propertyImageAdd',
                {'tnid':M.curTenantID, 'image_id':iid, 'property_id':M.ciniki_propertyrentals_main.property.property_id});
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            return true;
        };
        this.property.addDropImageRefresh = function() {
            if( M.ciniki_propertyrentals_main.property.property_id > 0 ) {
                var rsp = M.api.getJSONCb('ciniki.propertyrentals.propertyGet', {'tnid':M.curTenantID, 
                    'property_id':M.ciniki_propertyrentals_main.property.property_id, 'images':'yes'}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_propertyrentals_main.property;
                        p.data.images = rsp.property.images;
                        p.refreshSection('images');
                    });
            }
        };
        this.property.sectionData = function(s) {
            if( s == 'synopsis' || s == 'description' ) { return this.data[s].replace(/\n/g, '<br/>'); }
            if( s == 'info' || s == 'address' ) { return this.sections[s].list; }
            return this.data[s];
        };
        this.property.listLabel = function(s, i, d) { return d.label; };
        this.property.listValue = function(s, i, d) {
            return this.data[i];
        };
        this.property.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.property.thumbFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.propertyrentals.images\',null,\'M.ciniki_propertyrentals_main.showProperty();\',\'mc\',{\'property_image_id\':\'' + d.image.id + '\'});';
        };
        this.property.addButton('edit', 'Edit', 'M.ciniki_propertyrentals_main.propertyEdit(\'M.ciniki_propertyrentals_main.showProperty();\',M.ciniki_propertyrentals_main.property.property_id);');
        this.property.addClose('Back');
        this.property.addLeftButton('website', 'Preview', 'M.showWebsite(\'/properties/\'+M.ciniki_propertyrentals_main.property.data.permalink);');

        //
        // The panel for a site's menu
        //
        this.edit = new M.panel('Property',
            'ciniki_propertyrentals_main', 'edit',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.propertyrentals.main.edit');
        this.edit.data = null;
        this.edit.property_id = 0;
        this.edit.sections = { 
            '_image':{'label':'Image', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
                }},
            'general':{'label':'General', 'aside':'yes', 'fields':{
                'title':{'label':'Title', 'hint':'Property Title', 'type':'text'},
                'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '20':'Rented', '50':'Archived'}},
                'sqft':{'label':'Sqft', 'hint':'', 'type':'text', 'size':'small'},
                'owner':{'label':'Owner', 'hint':'', 'type':'text'},
                'flags':{'label':'Website', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
                }}, 
            '_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
                }},
            'address':{'label':'Address', 'aside':'yes', 'fields':{
                'address1':{'label':'Address', 'type':'text'},
                'address2':{'label':'', 'type':'text'},
                'city':{'label':'City', 'type':'text'},
                'province':{'label':'Province', 'type':'text'},
                'postal':{'label':'Postal', 'type':'text'},
                }},
            '_map':{'label':'Location Map', 'aside':'yes', 'visible':'yes', 'fields':{
                'latitude':{'label':'Latitude', 'type':'text', 'size':'small'},
                'longitude':{'label':'Longitude', 'type':'text', 'size':'small'},
                }},
            '_map_buttons':{'label':'', 'aside':'yes', 'buttons':{
                '_latlong':{'label':'Lookup Lat/Long', 'fn':'M.ciniki_propertyrentals_main.edit.lookupLatLong();'},
                }},
            '_synopsis':{'label':'Synopsis', 'fields':{
                'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
                }},
            '_description':{'label':'Description', 'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
                }},
            '_notes':{'label':'Notes', 'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_propertyrentals_main.saveProperty();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_propertyrentals_main.removeProperty();'},
                }},
            };  
        this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.propertyrentals.propertyHistory', 'args':{'tnid':M.curTenantID, 
                'property_id':this.property_id, 'field':i}};
        }
        this.edit.lookupLatLong = function() {
            M.startLoad();
            if( document.getElementById('googlemaps_js') == null) {
                var script = document.createElement("script");
                script.id = 'googlemaps_js';
                script.type = "text/javascript";
                script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curTenant.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_propertyrentals_main.edit.lookupGoogleLatLong";
                document.body.appendChild(script);
            } else {
                this.lookupGoogleLatLong();
            }
        };
        this.edit.lookupGoogleLatLong = function() {
            var address = this.formValue('address1') + ', ' + this.formValue('address2') + ', ' + this.formValue('city') + ', ' + this.formValue('province');
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode( { 'address': address}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    M.ciniki_propertyrentals_main.edit.setFieldValue('latitude', results[0].geometry.location.lat());
                    M.ciniki_propertyrentals_main.edit.setFieldValue('longitude', results[0].geometry.location.lng());
                    M.stopLoad();
                } else {
                    M.alert('We were unable to lookup your latitude/longitude, please check your address: ' + status);
                    M.stopLoad();
                }
            }); 
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_propertyrentals_main.edit.setFieldValue('primary_image_id', iid, null, null);
            return true;
        };
        this.edit.deleteImage = function(fid) {
            this.setFieldValue(fid, 0, null, null);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_propertyrentals_main.saveProperty();');
        this.edit.addClose('Cancel');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_propertyrentals_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        // Categories enabled
        if( M.curTenant.modules['ciniki.propertyrentals'] != null 
            && (M.curTenant.modules['ciniki.propertyrentals'].flags&0x01) ) {
            this.menu.size = 'medium narrowaside';
//          this.menu.sections.categories.visible = 'yes';
            this.property.sections.info.list.categories_text.visible = 'yes';
            this.edit.sections._categories.active = 'yes';
            this.edit.sections.address.aside = 'no';
            this.edit.sections._map.aside = 'no';
            this.edit.sections._map_buttons.aside = 'no';
        } else {
            this.menu.size = 'medium';
//          this.menu.sections.categories.visible = 'no';
            this.property.sections.info.list.categories_text.visible = 'no';
            this.edit.sections._categories.active = 'no';
            this.edit.sections.address.aside = 'yes';
            this.edit.sections._map.aside = 'yes';
            this.edit.sections._map_buttons.aside = 'yes';
        }

        this.menu.tag_type = '10';
        this.menu.tag_permalink = '';
        this.showMenu(cb);
    }

    this.showMenu = function(cb, status) {
        this.menu.data = {};
        if( status != null ) {
            this.menu.status = status;
            this.menu.sections.status.selected = status;
        }
        M.api.getJSONCb('ciniki.propertyrentals.propertyList', {'tnid':M.curTenantID, 'status':this.menu.status}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_propertyrentals_main.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };

    this.showProperty = function(cb, eid) {
        this.property.reset();
        if( eid != null ) { this.property.property_id = eid; }
        var rsp = M.api.getJSONCb('ciniki.propertyrentals.propertyGet', {'tnid':M.curTenantID, 
            'property_id':this.property.property_id, 'images':'yes', 'categories':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_propertyrentals_main.property;
                p.data = rsp.property;
                p.data.address = M.formatAddress({
                    'address1':p.data.address1,
                    'address2':p.data.address2,
                    'city':p.data.city,
                    'province':p.data.province,
                    'postal':p.data.postal,
                    });
                if( rsp.property.categories != null ) {
                    p.data.categories_text = rsp.property.categories.replace(/::/, ', ');
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.propertyEdit = function(cb, eid) {
        this.edit.reset();
        if( eid != null ) {
            this.edit.property_id = eid;
        }

        this.edit.sections._buttons.buttons.delete.visible = (this.edit.property_id>0?'yes':'no');
        this.edit.reset();
        M.api.getJSONCb('ciniki.propertyrentals.propertyGet', {'tnid':M.curTenantID, 
            'property_id':this.edit.property_id, 'categories':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_propertyrentals_main.edit;
                p.data = rsp.property;
                p.sections._categories.fields.categories.tags = [];
                if( rsp.categories != null ) {
                    for(i in rsp.categories) {
                        p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
                    }
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.saveProperty = function() {
        if( this.edit.property_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.propertyrentals.propertyUpdate', 
                    {'tnid':M.curTenantID, 'property_id':M.ciniki_propertyrentals_main.edit.property_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_propertyrentals_main.edit.close();
                    });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.propertyrentals.propertyAdd', 
                    {'tnid':M.curTenantID}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        if( rsp.id > 0 ) {
                            var cb = M.ciniki_propertyrentals_main.edit.cb;
                            M.ciniki_propertyrentals_main.edit.close();
                            M.ciniki_propertyrentals_main.showProperty(cb, rsp.id);
                        } else {
                            M.ciniki_propertyrentals_main.edit.close();
                        }
                    });
            } else {
                this.edit.close();
            }
        }
    };

    this.removeProperty = function() {
        M.confirm("Are you sure you want to remove '" + this.property.data.name + "' as an property ?",null,function() {
            M.api.getJSONCb('ciniki.propertyrentals.propertyDelete', 
                {'tnid':M.curTenantID, 'property_id':M.ciniki_propertyrentals_main.property.property_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_propertyrentals_main.property.close();
                });
        });
    }
};
