(function (OC, window, $, undefined) {
'use strict';

$(document).ready(function () {
	
	Handlebars.registerHelper( 'istrue', function( variable, options ) {
		if( variable == 'true' ) {
			return options.fn( this );
		} else {
			return options.inverse( this );
		}
	});
	Handlebars.registerHelper( 'isfalse', function( variable, options ) {
		if( variable == 'true' ) {
			return options.inverse( this );
		} else {
			return options.fn( this );
		}
	});
	
	Handlebars.registerHelper( 'each_attributes', function( data, attributes, options ) {
		var ret = '';
		
		// go through every attribute
		$.each( attributes, function( key, label ) {
			// check if the attribute is present
			if( typeof( data[ key ] ) == 'undefined' || data[ key ] == null ) {
				return;
			}
			// add the attribute to the output
			ret += options.fn( { label: label, data: data[ key ] } );
		});
		
		return ret;
	});
	
	var Users = function() {
		this._baseUrl = OC.generateUrl( '/apps/ldaporg' );
		this._ldapcontacts_baseUrl = OC.generateUrl( '/apps/ldapcontacts' );
		this._users = [];
		this._groups = [];
		this._settings = [];
		this._ldapcontacts_settings = [];
		this._lastForcedGroupMembershipsSearch = '';
		this._forcedGroupMembershipsSearchId = 0;
		this._checkbox_settings = [ 'superuser_groups' ];
	};
	
	Users.prototype = {
		init: function() {
			var self = this;
			$( '#ldaporg-add-user' ).click( function() {
				self.renderContent();
			});
		},
		loadLdapContactsSettings: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the ldapcontact settings
			$.get( this._ldapcontacts_baseUrl + '/settings', function( data ) {
				if( data.status == 'success' ) {
					self._ldapcontacts_settings = data.data;
					deferred.resolve();
				}
				else {
					// settings couldn't be loaded
					deferred.reject();
				}
			}).fail( function() {
				// settings couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		},
		loadUsers: function() {
			var deferred = $.Deferred();
			var self = this;
			$.get( this._baseUrl + '/admin/load/users' ).done( function( users ) {
				// reset variables
				self._users = users;
				deferred.resolve();
			}).fail( function() {
				deferred.reject();
			});
			return deferred.promise();
		},
		renderUsers: function() {
			var self = this;
			var source = $( '#ldaporg-existing-users-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template( { users: self._users } );
			$( '#ldaporg-existing-users' ).html( html );
			
			// button for selecting user
			$( '#ldaporg-existing-users > .user > span.icon-play' ).click( function() {
				var entry_id = $( this ).attr( 'data-id' );
				// show the users details
				self.showUserDetails( entry_id );
			});
		},
		showUserDetails: function( entry_id ) {
			var user = null;
			// look for the user
			$.each( this._users, function( key, value ) {
				if( value.ldapcontacts_entry_id == entry_id ) {
					user = value;
					return;
				}
			});
			
			var source = $( '#ldaporg-user-details-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template( { user: user, attributes: this._ldapcontacts_settings.user_ldap_attributes } );
			$( '#ldaporg-user-content' ).html( html );
			this.registerModifyUserButton();
		},
		registerModifyUserButton: function() {
			var self = this;
			// button for resending welcome email
			$( '#ldaporg-user-content .button.resend-welcome-mail' ).click( function() {
				OC.msg.startSaving( '#ldaporg-user-content .msg' );
				var entry_id = $( this ).attr( 'data-id' );
				var user;
				// look for the user
				$.each( self._users, function( key, value ) {
					if( value.ldapcontacts_entry_id == entry_id ) {
						user = value;
						return;
					}
				});
				// check if the user was found
				if( typeof( user ) == 'undefined' || user == null ) {
					OC.msg.finishedError( '#ldaporg-user-content .msg' );
					return;
				}
				
				// resend the welcome email
				$.ajax({
					url: self._baseUrl + '/welcomemail',
					method: 'POST',
					contentType: 'application/json',
					data: JSON.stringify( { mail: user[ self._settings.mail_attribute ], name: user.ldapcontacts_name } )
				}).done( function( data ) {
					// show message
					OC.msg.finishedSaving( '#ldaporg-user-content .msg', data );
				}).fail( function( data ) {
					OC.msg.finishedError( '#ldaporg-user-content .msg' );
				});
			});
			
			// button for deleting a user
			$( '#ldaporg-user-content .button.delete-user' ).click( function() {
				var entry_id = $( this ).attr( 'data-id' );
				var user;
				// look for the user
				$.each( self._users, function( key, value ) {
					if( value.ldapcontacts_entry_id == entry_id ) {
						user = value;
						return;
					}
				});
				// check if the user was found
				if( typeof( user ) == 'undefined' || user == null ) {return; }
				
				// check if the user should really be deleted
				var source = $( '#ldaporg-user-delete-tpl' ).html();
				var template = Handlebars.compile( source );
				var html = template( { user: user } );
				$( '#ldaporg-user-content' ).html( html );
				
				// really deleting the user
				$( '#ldaporg-delete-user' ).click( function() {
					OC.msg.startSaving( '#ldaporg-user-content .msg' );
					// delete the selected user
					$.getJSON( self._baseUrl + '/delete/user/' + encodeURI( entry_id ), function( data ) {
						// if the deleting was successful, reload all users
						if( data.status == 'success' ) {
							self.loadUsers().done( function() {
								// render the users again
								self.renderUsers();
								// render the initial content area
								self.renderContent();
								// show a message that the user was deleted
								OC.msg.finishedSaving( '#ldaporg-user-content .msg', data );
							});
						}
						else {
							// show error message
							OC.msg.finishedSaving( '#ldaporg-user-content .msg', data );
						}
					}).fail( function() {
						OC.msg.finishedError( '#ldaporg-user-content .msg' );
					});
				});
				
				// aborting the action
				$( '#ldaporg-abort-delete-user' ).click( function() {
					// render the initial content area
					self.renderContent();
				});
			});
		},
		renderContent: function() {
			var self = this;
			var source = $( '#ldaporg-user-content-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template();
			$( '#ldaporg-user-content' ).html( html );
			
			// creating user button
			$( '#ldaporg-create-user' ).click( function( e ) {
				e.preventDefault();
				
				var data = {};
				data.firstname = $( '#ldaporg-create-user-firstname' ).val();
				data.lastname = $( '#ldaporg-create-user-lastname' ).val();
				data.mail = $( '#ldaporg-create-user-mail' ).val();
				// start saving
				OC.msg.startSaving( '#ldaporg-user-content .msg' );
				
				// create a user
				$.ajax({
					url: self._baseUrl + '/create/user',
					method: 'POST',
					contentType: 'application/json',
					data: JSON.stringify( data )
				}).done( function( data ) {
					// if creating the user was successful, reload all users
					if( data.status == 'success' ) {
						self.loadUsers().done( function() {
							// render the users again
							self.renderUsers();
							// render the initial content area
							self.renderContent();
						});
					}
					// show message
					OC.msg.finishedSaving( '#ldaporg-user-content .msg', data );
				}).fail( function() {
					OC.msg.finishedError( '#ldaporg-user-content .msg' );
				});
			});
		},
		renderSettings: function() {
			var deferred = $.Deferred();
			var self = this;
			$.get( this._baseUrl + '/settings' ).done( function( settings ) {
				self._settings = settings;
				self.loadGroups().done( function() {
					// mark all superuser groups
					$.each( self._groups, function( i, group ) {
						if( $.inArray( group.ldapcontacts_entry_id, self._settings.superuser_groups ) != -1 ) {
							group.ldaporg_superuser_group = true;
						}
					});
					
					// render the settings area
					var source = $( '#ldaporg-edit-settings-tpl' ).html();
					var template = Handlebars.compile( source );
					var html = template( { settings: self._settings, groups: self._groups } );
					$( '#ldaporg-edit-settings' ).html( html );

					// hide password reset url options when deactivated
					$( '#ldaporg_pwd_reset_url_active_false' ).change( function() {
						$( '#ldaporg-edit-settings .pwd_reset_url' ).hide(400);
					});
					// show password reset url options when activated
					$( '#ldaporg_pwd_reset_url_active_true' ).change( function() {
						$( '#ldaporg-edit-settings .pwd_reset_url' ).show(400);
					});
					
					// save the settings
					$( '#ldaporg_settings_save' ).click( function( e ) {
						e.preventDefault();
						self.saveSettings();
					});

					deferred.resolve();
				}).fail( function() {
					deferred.reject();
				});
			});
			return deferred.promise();
		},
		saveSettings: function() {
			var self = this;
			var deferred = $.Deferred();
			// get all values from the form
			var form = $( '#ldaporg_settings_form' ).serialize();
			// go through all the checkboxes and check if they should be erased completely
			$.each( self._checkbox_settings, function( i, setting ) {
				// if the setting is not present in the serialized form, 
				if( !~form.toLowerCase().indexOf( setting ) ) {
					form += '&' + setting + '%5B%5D=';
				}
			});
			
			// start saving
			OC.msg.startSaving( '#ldaporg_settings_form .msg' );
			
			// update the settings
			$.ajax({
				url: self._baseUrl + '/settings',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify( { settings: form } )
			}).done( function( data ) {
				// reload all settings
				self.renderSettings().done( function() {
					// saving the settings was successful
					OC.msg.finishedSaving( '#ldaporg_settings_form .msg', data );
				});
			}).fail( function( data ) {
				OC.msg.finishedError( '#ldaporg_settings_form .msg' );
			});
			return deferred.promise();
		},
		// renders the settings for forcing group memberships
		renderForcedGroupMemberships: function () {
			var self = this;
			var source = $( '#ldaporg-force-group-membership-tpl' ).html();
			var template = Handlebars.compile( source );
			// get the forced groups details
			var forced_groups = [];
			$.each( this._settings.forced_group_memberships, function( k, forced_group_entry_id ) {
				$.each( self._groups, function( k2, group ) {
					// check if this is the group
					if( group.ldapcontacts_entry_id == forced_group_entry_id ) {
						forced_groups.push( group );
						return false;
					}
				});
			});
			
			var html = template( { groups: forced_groups } );
			$( '#ldaporg-force-group-membership' ).html( html );
			
			// make a group membership optional again
			$('#ldaporg-force-group-membership .remove').click( function() {
				// get the groups id
				var entry_id = this.attributes['target-id'].value;
				// unforce the groups membership
				self.unforceGroupMembership( entry_id );
			});
			
			// search form for forcing a group membership
			$('#ldaporg-search-non-forced-memberships-group').on( "change keyup paste", function() {
				var value = $( this ).val();
				
				// check if we are still searching
				if( value == '' ) $( this ).removeClass( 'searching' );
				else $( this ).addClass( 'searching' );
				
				// search for the given value and render the navigation
				self.searchNonforcedMembershipGroups( value );
			});
			
			// abort the search
			$('#ldaporg-search-non-forced-memberships-group + .abort').click( function() {
				// clear the search form
				$('#ldaporg-search-non-forced-memberships-group').val('');
				$('#ldaporg-search-non-forced-memberships-group').trigger( 'change' );
			});
			
			// apply forced group memberships
			$( '#ldaporg-apply-forced-group-membership' ).click( function() {
				OC.msg.startSaving( '#ldaporg-force-group-membership-msg' );
				// send request
				$.get( self._baseUrl + '/apply/forcedMemberships', function( data ) {
					OC.msg.finishedSaving( '#ldaporg-force-group-membership-msg', data );
				}).fail( function() {
					OC.msg.finishedError( '#ldaporg-force-group-membership-msg' );
				});
			});
		},
		// force the membership of a group
		forceGroupMembership: function( group_entry_id ) {
			var self = this;
			OC.msg.startSaving( '#ldaporg-force-group-membership-msg' );
			
			// get the newest forced_group_memberships
			self.loadForcedGroupMemberships().done( function() {
				// add the new group to the array
				self._settings.forced_group_memberships.push( group_entry_id );
				// save the forced groups
				self.saveForcedGroupMemberships( self._settings.forced_group_memberships );
			}).fail( function() {
				OC.msg.finishedError( '#ldaporg-force-group-membership-msg' );
			});
		},
		// make a certain group membership optional again
		unforceGroupMembership: function ( group_entry_id ) {
			var self = this;
			OC.msg.startSaving( '#ldaporg-force-group-membership-msg' );
			
			// get the newest forced_group_memberships
			self.loadForcedGroupMemberships().done( function() {
				// find the group and delete it
				var index = $.inArray( group_entry_id, self._settings.forced_group_memberships );
				delete self._settings.forced_group_memberships[ index ];
				// save the forced groups
				self.saveForcedGroupMemberships( self._settings.forced_group_memberships );
			}).fail( function() {
				OC.msg.finishedError( '#ldaporg-force-group-membership-msg' );
			});
		},
		saveForcedGroupMemberships: function( forced_groups ) {
			var self = this;
			// save the new forced_groups
			$.ajax({
				url: self._baseUrl + '/setting',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify( { key: 'forced_group_memberships', value: forced_groups } )
			}).done( function( data ) {
				// reload all data
				self.loadForcedGroupMemberships().done( function() {
					self.renderForcedGroupMemberships();
					// show message
					if( data ) {
						OC.msg.finishedSuccess( '#ldaporg-force-group-membership-msg' );
					}
					else {
						OC.msg.finishedError( '#ldaporg-force-group-membership-msg' );
					}
				}).fail( function() {
					OC.msg.finishedError( '#ldaporg-force-group-membership-msg' );
				});
			}).fail( function() {
				OC.msg.finishedError( '#ldaporg-force-group-membership-msg' );
			});
		},
		loadForcedGroupMemberships: function() {
			var self = this;
			var deferred = $.Deferred();
			
			// get the newest forced_group_memberships
			$.get( this._baseUrl + '/setting/forced_group_memberships', function( forced_groups ) {
				self._settings.forced_group_memberships = forced_groups;
				deferred.resolve();
			});
			
			return deferred.promise();
		},
		searchNonforcedMembershipGroups: function ( search ) {
			if( search == this._lastForcedGroupMembershipsSearch ) return false;
			this._lastForcedGroupMembershipsSearch = search;
			
			// if the search form is empty, clean up
			if( search == '' ) {
				this.renderNonforcedMembershipGroupSuggestions( this._groups );
				return true;
			}
			
			var self = this;
			this._forcedGroupMembershipsSearchId++;
			var id = this._forcedGroupMembershipsSearchId;
			search = search.toLowerCase();
			
			var matches = [];
			
			$( this._groups ).each( function( i, group ) {
				if( self._forcedGroupMembershipsSearchId != id ) return false;
				$.each( group, function( key, value ) {
					if( typeof( value ) != 'string' && typeof( value ) != 'number' ) return;
					value = String( value ).toLowerCase();
					if( ~value.indexOf( search ) ) {
						matches.push( group );
						return false;
					}
				});
			});
			return self.renderNonforcedMembershipGroupSuggestions( matches );
		},
		renderNonforcedMembershipGroupSuggestions: function( groups ) {
			var self = this;
			// clear the suggestions area
			$( '#ldaporg-force-group-membership .search + .search-suggestions' ).empty();
			// don't show all groups at once
			if( groups != this._groups ) {
				// show all found groups
				$.each( groups, function( i, group ) {
					// render the search suggestion
					var html = $( document.createElement( 'div' ) )
					.addClass( 'suggestion' )
					// add the groups name
					.text( group.ldapcontacts_name )
					// add the contact information to the suggestion
					.data( 'entry_id', group.ldapcontacts_entry_id )
					// when clicked on the group, it will be hidden
					.click( function() {
						self.forceGroupMembership( $( this ).data( 'entry_id' ) );
					});
					// add the option to the search suggestions
					$( '#ldaporg-force-group-membership .search + .search-suggestions' ).append( html );
				});
			}
			
			return true;
		},
		// load all visible and unvisible groups
		loadGroups: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the groups
			$.get( this._baseUrl + '/admin/load/groups', function( data ) {
				self._groups = data;
				deferred.resolve();
			}).fail( function() {
				// groups couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		}
	};
	
	var users = new Users();
	users.init();
	
	users.loadUsers().done( function(){
		users.renderContent();
		users.renderUsers();
	});
	users.loadLdapContactsSettings().done( function() {
		users.renderSettings().done( function() {
			users.loadGroups().done( function() {
				users.renderForcedGroupMemberships();
			});
		});
	});
});
})(OC, window, jQuery);