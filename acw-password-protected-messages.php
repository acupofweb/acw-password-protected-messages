<?php
/**
 * Plugin Name: ACW Password Protected Messages
 * Description: Customize the message displayed on password protected content, globally or with a different message for each page.
 * Author: A Cup of Web
 * Author URI: https://www.acupofweb.it/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acw-password-protected-messages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'ACWPPM_VERSION', '1.0.0' );
define( 'ACWPPM_OPTION', 'acwppm_settings' );

/**
 * Remove "Protected: " prefix from titles.
 */
function acwppm_remove_protected_title() {
	return '%s';
}
add_filter( 'private_title_format', 'acwppm_remove_protected_title' );
add_filter( 'protected_title_format', 'acwppm_remove_protected_title' );

/**
 * Get plugin settings with defaults.
 *
 * @return array
 */
function acwppm_get_settings() {
	$defaults = array(
		'default_message' => '',
		'password_label'  => '',
		'page_messages'   => array(),
	);

	$settings = get_option( ACWPPM_OPTION );

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args( $settings, $defaults );
}

/**
 * Return the custom message for a given post ID, if any.
 *
 * Checks per-page messages first, then falls back to the default message.
 *
 * @param int $post_id Post ID.
 * @return string Empty string when no custom message applies.
 */
function acwppm_get_message_for_post( $post_id ) {
	$settings = acwppm_get_settings();

	if ( ! empty( $settings['page_messages'] ) && is_array( $settings['page_messages'] ) ) {
		foreach ( $settings['page_messages'] as $row ) {
			if ( isset( $row['page_id'], $row['message'] ) && (int) $row['page_id'] === (int) $post_id && '' !== trim( $row['message'] ) ) {
				return $row['message'];
			}
		}
	}

	if ( ! empty( $settings['default_message'] ) ) {
		return $settings['default_message'];
	}

	return '';
}

/**
 * Build the password form with the custom message and label.
 *
 * Replaces the form markup entirely, following the same structure
 * WordPress core uses, so the custom message and label are rendered
 * natively instead of being patched into the default output.
 *
 * @param string $output Password form HTML.
 * @return string
 */
function acwppm_filter_password_form( $output ) {
	$post_id  = get_the_ID();
	$settings = acwppm_get_settings();
	$message  = acwppm_get_message_for_post( $post_id );

	// Nothing configured: leave the form untouched.
	if ( '' === $message && empty( $settings['password_label'] ) ) {
		return $output;
	}

	if ( '' === $message ) {
		$message = __( 'This content is password protected. To view it please enter your password below:', 'acw-password-protected-messages' );
	}

	$label = ! empty( $settings['password_label'] )
		? $settings['password_label']
		: __( 'Password:', 'acw-password-protected-messages' );

	$field_id = 'acwppm-pwbox-' . ( $post_id ? $post_id : wp_rand() );

	$form  = '<div id="acwppm_message_' . esc_attr( $post_id ) . '" class="acwppm_message post-password-message">' . wp_kses_post( wpautop( $message ) ) . '</div>';
	$form .= '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" class="post-password-form" method="post">';
	$form .= '<p><label for="' . esc_attr( $field_id ) . '">' . esc_html( $label ) . ' <input name="post_password" id="' . esc_attr( $field_id ) . '" type="password" spellcheck="false" required size="20"></label> ';
	$form .= '<input type="submit" name="Submit" value="' . esc_attr_x( 'Enter', 'post password form submit button', 'acw-password-protected-messages' ) . '"></p>';
	$form .= '</form>';

	return $form;
}
add_filter( 'the_password_form', 'acwppm_filter_password_form', 999999 );

/**
 * Add a "Settings" link on the Plugins screen.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function acwppm_settings_link( $links ) {
	$url     = admin_url( 'options-general.php?page=acw-password-protected-messages' );
	$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'acw-password-protected-messages' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'acwppm_settings_link' );

/**
 * Register the settings page under Settings.
 */
function acwppm_add_settings_page() {
	add_options_page(
		__( 'Password Protected Messages', 'acw-password-protected-messages' ),
		__( 'Password Messages', 'acw-password-protected-messages' ),
		'manage_options',
		'acw-password-protected-messages',
		'acwppm_render_settings_page'
	);
}
add_action( 'admin_menu', 'acwppm_add_settings_page' );

/**
 * Register the setting.
 */
function acwppm_register_settings() {
	register_setting(
		'acwppm_settings_group',
		ACWPPM_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'acwppm_sanitize_settings',
		)
	);
}
add_action( 'admin_init', 'acwppm_register_settings' );

/**
 * Sanitize settings before saving.
 *
 * @param array $input Raw input.
 * @return array
 */
