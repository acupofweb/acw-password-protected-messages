<?php
/**
 * Plugin Name: ACW Custom Messages for Password Protected Pages
 * Description: Customize the message displayed on password protected content, globally or with a different message for each page or post.
 * Author: A Cup of Web
 * Author URI: https://www.acupofweb.it/
 * Version: 1.1.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acw-password-protected-messages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'ACWPPM_VERSION', '1.1.0' );
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
		'error_message'   => '',
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
 * Whether the current request follows a failed password attempt.
 *
 * WordPress gives no feedback on a wrong password: it stores the hashed
 * attempt in the wp-postpass cookie and redirects back to the same page.
 * So a request that still requires a password, carries the cookie and
 * has the page itself as referer is a failed attempt.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function acwppm_is_failed_attempt( $post_id ) {
	if ( empty( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) ) {
		return false;
	}

	$referer = wp_get_raw_referer();
	if ( ! $referer ) {
		return false;
	}

	return untrailingslashit( $referer ) === untrailingslashit( get_permalink( $post_id ) );
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

	$error = '';
	if ( ! empty( $settings['error_message'] ) && acwppm_is_failed_attempt( $post_id ) ) {
		$error = $settings['error_message'];
	}

	// Nothing configured: leave the form untouched.
	if ( '' === $message && '' === $error && empty( $settings['password_label'] ) ) {
		return $output;
	}

	if ( '' === $message ) {
		$message = __( 'This content is password protected. To view it please enter your password below:', 'acw-password-protected-messages' );
	}

	$label = ! empty( $settings['password_label'] )
		? $settings['password_label']
		: __( 'Password:', 'acw-password-protected-messages' );

	$field_id = 'acwppm-pwbox-' . ( $post_id ? $post_id : wp_rand() );

	$form = '';
	if ( '' !== $error ) {
		$form .= '<div class="acwppm-error post-password-error" style="color:#b32d2e;">' . wp_kses_post( wpautop( $error ) ) . '</div>';
	}
	$form .= '<div id="acwppm_message_' . esc_attr( $post_id ) . '" class="acwppm_message post-password-message">' . wp_kses_post( wpautop( $message ) ) . '</div>';
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
		'error_message'   => '',
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

	if ( isset( $input['error_message'] ) ) {
		$clean['error_message'] = wp_kses_post( $input['error_message'] );
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
 * Get all password protected posts, grouped by post type.
 *
 * Only protected content is listed, so the dropdown stays short and
 * relevant even on large sites. Results are cached per request.
 *
 * @return array Map of post type name => array of WP_Post objects.
 */
function acwppm_get_protected_posts() {
	static $grouped = null;

	if ( null !== $grouped ) {
		return $grouped;
	}

	$post_types = get_post_types( array( 'public' => true ) );
	unset( $post_types['attachment'] );

	$query = new WP_Query(
		array(
			'post_type'              => array_values( $post_types ),
			'has_password'           => true,
			'posts_per_page'         => 500,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$grouped = array();
	foreach ( $query->posts as $post ) {
		$grouped[ $post->post_type ][] = $post;
	}

	return $grouped;
}

/**
 * Render the dropdown of password protected content, grouped by post type.
 *
 * @param string $id       Select element ID.
 * @param string $name     Select element name.
 * @param int    $selected Selected post ID.
 */
function acwppm_render_post_dropdown( $id, $name, $selected = 0 ) {
	$grouped   = acwppm_get_protected_posts();
	$selected  = (int) $selected;
	$has_match = false;

	echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
	echo '<option value="0">' . esc_html__( '— Select content —', 'acw-password-protected-messages' ) . '</option>';

	foreach ( $grouped as $post_type => $posts ) {
		$type_object = get_post_type_object( $post_type );
		$type_label  = $type_object ? $type_object->labels->name : $post_type;

		echo '<optgroup label="' . esc_attr( $type_label ) . '">';
		foreach ( $posts as $post ) {
			if ( $post->ID === $selected ) {
				$has_match = true;
			}
			$title = get_the_title( $post );
			if ( '' === $title ) {
				/* translators: %d: post ID. */
				$title = sprintf( __( '(no title) #%d', 'acw-password-protected-messages' ), $post->ID );
			}
			echo '<option value="' . esc_attr( $post->ID ) . '"' . selected( $post->ID, $selected, false ) . '>' . esc_html( $title ) . '</option>';
		}
		echo '</optgroup>';
	}

	// Keep a previously saved selection visible even if the content is
	// no longer password protected.
	if ( $selected && ! $has_match ) {
		$saved = get_post( $selected );
		if ( $saved ) {
			/* translators: %s: post title. */
			$label = sprintf( __( '%s (no longer protected)', 'acw-password-protected-messages' ), get_the_title( $saved ) );
			echo '<option value="' . esc_attr( $saved->ID ) . '" selected>' . esc_html( $label ) . '</option>';
		}
	}

	echo '</select>';
}

/**
 * Render one per-content message row.
 *
 * @param int    $index   Row index.
 * @param int    $page_id Selected post ID.
 * @param string $message Message text.
 */
function acwppm_render_page_message_row( $index, $page_id = 0, $message = '' ) {
	?>
	<div class="acwppm-row" style="border:1px solid #c3c4c7; background:#fff; padding:15px; margin-bottom:15px;">
		<p>
			<label for="acwppm_page_<?php echo esc_attr( $index ); ?>"><strong><?php esc_html_e( 'Page or post', 'acw-password-protected-messages' ); ?></strong></label><br>
			<?php
			acwppm_render_post_dropdown(
				'acwppm_page_' . $index,
				ACWPPM_OPTION . '[page_messages][' . $index . '][page_id]',
				$page_id
			);
			?>
		</p>
		<p>
			<label for="acwppm_message_<?php echo esc_attr( $index ); ?>"><strong><?php esc_html_e( 'Custom message for this content', 'acw-password-protected-messages' ); ?></strong></label><br>
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
			<p class="description"><?php esc_html_e( 'This message replaces the default WordPress text on all password protected content, unless a specific message is set below.', 'acw-password-protected-messages' ); ?></p>
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

			<h2><?php esc_html_e( 'Wrong password message', 'acw-password-protected-messages' ); ?></h2>
			<p>
				<textarea id="acwppm_error_message" name="<?php echo esc_attr( ACWPPM_OPTION . '[error_message]' ); ?>" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'The password you entered is incorrect. Please try again.', 'acw-password-protected-messages' ); ?>"><?php echo esc_textarea( $settings['error_message'] ); ?></textarea>
			</p>
			<p class="description"><?php esc_html_e( 'Optional. WordPress shows no feedback when a wrong password is entered: with this message, visitors are told their attempt failed. Leave empty to disable.', 'acw-password-protected-messages' ); ?></p>

			<h2><?php esc_html_e( 'Content-specific messages', 'acw-password-protected-messages' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Show a different message on specific pages, posts or custom post types. Each one can have its own custom message, which overrides the default message above. Only password protected content is listed.', 'acw-password-protected-messages' ); ?></p>

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
