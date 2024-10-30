/**
 * Copyright (c) 2010 Marcelo Mesquita
 *
 * Written by Marcelo Mesquita <stallefish@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * Public License can be found at http://www.gnu.org/copyleft/gpl.html
 */

// ATTRIBUTES ////////////////////////////////////////////////////////////////////////////////////

// METHODS ///////////////////////////////////////////////////////////////////////////////////////
/**
 * ...
 *
 * @name    open_dialog
 * @author  Marcelo Mesquita <stallefish@gmail.com>
 * @since   2011-05-24
 * @updated 2011-05-26
 * @return  void
 */
function open_dialog( contact_id, contact_name )
{
	if( jQuery( '#dialog-' + contact_id ).html() == null )
	{
		jQuery( 'body' ).append( '<div id="dialog-' + contact_id + '" class="chat" title="' + contact_name + '"><div class="chat-read"></div><div class="chat-write"><textarea name="' + contact_id + '"></textarea></div>' );

		jQuery( '#dialog-' + contact_id ).dialog( { position: [ dialog_x, dialog_y ] } );

		dialog_x = dialog_x + 10;
		dialog_y = dialog_y + 10;
	}
	else
	{
		jQuery( '#dialog-' + contact_id ).dialog( 'open' );
		jQuery( '#dialog-' + contact_id ).dialog( 'moveToTop' );
	}

	jQuery( '#dialog-' + contact_id + ' textarea' ).focus();
}

/**
 * ...
 *
 * @name    chat_send_message
 * @author  Marcelo Mesquita <stallefish@gmail.com>
 * @since   2011-05-24
 * @updated 2011-05-25
 * @return  void
 */
function chat_send_message( message_receiver, message_content )
{
	jQuery.ajax( {
		type: 'POST',
		url:  ajaxurl,
		data: '&action=chat_send_message&message_receiver=' + message_receiver + '&message_content=' + message_content
	} );
}

/**
 * ...
 *
 * @name    chat_block_contact
 * @author  Marcelo Mesquita <stallefish@gmail.com>
 * @since   2011-06-10
 * @updated 2011-06-10
 * @return  void
 */
function chat_block_contact( contact )
{
	jQuery.ajax( {
		type: 'POST',
		url:  ajaxurl,
		data: '&action=chat_block_contact&contact=' + contact
	} );
}

/**
 * ...
 *
 * @name    chat_unblock_contact
 * @author  Marcelo Mesquita <stallefish@gmail.com>
 * @since   2011-06-10
 * @updated 2011-06-10
 * @return  void
 */
function chat_unblock_contact( contact )
{
	jQuery.ajax( {
		type: 'POST',
		url:  ajaxurl,
		data: '&action=chat_unblock_contact&contact=' + contact
	} );
}

/**
 * ...
 *
 * @name    chat_update_contacts
 * @author  Marcelo Mesquita <stallefish@gmail.com>
 * @since   2011-05-24
 * @updated 2011-06-10
 * @return  void
 */
function chat_update_contacts()
{
	jQuery.ajax( {
		type:     'POST',
		url:      ajaxurl,
		data:     '&action=chat_update_contacts',
		complete: function() {
			chat_bind();

			setTimeout( 'chat_update_contacts()', chat_contact_timeout );
		},
		success:  function( data ) {
			if( '-1' == data )
			{
				//jQuery( '.ui-dialog textarea' ).attr( 'readonly', 'readonly' );

			  jQuery( '#chat-contacts' ).html( chat_error_offline );
			}
			else
			{
				//jQuery( '.ui-dialog textarea' ).attr( 'readonly', '' );

				jQuery( '#chat-contacts' ).html( data );
			}
		},
		error:    function( jqXHR, textStatus, errorThrown ) {
			//jQuery( '.ui-dialog textarea' ).attr( 'readonly', 'readonly' );

			jQuery( '#chat-contacts' ).html( chat_error_update_contacts + ' ' + textStatus + ': ' + errorThrown );
		}
	});
}

/**
 * ...
 *
 * @name    chat_update_messages
 * @author  Marcelo Mesquita <stallefish@gmail.com>
 * @since   2011-05-24
 * @updated 2011-10-18
 * @return  void
 */
