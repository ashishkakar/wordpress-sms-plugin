<?php
/**
 * Plugin Name: Logon Utility SMS
 * Plugin URI: https://www.logonutility.com/plugins/wordpress.zip
 * Description: This plugin is used to add SMS functionality to yur Wordpress site. 
 * Version: 1.0.0
 * Requires at least: 5.7.1
 * Requires PHP: 7.3.7
 * Author: Ashish Kakar
 * Author URI: https://github.com/ashish-kakar
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
 
add_action( 'admin_menu', 'create_plugin_settings_page' );
add_action( 'init', 'register_events');
register_activation_hook( __FILE__, 'install' );
register_deactivation_hook( __FILE__, 'deactivate' );
register_uninstall_hook( __FILE__, 'uninstall' );

function create_plugin_settings_page() {
    add_menu_page( 'SMS', 'SMS', 'manage_options', 'sms-defaults' );
	add_submenu_page('sms-defaults', 'Default Settings', 'Default Settings', 'manage_options', 'sms-defaults', 'view_sms_defaults');
	add_submenu_page('sms-defaults', 'SMS Events', 'SMS Events', 'manage_options', 'sms-eventis', 'view_sms_events');
}

function register_events() {
	$count = esc_attr(get_option('event_count'));
	$count = is_numeric($count)?$count+1:1;
	for($i=1; $i<$count; $i++) {
		if(esc_attr(get_option('deleted'.$i))==1) continue;
		add_action(esc_attr(get_option('event'.$i)), 'call_sms_api');
	}
}

function install() {
	$state = esc_attr(get_option('plugin_state'));
	if($state=='d') {
		update_option('plugin_state', 'a');
		return;
	}
	add_option('plugin_state');
	update_option('plugin_state', 'a');
	add_option('def_apikey');
	add_option('def_campaign');
	add_option('def_route');
	add_option('def_senderid');
	add_option('def_entityid');
	add_option('event_count');
	update_option('event_count', 0);
}

function deactivate() {
	update_option('plugin_state', 'd');
}

function uninstall() {
	delete_option('plugin_state');
	delete_option('def_apikey');
	delete_option('def_campaign');
	delete_option('def_route');
	delete_option('def_senderid');
	delete_option('def_entityid');
	$count = esc_attr(get_option('event_count'));
	for($i=1; $i<=$count; $i++) {
		delete_option('deleted'.$i);
		delete_option('event'.$i);
		delete_option('apikey'.$i);
		delete_option('campaign'.$i);
		delete_option('route'.$i);
		delete_option('contacts'.$i);
		delete_option('senderid'.$i);
		delete_option('templateid'.$i);
		delete_option('entityid'.$i);
		delete_option('message'.$i);
	}
	delete_option('event_count');
}

function view_sms_defaults() {
	if(isset($_POST['def_apikey'])) {
		update_option('def_apikey', $_POST['def_apikey']);
		update_option('def_campaign', $_POST['def_campaign']);
		update_option('def_route', $_POST['def_route']);
		update_option('def_senderid', $_POST['def_senderid']);
		update_option('def_entityid', $_POST['def_entityid']);
	}
?>	
<div class="wrap">
<h1>Default SMS Settings</h1>
<form method="post">
<table class="form-table">
<?php wp_nonce_field( 'awesome_update', 'awesome_form' ); ?>
<tr valign="top"><th scope="row">Default API Key</th><td><input type="text" name="def_apikey" value="<?php echo esc_attr(get_option("def_apikey")); ?>" /></td></tr>
<tr valign="top"><th scope="row">Default Campaign ID</th><td><input type="text" name="def_campaign" value="<?php echo esc_attr(get_option("def_campaign")); ?>" /></td></tr>
<tr valign="top"><th scope="row">Default Route ID</th><td><input type="text" name="def_route" value="<?php echo esc_attr(get_option("def_route")); ?>" /></td></tr>
<tr valign="top"><th scope="row">Default Sender ID</th><td><input type="text" name="def_senderid" value="<?php echo esc_attr(get_option("def_senderid")); ?>" /></td></tr>
<tr valign="top"><th scope="row">Default Entity ID</th><td><input type="text" name="def_entityid" value="<?php echo esc_attr(get_option("def_entityid")); ?>" /></td></tr>
</table>
<p class="submit"><input type="submit" name="submit" class="button button-primary" value="Submit"></p>
</form>
</div>
<?php
}



function call_sms_api($var1=null, $var2=null, var3=null, var4=null, var5=null) {
	static $last_event=0;
	$count = esc_attr(get_option('event_count'));
	for($i=$last_event+1; $i<=$count; $i++) {
		if(esc_attr(get_option('deleted'.$i))==0 && esc_attr(get_option('event'.$i))==current_filter()) {
			$last_event = $i;
		}
	}
	
	$key = esc_attr(get_option('apikey'.$last_event));
	$campaign = esc_attr(get_option('campaign'.$last_event));
	$route = esc_attr(get_option('route'.$last_event));
	$contacts = esc_attr(get_option('contacts'.$last_event));
	$senderid = esc_attr(get_option('senderid'.$last_event));
	$message = esc_attr(get_option('message'.$last_event));
	$entityid = esc_attr(get_option('entityid'.$last_event));
	$templateid = esc_attr(get_option('templateid'.$last_event));
	$tlv = json_encode(array('EntityID'=>$entityid, 'ContentID'=>$templateid));
	$url = "https://module.logonutility.com/smsapi/index?key=$key&campaign=$campaign&routeid=$route&type=text&contacts=$contacts&senderid=$senderid&msg=$message&tlv=$tlv";
	if((current_filter()=='woocommerce_order_status_completed' || current_filter()=='woocommerce_order_status_cancelled') && $var1!=null) {
		$order = new WC_Order ($var1);
		preg_replace('/\[billing_address_1\]/', $order->get_billing_address_1(), $url);
		preg_replace('/\[billing_address_2\]/', $order->get_billing_address_2(), $url);
		preg_replace('/\[billing_city\]/', $order->get_billing_city(), $url);
		preg_replace('/\[billing_company\]/', $order->get_billing_company(), $url);
		preg_replace('/\[billing_country\]/', $order->get_billing_country(), $url);
		preg_replace('/\[billing_email\]/', $order->get_billing_email(), $url);
		preg_replace('/\[billing_first_name\]/', $order->get_billing_first_name(), $url);
		preg_replace('/\[billing_last_name\]/', $order->get_billing_last_name(), $url);
		preg_replace('/\[billing_phone\]/', $order->get_billing_phone(), $url);
		preg_replace('/\[billing_postcode\]/', $order->get_billing_postcode(), $url);
		preg_replace('/\[billing_state\]/', $order->get_billing_state(), $url);
		preg_replace('/\[cart_tax\]/', $order->get_cart_tax(), $url);
		preg_replace('/\[currency\]/', $order->get_currency(), $url);
		preg_replace('/\[customer_id\]/', $order->get_customer_id(), $url);
		preg_replace('/\[customer_ip_address\]/', $order->get_customer_ip_address(), $url);
		preg_replace('/\[customer_user_agent\]/', $order->get_customer_user_agent(), $url);
		preg_replace('/\[date_completed\]/', $order->get_date_completed().__toString(), $url);
		preg_replace('/\[date_created\]/', $order->get_date_created().__toString(), $url);
		preg_replace('/\[date_modified\]/', $order->get_date_modified().__toString(), $url);
		preg_replace('/\[date_paid\]/', $order->get_date_paid().__toString, $url);
		preg_replace('/\[discount_tax\]/', $order->get_discount_tax(), $url);
		preg_replace('/\[discount_to_display\]/', $order->get_discount_to_display(), $url);
		preg_replace('/\[discount_total\]/', $order->get_discount_total(), $url);
		preg_replace('/\[formatted_billing_address\]/', $order->get_formatted_billing_address(), $url);
		preg_replace('/\[formatted_billing_full_name\]/', $order->get_formatted_billing_full_name(), $url);
		preg_replace('/\[formatted_line_subtotal\]/', $order->get_formatted_line_subtotal(), $url);
		preg_replace('/\[formatted_order_total\]/', $order->get_formatted_order_total(), $url);
		preg_replace('/\[formatted_shipping_address\]/', $order->get_formatted_shipping_address(), $url);
		preg_replace('/\[formatted_shipping_full_name\]/', $order->get_formatted_shipping_full_name(), $url);
		preg_replace('/\[id\]/', $order->get_id(), $url);
		preg_replace('/\[line_subtotal\]/', $order->get_line_subtotal(), $url);
		preg_replace('/\[line_tax\]/', $order->get_line_tax(), $url);
		preg_replace('/\[line_total\]/', $order->get_line_total(), $url);
		preg_replace('/\[order_number\]/', $order->get_order_number(), $url);
		preg_replace('/\[payment_method\]/', $order->get_payment_method(), $url);
		preg_replace('/\[payment_method_title\]/', $order->get_payment_method_title(), $url);
		preg_replace('/\[qty_refunded_for_item\]/', $order->get_qty_refunded_for_item(), $url);
		preg_replace('/\[rounded_items_total\]/', $order->get_rounded_items_total(), $url);
		preg_replace('/\[shipping_address_1\]/', $order->get_shipping_address_1(), $url);
		preg_replace('/\[shipping_address_2\]/', $order->get_shipping_address_2(), $url);
		preg_replace('/\[shipping_city\]/', $order->get_shipping_city(), $url);
		preg_replace('/\[shipping_company\]/', $order->get_shipping_company(), $url);
		preg_replace('/\[shipping_country\]/', $order->get_shipping_country(), $url);
		preg_replace('/\[shipping_first_name\]/', $order->get_shipping_first_name(), $url);
		preg_replace('/\[shipping_last_name\]/', $order->get_shipping_last_name(), $url);
		preg_replace('/\[shipping_method\]/', $order->get_shipping_method(), $url);
		preg_replace('/\[shipping_postcode\]/', $order->get_shipping_postcode(), $url);
		preg_replace('/\[shipping_state\]/', $order->get_shipping_state(), $url);
		preg_replace('/\[shipping_tax\]/', $order->get_shipping_tax(), $url);
		preg_replace('/\[shipping_to_display\]/', $order->get_shipping_to_display(), $url);
		preg_replace('/\[shipping_total\]/', $order->get_shipping_total(), $url);
		preg_replace('/\[status\]/', $order->get_status(), $url);
		preg_replace('/\[subtotal\]/', $order->get_subtotal(), $url);
		preg_replace('/\[subtotal_to_display\]/', $order->get_subtotal_to_display(), $url);
		preg_replace('/\[total\]/', $order->get_total(), $url);
		preg_replace('/\[total_discount\]/', $order->get_total_discount(), $url);
		preg_replace('/\[total_fees\]/', $order->get_total_fees(), $url);
		preg_replace('/\[total_qty_refunded\]/', $order->get_total_qty_refunded(), $url);
		preg_replace('/\[total_refunded\]/', $order->get_total_refunded(), $url);
		preg_replace('/\[total_shipping\]/', $order->get_total_shipping(), $url);
		preg_replace('/\[total_shipping_refunded\]/', $order->get_total_shipping_refunded(), $url);
		preg_replace('/\[total_tax\]/', $order->get_total_tax(), $url);
		preg_replace('/\[total_tax_refunded\]/', $order->get_total_tax_refunded(), $url);
		preg_replace('/\[user_id\]/', $order->get_user_id(), $url);
	}
	wp_remote_get(urlencode($url));
	for($i=$last_event+1; $i<=$count; $i++) {
		if(esc_attr(get_option('deleted'.$count))==0 && esc_attr(get_option('event'.$count))==current_filter()) {
			//nop
		} else {
			$last_event=0;
		}
	}
}

function view_sms_events() {
	if(isset($_POST['action']) && $_POST['action']=='delete') {
		update_option('deleted'.$_POST['delete'], 1);
	}
	if(isset($_POST['action']) && $_POST['action']=='create') {
		$count = $_POST['count'];
		update_option('event_count', $count);
		add_option('deleted'.$count);
		add_option('event'.$count);
		add_option('apikey'.$count);
		add_option('campaign'.$count);
		add_option('route'.$count);
		add_option('contacts'.$count);
		add_option('senderid'.$count);
		add_option('templateid'.$count);
		add_option('entityid'.$count);
		add_option('message'.$count);
		update_option('deleted'.$count, $_POST['deleted'.$count]);
		update_option('event'.$count, $_POST['event'.$count]);
		update_option('apikey'.$count, $_POST['apikey'.$count]);
		update_option('campaign'.$count, $_POST['campaign'.$count]);
		update_option('route'.$count, $_POST['route'.$count]);
		update_option('contacts'.$count, $_POST['contacts'.$count]);
		update_option('senderid'.$count, $_POST['senderid'.$count]);
		update_option('templateid'.$count, $_POST['templateid'.$count]);
		update_option('entityid'.$count, $_POST['entityid'.$count]);
		update_option('message'.$count, $_POST['message'.$count]);
	}
	$count = esc_attr(get_option('event_count'));
	$count++;
	if(isset($_POST['action']) && $_POST['action']=='edit') {
		$count = $_POST['edit'];
	}

?>
<div class="wrap">
<h1>Add SMS Event</h1>
<form method="post">
<table class="form-table">
<input type="hidden" name="count" value="<?php echo $count; ?>" />
<input type="hidden" name="action" value="create" />
<input type="hidden" name="deleted<?=$count?>" value="0" /></td></tr>
<?php wp_nonce_field( 'awesome_update', 'awesome_form' ); ?>
<tr valign="top"><th scope="row">Event</th><td><input type="text" name="event<?=$count?>" value="<?php if(isset($_POST['edit_event'])) echo $_POST['edit_event']; ?>" /></td></tr>
<tr valign="top"><th scope="row">API Key</th><td><input type="text" name="apikey<?=$count?>" value="<?php if(isset($_POST['edit_apikey'])) echo $_POST['edit_apikey']; else echo esc_attr(get_option('def_apikey')); ?>" /></td></tr>
<tr valign="top"><th scope="row">Campaign ID</th><td><input type="text" name="campaign<?=$count?>" value="<?php if(isset($_POST['edit_campaign'])) echo $_POST['edit_campaign']; else echo esc_attr(get_option('def_campaign')); ?>" /></td></tr>
<tr valign="top"><th scope="row">Route ID</th><td><input type="text" name="route<?=$count?>" value="<?php if(isset($_POST['edit_route'])) echo $_POST['edit_route']; else echo esc_attr(get_option('def_route')); ?>" /></td></tr>
<tr valign="top"><th scope="row">Contacts</th><td><input type="text" name="contacts<?=$count?>" value="<?php if(isset($_POST['edit_contacts'])) echo $_POST['edit_contacts']; ?>" /></td></tr>
<tr valign="top"><th scope="row">Sender ID</th><td><input type="text" name="senderid<?=$count?>" value="<?php if(isset($_POST['edit_senderid'])) echo $_POST['edit_senderid']; else echo esc_attr(get_option('def_senderid')); ?>" /></td></tr>
<tr valign="top"><th scope="row">Template ID</th><td><input type="text" name="templateid<?=$count?>" value="<?php if(isset($_POST['edit_templateid'])) echo $_POST['edit_templateid']; ?>" /></td></tr>
<tr valign="top"><th scope="row">Entity ID</th><td><input type="text" name="entityid<?=$count?>" value="<?php if(isset($_POST['edit_entityid'])) echo $_POST['edit_entityid']; else echo esc_attr(get_option('def_entityid')); ?>" /></td></tr>
<tr valign="top"><th scope="row">Message</th><td><textarea name="message<?=$count?>"><?php if(isset($_POST['edit_message'])) echo $_POST['edit_message']; ?></textarea></td></tr>
</table>
<p class="submit"><input type="submit" name="submit" class="button button-primary" value="Add/Edit Event"></p>
</form>
<h2>SMS Events</h2>
<table>
<tr><th>Event</th><th>API Key</th><th>Campaign ID</th><th>Route ID</th><th>Contacts</th><th>Sender ID</th><th>Template ID</th><th>Entity ID</th><th>Message</th><th>Edit</th><th>Delete</th>
<?php
	$count = esc_attr(get_option('event_count'));
	for($i=1; $i<=$count; $i++) {
		if(esc_attr(get_option('deleted'.$i))==1) continue;
?>
<tr>
<td><?php echo esc_attr(get_option('event'.$i)); ?></td>
<td><?php echo esc_attr(get_option('apikey'.$i)); ?></td>
<td><?php echo esc_attr(get_option('campaign'.$i)); ?></td>
<td><?php echo esc_attr(get_option('route'.$i)); ?></td>
<td><?php echo esc_attr(get_option('contacts'.$i)); ?></td>
<td><?php echo esc_attr(get_option('senderid'.$i)); ?></td>
<td><?php echo esc_attr(get_option('templateid'.$i)); ?></td>
<td><?php echo esc_attr(get_option('entityid'.$i)); ?></td>
<td><?php echo esc_attr(get_option('message'.$i)); ?></td>
<td><form method="post"><input type="hidden" name="action" value="edit" /><?php wp_nonce_field( 'awesome_update', 'awesome_form' ); ?><input type="hidden" name="edit" value="<?=$i?>" /><input type="hidden" name="edit_event" value="<?php echo esc_attr(get_option('event'.$i)); ?>" /><input type="hidden" name="edit_apikey" value="<?php echo esc_attr(get_option('apikey'.$i)); ?>" /><input type="hidden" name="edit_campaign" value="<?php echo esc_attr(get_option('campaign'.$i)); ?>" /><input type="hidden" name="edit_route" value="<?php echo esc_attr(get_option('route'.$i)); ?>" /><input type="hidden" name="edit_contacts" value="<?php echo esc_attr(get_option('contacts'.$i)); ?>" /><input type="hidden" name="edit_senderid" value="<?php echo esc_attr(get_option('senderid'.$i)); ?>" /><input type="hidden" name="edit_templateid" value="<?php echo esc_attr(get_option('templateid'.$i)); ?>" /><input type="hidden" name="edit_entityid" value="<?php echo esc_attr(get_option('entityid'.$i)); ?>" /><textarea style="display:none;" name="edit_message"><?php echo esc_attr(get_option('message'.$i)); ?></textarea><p class="submit"><input type="submit" name="submit" class="button button-primary" value="Edit"></p></form></td>
<td><form method="post"><input type="hidden" name="action" value="delete" /><?php wp_nonce_field( 'awesome_update', 'awesome_form' ); ?><input type="hidden" name="delete" value="<?=$i?>" /><p class="submit"><input type="submit" name="submit" class="button button-primary" value="Delete"></p></form></td>
</tr>
<?php
	}
?>
</table>
</div>
<?php
}

function plugin_settings_page_content() {
        if( $_POST['updated'] === 'true' ){
            handle_form();
        } ?>
    	<div class="wrap">
    		<h2>My Awesome Settings Page</h2>
    		<form method="POST">
                <input type="hidden" name="updated" value="true" />
                <?php wp_nonce_field( 'awesome_update', 'awesome_form' ); ?>
                <table class="form-table">
                	<tbody>
                        <tr>
                    		<th><label for="username">Username</label></th>
                    		<td><input name="username" id="username" type="text" value="<?php echo get_option('awesome_username'); ?>" class="regular-text" /></td>
                    	</tr>
                        <tr>
                    		<th><label for="email">Email Address</label></th>
                    		<td><input name="email" id="email" type="text" value="<?php echo get_option('awesome_email'); ?>" class="regular-text" /></td>
                    	</tr>
                	</tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Send my Info!">
                </p>
    		</form>
    	</div> <?php
    }

function handle_form() {
        if( ! isset( $_POST['awesome_form'] ) || ! wp_verify_nonce( $_POST['awesome_form'], 'awesome_update' ) ){ ?>
           <div class="error">
               <p>Sorry, your nonce was not correct. Please try again.</p>
           </div> <?php
           exit;
        } else {
            $valid_usernames = array( 'admin', 'matthew' );
            $valid_emails = array( 'email@domain.com', 'anotheremail@domain.com' );

            $username = sanitize_text_field( $_POST['username'] );
            $email = sanitize_email( $_POST['email'] );

            if( in_array( $username, $valid_usernames ) && in_array( $email, $valid_emails ) ){
                update_option( 'awesome_username', $username );
                update_option( 'awesome_email', $email );?>
                <div class="updated">
                    <p>Your fields were saved!</p>
                </div> <?php
            } else { ?>
                <div class="error">
                    <p>Your username or email were invalid.</p>
                </div> <?php
            }
        }
    }