function acwppm_sanitize_settings( $input ) {
	$clean = array(
		'default_message' => '',
		'password_label'  => '',
		'page_messages'   => array(),
	);

	if ( ! is_array( $input ) ) {
		return $clean;
	}

	if ( isset( $input['default_message'] ) ) {
		$clean['default_message'] = wp_kses_post( $input['default_message'] );
	}

	if ( isset( $input['password_label'] ) ) {
		$clean['password_label'] = sanitize_text_field( $input['password_label'] );
	}

	if ( isset( $input['page_messages'] ) && is_array( $input['page_messages'] ) ) {
		$seen = array();
		foreach ( $input['page_messages'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$page_id = isset( $row['page_id'] ) ? absint( $row['page_id'] ) : 0;
			$message = isset( $row['message'] ) ? wp_kses_post( $row['message'] ) : '';

			// Skip empty rows and duplicate pages.
			if ( ! $page_id || '' === trim( $message ) || isset( $seen[ $page_id ] ) ) {
				continue;
			}

			$seen[ $page_id ] = true;

			$clean['page_messages'][] = array(
				'page_id' => $page_id,
				'message' => $message,
			);
		}
	}

	return $clean;
}

/**
 * Enqueue admin script on the settings page only.
 *
 * @param string $hook Current admin page hook.
 */
function acwppm_admin_scripts( $hook ) {
	if ( 'settings_page_acw-password-protected-messages' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'acwppm-admin',
		plugins_url( 'assets/js/admin.js', __FILE__ ),
		array( 'jquery' ),
		ACWPPM_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'acwppm_admin_scripts' );

/**
 * Render one per-page message row.
 *
 * @param int    $index   Row index.
 * @param int    $page_id Selected page ID.
 * @param string $message Message text.
 */
function acwppm_render_page_message_row( $index, $page_id = 0, $message = '' ) {
	?>
	<div class="acwppm-row" style="border:1px solid #c3c4c7; background:#fff; padding:15px; margin-bottom:15px;">
		<p>
			<label for="acwppm_page_<?php echo esc_attr( $index ); ?>"><strong><?php esc_html_e( 'Page', 'acw-password-protected-messages' ); ?></strong></label><br>
			<?php
			wp_dropdown_pages(
				array(
					'id'                => esc_attr( 'acwppm_page_' . $index ),
					'name'              => esc_attr( ACWPPM_OPTION . '[page_messages][' . $index . '][page_id]' ),
					'selected'          => (int) $page_id,
					'show_option_none'  => esc_html__( '— Select a page —', 'acw-password-protected-messages' ),
					'option_none_value' => '0',
				)
			);
			?>
		</p>
		<p>
			<label for="acwppm_message_<?php echo esc_attr( $index ); ?>"><strong><?php esc_html_e( 'Custom message for this page', 'acw-password-protected-messages' ); ?></strong></label><br>
			<textarea id="acwppm_message_<?php echo esc_attr( $index ); ?>" name="<?php echo esc_attr( ACWPPM_OPTION . '[page_messages][' . $index . '][message]' ); ?>" rows="4" class="large-text"><?php echo esc_textarea( $message ); ?></textarea>
		</p>
		<p>
			<button type="button" class="button acwppm-remove-row"><?php esc_html_e( 'Remove', 'acw-password-protected-messages' ); ?></button>
		</p>
	</div>
	<?php
}

/**
 * Render the settings page.
 */
function acwppm_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings      = acwppm_get_settings();
	$page_messages = is_array( $settings['page_messages'] ) ? $settings['page_messages'] : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Password Protected Messages', 'acw-password-protected-messages' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'acwppm_settings_group' ); ?>

			<h2><?php esc_html_e( 'Default message', 'acw-password-protected-messages' ); ?></h2>
			<p class="description"><?php esc_html_e( 'This message replaces the default WordPress text on all password protected posts and pages, unless a page-specific message is set below.', 'acw-password-protected-messages' ); ?></p>
			<?php
			wp_editor(
				$settings['default_message'],
				'acwppm_default_message',
				array(
					'textarea_name' => ACWPPM_OPTION . '[default_message]',
					'textarea_rows' => 6,
					'media_buttons' => false,
					'tinymce'       => array(
						'toolbar1' => 'bold,italic,underline,forecolor,bullist,numlist,alignleft,aligncenter,alignright,link,unlink',
						'toolbar2' => '',
					),
				)
			);
			?>

			<h2><?php esc_html_e( 'Password field label', 'acw-password-protected-messages' ); ?></h2>
			<p>
				<input type="text" id="acwppm_password_label" name="<?php echo esc_attr( ACWPPM_OPTION . '[password_label]' ); ?>" class="regular-text" value="<?php echo esc_attr( $settings['password_label'] ); ?>" placeholder="<?php esc_attr_e( 'Password', 'acw-password-protected-messages' ); ?>">
			</p>
			<p class="description"><?php esc_html_e( 'Optional. Replaces the "Password" label of the input field.', 'acw-password-protected-messages' ); ?></p>

			<h2><?php esc_html_e( 'Page-specific messages', 'acw-password-protected-messages' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Show a different message on specific pages. Each page can have its own custom message, which overrides the default message above.', 'acw-password-protected-messages' ); ?></p>

			<div id="acwppm-rows">
				<?php
				$index = 0;
				foreach ( $page_messages as $row ) {
					acwppm_render_page_message_row( $index, $row['page_id'], $row['message'] );
					$index++;
				}
				?>
			</div>

			<template id="acwppm-row-template">
				<?php acwppm_render_page_message_row( '__INDEX__' ); ?>
			</template>

			<p>
				<button type="button" class="button button-secondary" id="acwppm-add-row"><?php esc_html_e( 'Add another message', 'acw-password-protected-messages' ); ?></button>
			</p>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
