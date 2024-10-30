<?php

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
 *
 * Plugin Name: MM Chat
 * Plugin URI: http://marcelomesquita.com/
 * Description: A plugin to talk online with the other users of a blog.
 * Author: Marcelo Mesquita
 * Version: 0.6
 * Author URI: http://marcelomesquita.com/
 */

class Chat
{
	// ATRIBUTES /////////////////////////////////////////////////////////////////////////////////////
	var $slug = 'mm-chat';
	var $dir  = '';
	var $url  = '';

	// METHODS ///////////////////////////////////////////////////////////////////////////////////////
	/**
	 * define chat tables
	 *
	 * @name    tables
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-05
	 * @updated 2011-05-05
	 * @return  void
	 */
	function tables()
	{
		global $wpdb;

		$wpdb->chat_messages = "{$wpdb->prefix}chat_messages";
	}

	/**
	 * create tables and initiate options
	 *
	 * @name    install
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-05
	 * @updated 2011-05-24
	 * @return  void
	 */
	function install()
	{
		global $wpdb, $wp_roles;

		// creating tables
		if( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->chat_messages}'" ) !== $wpdb->chat_messages )
		{
			$sql = "
			CREATE TABLE {$wpdb->chat_messages}
			(
				message_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				message_sender INTEGER UNSIGNED NOT NULL,
				message_receiver INTEGER UNSIGNED NOT NULL,
				message_content VARCHAR( 255 ) NOT NULL,
				message_status ENUM( 'chat', 'wait', 'approved', 'rejected', 'broad' ) DEFAULT 'wait' NOT NULL,
				message_time DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',

				PRIMARY KEY( message_id )
			)"; //print $sql;

			$wpdb->query( $sql );
		}

		// initializing options
		update_option( 'chat_max_length', 300 );
		update_option( 'chat_message_timeout', 3 );
		update_option( 'chat_contact_timeout', 15 );

		// creating privileges
		$role = get_role( 'administrator' );
			$role->add_cap( 'chat' );
			$role->add_cap( 'chat_options' );

		foreach( $wp_roles->role_names as $role => $rolename )
			$wp_roles->role_objects[ $role ]->add_cap( 'chat' );
	}

  /**
	 * drop tables and delete options
	 *
	 * @name    uninstall
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-05
	 * @updated 2011-05-05
	 * @return  void
	 */
  function uninstall()
	{
		global $wpdb, $wp_roles;

		// dropping tables and datas
		$wpdb->query( "DROP TABLES {$wpdb->chat_messages}" );
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'chat_last_message' OR meta_key = 'chat_last_activity'" );

		// deleting privileges
		foreach( $wp_roles->role_names as $role => $rolename )
		{
			$wp_roles->role_objects[ $role ]->remove_cap( 'chat' );
			$wp_roles->role_objects[ $role ]->remove_cap( 'chat_options' );
		}
	}

	/**
	 * create the administrative menus
	 *
	 * @name    menu
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-05
	 * @updated 2011-05-24
	 * @return  void
	 */
	function menus()
	{
		// add_submenu_page( $parent, $page_title, $menu_title, $access_level, $file, $function = '' )
		add_submenu_page( 'options-general.php', __( 'Chat', 'chat' ), __( 'Chat', 'chat' ), 'chat_options', 'chat', array( &$this, 'chat_options' ) );
	}

