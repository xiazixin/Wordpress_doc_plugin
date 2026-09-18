<?php
/**
 * Admin screen for managing the docs navigation tree (Projects → Groups → Docs).
 * Adds a top-level "Docs Nav" menu; saves the tree to the docs_layout_nav_tree option.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DOCS_LAYOUT_NAV_MENU_SLUG = 'docs-layout-nav';

add_action( 'admin_menu', function () {
	add_menu_page(
		'Docs Navigation',
		'Docs Nav',
		'manage_options',
		DOCS_LAYOUT_NAV_MENU_SLUG,
		'docs_layout_render_nav_admin_page',
		'dashicons-media-document',
		26
	);
} );

/** Save handler: runs before any output so we can redirect after POST. */
add_action( 'admin_init', function () {
	if ( ! isset( $_POST['docs_layout_nav_nonce'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['docs_layout_nav_nonce'] ) ), 'docs_layout_save_nav' ) ) {
		return;
	}

	$input = isset( $_POST['docs_nav'] ) ? wp_unslash( $_POST['docs_nav'] ) : array();
	update_option( DOCS_LAYOUT_NAV_OPTION, docs_layout_sanitize_nav_tree( $input ) );

	wp_safe_redirect( admin_url( 'admin.php?page=' . DOCS_LAYOUT_NAV_MENU_SLUG . '&updated=1' ) );
	exit;
} );

add_action( 'admin_enqueue_scripts', function ( $hook_suffix ) {
	if ( 'toplevel_page_' . DOCS_LAYOUT_NAV_MENU_SLUG !== $hook_suffix ) {
		return;
	}
	wp_enqueue_style(
		'docs-layout-nav-admin',
		DOCS_LAYOUT_URL . 'admin/docs-nav-admin.css',
		array(),
		DOCS_LAYOUT_VERSION
	);
	wp_enqueue_script(
		'docs-layout-nav-admin',
		DOCS_LAYOUT_URL . 'admin/docs-nav-admin.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		DOCS_LAYOUT_VERSION,
		true
	);
} );

/** Render one project block (also used with '__P__' placeholders for the JS template). */
function docs_layout_render_project_row( $project, $p_index, $pages ) {
	$base = 'docs_nav[projects][' . $p_index . ']';
	?>
	<div class="docs-nav-project" data-index="<?php echo esc_attr( $p_index ); ?>">
		<div class="docs-nav-row-head">
			<span class="dashicons dashicons-menu drag-handle" title="Drag to reorder"></span>
			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo esc_attr( $project['id'] ); ?>">
			<input type="text" class="regular-text docs-nav-title" name="<?php echo esc_attr( $base ); ?>[title]" value="<?php echo esc_attr( $project['title'] ); ?>" placeholder="Project name">
			<button type="button" class="button docs-nav-remove-project">Remove project</button>
		</div>
		<div class="docs-nav-groups">
			<?php
			foreach ( $project['groups'] as $g_index => $group ) {
				docs_layout_render_group_row( $group, $p_index, $g_index, $pages );
			}
			?>
		</div>
		<p class="docs-nav-add-line"><button type="button" class="button docs-nav-add-group">+ Add group</button></p>
	</div>
	<?php
}

/** Render one group block inside a project. */
function docs_layout_render_group_row( $group, $p_index, $g_index, $pages ) {
	$base = 'docs_nav[projects][' . $p_index . '][groups][' . $g_index . ']';
	?>
	<div class="docs-nav-group" data-index="<?php echo esc_attr( $g_index ); ?>">
		<div class="docs-nav-row-head">
			<span class="dashicons dashicons-menu drag-handle" title="Drag to reorder"></span>
			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo esc_attr( $group['id'] ); ?>">
			<input type="text" class="regular-text docs-nav-title" name="<?php echo esc_attr( $base ); ?>[title]" value="<?php echo esc_attr( $group['title'] ); ?>" placeholder="Group name (e.g. Getting Started)">
			<button type="button" class="button docs-nav-remove-group">Remove group</button>
		</div>
		<div class="docs-nav-docs">
			<?php
			foreach ( $group['docs'] as $d_index => $doc ) {
				docs_layout_render_doc_row( $doc, $p_index, $g_index, $d_index, $pages );
			}
			?>
		</div>
		<p class="docs-nav-add-line"><button type="button" class="button docs-nav-add-doc">+ Add documentation</button></p>
	</div>
	<?php
}

