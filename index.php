<?php
/**
 * Plugin Name: Merit SP Batching
 * Plugin URI: http://boomtownig.com
 * Description: Batch vetted Service Partners to an SFTP server
 * Version: 0.1
 * Author: BoomtownIG
 * Author URI: http://www.boomtownig.com
 * License: MIT
 */

defined('ABSPATH') or die();

register_deactivation_hook( __FILE__, 'sp_deactivate' );
function sp_deactivate() {
	unregister_setting('general','sp_host', 'esc_attr');
	unregister_setting('general','sp_port', 'esc_attr');
	unregister_setting('general','sp_username', 'esc_attr');
	unregister_setting('general','sp_password', 'esc_attr');
	unregister_setting('general','sp_dfilepath', 'esc_attr');
}

/*****************************************************************************************************
	Add a options for sp_batching info
*****************************************************************************************************/
add_action('admin_init', 'sp_settings');
function sp_settings() {
	add_settings_section(
		'sp_settings_section',
		'Service Partner Batching Settings',
		'sp_section_options_callback',
		'general'
	);

	add_settings_field(
		'sp_host',
		'Hostname',
		'sp_textbox_callback',
		'general',
		'sp_settings_section',
		array(
			'sp_host',
		)
	);

	add_settings_field(
		'sp_port',
		'Port',
		'sp_number_callback',
		'general',
		'sp_settings_section',
		array(
			'sp_port',
		)
	);

	add_settings_field(
		'sp_username',
		'Username',
		'sp_textbox_callback',
		'general',
		'sp_settings_section',
		array(
			'sp_username',
		)
	);

	add_settings_field(
		'sp_password',
		'Password',
		'sp_password_callback',
		'general',
		'sp_settings_section',
		array(
			'sp_password',
		)
	);

	add_settings_field(
		'sp_dfilepath',
		'Destination File Path',
		'sp_textbox_callback',
		'general',
		'sp_settings_section',
		array(
			'sp_dfilepath',
		)
	);

	register_setting('general','sp_host', 'esc_attr');
	register_setting('general','sp_port', 'esc_attr');
	register_setting('general','sp_username', 'esc_attr');
	register_setting('general','sp_password', 'esc_attr');
	register_setting('general','sp_dfilepath', 'esc_attr');
}

function sp_section_options_callback() { // Section Callback
	echo '<p>This information is used to upload vetted Service Partners</p>';
}

function sp_password_callback($args) {  // Textbox Callback
	$option = get_option($args[0]);
	echo '<input type="password" id="'. $args[0] .'" name="'. $args[0] .'" value="' . $option . '" class="regular-text" />';
}

function sp_number_callback($args) {  // Number Callback
	$option = get_option($args[0]);
	echo '<input type="number" id="'. $args[0] .'" name="'. $args[0] .'" value="' . $option . '" class="regular-text" />';
}

function sp_textbox_callback($args) {  // Password Callback
	$option = get_option($args[0]);
	echo '<input type="text" id="'. $args[0] .'" name="'. $args[0] .'" value="' . $option . '" class="regular-text" />';
}

/*****************************************************************************************************
	Add a checkbox to declare whether or not the Service Partner has been vetted
*****************************************************************************************************/
add_action('post_submitbox_start', 'sp_add_vetted_checkbox');
function sp_add_vetted_checkbox() {
	global $post;

	if ($post->post_type !== 'mss_service_partner') {
		return;
	}
	?><div><label><input type="checkbox" name="sp_vetted" value="true" <?=get_post_meta($post->ID, 'sp_vetted', true) ? 'checked' : ''?> /> Vetted</label></div><?php
}

/*****************************************************************************************************
	Handle the checkbox declaring whether or not the Service Partner has been vetted
*****************************************************************************************************/
add_action('save_post_mss_service_partner', 'sp_save_vetted_info');
function sp_save_vetted_info($post_id) {

	$sp_vetted = false;
	if (isset($_REQUEST['sp_vetted'])) {
		$sp_vetted = true;
	}
	update_post_meta($post_id, 'sp_vetted', $sp_vetted);
}

/*****************************************************************************************************
	Only show Service Partners which have not yet been vetted
*****************************************************************************************************/
add_filter('posts_where' , 'sp_table_filtering');
function sp_table_filtering($where) {
	global $wpdb;
	global $typenow;

	if (is_admin()) :
		if ($typenow == 'mss_service_partner'):
			$where .= " AND ID NOT IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='sp_vetted' AND meta_value=1)";
		endif;
	endif;

	return $where;
}

/*****************************************************************
	add the button to batch to vetted Service Partners
*****************************************************************/
add_filter( 'views_edit-mss_service_partner', 'sp_add_batch_button' );
function sp_add_batch_button($views) {
	$views['my-button'] = '<button id="batch-vetted-records" type="button"  title="Batch Vetted Records" style="margin:5px">Batch Vetted Records</button>';
	return $views;
}

/*****************************************************************
	move the batch button and hook it to an AJAX endpoint
*****************************************************************/
add_action( 'admin_head-edit.php', 'sp_move_batch_button' );
function sp_move_batch_button() {
	global $current_screen;

	// Not our post type, exit earlier
	if ('mss_service_partner' != $current_screen->post_type)
		return;
	?>
		<script type="text/javascript">
			jQuery(document).ready( function($) {
				$('#batch-vetted-records')
					.prependTo('span.displaying-num')
					.click(function(evt) {
						$.ajax({
								url: '<?=admin_url('admin-ajax.php');?>',
								type: 'POST',
								data: {
									'action': 'batch_service_partners',
									'nonceBatchServicePartners': '<?=wp_create_nonce('nonce-batch-service-partners');?>',
								},
							})
							.always(function(data) {
								alert((data.status ? 'Success' : 'Error') + ': ' + data.message);
							})
							;
						evt.preventDefault();
						return false;
					})
					;
			});
		</script>
	<?php
}

/*****************************************************************
	register AJAX listener for batch endpoint
*****************************************************************/
add_action('wp_ajax_batch_service_partners', 'sp_batch_service_partners');
function sp_batch_service_partners() {

	header('Content-Type: application/json');

	if (!wp_verify_nonce($_POST['nonceBatchServicePartners'], 'nonce-batch-service-partners')) {
		echo json_encode(
			array(
				'status' => 0,
				'message' => 'Invalid Nonce!',
				)
			);
	} else {
		include '_ajax.batch_service_partners.php';
	}
	exit;
}