	/**
	 * ...
	 *
	 * @name    chat_options
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-05
	 * @updated 2011-05-25
	 * @return  void
	 */
	function chat_options()
	{
    // check permissions
		if( !current_user_can( 'chat_options' ) )
			return false;

		if( isset( $_POST[ 'chat_update_options' ] ) )
		{
			$chat_max_length      = ( int ) $_POST[ 'chat_max_length' ];
			$chat_message_timeout = ( int ) $_POST[ 'chat_message_timeout' ];
			$chat_contact_timeout = ( int ) $_POST[ 'chat_contact_timeout' ];

			// defaults
			if( empty( $chat_max_length ) )      $chat_max_length      = 100;
			if( empty( $chat_message_timeout ) ) $chat_message_timeout = 5;
			if( empty( $chat_contact_timeout ) ) $chat_contact_timeout = 15;

			// update options
			update_option( 'chat_max_length', $chat_max_length );
			update_option( 'chat_message_timeout', $chat_message_timeout );
			update_option( 'chat_contact_timeout', $chat_contact_timeout );
		}

		?>
      <div class="wrap">
        <h2><?php _e( 'Chat Options', 'chat' ); ?></h2>

        <form action="" method="post">
          <table class="form-table">
            <tr valign="top">
              <th><label for="chat_max_length"><?php _e( 'Message max length', 'chat' ); ?>:</label></th>
              <td>
                <input type="text" id="chat_max_length" name="chat_max_length" value="<?php print get_option( 'chat_max_length' ); ?>" maxlength="4" class="small-text" /><br />
                <span class="description"><?php _e( 'Max number of characters allowed.', 'chat' ); ?></span>
              </td>
            </tr>
            <tr valign="top">
              <th><label for="chat_message_timeout"><?php _e( 'Message Timeout', 'chat' ); ?>:</label></th>
              <td>
                <input type="text" id="chat_message_timeout" name="chat_message_timeout" value="<?php print get_option( 'chat_message_timeout' ); ?>" maxlength="2" class="small-text" /> <?php _e( 'seconds', 'chat' ); ?><br />
                <span class="description"><?php _e( 'Time between message update. <strong>Short timeouts can increase bandwidth transfer.</strong>', 'chat' ); ?></span>
              </td>
            </tr>
            <tr valign="top">
              <th><label for="chat_contact_timeout"><?php _e( 'Contact Timeout', 'chat' ); ?>:</label></th>
              <td>
                <input type="text" id="chat_contact_timeout" name="chat_contact_timeout" value="<?php print get_option( 'chat_contact_timeout' ); ?>" maxlength="2" class="small-text" /> <?php _e( 'seconds', 'chat' ); ?><br />
                <span class="description"><?php _e( 'Time between contact update. <strong>Short timeouts can increase bandwidth transfer.</strong>', 'chat' ); ?></span>
              </td>
            </tr>
          </table>
          <p class="submit">
						<input type="submit" name="chat_update_options" class="button-primary" value="<?php _e( 'Save' ); ?>" />
					</p>
        </form>

      </div>
    <?php
	}

  /**
	 * load necessary scripts
	 *
	 * @name    wp_scripts
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-24
	 * @updated 2011-05-31
	 * @return  void
	 */
	function wp_scripts()
	{
    ?>
      <script type="text/javascript">
        var dialog_x = 50;
        var dialog_y = 50;

        var ajaxurl              = '<?php print admin_url( 'admin-ajax.php' ); ?>';
        var chat_max_length      = <?php print get_option( 'chat_max_length' ); ?>;
        var chat_contact_timeout = <?php print get_option( 'chat_contact_timeout' ); ?>000;
        var chat_message_timeout = <?php print get_option( 'chat_message_timeout' ); ?>000;

        // error messages
        var chat_error_offline         = '<?php _e( 'You have to be logged to use the chat.', 'chat' ); ?>';
        var chat_error_update_contacts = '<?php _e( 'Something goes wrong...', 'chat' ); ?>';
      </script>
    <?php

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-core' );
    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_script( 'jquery-ui-draggable' );
    wp_enqueue_script( 'jquery-ui-resizable' );
    wp_enqueue_script( 'chat', "{$this->url}/js/chat.js", array( 'jquery' ) );

    wp_localize_script( 'chat', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
  }

  /**
	 * load necessary styles
	 *
	 * @name    wp_styles
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-24
	 * @updated 2011-05-25
	 * @return  void
	 */
	function wp_styles()
	{
    wp_enqueue_style( 'jquery-ui-dialog', "{$this->url}/css/smoothness/jquery-ui-1.8.13.custom.css" );
    wp_enqueue_style( 'chat', "{$this->url}/css/chat.css" );
  }

  /**
	 * return contact name
	 *
	 * @name    chat_contact_name
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-25
	 * @updated 2011-05-25
	 * @return  void
	 */
  function chat_contact_name( $contact_id )
	{
		// check permissions
		if( !current_user_can( 'chat' ) )
			return false;

		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->users} WHERE ID = %d", $contact_id ) );
	}

