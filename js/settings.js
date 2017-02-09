(function (OC, window, $, undefined) {
'use strict';

$(document).ready(function () {
	
	var Users = function() {
		this._baseUrl = OC.generateUrl( '/apps/ldaporg' );
		this._ldapcontacts_baseUrl = OC.generateUrl( '/apps/ldapcontacts' );
		this._users = [];
	};
	
	Users.prototype = {
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
			
			// button for deleting a user
			$( '#ldaporg-existing-users > .user > span.icon-delete' ).click( function( e ) {
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
				if( typeof( user ) == 'undefined' || user == null ) return;
				
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
								// show a message that the use was deleted
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
				$.get( self._ldapcontacts_baseUrl + '/contacts/groups' ).done( function( groups ) {
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
				console.log( data );
				// reload all settings
				self.renderSettings().done( function() {
					// saving the settings was successful
					OC.msg.finishedSaving( '#ldaporg_settings_form .msg', data );
				});
			});
			deferred.promise();
		},
	};
	
	var users = new Users;
	users.loadUsers().done( function(){
		users.renderContent();
		users.renderUsers( users._users );
	});
	users.renderSettings();
});

})(OC, window, jQuery);