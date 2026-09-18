<?php
/**
 * Managed docs navigation tree (Projects → Groups → Docs): storage, sanitizing, resolving.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DOCS_LAYOUT_NAV_OPTION = 'docs_layout_nav_tree';

/** Read the raw managed navigation tree from the options table. */
function docs_layout_get_nav_tree() {
	$tree = get_option( DOCS_LAYOUT_NAV_OPTION, array() );
	return is_array( $tree ) ? $tree : array();
}

/** Stable, sanitize_key-safe node id, e.g. "p_65f3a1b2c3d4e12345678". */
function docs_layout_nav_node_id( $prefix ) {
	return $prefix . '_' . str_replace( '.', '', uniqid( '', true ) );
}

/** Keep a posted node id when valid, otherwise mint a new one. */
function docs_layout_nav_clean_node_id( $id, $prefix ) {
	$id = is_string( $id ) ? sanitize_key( $id ) : '';
	return '' !== $id ? $id : docs_layout_nav_node_id( $prefix );
}

/**
 * Normalize a docs_nav POST array into the stored tree shape.
 * Drops projects/groups without a title and docs without a page.
 */
function docs_layout_sanitize_nav_tree( $input ) {
	$tree = array();

	if ( ! is_array( $input ) || empty( $input['projects'] ) || ! is_array( $input['projects'] ) ) {
		return $tree;
	}

	foreach ( $input['projects'] as $project ) {
		if ( ! is_array( $project ) ) {
			continue;
		}
		$title = isset( $project['title'] ) ? sanitize_text_field( $project['title'] ) : '';
		if ( '' === $title ) {
			continue;
		}

		$clean_project = array(
			'id'     => docs_layout_nav_clean_node_id( isset( $project['id'] ) ? $project['id'] : '', 'p' ),
			'title'  => $title,
			'groups' => array(),
		);

		if ( ! empty( $project['groups'] ) && is_array( $project['groups'] ) ) {
			foreach ( $project['groups'] as $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}
				$group_title = isset( $group['title'] ) ? sanitize_text_field( $group['title'] ) : '';
				if ( '' === $group_title ) {
					continue;
				}

				$clean_group = array(
					'id'    => docs_layout_nav_clean_node_id( isset( $group['id'] ) ? $group['id'] : '', 'g' ),
					'title' => $group_title,
					'docs'  => array(),
				);

				if ( ! empty( $group['docs'] ) && is_array( $group['docs'] ) ) {
					foreach ( $group['docs'] as $doc ) {
						if ( ! is_array( $doc ) ) {
							continue;
						}
						$page_id = isset( $doc['page_id'] ) ? absint( $doc['page_id'] ) : 0;
						if ( ! $page_id ) {
							continue;
						}
						$clean_group['docs'][] = array(
							'id'      => docs_layout_nav_clean_node_id( isset( $doc['id'] ) ? $doc['id'] : '', 'd' ),
							'page_id' => $page_id,
						);
					}
				}

				$clean_project['groups'][] = $clean_group;
			}
		}

		$tree[] = $clean_project;
	}

	return $tree;
}

/**
 * Resolve the stored tree for rendering on the page with ID $current_id.
 *
 * Docs pointing at missing/unpublished pages are skipped, and groups/projects
 * left empty after that are pruned. Returns null when nothing is configured
 * (the template then falls back to the page-hierarchy nav). Otherwise returns:
 *
 * array(
 *   'projects' => array( ... ),  // only the matching project when the current
 *                                // page is in the tree, otherwise the full tree
 *   'matched'  => bool,          // whether the current page was found
 * )
 *
 * Each project: id, title, groups[]; each group: id, title, docs[];
 * each doc: id, page_id, title, url, current.
 */
function docs_layout_get_nav_for_page( $current_id ) {
	$tree = docs_layout_get_nav_tree();
	if ( empty( $tree ) ) {
		return null;
	}

	$resolved        = array();
	$matched_project = null;

	foreach ( $tree as $project ) {
		$resolved_project = array(
			'id'     => $project['id'],
			'title'  => $project['title'],
			'groups' => array(),
		);
		$project_matched  = false;

		foreach ( $project['groups'] as $group ) {
			$resolved_group = array(
				'id'    => $group['id'],
				'title' => $group['title'],
				'docs'  => array(),
			);

			foreach ( $group['docs'] as $doc ) {
				$post = get_post( $doc['page_id'] );
				if ( ! $post || 'page' !== $post->post_type || 'publish' !== $post->post_status ) {
					continue;
				}
				$is_current = (int) $doc['page_id'] === (int) $current_id;
				if ( $is_current ) {
					$project_matched = true;
				}
				$resolved_group['docs'][] = array(
					'id'      => $doc['id'],
					'page_id' => (int) $doc['page_id'],
					'title'   => get_the_title( $post ),
					'url'     => get_permalink( $post ),
					'current' => $is_current,
				);
			}

			if ( ! empty( $resolved_group['docs'] ) ) {
				$resolved_project['groups'][] = $resolved_group;
			}
		}

		if ( ! empty( $resolved_project['groups'] ) ) {
			$resolved[] = $resolved_project;
			if ( $project_matched && null === $matched_project ) {
				$matched_project = $resolved_project;
			}
		}
	}

	if ( empty( $resolved ) ) {
		return null;
	}

	return array(
		'projects' => null !== $matched_project ? array( $matched_project ) : $resolved,
		'matched'  => null !== $matched_project,
	);
}
