<?php
/**
 * Wordpress Doc Plugin template — left docs navigation (managed via the Docs Nav admin,
 * falling back to the page hierarchy), content in the middle, right on-page TOC.
 * Rendered by the docs-layout plugin via template_include.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'docs-layout-body' ); ?>>
<?php wp_body_open(); ?>

<?php
if ( function_exists( 'block_template_part' ) ) {
	block_template_part( 'header' );
}
?>

<main id="docs-main" class="docs-main">
<?php
while ( have_posts() ) :
	the_post();
	$current_id = get_the_ID();
	$parent_id  = wp_get_post_parent_id( $current_id );
	$hub_id     = $parent_id ? $parent_id : $current_id;
	$hub_title  = get_the_title( $hub_id );

	// Left nav: the docs hub + all pages under it.
	$nav_items = array();
	if ( $parent_id ) {
		$nav_items[] = get_post( $hub_id );
	}
	$nav_items = array_merge(
		$nav_items,
		get_posts(
			array(
				'post_type'   => 'page',
				'post_parent' => $hub_id,
				'post_status' => 'publish',
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'numberposts' => -1,
			)
		)
	);
	?>
	<div class="docs-grid">
		<aside class="docs-sidebar-left" aria-label="Documentation navigation">
			<?php
			$managed_nav = function_exists( 'docs_layout_get_nav_for_page' ) ? docs_layout_get_nav_for_page( $current_id ) : null;
			if ( $managed_nav ) :
				// Managed nav (Docs Nav admin): the matching project, or the full tree.
				foreach ( $managed_nav['projects'] as $project ) :
					?>
					<div class="docs-nav-project-block">
						<p class="docs-nav-hub"><?php echo esc_html( $project['title'] ); ?></p>
						<nav class="docs-nav">
							<ul>
								<?php foreach ( $project['groups'] as $group ) : ?>
									<li class="docs-nav-group">
										<span class="docs-nav-group-title"><?php echo esc_html( $group['title'] ); ?></span>
										<ul>
											<?php foreach ( $group['docs'] as $doc ) : ?>
												<li class="<?php echo ! empty( $doc['current'] ) ? 'current' : ''; ?>">
													<a href="<?php echo esc_url( $doc['url'] ); ?>"><?php echo esc_html( $doc['title'] ); ?></a>
												</li>
											<?php endforeach; ?>
										</ul>
									</li>
								<?php endforeach; ?>
							</ul>
						</nav>
					</div>
					<?php
				endforeach;
			else :
				// Fallback: docs hub + its child pages.
				?>
				<p class="docs-nav-hub"><?php echo esc_html( $hub_title ); ?></p>
				<nav class="docs-nav">
					<ul>
						<?php foreach ( $nav_items as $nav_post ) : ?>
							<li class="<?php echo $nav_post->ID === $current_id ? 'current' : ''; ?>">
								<a href="<?php echo esc_url( get_permalink( $nav_post ) ); ?>"><?php echo esc_html( get_the_title( $nav_post ) ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</aside>

		<article class="docs-content">
			<h1 class="docs-title"><?php the_title(); ?></h1>
			<?php the_content(); ?>
		</article>

		<aside class="docs-sidebar-right" aria-label="On this page">
			<p class="docs-toc-title">On this page</p>
			<nav id="docs-toc" class="docs-toc" aria-label="Table of contents"></nav>
		</aside>
	</div>
<?php endwhile; ?>
</main>

<?php
if ( function_exists( 'block_template_part' ) ) {
	block_template_part( 'footer' );
}
wp_footer();
?>
</body>
</html>