  /**
	 * ajax send messages
	 *
	 * @name    chat_send_message
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-25
	 * @updated 2011-05-25
	 * @return  void
	 */
	function chat_send_message()
	{
    // check permissions
    if( !current_user_can( 'chat' ) )
			return false;

    global $wpdb, $current_user;

    $message_receiver = ( int ) $_POST[ 'message_receiver' ];
		$message_content  = substr( strip_tags( $_POST[ 'message_content' ] ), 0, get_option( 'chat_max_length' ) );

		// save message
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->chat_messages} ( message_sender, message_receiver, message_content, message_status, message_time ) VALUES ( %d, %d, %s, 'chat', NOW() )", $current_user->id, $message_receiver, $message_content ) );

		// avoid flood
		if( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( message_id ) FROM {$wpdb->chat_messages} WHERE message_sender = %d AND message_time = NOW()", $current_user->id ) ) > 3 )
			wp_logout();

    exit();
  }

  /**
	 * ajax update messages
	 *
	 * @name    chat_update_messages
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-25
	 * @updated 2011-06-10
	 * @return  void
	 */
	function chat_update_messages()
	{
    // check permissions
    if( !current_user_can( 'chat' ) )
			return false;

    global $wpdb, $current_user;

		// get last checked message
		$chat_last_message = ( int ) get_usermeta( $current_user->id, 'chat_last_message' );

		// get messages
		$messages = $wpdb->get_results( $wpdb->prepare( "SELECT message_id, message_sender, message_receiver, message_content, message_time FROM {$wpdb->chat_messages} WHERE message_id > %d AND message_status = 'chat' AND ( message_sender = %d OR message_receiver = %d ) LIMIT 50", $chat_last_message, $current_user->id, $current_user->id ) );

		// get user blacklist
    $blacklist = get_user_meta( $current_user->id, 'chat_blacklist', true ); //print_r( $blacklist );

    // list messages
    header( 'content-type: text/xml' );

    // list messages
		?>
			<?php if( !empty( $messages ) ) : ?>
				<chat>
				<?php foreach( $messages as $message ) : ?>
          <?php if( in_array( $message->message_sender, $blacklist ) or in_array( $message->message_receiver, $blacklist ) ) continue; ?>
					<message id="<?php print $message->message_id; ?>">
						<contact id="<?php print ( $message->message_receiver !== $current_user->id ) ? $message->message_receiver : $message->message_sender; ?>"><?php print ( $message->message_receiver !== $current_user->id ) ? $this->chat_contact_name( $message->message_receiver ) : $this->chat_contact_name( $message->message_sender ); ?></contact>
						<sender><?php print $this->chat_contact_name( $message->message_sender ); ?></sender>
						<receiver><?php print $this->chat_contact_name( $message->message_receiver ); ?></receiver>
						<content><?php print $message->message_content; ?></content>
						<time><?php print $message->message_time; ?></time>
					</message>
					<?php $chat_last_message = $message->message_id; ?>
				<?php endforeach; ?>
				</chat>

				<?php update_usermeta( $current_user->id, 'chat_last_message', $chat_last_message ); ?>
			<?php endif; ?>
		<?php

    exit();
  }

