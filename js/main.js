(function (OC, window, $, undefined) {
'use strict';

$(document).ready(function() {
	
// main rendering object
var Groups = function() {
	this._baseUrl = OC.generateUrl( '/apps/ldaporg' );
	this._ldapContactsbaseUrl = OC.generateUrl( '/apps/ldapcontacts' );
	this._activeGroup = undefined;
	this._groups = [];
	this._users = [];
	this._settings = [];
	this._me = undefined;
	this._last_search = '';
	this._search_id = 0;
	this._superuser = false;
};

// all group related functions
Groups.prototype = {
	// load all groups you have access  to
	loadGroups: function() {
		var deferred = $.Deferred();
        var self = this;
        $.get( this._baseUrl + '/load/groups' ).done( function( data ) {
			if( data.status == 'success' ) {
				// save the fetched groups
				self._groups = data.data;
				// reload the active group if there was one
				if( typeof( self._activeGroup ) != 'undefined' && self._activeGroup != null ) {
					self.load( self._activeGroup.ldapcontacts_entry_id, true );
				}
				deferred.resolve();
			}
			else {
				deferred.reject();
			}
        }).fail( function () {
            deferred.reject();
        });
        return deferred.promise();
	},
	// load all existing users
	loadUsers: function() {
		var deferred = $.Deferred();
        var self = this;
        $.get( this._baseUrl + '/load/users' ).done( function( data ) {
			if( data.status == 'success' ) {
				// save the fetched users
				self._users = data.data;
				deferred.resolve();
			}
			else {
				deferred.reject();
			}
        }).fail(function () {
            deferred.reject();
        });
        return deferred.promise();
	},
	// load the own user
	loadOwn: function() {
		var deferred = $.Deferred();
        var self = this;
        $.get( this._ldapContactsbaseUrl + '/own' ).done( function( me ) {
			// save the fetched users
			self._me = me[0];
            deferred.resolve();
        }).fail(function () {
            deferred.reject();
        });
        return deferred.promise();
	},
	// load all required data
	loadAll: function() {
		var deferred = $.Deferred();
		var self = this;
		// load groups
		this.loadGroups().done( function() {
			// load users
			self.loadUsers().done( function() {
				// load current user
				self.loadOwn().done( function() {
					// load forced group memberships
					self.loadSettings().done( function() {
						deferred.resolve();
					}).fail( function() {
						deferred.reject();
					});
				}).fail( function() {
					deferred.reject();
				});
			}).fail( function() {
				deferred.reject();
			});
		}).fail( function() {
            deferred.reject();
        });
		return deferred.promise();
	},
	loadSettings: function() {
		var deferred = $.Deferred();
		var self = this;
		$.get( this._baseUrl + '/settings' ).done( function( settings ) {
			self._settings = settings;
			deferred.resolve();
		}).fail( function( data ) {
            deferred.reject();
        });
		return deferred.promise();
	},
	// load a groups data and render it in the content area if needed
	load: function( entry_id, no_render ) {
		var self = this;
		this._activeGroup = this._groups[ entry_id ];
		
		// render the content if needed
		if( typeof( no_render ) == 'undefined' || !no_render )
			return this.renderContent( true );
	},
	// render everything
	render: function() {
		var deferred = $.Deferred();
		var self = this;
		this.renderContent().done( function() {
			self.renderNavigationHeader().done( function() {
				self.renderNavigation();
				deferred.resolve();
			}).fail(function () {
				deferred.reject();
			});
		}).fail(function () {
            deferred.reject();
        });
		return deferred.promise();
	},
	// render navigation header
	renderNavigationHeader: function() {
		var deferred = $.Deferred();
		var self = this;
		
		// check if the current user has superuser rights
		$.get( this._baseUrl + '/superuser' ).done( function( data ) {
			var superuser = ( data.status == 'success' && data.data ) ? true : false;
			// if the user is not a superuser, don't show the special options
			if( !superuser ) {
				$( '#navigation-header' ).remove();
				// remove the superuser attribute from every group
				$.each( self._groups, function( key, value ) {
					self._groups[ key ].superuser = false;
				});
				self._superuser = false;
				
				deferred.resolve();
				return;
			}
			
			// add the superuser attribute to every group
			$.each( self._groups, function( key, value ) {
				self._groups[ key ].superuser = true;
			});
			self._superuser = true;
			
			// show special superuser options
			var html = OCA.LdapOrgTempaltes.main_navigation_header({
				addGroupTXT: t('ldaporg', 'Add Group')
			});
			$( '#navigation-header' ).html( html );
			
			// add action for creating a group
			$( '#add-group' ).click( function() {
				// no group is active at the moment
				self._activeGroup = undefined;
				// load the adding form into the content area
				var html = OCA.LdapOrgTempaltes.main_add_group({
					nameTXT: t('ldaporg', 'Group Name'),
					addTXT: t('ldaporg', 'Add Group')
				});
				$( '#info' ).html( html );
				
				$( '#add-group-create' ).click( function( e ) {
					e.preventDefault();
					var deferred = $.Deferred();
					OC.msg.startSaving( '#info .msg' );
					
					// create a new group
					$.ajax({
						url: self._baseUrl + '/create/group',
						method: 'POST',
						contentType: 'application/json',
						data: JSON.stringify( { group_name: $( '#add-group-name' ).val() } )
					}).done( function( data ) {
						// if the creating was successful, reload all groups
						if( data.status == 'success' ) {
							self.loadAll().done( function() {
								self.render();
								// load the newly created group into the content area
								self.load( data.gid ).done( function() {
									// show group created message
									OC.msg.finishedSaving( '#info .msg', data );
									deferred.resolve();
								});
							});
						}
						else {
							// show error message
							OC.msg.finishedSaving( '#info .msg', data );
							deferred.resolve();
						}
					});
					return deferred.promise();
				});
			});
			deferred.resolve();
		});
		return deferred.promise();
	},
	// render group overview
	renderNavigation: function() {
		var self = this;

		var html = OCA.LdapOrgTempaltes.main_navigation({
			groups: this._groups,
			deleteTXT: t('ldaporg', 'Delete Group'),
			noMemberTXT: t('ldaporg', 'You are not a member of any group')
        });
        $( '#group-navigation' ).html( html );
		
		// load a group
        $( '#group-navigation .group > a.load' ).click( function() {
            var entry_id = $( this ).parent().data( 'id' );
			self.load( entry_id );
        });
		
		// remove a group if the user is a superuser
		if( this._superuser ) {
			$( '#group-navigation .group > a > span.icon-delete' ).click( function( e ) {
				var group_entry_id = $( this ).attr( 'data-id' );
				// ask the user if he really wants to delete this group

				// get the group
				var group = self._groups[ group_entry_id ];
				// check if the group was found
				if( typeof( group ) == 'undefined' || group == null ) return;
				
				var html = OCA.LdapOrgTempaltes.main_remove_group({
					yesTXT: t('ldaporg', 'Yes'),
					noTXT: t('ldaporg', 'No'),
					questionTXT: t('ldaporg', 'Do you really want to remove the group {groupName]', {
						groupName: group.ldapcontacts_name
					})
				});
				$( '#info' ).html( html );
				
				// really remove button
				$( '#remove-group' ).click( function() {
					var deferred = $.Deferred();
					OC.msg.startSaving( '#info .msg' );
					
					// remove the group
					$.get( self._baseUrl + '/delete/group/' + encodeURI( group_entry_id ) ).done( function( data ) {
						// if the removing was successful, reload all groups
						if( data.status == 'success' ) {
							self.loadAll().done( function() {
								// clear selected group
								self._activeGroup = undefined;
								// render the initial content
								self.render().done( function() {
									// show group deleted message
									OC.msg.finishedSaving( '#info .msg', data );
									deferred.resolve();
								});
							});
						}
						else {
							// show error message
							OC.msg.finishedSaving( '#info .msg', data );
							deferred.reject();
						}
					}).fail( function() {
						// show error message
						OC.msg.finishedError( '#info .msg' );
						deferred.reject();
					});
					return deferred.promise();
				});
				// abort button
				$( '#abort-remove-group' ).click( function() {
					// load the initial content
					self.load();
				});
			});
		}
	},
	// render the content area
	renderContent: function( load ) {
		// show loading icon if needed
		if( typeof( load ) != 'undefined' && load != null ) {
			$( '#info' ).html( Handlebars.compile( $( '#loading-tpl' ).html() )() );
		}
		
		var self = this;
		
		var group_entry_id;
		if( typeof( self._activeGroup ) != 'undefined' && self._activeGroup != null ) {
			group_entry_id = self._activeGroup.ldapcontacts_entry_id;
		}
		
		// check if the user is allowed to edit this group
		return $.get( this._baseUrl + '/canedit/' + encodeURI( group_entry_id ) ).done( function( data ) {
			var canedit = data.status == 'success' && data.data ? true : false;
			
			var html_option = { group: self._activeGroup, notForcedMembership: true };
			
			// check if a group has been selected
			if( typeof( self._activeGroup ) != 'undefined' && self._activeGroup != null ) {
				// check the users editing rights on the group
				if( canedit === true ) self._activeGroup.ldaporg_canedit = true;
				else delete self._activeGroup.ldaporg_canedit;
				
				// check if the current user is in the group
				var is_member = self._activeGroup.ldaporg_members[ self._me.ldapcontacts_entry_id ];
				if( typeof( is_member ) != 'undefined' && is_member != null ) {
					html_option.me = true;
				}
				
				// check if the current group has forced membership
				$.each( self._settings.forced_group_memberships, function( key, entry_id ) {
					// check if this is the active group
					if( self._activeGroup.ldapcontacts_entry_id == entry_id ) {
						html_option.notForcedMembership = false;
						return false;
					}
				});
				
				// get number of members
				html_option.memberCount = self._activeGroup.ldaporg_members.length;
				
				// add export member details url
				html_option.exportURL = self._baseUrl + '/export/' + encodeURI( self._activeGroup.ldapcontacts_entry_id );
			}
			else {
				canedit = false;
			}
			
			// render content
			$( '#info' ).html( OCA.LdapOrgTempaltes.main_content( _.extend({
				addMemberTXT: t('ldaporg', 'Add Member'),
				endGroupMembershipTXT: t('ldaporg', 'End Group Membership'),
				exportGroupDetailsTXT: t('ldaporg', 'Export group member details'),
				membersTXT: t('ldaporg', 'Members'),
				groupAdminTXT: t('ldaporg', 'Group Admin'),
				removeAdminPrivTXT: t('ldaporg', 'Remove Admin Privileges'),
				makeAdminTXT: t('ldaporg', 'Make Admin'),
				removeTXT: t('ldaporg', 'Remove'),
				noMembersTXT: t('ldaporg', 'There are no members in this group yet'),
				selectGroupTXT: t('ldaporg', 'Select a group from the list to view details')
			}, html_option) ) );
			$( '#info' ).focus();
			
			// button for leaving the group
			$( "#leave_group" ).click( function() {
				// remove the current user
				self.removeUser( self._me.ldapcontacts_entry_id );
			});
			
			// admin only functions
			if( canedit ) {
				$( '#group_add_member' ).on( "change keyup paste", function() {
					var value = $( this ).val();

					// check if we are still searching
					if( value == '' ) $( this ).removeClass( 'searching' );
					else $( this ).addClass( 'searching' );

					// search for the given value and render the navigation
					self.searchUsers( value );
				});

				// button for clearing search input
				$( "#group_add_member + .abort" ).click( function() {
					$( "#group_add_member" ).val('');
					$( "#group_add_member" ).trigger( 'change' );
				});
				
				// expanding menu
				$( ".members-menu > td > a" ).click( function( e ) {
					e.stopPropagation();
					var target = e.target;
					var visible = $( ".options", target.parentElement ).is( ":visible" );
					
					// hide all options
					$( ".options", target.parentElement.parentElement.parentElement ).hide();
					// now open the options for this element
					if( visible )
						$( ".options", target.parentElement ).hide();
					else
						$( ".options", target.parentElement ).show();
					
					// hide the options again if the user clicks somewhere else
					$( document ).click( function() {
						$( ".options", target.parentElement ).hide();
					});
				});
				
				// admin options for each user
				$( ".members-menu > td > .options a" ).click( function( e ) {
					var target = $( this );
					// get the user id and required action
					var action = target.attr( "data-action" );
					var user_entry_id = target.attr( "data-id" );
					
					// choose the right action
					switch( action ) {
						case "addAdmin":
							self.addAdminUser( user_entry_id );
							break;
						case "removeAdmin":
							self.removeAdminUser( user_entry_id );
							break;
						case "remove":
							self.removeUser( user_entry_id );
							break;
					}
				});
			}
		});
	},
	searchUsers: function ( search ) {
		if( search == this._last_search ) return false;
		this._last_search = search;
		
		// if the search form is empty, clean up
		if( search == '' ) {
			this.renderUserSuggestions( this._users );
			return true;
		}
		
		var self = this;
		this._search_id++;
		var id = this._search_id;
		search = search.toLowerCase();
		
		var matches = [];
		
		$( this._users ).each( function( i, user ) {
			if( self._search_id != id ) return false;
			$.each( user, function( key, value ) {
				if( typeof( value ) != 'string' && typeof( value ) != 'number' ) return;
				value = String( value ).toLowerCase();
				
				if( ~value.indexOf( search ) ) {
					var in_group = self._activeGroup.ldaporg_members[ user.ldapcontacts_entry_id ];
					in_group = ( typeof( in_group ) == 'undefined' || in_group == null ) ? false : true;
					
					// check if the user was identified as a member of the group
					if( !in_group && $.inArray( user, matches ) == -1 ) {
						matches.push( user );
					}
					return;
				}
			});
		});
		
		return self.renderUserSuggestions( matches )
	},
	renderUserSuggestions: function( users ) {
		var self = this;
		// clear the suggestions area
		$( '#info .content-nav > .search + .search-suggestions' ).empty();
		// don't show all users at once
		if( users != this._users ) {
			// show all found users
			$.each( users, function( i, user ) {
				// render the search suggestion
				var html = $( document.createElement( 'div' ) )
				.addClass( 'suggestion' )
				// add the users name
				.text( user.ldapcontacts_name )
				// add the user information to the suggestion
				.data( 'user', user )
				// when clicked on the user, he will be added to the group
				.click( function() {
					var user = $( this ).data( 'user' );
					// clear the search box and suggestions
					$( "#group_add_member" ).val('');
					$( "#group_add_member" ).trigger( 'change' );
					// add the user to the group
					self.addUserToGroup( user.ldapcontacts_entry_id );
				});
				
				// add the option to the search suggestions
				$( '#info .content-nav > .search + .search-suggestions' ).append( html );
			});
		}
		
		return true;
	},
	// add a user the currently active group
	addUserToGroup: function( user_entry_id ) {
		var self = this;
		OC.msg.startSaving( '#info .msg' );
		
		// add the user to the group
		return $.ajax({
            url: this._baseUrl + '/add/group/user',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( { user_entry_id: user_entry_id, group_entry_id: self._activeGroup.ldapcontacts_entry_id } )
        }).done( function( data ) {
			self.loadAll().done( function() {
				self.render().done( function() {
					OC.msg.finishedSaving( '#info .msg', data );
				}).fail( function() {
					OC.msg.finishedError( '#info .msg' );
				});
			}).fail( function() {
				OC.msg.finishedError( '#info .msg' );
			});
		}).fail( function() {
			OC.msg.finishedError( '#info .msg' );
		});
	},
	// remove a user from the currently active group
	removeUser: function( user_entry_id ) {
		var self = this;
		OC.msg.startSaving( '#info .msg' );
		
		// add the user to the group
		return $.ajax({
            url: this._baseUrl + '/remove/group/user',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( { user_entry_id: user_entry_id, group_entry_id: this._activeGroup.ldapcontacts_entry_id } )
        }).done( function( data ) {
			self.loadAll().done( function() {
				self.render().done( function() {
					OC.msg.finishedSaving( '#info .msg', data );
				}).fail( function() {
					OC.msg.finishedError( '#info .msg' );
				});
			}).fail( function() {
				OC.msg.finishedError( '#info .msg' );
			});
		}).fail( function() {
			OC.msg.finishedError( '#info .msg' );
		});
	},
	// add admin privileges to a user the currently active group
	addAdminUser: function( user_entry_id ) {
		var self = this;
		OC.msg.startSaving( '#info .msg' );
		
		// add the user to the group
		return $.ajax({
            url: this._baseUrl + '/add/group/user/admin',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( { user_entry_id: user_entry_id, group_entry_id: this._activeGroup.ldapcontacts_entry_id } )
        }).done( function( data ) {
			self.loadAll().done( function() {
				self.render().done( function() {
					OC.msg.finishedSaving( '#info .msg', data );
				}).fail( function() {
					OC.msg.finishedError( '#info .msg' );
				});
			}).fail( function() {
				OC.msg.finishedError( '#info .msg' );
			});
		}).fail( function() {
			OC.msg.finishedError( '#info .msg' );
		});
	},
	// remove admin privileges from a user from the currently active group
	removeAdminUser: function( user_entry_id ) {
		var self = this;
		OC.msg.startSaving( '#info .msg' );
		
		// add the user to the group
		return $.ajax({
            url: this._baseUrl + '/remove/group/user/admin',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( { user_entry_id: user_entry_id, group_entry_id: this._activeGroup.ldapcontacts_entry_id } )
        }).done( function( data ) {
			self.loadAll().done( function() {
				self.render().done( function() {
					OC.msg.finishedSaving( '#info .msg', data );
				}).fail( function() {
					OC.msg.finishedError( '#info .msg' );
				});
			}).fail( function() {
				OC.msg.finishedError( '#info .msg' );
			});
		}).fail( function() {
			OC.msg.finishedError( '#info .msg' );
		});
	}
};




var Tutorial = function () {
	this._baseUrl = OC.generateUrl( '/apps/ldaporg' );
	this._state = 0;
	this._max_state = 6;
	this._parents = [
		"#group-navigation > ul",
		"#group-navigation > ul",
		"#group-navigation > ul",
		"#info > .content-nav",
		"#info > .content-nav",
		"#info > h3",
		"#export_member_details",
	];
};

Tutorial.prototype = {
	// get the users current state
	getState: function() {
        var deferred = $.Deferred();
        var self = this;
		
		// send request for the users setting
		$.get( this._baseUrl + '/settings/personal/tutorial_state' ).done( function( state ) {
			// check if the value is valid
			if( Math.floor( state ) != state || !$.isNumeric( state ) ) state = 0;
			// set the users state
			self._state = state;
			return deferred.resolve();
		}).fail( function(data) {
            deferred.reject();
        });
		return deferred.promise();
	},
	// gets the message for the current tutorial
	getMessage: function() {
		return $( $( '#tutorial-translations' ).children( 'p' )[ this._state ] ).text();
	},
	// gets the parent element for the current tutorial to be placed in
	getTutorialParent: function() {
		return $( this._parents[ this._state ] );
	},
	// execute a custom 
	doCustomAction: function() {
		if( this._state == 0 || this._state == "0" )
		{
			if( $( document ).width() < 769 && groups._superuser )
				$( '#app-content' ).css( 'transform', 'translate3d(250px, 0px, 0px)' );
			return !groups._superuser;
		}
		else if( this._state == 1 || this._state == "1" )
		{
			if( $( document ).width() < 769 )
				$( '#app-content' ).css( 'transform', 'translate3d(250px, 0px, 0px)' );
		}
		else if( this._state == 2 || this._state == "2" )
		{
			if( $( document ).width() < 769 && groups._superuser )
				$( '#app-content' ).css( 'transform', 'translate3d(250px, 0px, 0px)' );
			return !groups._superuser;
		}
		else if( this._state == 3 || this._state == "3" )
		{
			if( $( document ).width() < 769 )
				$( '#app-content' ).css( 'transform', 'translate3d(0px, 0px, 0px)' );
			if( typeof( groups._activeGroup ) == 'undefined' || groups._activeGroup == null )
				return groups.load( $( '#group-navigation > ul > li:first-child' ).attr( 'data-id' ) );
		}
		else if( this._state == 4 || this._state == "4" )
		{
			if( $( document ).width() < 769 )
				$( '#app-content' ).css( 'transform', 'translate3d(0px, 0px, 0px)' );
			if( typeof( groups._activeGroup ) == 'undefined' || groups._activeGroup == null )
				return groups.load( $( '#group-navigation > ul > li:first-child' ).attr( 'data-id' ) );
		}
		else if( this._state == 5 || this._state == "5" )
		{
			if( $( document ).width() < 769 )
				$( '#app-content' ).css( 'transform', 'translate3d(0px, 0px, 0px)' );
			if( typeof( groups._activeGroup ) == 'undefined' || groups._activeGroup == null )
				return groups.load( $( '#group-navigation > ul > li:first-child' ).attr( 'data-id' ) );
		}
		else if( this._state == 6 || this._state == "6" )
		{
			if( $( document ).width() < 769 )
				$( '#app-content' ).css( 'transform', 'translate3d(0px, 0px, 0px)' );
			if( typeof( groups._activeGroup ) == 'undefined' || groups._activeGroup == null )
				return groups.load( $( '#group-navigation > ul > li:first-child' ).attr( 'data-id' ) );
		}
	},
	// show the next tutorial step and hide the current one
    next: function() {
		var self = this;
		// remove the current tutorial
		$( '#tutorial-container' ).remove();
		
		// save the current tutorial state
		this.saveState();
		// check if the user is already up to date with this tutorials
		if( this._state > this._max_state ) return;
		
		// do custom action
		var action_result = this.doCustomAction();
		
		// check if an ajax request is running
		if( typeof( action_result ) != 'undefined' && typeof( action_result.readyState ) != 'undefined' ) {
			action_result.done( function() {
				// render new tutorial
				var html = OCA.LdapOrgTempaltes.main_tutorial({
					message: self.getMessage(),
					gotItTXT: t('ldaporg', 'Got it')
				});
				self.getTutorialParent().append( html );
				// add custom attribute
				$( '#tutorial-container' ).attr( "tutorial-id", self._state ).slideDown( 300 );
				
				// increase state count
				self._state++;
				
				// add action for going to the next tutorial
				$( '#tutorial-next' ).one( 'click', function() {
					self.next();
				});
			});
		}
		// check if the custom action wants to go to the next tutorial
		else if( action_result ) {
			// increase state count
			this._state++;
			this.next();
		}
		// keep going normally
		else {
			
			// render new tutorial
			var html = OCA.LdapOrgTempaltes.main_tutorial({
				message: self.getMessage(),
				gotItTXT: t('ldaporg', 'Got it')
			});
			this.getTutorialParent().append( html );
			// add custom attribute
			$( '#tutorial-container' ).attr( "tutorial-id", this._state ).slideDown( 300 );
			
			// increase state count
			this._state++;
			// add action for going to the next tutorial
			$( '#tutorial-next' ).one( 'click', function() {
				self.next();
			});
		}
	},
	// when the user finished the current tutorial, save his tutorial status
	saveState: function() {
		var settings = new Object();
		settings.key = 'tutorial_state';
		settings.value = this._state;
		
		// save the state
		return $.ajax({
            url: this._baseUrl + '/settings/personal',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( settings )
        });
	},
};

var tutorial = new Tutorial();

var groups = new Groups();
groups.loadAll().done( function() {
	groups.render().done( function() {
		// only show the tutorial if the user is in at least one group
		if( groups._groups.length < 1 ) return;
		
		// load the tutorial
		tutorial.getState().done( function() {
			// show the first tutorial text
			tutorial.next();
		});
	});
});
});

})(OC, window, jQuery);
