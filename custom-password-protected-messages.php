<?php
/**
 * Plugin Name: Custom Password Protected Messages
 * Plugin URI: https://www.acupofweb.it/
 * Description: Customize the message displayed on password protected content, globally or with a different message for each page.
 * Author: Lorenzo Fracassi
 * Author URI: https://www.acupofweb.it/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-password-protected-messages
 *
 * Based on "Change Password Protected Message" by pipdig (GPLv2 or later).
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
 * Filter the markup of the password form.
 *
 * @param string $output Password form HTML.
 * @return string
 */
function acwppm_filter_password_form( $output ) {
	$post_id  = get_the_ID();
	$settings = acwppm_get_settings();
	$message  = acwppm_get_message_for_post( $post_id );

	if ( '' === $message && empty( $settings['password_label'] ) ) {
		return $output;
	}

	if ( '' !== $message ) {
		// Strip the default first paragraph added by WordPress.
		$first_paragraph = '';
		if ( class_exists( 'DOMDocument' ) ) {
			$doc = new DOMDocument();
			libxml_use_internal_errors( true );
			$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $output );
			libxml_clear_errors();
			foreach ( $doc->getElementsByTagName( 'p' ) as $paragraph ) {
				$first_paragraph = $paragraph->textContent;
				break;
			}
		}

		if ( $first_paragraph ) {
			$output = str_replace( $first_paragraph, '', $output );
			$output = str_replace( '<p class="post-password-message"></p>', '', $output );
			$output = str_replace( '<p></p>', '', $output );
		}

		// Prepend the custom message to the form.
		$message_html = '<div id="acwppm_message_' . esc_attr( $post_id ) . '" class="acwppm_message post-password-message" style="margin-bottom:10px;">' . wp_kses_post( wpautop( $message ) ) . '</div>';
		$output       = str_replace( '<form', $message_html . '<form', $output );
	}

	if ( ! empty( $settings['password_label'] ) ) {
		$output = str_replace( 'Password', esc_html( $settings['password_label'] ), $output );
	}

	return $output;
}
add_filter( 'the_password_form', 'acwppm_filter_password_form', 999999 );

/**
 * Add a "Settings" link on the Plugins screen.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function acwppm_settings_link( $links ) {
	$url     = admin_url( 'options-general.php?page=custom-password-protected-messages' );
	$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'custom-password-protected-messages' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'acwppm_settings_link' );

/**
 * Register the settings page under Settings.
 */
function acwppm_add_settings_page() {
	add_options_page(
		__( 'Password Protected Messages', 'custom-password-protected-messages' ),
		__( 'Password Messages', 'custom-password-protected-messages' ),
		'manage_options',
		'custom-password-protected-messages',
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
	if ( 'settings_page_custom-password-protected-messages' !== $hook ) {
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
			<label for="acwppm_page_<?php echo esc_attr( $index ); ?>"><strong><?php esc_html_e( 'Page', 'custom-password-protected-messages' ); ?></strong></label><br>
			<?php
			wp_dropdown_pages(
				array(
					'id'                => 'acwppm_page_' . $index,
					'name'              => esc_attr( ACWPPM_OPTION . '[page_messages][' . $index . '][page_id]' ),
					'selected'          => (int) $page_id,
					'show_option_none'  => esc_html__( '— Select a page —', 'custom-password-protected-messages' ),
					'option_none_value' => '0',
				)
			);
			?>
		</p>
		<p>
			<label for="acwppm_message_<?php echo esc_attr( $index ); ?>"><strong><?php esc_html_e( 'Custom message for this page', 'custom-password-protected-messages' ); ?></strong></label><br>
			<textarea id="acwppm_message_<?php echo esc_attr( $index ); ?>" name="<?php echo esc_attr( ACWPPM_OPTION . '[page_messages][' . $index . '][message]' ); ?>" rows="4" class="large-text"><?php echo esc_textarea( $message ); ?></textarea>
		</p>
		<p>
			<button type="button" class="button acwppm-remove-row"><?php esc_html_e( 'Remove', 'custom-password-protected-messages' ); ?></button>
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
		<h1><?php esc_html_e( 'Password Protected Messages', 'custom-password-protected-messages' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'acwppm_settings_group' ); ?>

			<h2><?php esc_html_e( 'Default message', 'custom-password-protected-messages' ); ?></h2>
			<p class="description"><?php esc_html_e( 'This message replaces the default WordPress text on all password protected posts and pages, unless a page-specific message is set below.', 'custom-password-protected-messages' ); ?></p>
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

			<h2><?php esc_html_e( 'Password field label', 'custom-password-protected-messages' ); ?></h2>
			<p>
				<input type="text" id="acwppm_password_label" name="<?php echo esc_attr( ACWPPM_OPTION . '[password_label]' ); ?>" class="regular-text" value="<?php echo esc_attr( $settings['password_label'] ); ?>" placeholder="<?php esc_attr_e( 'Password', 'custom-password-protected-messages' ); ?>">
			</p>
			<p class="description"><?php esc_html_e( 'Optional. Replaces the "Password" label of the input field.', 'custom-password-protected-messages' ); ?></p>

			<h2><?php esc_html_e( 'Page-specific messages', 'custom-password-protected-messages' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Show a different message on specific pages. Each page can have its own custom message, which overrides the default message above.', 'custom-password-protected-messages' ); ?></p>

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
				<button type="button" class="button button-secondary" id="acwppm-add-row"><?php esc_html_e( 'Add another message', 'custom-password-protected-messages' ); ?></button>
			</p>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Load the plugin text domain.
 */
function acwppm_load_textdomain() {
	load_plugin_textdomain( 'custom-password-protected-messages', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'acwppm_load_textdomain' );
