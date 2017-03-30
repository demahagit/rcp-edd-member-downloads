<?php
/**
 * Plugin Name: Restrict Content Pro - EDD Member Downloads
 * Description: Allow members to download a certain number of items based on their subscription level.
 * Version: 1.0.2
 * Author: Restrict Content Pro Team
 * Text Domain: rcp-edd-member-downloads
 */


/**
 * Loads the plugin textdomain.
 */
function rcp_edd_member_downloads_textdomain() {
	load_plugin_textdomain( 'rcp-edd-member-downloads', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'rcp_edd_member_downloads_textdomain' );


/**
 * Adds the plugin settings form fields to the subscription level form.
 */
function rcp_edd_member_downloads_level_fields( $level ) {

	if ( ! function_exists( 'EDD' ) ) {
		return;
	}

	global $rcp_levels_db;

	if ( empty( $level->id ) ) {
		$allowed = 0;
	} else {
		$existing = $rcp_levels_db->get_meta( $level->id, 'edd_downloads_allowed', true );
		$allowed  = ! empty( $existing ) ? $existing : 0;
	}
	?>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="rcp-edd-downloads-allowed"><?php printf( __( '%s Allowed', 'rcp-edd-member-downloads' ), edd_get_label_plural() ); ?></label>
		</th>
		<td>
			<input type="number" min="0" step="1" id="rcp-edd-downloads-allowed" name="rcp-edd-downloads-allowed" value="<?php echo absint( $allowed ); ?>" style="width: 60px;"/>
			<p class="description"><?php printf( __( 'The number of %s allowed each subscription period.', 'rcp-edd-member-downloads' ), strtolower( edd_get_label_plural() ) ); ?></p>
		</td>
	</tr>

	<?php
	wp_nonce_field( 'rcp_edd_downloads_allowed_nonce', 'rcp_edd_downloads_allowed_nonce' );
}
add_action( 'rcp_add_subscription_form', 'rcp_edd_member_downloads_level_fields' );
add_action( 'rcp_edit_subscription_form', 'rcp_edd_member_downloads_level_fields' );



/**
 * Saves the subscription level limit settings.
 */
function rcp_edd_member_downloads_save_level_limits( $level_id = 0, $args = array() ) {

	if ( ! function_exists( 'EDD' ) ) {
		return;
	}

	global $rcp_levels_db;

	if ( empty( $_POST['rcp_edd_downloads_allowed_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_edd_downloads_allowed_nonce'], 'rcp_edd_downloads_allowed_nonce' ) ) {
		return;
	}

	if ( empty( $_POST['rcp-edd-downloads-allowed'] ) ) {
		$rcp_levels_db->delete_meta( $level_id, 'edd_downloads_allowed' );
		return;
	}

	$rcp_levels_db->update_meta( $level_id, 'edd_downloads_allowed', absint( $_POST['rcp-edd-downloads-allowed'] ) );
}
add_action( 'rcp_add_subscription', 'rcp_edd_member_downloads_save_level_limits', 10, 2 );
add_action( 'rcp_edit_subscription_level', 'rcp_edd_member_downloads_save_level_limits', 10, 2 );


/**
 * Determines if the member is at the product submission limit.
 *
 * @param int $user_id ID of the user to check, or 0 for current user.
 *
 * @since  1.0
 * @return bool True if the user is at the limit, false if not.
 */
function rcp_edd_member_downloads_member_at_limit( $user_id = 0 ) {

	if ( ! function_exists( 'rcp_get_subscription_id' ) ) {
		return;
	}

	if ( empty( $user_id ) ) {
		$user_id = wp_get_current_user()->ID;
	}

	$remaining = rcp_edd_member_downloads_number_remaining( $user_id );
	$at_limit  = ( $remaining > 0 ) ? false : true;

	return $at_limit;
}

/**
 * Get the number of downloads remaining for a user.
 *
 * @param int $user_id ID of the user to check, or 0 for current user.
 *
 * @since  1.0.1
 * @return int|false Number of downloads available.
 */
function rcp_edd_member_downloads_number_remaining( $user_id = 0 ) {

	if ( ! function_exists( 'rcp_get_subscription_id' ) ) {
		return false;
	}

	global $rcp_levels_db;

	if ( empty( $user_id ) ) {
		$user_id = wp_get_current_user()->ID;
	}

	$remaining = 0;

	$sub_id = rcp_get_subscription_id( $user_id );

	if ( $sub_id ) {
		$max       = (int) $rcp_levels_db->get_meta( $sub_id, 'edd_downloads_allowed', true );
		$current   = (int) get_user_meta( $user_id, 'rcp_edd_member_downloads_current_download_count', true );
		$remaining = $max - $current;
	}

	if ( $remaining < 0 ) {
		$remaining = 0;
	}

	return $remaining;

}

/**
 * Displays the number of downloads the current user has remaining in this period.
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @since  1.0.1
 * @return int|false
 */
function rcp_edd_member_downloads_remaining_shortcode( $atts, $content = null ) {
	return rcp_edd_member_downloads_number_remaining();
}

add_shortcode( 'rcp_edd_member_downloads_remaining', 'rcp_edd_member_downloads_remaining_shortcode' );


/**
 * Resets a vendor's product submission count when making a new payment.
 */
function rcp_edd_member_downloads_reset_limit( $payment_id, $args = array(), $amount ) {

	if ( ! empty( $args['user_id'] ) ) {
		delete_user_meta( $args['user_id'], 'rcp_edd_member_downloads_current_download_count' );
	}
}
add_action( 'rcp_insert_payment', 'rcp_edd_member_downloads_reset_limit', 10, 3 );


/**
 * Determines if a user has a membership that allows downloads.
 */
function rcp_edd_member_downloads_user_has_download_membership( $user_id ) {

	if ( empty( $user_id ) ) {
		$user_id = wp_get_current_user()->ID;
	}

	$member = new RCP_Member( $user_id );

	global $rcp_levels_db;

	if ( ! $sub_id = $member->get_subscription_id() ) {
		return false;
	}

	if ( $member->is_expired() || 'pending' === $member->get_status() ) {
		return false;
	}

	$max = (int) $rcp_levels_db->get_meta( $sub_id, 'edd_downloads_allowed', true );

	if ( ! empty( $max ) && $max > 0 ) {
		return true;
	}

	return false;
}


function rcp_edd_member_downloads_download_button( $purchase_form, $args ) {

	if ( ! is_user_logged_in() ) {
		return $purchase_form;
	}

	// @todo support bundles
	if ( edd_is_bundled_product( $args['download_id'] ) ) {
		return $purchase_form;
	}

	// @todo maybe support variable prices
	if ( edd_has_variable_prices( $args['download_id'] ) ) {
		return $purchase_form;
	}

	// Check to see if the product has files
	$files = edd_get_download_files( $args['download_id'] );
	if ( empty( $files ) ) {
		return $purchase_form;
	}

	// Check if the member has a membership that allows downloads
	$user = wp_get_current_user();
	if ( ! rcp_edd_member_downloads_user_has_download_membership( $user->ID ) ) {
		return $purchase_form;
	}

	// Check if the member is at the download limit
	if ( rcp_edd_member_downloads_member_at_limit( $user->ID ) && ! edd_has_user_purchased( $user->ID, $args['download_id'] ) ) {
		return $purchase_form;
	}

	global $edd_displayed_form_ids;

	$download = new EDD_Download( $args['download_id'] );

	if ( isset( $edd_displayed_form_ids[ $download->ID ] ) ) {
		$edd_displayed_form_ids[ $download->ID ]++;
	} else {
		$edd_displayed_form_ids[ $download->ID ] = 1;
	}
?>
	<script type="text/javascript">
		(function($) {
			$(document).ready(function() {
				$('.rcp-edd-member-download-request').on('click', function(e) {
					e.preventDefault();
					e.stopImmediatePropagation();
					var item = $(this).parent().find("input[name='rcp-edd-member-download-request']").val();
					var data = {
						action: 'rcp-edd-member-download-request',
						security: $('#rcp-edd-member-download-nonce').val(),
						item: item
					}

					$.ajax({
						data: data,
						type: "POST",
						dataType: "json",
						url: edd_scripts.ajaxurl,
						success: function (response) {
							if ( response.file && response.file.length > 0 ) {
								window.location.replace(response.file);
							}
						},
						error: function (response) {
							console.log('error ' + response);
						}
					});
				});
			});
		})(jQuery);
	</script>

<?php
	$form_id      = ! empty( $args['form_id'] ) ? $args['form_id'] : 'edd_purchase_' . $download->ID;
	$button_color = edd_get_option( 'checkout_color', 'blue' );
	ob_start();
?>
	<form id="<?php echo $form_id; ?>" class="edd_download_purchase_form edd_purchase_<?php echo absint( $download->ID ); ?>" method="post">
		<input type="hidden" name="download_id" value="<?php echo esc_attr( $download->ID ); ?>">
		<input type="hidden" name="rcp-edd-member-download-request" value="<?php echo esc_attr( $download->ID ); ?>">
		<input type="hidden" id="rcp-edd-member-download-nonce" name="rcp-edd-member-download-nonce" value="<?php echo wp_create_nonce( 'rcp-edd-member-download-nonce' ); ?>">
		<input type="submit" class="rcp-edd-member-download-request button edd-submit <?php echo esc_attr( $button_color ); ?>" value="<?php esc_html_e( 'Download', 'rcp-edd-member-downloads' ); ?>">
	</form>
<?php
	return ob_get_clean();

}
add_filter( 'edd_purchase_download_form', 'rcp_edd_member_downloads_download_button', 10, 2 );


function rcp_edd_member_downloads_process_ajax_download() {

	global $rcp_levels_db;

	check_ajax_referer( 'rcp-edd-member-download-nonce', 'security' );

	if ( ! is_user_logged_in() ) {
		wp_die(-1);
	}

	// Check if the member has a membership that allows downloads
	$user = wp_get_current_user();
	if ( ! rcp_edd_member_downloads_user_has_download_membership( $user->ID ) ) {
		wp_die(-1);
	}

	if ( empty( $_POST['item'] ) ) {
		wp_die(-1);
	} else {
		$item = absint( $_POST['item'] );
	}

	if ( edd_has_user_purchased( $user->ID, $item ) ) {

		$payment_args = array(
			'number'   => 1,
			'status'   => 'publish',
			'user'     => $user->ID,
			'download' => $item,
			'meta_key' => '_rcp_edd_member_downloads'
		);

		$payments = new EDD_Payments_Query( $payment_args );

		$payment  = $payments->get_payments();

		if ( ! $payment ) {
			unset($payment_args['meta_key'] );
			$payments = new EDD_Payments_Query( $payment_args );
			$payment  = $payments->get_payments();
		}

		$payment_meta = edd_get_payment_meta( $payment[0]->ID );

		$files        = edd_get_download_files( $payment_meta['cart_details'][0]['id'] );

		if ( ! empty( $files ) ) {
			$file_keys = array_keys( $files );
			$url       = edd_get_download_file_url( $payment_meta['key'], $payment_meta['user_info']['email'], $file_keys[0], $payment_meta['cart_details'][0]['id'] );
		} else {

			$files    = false;
			$file_key = false;

			foreach ( $payment_meta['cart_details'] as $key => $cart_item ) {
				if ( $cart_item['id'] === $item ) {
					$files = edd_get_download_files( $cart_item['id'] );
					$file_key = $key;
					break;
				}
			}

			if ( $files && $file_key ) {
				$file_keys = array_keys( $files );
				$url       = edd_get_download_file_url( $payment_meta['key'], $payment_meta['user_info']['email'], $file_keys[0], $payment_meta[$key] );
			}
		}

	} else {

		remove_action( 'edd_complete_purchase', 'edd_trigger_purchase_receipt', 999 );

		$sub_id = rcp_get_subscription_id( $user->ID );

		if ( ! $sub_id ) {
			wp_die( __( 'You do not have a membership.', 'rcp-edd-member-downloads' ) );
		}

		$max = (int) $rcp_levels_db->get_meta( $sub_id, 'edd_downloads_allowed', true );

		if ( empty( $max ) ) {
			wp_die( __( 'You must have a valid membership.', 'rcp-edd-member-downloads' ) );
		}

		$current = get_user_meta( $user->ID, 'rcp_edd_member_downloads_current_download_count', true );

		if ( $current >= $max ) {
			wp_die( __( 'You have reached the limit defined by your membership.', 'rcp-edd-member-downloads' ) );
		}

		$payment = new EDD_Payment();
		$payment->add_download( $item, array( 'item_price' => 0.00 ) );
		$payment->email      = $user->user_email;
		$payment->first_name = $user->first_name;
		$payment->last_name  = $user->last_name;
		$payment->user_id    = $user->ID;
		$payment->user_info  = array(
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'email'      => $user->user_email,
			'id'         => $user->ID
		);
		$payment->gateway = 'manual';
		$payment->status  = 'pending';
		$payment->save();
		$payment->status  = 'complete';
		$payment->save();

		// Add a piece of meta to the payment letting us know it was created by this plugin. We query using this meta for future checks.
		edd_update_payment_meta( $payment->ID, '_rcp_edd_member_downloads', true );

		edd_insert_payment_note( $payment->ID, __( 'Downloaded with RCP membership', 'rcp-edd-member-downloads' ) );

		$payment_meta = edd_get_payment_meta( $payment->ID );
		$files        = edd_get_download_files( $item );
		$file_keys    = array_keys( $files );
		$url          = edd_get_download_file_url( $payment_meta['key'], $user->user_email, $file_keys[0], $item );

		$current++;
		update_user_meta( $user->ID, 'rcp_edd_member_downloads_current_download_count', $current );
	}

	wp_send_json( array(
		'files' => $files,
		'file'  => $url
	) );

}
add_action( 'wp_ajax_rcp-edd-member-download-request', 'rcp_edd_member_downloads_process_ajax_download' );
add_action( 'wp_ajax_nopriv_rcp-edd-member-download-request', 'rcp_edd_member_downloads_process_ajax_download' );

/**
 * Credit downloads remaining when payment is refunded.
 *
 * @param EDD_Payment $edd_payment
 *
 * @return void
 */
function rcp_edd_member_downloads_refund_payment( $edd_payment ) {

	// Bail if this wasn't from EDD Member Downloads.
	if ( ! $edd_payment->get_meta( '_rcp_edd_member_downloads' ) ) {
		return;
	}

	$current_count = (int) get_user_meta( $edd_payment->user_id, 'rcp_edd_member_downloads_current_download_count', true );

	// Don't let the count go below 0.
	if ( empty( $current_count ) ) {
		return;
	}

	update_user_meta( $edd_payment->user_id, 'rcp_edd_member_downloads_current_download_count', ( $current_count - 1 ) );

}
add_action( 'edd_post_refund_payment', 'rcp_edd_member_downloads_refund_payment' );