  /**
	 * ajax update contacts
	 *
	 * @name    chat_update_contacts
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-25
	 * @updated 2011-12-29
	 * @return  void
	 */
	function chat_update_contacts()
	{
    // check permissions
    if( !current_user_can( 'chat' ) )
			return false;

    global $wpdb, $current_user;

		// get the contact timeout
		$contact_timeout = get_option( 'chat_contact_timeout' );

		// update the user last activity
		update_usermeta( $current_user->id, 'chat_last_activity', time() );

		// get online users
		$contacts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, display_name, meta_value as chat_last_activity FROM {$wpdb->users} INNER JOIN {$wpdb->usermeta} ON (ID = user_id) WHERE user_id <> %d AND meta_key = 'chat_last_activity'", $current_user->ID ) );

    // get user blacklist
    $blacklist = get_user_meta( $current_user->id, 'chat_blacklist', true ); //print_r( $blacklist );

    // order contacts
    $contacts = $this->chat_order_contacts( $contacts, $blacklist );

		// list contacts
		?>
			<?php if( !empty( $contacts ) ) : ?>
				<ul>
				<?php foreach( $contacts as $contact ) : ?>
					<li class="<?php print ( $contact->chat_last_activity > ( time() - ( 2 * $contact_timeout ) ) ) ? 'online' : 'offline'; ?> <?php if( $odd != $odd ) print 'odd'; ?>">
						<a href="#talk" contact="<?php print $contact->ID; ?>" title="<?php ( $contact->chat_last_activity > ( time() - ( 2 * $contact_timeout ) ) ) ? printf( __( 'talk with %s', 'chat' ), $contact->display_name ) : printf( __( 'send offline message to %s', 'chat' ), $contact->display_name ); ?>" class="talk"><?php print $contact->display_name; ?></a>
						<div class="chat-contact-options">
              <?php if( is_array( $blacklist ) and in_array( $contact->ID, $blacklist ) ) : ?>
                <a href="#unblock" contact="<?php print $contact->ID; ?>" title="<?php printf( __( 'unblock %s', 'chat' ), $contact->display_name ); ?>" class="unblock ui-icon ui-icon-locked"><?php _e( 'unblock', 'chat' ); ?></a>
              <?php else : ?>
                <a href="#block" contact="<?php print $contact->ID; ?>" title="<?php printf( __( 'block %s', 'chat' ), $contact->display_name); ?>" class="block ui-icon ui-icon-unlocked"><?php _e( 'block', 'chat' ); ?></a>
              <?php endif; ?>
              <!--<a href="#friend" contact="<?php print $contact->ID; ?>" title="<?php _e( 'friend', 'chat' ); ?>" class="ui-icon ui-icon-star"><?php _e( 'friend', 'chat' ); ?></a>-->
              <a href="<?php print get_author_posts_url( $contact->ID ); ?>" contact="<?php print $contact->ID; ?>" title="<?php _e( 'profile', 'chat' ); ?>" class="ui-icon ui-icon-person"><?php _e( 'Profile', 'chat' ); ?></a>
            </div>
					</li>
				<?php endforeach; ?>
				</ul>
      <?php else : ?>
        <p><?php _e( 'no users' ); ?></p>
			<?php endif; ?>
		<?php

    exit();
  }

  /**
	 * order contacts
	 *
	 * @name    chat_order_contacts
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-06-10
	 * @updated 2011-11-22
	 * @return  array
	 */
	function chat_order_contacts( $contacts, $blacklist )
	{
		if( !is_array( $contacts ) or !is_array( $blacklist ) )
			return $contacts;

    $ordered_contacts = array();

    if( !empty( $contacts ) )
    {
      // keep alphabetical after order
      $contacts = array_reverse( $contacts );

      foreach( $contacts as $contact )
      {
        if( in_array( $contact->ID, $blacklist ) )
          array_push( $ordered_contacts, $contact );
        else
          array_unshift( $ordered_contacts, $contact );
      }
    }

    return $ordered_contacts;
  }