/** Render one doc row: a page dropdown plus an optional custom-title input. */
function docs_layout_render_doc_row( $doc, $p_index, $g_index, $d_index, $pages ) {
	$base  = 'docs_nav[projects][' . $p_index . '][groups][' . $g_index . '][docs][' . $d_index . ']';
	$found = false;
	?>
	<div class="docs-nav-doc">
		<span class="dashicons dashicons-menu drag-handle" title="Drag to reorder"></span>
		<input type="hidden" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo esc_attr( $doc['id'] ); ?>">
		<select name="<?php echo esc_attr( $base ); ?>[page_id]">
			<option value="0">&mdash; Select a page &mdash;</option>
			<?php foreach ( $pages as $page ) : ?>
				<?php
				if ( (int) $doc['page_id'] === (int) $page->ID ) {
					$found = true;
				}
				?>
				<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $doc['page_id'], $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
			<?php endforeach; ?>
			<?php if ( $doc['page_id'] && ! $found ) : ?>
				<option value="<?php echo esc_attr( $doc['page_id'] ); ?>" selected>(missing page #<?php echo esc_html( $doc['page_id'] ); ?>)</option>
			<?php endif; ?>
		</select>
		<input type="text" class="docs-nav-doc-title" name="<?php echo esc_attr( $base ); ?>[title]" value="<?php echo esc_attr( isset( $doc['title'] ) ? $doc['title'] : '' ); ?>" placeholder="Custom title (optional)">
		<button type="button" class="button docs-nav-remove-doc">Remove</button>
	</div>
	<?php
}

/** The admin page itself. */
function docs_layout_render_nav_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tree  = docs_layout_get_nav_tree();
	$pages = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'orderby'     => 'title',
			'order'       => 'ASC',
			'numberposts' => -1,
		)
	);
	?>
	<div class="wrap docs-nav-admin">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
			<div class="notice notice-success is-dismissible"><p>Navigation saved.</p></div>
		<?php endif; ?>

		<p class="description">
			Build the left sidebar navigation shown on pages using the <strong>Wordpress Doc Plugin</strong> template.
			A project contains groups; a group contains documentation pages. Drag rows by their handle to reorder.
			Projects, groups, or docs left empty are dropped on save.
		</p>

		<?php if ( empty( $pages ) ) : ?>
			<div class="notice notice-warning"><p>No published pages found yet &mdash; create and publish some pages first, then come back to build the navigation.</p></div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'docs_layout_save_nav', 'docs_layout_nav_nonce' ); ?>

			<div id="docs-nav-projects" class="docs-nav-projects">
				<?php
				foreach ( $tree as $p_index => $project ) {
					docs_layout_render_project_row( $project, $p_index, $pages );
				}
				?>
			</div>

			<p id="docs-nav-empty" <?php echo empty( $tree ) ? '' : 'style="display:none"'; ?>>No projects yet &mdash; add your first project to start building the navigation.</p>

			<p><button type="button" class="button button-secondary" id="docs-nav-add-project">+ Add project</button></p>

			<?php submit_button( 'Save navigation' ); ?>
		</form>
	</div>

	<script type="text/html" id="tmpl-docs-nav-project">
		<?php docs_layout_render_project_row( array( 'id' => '', 'title' => '', 'groups' => array() ), '__P__', $pages ); ?>
	</script>
	<script type="text/html" id="tmpl-docs-nav-group">
		<?php docs_layout_render_group_row( array( 'id' => '', 'title' => '', 'docs' => array() ), '__P__', '__G__', $pages ); ?>
	</script>
	<script type="text/html" id="tmpl-docs-nav-doc">
		<?php docs_layout_render_doc_row( array( 'id' => '', 'page_id' => 0, 'title' => '' ), '__P__', '__G__', '__D__', $pages ); ?>
	</script>
	<?php
}
