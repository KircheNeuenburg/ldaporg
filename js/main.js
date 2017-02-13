(function (OC, window, $, undefined) {
'use strict';

$(document).ready(function() {
	
// main rendering object
var Groups = function() {
	this._baseUrl = OC.generateUrl('/apps/ldaporg');
	this._activeGroup = undefined;
	this._groups = [];
	this._users = [];
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
        $.get( this._baseUrl + '/load' ).done( function( groups ) {
			// save the fetched groups
			self._groups = groups;
			// reload the active group if there was one
			if( typeof( self._activeGroup ) != 'undefined' && self._activeGroup != null )
				self.load( self._activeGroup['id'], true );
            deferred.resolve();
        }).fail(function () {
            deferred.reject();
        });
        return deferred.promise();
	},
	// load all existing users
	loadUsers: function() {
		var deferred = $.Deferred();
        var self = this;
        $.get( this._baseUrl + '/load/users' ).done( function( users ) {
			// save the fetched users
			self._users = users;
            deferred.resolve();
        }).fail(function () {
            deferred.reject();
        });
        return deferred.promise();
	},
	// load the own user
	loadOwn: function() {
		var deferred = $.Deferred();
        var self = this;
        $.get( this._baseUrl + '/load/own' ).done( function( me ) {
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
					deferred.resolve();
				}).fail(function () {
					deferred.reject();
				});;
			}).fail(function () {
				deferred.reject();
			});;
		}).fail(function () {
            deferred.reject();
        });;
		return deferred.promise();
	},
	// load a groups data and render it in the content area if needed
	load: function( id, no_render ) {
		var self = this;
		this._activeGroup = undefined;
		// go through all groups and look for this one
		$.each( this._groups, function( key, data ) {
			// check if this is the right group
			if( data.id == id ) {
				self._activeGroup = data;
				return;
			}
		});
		// render the content if needed
		if( typeof( no_render ) == 'undefined' || !no_render )
			return this.renderContent( true );
	},
	// render everything
	render: function() {
		var self = this;
		return this.renderContent().done( function() {
			self.renderNavigationHeader().done( function() {
				self.renderNavigation();
			});
		});
	},
	// render 
	renderNavigationHeader: function() {
		var deferred = $.Deferred();
		var self = this;
		
		// check if the current user has superuser rights
		$.get( this._baseUrl + '/superuser' ).done( function( superuser ) {
			// if the user is not a superuser, don't show the special options
			if( !superuser ) {
				$( '#navigation-header' ).remove();
				
				// remove the superuser attribute to every group
				$.each( self._groups, function( key, value ) {
					self._groups[ key ]['superuser'] = false;
				});
				self._superuser = false;
				
				deferred.resolve();
			}
			// add the superuser attribute to every group
			$.each( self._groups, function( key, value ) {
				self._groups[ key ]['superuser'] = true;
			});
			self._superuser = true;
			
			// show special superuser options
			var source = $( '#navigation-header-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = template();
			$( '#navigation-header' ).html( html );
			
			// add action for creating a group
			$( '#add-group' ).click( function() {
				// no group is active at the moment
				self._activeGroup = undefined;
				// load the adding form into the content area
				var source = $( '#add-group-tpl' ).html();
				var template = Handlebars.compile( source );
				var html = template();
				$( '#info' ).html( html );
				
				$( '#add-group-create' ).click( function( e ) {
					e.preventDefault();
					var gid = false;
					var deferred = $.Deferred();
					OC.msg.startSaving( '#info .msg' );
					var data = Object();
					data.group_name = $( '#add-group-name' ).val();
					
					// create a new group
					$.ajax({
						url: self._baseUrl + '/add/group',
						method: 'POST',
						contentType: 'application/json',
						data: JSON.stringify( data )
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
        var source = $( '#navigation-tpl' ).html();
        var template = Handlebars.compile( source );
		
        var html = template( { groups: this._groups } );
        $( '#group-navigation' ).html( html );
		
		// load a group
        $( '#group-navigation .group > a.load' ).click( function() {
            var id = parseInt( $( this ).parent().data( 'id' ), 10 );
			self.load( id );
        });
		
		// remove a group if the user is a superuser
		if( this._superuser ) {
			$( '#group-navigation .group > a > span.icon-delete' ).click( function( e ) {
				var group_id = $( this ).attr( 'data-id' );
				// ask the user if he really wants to delete this group
				var source = $( '#remove-group-tpl' ).html();
				var template = Handlebars.compile( source )
				
				var group = undefined;
				// find the group that has to be removed
				$.each( self._groups, function( key, value ) {
					if( value['id'] == group_id ) {
						group = value;
						return;
					}
				});
				// check if the group was found
				if( typeof( group ) == 'undefined' || group == null ) return;
				
				var html = template( { group: group } );
				$( '#info' ).html( html );
				
				// really remove button
				$( '#remove-group' ).click( function() {
					var deferred = $.Deferred();
					OC.msg.startSaving( '#info .msg' );
					var data = Object();
					data.group = group;
					
					// remove the group
					$.ajax({
						url: self._baseUrl + '/remove/group',
						method: 'POST',
						contentType: 'application/json',
						data: JSON.stringify( data )
					}).done( function( data ) {
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
							deferred.resolve();
						}
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
		if( typeof( load ) != 'undefined' && load != null )
			$( '#info' ).html( Handlebars.compile( $( '#loading-tpl' ).html() )() );
		
		var self = this;
		var data = Object();
		data.group = this._activeGroup;
        // check if the user is allowed to edit this group
		return $.ajax({
            url: this._baseUrl + '/canedit',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( data )
        }).done( function( canedit ) {
			var source = $( '#content-tpl' ).html();
			var template = Handlebars.compile( source );
			var html = undefined;
			
			// check if a group has been selected
			if( typeof( self._activeGroup ) != 'undefined' && self._activeGroup != null ) {
				// check the users editing rights on the group
				if( canedit === true ) self._activeGroup.canedit = true;
				else delete self._activeGroup.canedit;
			
				// check if the current user is in the group
				$.each( self._me.groups, function( index, group ) {
					if( group.id == self._activeGroup.id ) html = template( { group: self._activeGroup, me: true } );
				});
			}
			
			// if the current user is not a member of this group, use alternative template render
			if( typeof( html ) == 'undefined' || html == null ) html = template( { group: self._activeGroup } );
			// render content
			$( '#info' ).html( html );
			$( '#info' ).focus();
			
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
			
			// button for leaving the group
			$( "#leave_group" ).click( function() {
				// remove the current user
				self.removeUser( self._me );
			});
			
			// admin only functions
			if( canedit ) {
				// expanding menu
				$( ".menu > td > a" ).click( function( e ) {
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
				$( ".menu > td > .options a" ).click( function( e ) {
					var target = $( this );
					// get the user id and required action
					var action = target.attr( "data-action" );
					var uid = target.attr( "data-id" );
					
					var user = undefined;
					// go through the members of the current group and look for the user
					$.each( self._activeGroup["members"], function( k, member ) {
						if( member["id"] == uid ) {
							user = member;
							return;
						}
					});
					
					// check if the user was found in the group
					if( typeof( user ) == 'undefined' || user == null ) return false;
					
					// choose the right action
					switch( action ) {
						case "addAdmin":
							self.addAdminUser( user );
							break;
						case "removeAdmin":
							self.removeAdminUser( user );
							break;
						case "remove":
							self.removeUser( user );
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
		
		$( this._users ).each( function( i, contact ) {
			if( self._search_id != id ) return false;
			$.each( contact, function( key, value ) {
				if( typeof( value ) != 'string' && typeof( value ) != 'number' ) return;
				value = String( value ).toLowerCase();
				if( ~value.indexOf( search ) ) {
					var in_group = false;
					// check if the user is already a member of this group
					$.each( self._activeGroup.members, function( i, member ) {
						// if the user is already a member of the group, we are done searching
						if( contact.id == member.id ) {
							in_group = true;
							return;
						}
					});
					// check if the user was identified as a member of the group
					if( !in_group && $.inArray( contact, matches ) == -1 ) {
						matches.push( contact );
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
				.text( user.name )
				// add the user information to the suggestion
				.data( 'user', user )
				// when clicked on the user, he will be added to the group
				.click( function() {
					var user = $( this ).data( 'user' );
					// clear the search box and suggestions
					$( "#group_add_member" ).val('');
					$( "#group_add_member" ).trigger( 'change' );
					// add the user to the group
					self.addUser( user );
				});
				
				// add the option to the search suggestions
				$( '#info .content-nav > .search + .search-suggestions' ).append( html );
			});
		}
		
		return true;
	},
	// add a user the currently active group
	addUser: function( user ) {
		var self = this;
		OC.msg.startSaving( '#info .msg' );
		// prepare the data for posting
		var data = Object();
		data.group = this._activeGroup;
		data.user = user;
		
		// add the user to the group
		return $.ajax({
            url: this._baseUrl + '/add/group/user',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( data )
        }).done( function( data ) {
			self.loadAll().done( function() {
				self.render().done( function() {
					OC.msg.finishedSaving( '#info .msg', data );
				});
			});
		});
	},
	// remove a user from the currently active group
	removeUser: function( user ) {
		var self = this;
		OC.msg.startSaving( '#info .msg' );
		// prepare the data for posting
		var data = Object();
		data.group = this._activeGroup;
		data.user = user;
		
		// add the user to the group
		return $.ajax({
            url: this._baseUrl + '/remove/group/user',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( data )
        }).done( function( data ) {
			self.loadAll().done( function() {
				self.render().done( function() {
					OC.msg.finishedSaving( '#info .msg', data );
				});
			});
		});
	},
	// add admin privileges to a user the currently active group
	addAdminUser: function( user ) {
		var self = this;
		OC.msg.startSaving( '#info .msg' );
		// prepare the data for posting
		var data = Object();
		data.group = this._activeGroup;
		data.user = user;
		
		// add the user to the group
		return $.ajax({
            url: this._baseUrl + '/add/group/user/admin',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( data )
        }).done( function( data ) {
			self.loadAll().done( function() {
				self.render().done( function() {
					OC.msg.finishedSaving( '#info .msg', data );
				});
			});
		});
	},
	// remove admin privileges from a user from the currently active group
	removeAdminUser: function( user ) {
		var self = this;
		OC.msg.startSaving( '#info .msg' );
		// prepare the data for posting
		var data = Object();
		data.group = this._activeGroup;
		data.user = user;
		
		// add the user to the group
		return $.ajax({
            url: this._baseUrl + '/remove/group/user/admin',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify( data )
        }).done( function( data ) {
			self.loadAll().done( function() {
				self.render().done( function() {
					OC.msg.finishedSaving( '#info .msg', data );
				});
			});
		});
	}
};

var groups = new Groups();
groups.loadAll().done( function() {
	groups.render();
});


});

})(OC, window, jQuery);