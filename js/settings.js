(function (OC, window, $, undefined) {
'use strict';

$(document).ready(function () {
	
	var Users = function() {
		this._baseUrl = OC.generateUrl( '/apps/ldaporg' );
		this._ldapcontacts_baseUrl = OC.generateUrl( '/apps/ldapcontacts' );
		this._users = [];
		this._groups = [];
		this._forcedGroupMemberships = [];
		this._lastForcedGroupMembershipsSearch = '';
		this._forcedGroupMembershipsSearchId = 0;
	};
	
	Users.prototype = {
		init: function() {
			var self = this;
			$( '#ldaporg-add-user' ).click( function() {
				self.renderContent();
			});
		},
		loadUsers: function() {
			var deferred = $.Deferred();
			var self = this;
			$.get( this._baseUrl + '/load/users' ).done( function( users ) {
				// reset variables
				self._users = users;
				deferred.resolve();
			}).fail( function() {
				deferred.reject();
			});
			return deferred.promise();
		},
		renderUsers: function( users ) {
			var self = this;
			var source = $( '#ldaporg-existing-users-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template( { users: users } );
			$( '#ldaporg-existing-users' ).html( html );
			
			// button for selecting user
			$( '#ldaporg-existing-users > .user > span.icon-play' ).click( function( e ) {
				var id = $( this ).attr( 'data-id' );
				// show the users details
				self.showUserDetails( id );
			});
		},
		showUserDetails: function( id ) {
			var user = null;
			// look for the user
			$.each( this._users, function( key, value ) {
				if( value.id == id ) {
					user = value;
					return;
				}
			});
			
			var self = this;
			var source = $( '#ldaporg-user-details-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template( { user: user } );
			$( '#ldaporg-user-content' ).html( html );
			this.registerModifyUserButton();
		},
		registerModifyUserButton: function() {
			var self = this;
			// button for resending welcome email
			$( '#ldaporg-user-content .button.resend-welcome-mail' ).click( function( e ) {
				var id = $( this ).attr( 'data-id' );
				var user = undefined;
				// look for the user
				$.each( self._users, function( key, value ) {
					if( value.id == id ) {
						user = value;
						return;
					}
				});
				// check if the user was found
				if( typeof( user ) == 'undefined' || user == null ) { return; }
				
				// resend the welcome email
				var data = { user: user };
				$.ajax({
					url: self._baseUrl + '/welcomemail/resend',
					method: 'POST',
					contentType: 'application/json',
					data: JSON.stringify( data )
				}).done( function( data ) {
					// show message
					OC.msg.finishedSaving( '#ldaporg-user-content .msg', data );
				});
			});
			
			// button for deleting a user
			$( '#ldaporg-user-content .button.delete-user' ).click( function( e ) {
				var id = $( this ).attr( 'data-id' );
				var user = undefined;
				// look for the user
				$.each( self._users, function( key, value ) {
					if( value.id == id ) {
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
				$( '#ldaporg-delete-user' ).click( function( e ) {
					OC.msg.startSaving( '#ldaporg-user-content .msg' );
					var data = Object();
					data.user = user;
					
					// delete the selected user
					$.ajax({
						url: self._baseUrl + '/delete/user',
						method: 'POST',
						contentType: 'application/json',
						data: JSON.stringify( data )
					}).done( function( data ) {
						// if the deleting was successful, reload all users
						if( data.status == 'success' ) {
							self.loadUsers().done( function() {
								// render the users again
								self.renderUsers( self._users );
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
					});
				});
				
				// aborting the action
				$( '#ldaporg-abort-delete-user' ).click( function( e ) {
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
				var data = Object();
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
					// if sending the reset password mail was successful, reload all users
					if( data.status == 'success' ) {
						self.loadUsers().done( function() {
							// render the users again
							self.renderUsers( self._users );
							// render the initial content area
							self.renderContent();
							// show a message that the use was saved
							OC.msg.finishedSaving( '#ldaporg-user-content .msg', data );
						});
					}
					else {
						// show error message
						OC.msg.finishedSaving( '#ldaporg-user-content .msg', data );
					}
				});
			});
		},
		renderSettings: function() {
			var deferred = $.Deferred();
			var self = this;
			$.get( this._baseUrl + '/settings' ).done( function( settings ) {
				// get all existing ldap groups
				$.get( self._ldapcontacts_baseUrl + '/groups' ).done( function( groups ) {
					// perform special actions on every group
					$.each( groups, function( key, group ) {
						// check if this is the currently selected admin group
						if( settings.superuser_group_id == group.id ) groups[ key ].isadmin = true;
						// check if this is the currently selected default group
						if( settings.user_gidnumber == group.id ) groups[ key ].isdefault = true;
					});
					settings.groups = groups;
					// render the settings area
					var source = $( '#ldaporg-edit-settings-tpl' ).html();
					var template = Handlebars.compile( source );
					var html = template({ settings: settings });
					$( '#ldaporg-edit-settings' ).html( html );
					
					// hide password reset url options deactivated
					$( '#ldaporg_pwd_reset_url_active_false' ).change( function() {
						$( '#ldaporg-edit-settings .pwd_reset_url' ).hide(400);
					});
					// show password reset url options when activated
					$( '#ldaporg_pwd_reset_url_active_true' ).change( function() {
						$( '#ldaporg-edit-settings .pwd_reset_url' ).show(400);
					});
					
					// check if the passsword reset url options should be shown from the beginning or not
					if( settings.pwd_reset_url_active ) $( '#ldaporg-edit-settings .pwd_reset_url' ).show();
					else $( '#ldaporg-edit-settings .pwd_reset_url' ).hide();
					
					// save the settings
					$( '#ldaporg_settings_save' ).click( function(e) {
						e.preventDefault();
						self.saveSettings();
					});
					
					deferred.resolve();
				});
			});
			return deferred.promise();
		},
		saveSettings: function() {
			var self = this;
			var deferred = $.Deferred();
			// get all values from the form
			var data = Object();
			data.settings = $( '#ldaporg_settings_form' ).serializeArray();
			// start saving
			OC.msg.startSaving( '#ldaporg_settings_form .msg' );
			
			// update the settings
			$.ajax({
				url: self._baseUrl + '/settings',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify( data )
			}).done( function( data ) {
				// reload all settings
				self.renderSettings().done( function() {
					// saving the settings was successful
					OC.msg.finishedSaving( '#ldaporg_settings_form .msg', data );
				});
			});
			return deferred.promise();
		},
		loadForcedGroupMemberships: function() {
			var self = this;
			var deferred = $.Deferred();
			
			$.get( this._baseUrl + '/load/group/forcedMembership' ).done( function( data ) {
				if( data.status == 'success' ) {
					// reset variables
					self._forcedGroupMemberships = data.data;
					deferred.resolve();
				}
				else {
					deferred.reject();
				}
			}).fail( function() {
				deferred.reject();
			});
			return deferred.promise();
		},
		// renders the settings for forcing group memberships
		renderForcedGroupMemberships: function () {
			var self = this;
			var source = $('#ldaporg-force-group-membership-tpl').html();
			var template = Handlebars.compile(source);
			// get the forced groups details
			var groups = [];
			$.each( this._forcedGroupMemberships, function( k, id ) {
				$.each( self._groups, function( k2, group ) {
					// check if this is the group
					if( group.id == id ) {
						groups.push( group );
						return false;
					}
				});
			});
			
			var html = template({ groups: groups });
			$('#ldaporg-force-group-membership').html(html);
			
			// make a group membership optional again
			$('#ldaporg-force-group-membership .remove').click( function() {
				// get the groups id
				var id = this.attributes['target-id'].value;
				// unforce the groups membership
				self.unforceGroupMembership( id );
			});
			
			// search form for forcing a gropu membership
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
				$.get( self._baseUrl + '/apply/forcedMemberships', function(data) {
					OC.msg.finishedSaving( '#ldaporg-force-group-membership-msg', data );
				});
			});
		},
		// force the membership of a group
		forceGroupMembership: function(group) {
			var self = this;
			OC.msg.startSaving( '#ldaporg-force-group-membership-msg' );
			// send request
			$.get( this._baseUrl + '/add/group/forcedMembership/' + encodeURI(group.id), function(data) {
				// reload all data
				self.loadForcedGroupMemberships().done( function() {
					self.renderForcedGroupMemberships();
					OC.msg.finishedSaving( '#ldaporg-force-group-membership-msg', data );
				});
			});
		},
		// make a certain group membership optional again
		unforceGroupMembership: function (group_id ) {
			var self = this;
			OC.msg.startSaving( '#ldaporg-force-group-membership-msg' );
			// send request
			$.get( this._baseUrl + '/remove/group/forcedMembership/' + encodeURI( group_id ), function( data ) {
				// reload all data
				self.loadForcedGroupMemberships().done( function() {
					self.renderForcedGroupMemberships();
					OC.msg.finishedSaving( '#ldaporg-force-group-membership-msg', data );
				});
			});
		},
		searchNonforcedMembershipGroups: function ( search ) {
			if( search == this._lastForcedGroupMembershipsSearch ) return false;
			this._lastForcedGroupMembershipsSearch = search;
			
			// if the search form is empty, clean up
			if( search == '' ) {
				this.renderNonforcedMembershipGroupSuggestions(this._groups);
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
			return self.renderNonforcedMembershipGroupSuggestions(matches)
		},
		renderNonforcedMembershipGroupSuggestions: function(groups) {
			var self = this;
			// clear the suggestions area
			$('#ldaporg-force-group-membership .search + .search-suggestions').empty();
			// don't show all groups at once
			if( groups != this._groups ) {
				// show all found groups
				$.each( groups, function(i, group) {
					// render the search suggestion
					var html = $(document.createElement('div'))
					.addClass('suggestion')
					// add the groups name
					.text(group.cn)
					// add the contact information to the suggestion
					.data('contact', group)
					// when clicked on the group, it will be hidden
					.click(function() {
						self.forceGroupMembership( $(this).data('contact') );
					});
					// add the option to the search suggestions
					$('#ldaporg-force-group-membership .search + .search-suggestions').append(html);
				});
			}
			
			return true;
		},
		// load all visible groups
		loadGroups: function() {
			var deferred = $.Deferred();
			var self = this;
			// load the groups
			$.get( this._baseUrl + '/admin/load', function(data) {
				self._groups = data;
				deferred.resolve();
			}).fail( function() {
				// groups couldn't be loaded
				deferred.reject();
			});
			return deferred.promise();
		}
	};
	
	var users = new Users;
	users.init();
	users.loadUsers().done( function(){
		users.renderContent();
		users.renderUsers( users._users );
	});
	users.loadGroups().done( function() {
		users.loadForcedGroupMemberships().done( function() {
			users.renderForcedGroupMemberships();
		});
	});
	users.renderSettings();
});
})(OC, window, jQuery);