function chat_update_messages()
{
	var rolling = true;

	jQuery.ajax( {
		type:     'POST',
		url:      ajaxurl,
		data:     '&action=chat_update_messages',
		complete: function() {
			chat_bind();
			setTimeout( 'chat_update_messages()', chat_message_timeout );
		},
		success:  function( xml ) {
			jQuery( 'message', xml ).each( function() {
				var message_id      = jQuery( this ).attr( 'id' );
				var contact_id      = jQuery( this ).children( 'contact' ).attr( 'id' );
				var contact_name    = jQuery( this ).children( 'contact' ).text();

				var message_sender  = jQuery( this ).children( 'sender' ).text();
				var message_content = jQuery( this ).children( 'content' ).text();
				var message_time    = jQuery( this ).children( 'time' ).text();

				open_dialog( contact_id, contact_name );

				// check if user is rolling
				//alert( jQuery( '#dialog-' + contact_id + ' .chat-read' )[ 0 ].scrollHeight + " <= " + ( jQuery( '#dialog-' + contact_id + ' .chat-read' ).scrollTop() + jQuery( '#dialog-' + contact_id + ' .chat-read' ).height() ) );
				rolling = ( jQuery( '#dialog-' + contact_id + ' .chat-read' )[ 0 ].scrollHeight <= ( jQuery( '#dialog-' + contact_id + ' .chat-read' ).scrollTop() + jQuery( '#dialog-' + contact_id + ' .chat-read' ).height() ) ) ? true : false;

				// push new messages
				jQuery( '#dialog-' + contact_id + ' .chat-read' ).append( '<p id="message-' + message_id + '" title="' + message_time + '"><strong>' + message_sender + '</strong> &raquo; ' + message_content + '</p>' );

				// auto roll (only if user is not rolling)
				if( rolling )
					jQuery( '#dialog-' + contact_id + ' .chat-read' ).scrollTop( jQuery( '#dialog-' + contact_id + ' .chat-read' )[ 0 ].scrollHeight );
			});

			//alert( xml );
		}
	});
}

/**
 * define chat events
 *
 * @name    chat_events
 * @author  Marcelo Mesquita <stallefish@gmail.com>
 * @since   2011-05-24
 * @updated 2011-06-10
 * @return  void
 */
function chat_bind()
{
	// avoid event duplication
	jQuery( '.talk' ).unbind( 'click' );
	jQuery( '.block' ).unbind( 'click' );
	jQuery( '.unblock' ).unbind( 'click' );
	jQuery( '.chat-write textarea' ).unbind( 'keypress' );

	// open a dialog when click to talk
	jQuery( '.talk' ).bind( 'click', function() {
		var contact_id   = jQuery( this ).attr( 'contact' );
		var contact_name = jQuery( this ).html();

		open_dialog( contact_id, contact_name );

		return false;
	} );

	// block the contact
	jQuery( '.block' ).bind( 'click', function() {
	 var contact_id = jQuery( this ).attr( 'contact' );

	 chat_block_contact( contact_id );

	 return false;
	} );

	// block the contact
	jQuery( '.unblock' ).bind( 'click', function() {
	 var contact_id = jQuery( this ).attr( 'contact' );

	 chat_unblock_contact( contact_id );

	 return false;
	} );

	// send the message when press 'Enter'
	jQuery( '.chat-write textarea' ).bind( 'keypress', function( e ) {
		if( e.which == 13 )
		{
			var message_receiver = jQuery( this ).attr( 'name' );
			var message_content  = jQuery( this ).val();

			// clear textbox
			jQuery( this ).val( '' );

			// send the message only if have some text
			if( message_content !== '' )
			{
				chat_send_message( message_receiver, message_content );
			}

			return false;
		}
		 else
		{
			var message_content = jQuery( this ).val();

			if( message_content.length > chat_max_length )
			{
				jQuery( this ).val( message_content.substr( 0, chat_max_length ) );
			}
		}
	} );

	// wich code is some key
	// jQuery( document ).keypress( function( e ){ alert( e.keyCode ); } );
}

// CONSTRUCTOR ///////////////////////////////////////////////////////////////////////////////////
jQuery( function() {
	chat_update_contacts();
	chat_update_messages();

	chat_bind();
} );