  /**
	 * ajax block contact
	 *
	 * @name    chat_block_contact
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-31
	 * @updated 2011-06-10
	 * @return  void
	 */
	function chat_block_contact()
	{
    // check permissions
    if( !current_user_can( 'chat' ) )
			return false;

    global $wpdb, $current_user;

    $contact = ( int ) $_POST[ 'contact' ];

		if( !empty( $contact ) )
    {
      $blacklist = array();

      // get user blacklist
      $blacklist = get_user_meta( $current_user->id, 'chat_blacklist', true );

      // search contact key
      $key = array_search( $contact, $blacklist );

      // add contact to user blacklist
      if( empty( $key ) )
        $blacklist[ $contact ] = $contact;

      // save user blacklist
      update_user_meta( $current_user->id, 'chat_blacklist', $blacklist );
    }

    exit();
  }

  /**
	 * ajax unblock contact
	 *
	 * @name    chat_unblock_contact
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-31
	 * @updated 2011-06-10
	 * @return  void
	 */
	function chat_unblock_contact()
	{
    // check permissions
    if( !current_user_can( 'chat' ) )
			return false;

    global $wpdb, $current_user;

    $contact = ( int ) $_POST[ 'contact' ];

		if( !empty( $contact ) )
    {
      $blacklist = array();

      // get user blacklist
      $blacklist = get_user_meta( $current_user->id, 'chat_blacklist', true );

      // search contact key
      $key = array_search( $contact, $blacklist );

      // remove contact to user blacklist
      if( !empty( $key ) )
        unset( $blacklist[ $key ] );

      // save user blacklist
      update_user_meta( $current_user->id, 'chat_blacklist', $blacklist );
    }

    exit();
  }

	// CONSTRUCTOR ///////////////////////////////////////////////////////////////////////////////////
	/**
	 * @name    Chat
	 * @author  Marcelo Mesquita <stallefish@gmail.com>
	 * @since   2011-05-05
	 * @updated 2011-06-10
	 * @return  void
	 */
	function Chat()
	{
		// define plugin url
		$this->url = WP_PLUGIN_URL . '/' . $this->slug;

		// define plugin dir
		$this->dir = WP_PLUGIN_DIR . '/' . $this->slug;

		// load languages
		load_plugin_textdomain( 'category-color', '', 'lang' );

    // load tables
    $this->tables();

    // install o plugin
		register_activation_hook( __FILE__, array( &$this, 'install' ) );

		// uninstall plugin
		register_deactivation_hook( __FILE__, array( &$this, 'uninstall' ) );

		// menu
		add_action( 'admin_menu', array( &$this, 'menus' ) );

		// load scripts
    add_action( 'wp_enqueue_scripts', array( &$this, 'wp_scripts' ) );
		//add_action( 'wp_print_scripts', array( &$this, 'wp_scripts' ) );

		// load styles
		add_action( 'wp_print_styles', array( &$this, 'wp_styles' ) );
    //add_action( 'admin_print_styles', array( &$this, 'wp_styles' ) );

    // define ajax handlers
    add_action( 'wp_ajax_chat_send_message', array( &$this, 'chat_send_message' ) );
    add_action( 'wp_ajax_chat_update_messages', array( &$this, 'chat_update_messages' ) );
    add_action( 'wp_ajax_chat_block_contact', array( &$this, 'chat_block_contact' ) );
    add_action( 'wp_ajax_chat_unblock_contact', array( &$this, 'chat_unblock_contact' ) );
    add_action( 'wp_ajax_chat_update_contacts', array( &$this, 'chat_update_contacts' ) );

    add_action( 'wp_ajax_chat_block_contac', array( &$this, 'chat_block_contact' ) );

		// includes
		require( "{$this->dir}/chat-widget.php" );
	}

	// DESTRUCTOR ////////////////////////////////////////////////////////////////////////////////////

}

$Chat = new Chat();

?>