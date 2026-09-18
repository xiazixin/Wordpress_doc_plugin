<?php
/**
 * Plugin Name: Wordpress Doc Plugin
 * Description: Documentation page template with a managed left sidebar navigation (Projects → Groups → Docs, see the "Docs Nav" admin menu) and right sidebar on-page table of contents.
 * Version: 1.3.0
 * Author: Xiaz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DOCS_LAYOUT_TEMPLATE      = 'docs-layout';
const DOCS_LAYOUT_VERSION       = '1.3.0';
const DOCS_LAYOUT_CHROME_OPTION = 'docs_layout_show_header_footer';

define( 'DOCS_LAYOUT_URL', plugin_dir_url( __FILE__ ) );

require_once plugin_dir_path( __FILE__ ) . 'includes/nav-tree.php';

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/docs-nav-admin.php';
}

/** Whether docs-layout pages render the theme's header and footer (stored as '1'/'0', default on). */
function docs_layout_show_header_footer() {
	return '1' === get_option( DOCS_LAYOUT_CHROME_OPTION, '1' );
}

/** Add the template to the page attribute dropdown. */
add_filter( 'theme_page_templates', function ( $templates ) {
	$templates[ DOCS_LAYOUT_TEMPLATE ] = 'Wordpress Doc Plugin (left nav + TOC)';
	return $templates;
} );

/** Load the template file from this plugin when a page uses it. */
add_filter( 'template_include', function ( $template ) {
	if ( is_page() && get_page_template_slug() === DOCS_LAYOUT_TEMPLATE ) {
		$custom = plugin_dir_path( __FILE__ ) . 'templates/docs-layout.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}
	return $template;
} );

/** Enqueue assets only on docs-layout pages. */
add_action( 'wp_enqueue_scripts', function () {
	if ( is_page() && get_page_template_slug() === DOCS_LAYOUT_TEMPLATE ) {
		wp_enqueue_style(
			'docs-layout',
			plugin_dir_url( __FILE__ ) . 'assets/docs-layout.css',
			array(),
			DOCS_LAYOUT_VERSION
		);
		wp_enqueue_script(
			'docs-layout',
			plugin_dir_url( __FILE__ ) . 'assets/docs-layout.js',
			array(),
			DOCS_LAYOUT_VERSION,
			true
		);
	}
} );
