<?php
/*
Plugin Name: BuddyPress Docs Personal Tab
Version: 0.1-alpha
Description: Personal Tab for BuddyPress Docs - for Shands Healthcare
Author: Boone Gorges
Text Domain: buddypress-docs-personal-tab
Domain Path: /languages
*/

// The slug used for the Personal tab
if ( ! defined( 'BP_DOCS_PERSONAL_SLUG' ) ) {
	define( 'BP_DOCS_PERSONAL_SLUG', 'personal' );
}

/**
 * Conditionally hook functionality.
 */
function bpdpt_setup() {
	if ( ! bp_docs_enable_folders() ) {
		return;
	}

	bpdpt_setup_nav();

	add_filter( 'bp_before_bp_docs_get_folders_parse_args', 'bpdpt_filter_bp_docs_get_folders_args' );
	add_filter( 'bp_before_bp_docs_has_docs_parse_args', 'bpdpt_filter_bp_docs_has_docs_args' );
	add_action( 'bp_screens', 'bpdpt_remove_group_column' );
	add_action( 'bp_screens', 'bpdpt_remove_author_column' );
	add_action( 'wp_enqueue_scripts', 'bpdpt_enqueue_assets' );
	add_filter( 'bp_docs_directory_breadcrumb', 'bp_docs_personal_directory_breadcrumb', 1 );
	add_action( 'bp_docs_doc_breadcrumbs', 'bpdpt_user_single_breadcrumb', 98, 2 );
	add_filter( 'bp_setup_nav', 'bpdpt_current_action', 9999 );
	add_filter( 'bp_docs_folder_type_selector', 'bpdpt_folder_type_selector', 10, 2 );
	add_action( 'bp_before_bp_docs_folder_selector_parse_args', 'bpdpt_folder_selector_args' );
	add_filter( 'bp_docs_taxonomy_get_user_terms', 'bpdpt_get_user_terms' );
	add_filter( 'bp_docs_page_links_base_url', 'bpdpt_filter_bp_docs_page_links_base_url', 10, 2 );
}
add_action( 'bp_docs_load_doc_extras', 'bpdpt_setup' );

/**
 * Set up Personal nav item.
 */
function bpdpt_setup_nav() {
	bp_core_new_subnav_item( array(
		'name'            => _x( 'Personal', 'Personal Docs tab name', 'bp-docs' ),
		'slug'            => BP_DOCS_PERSONAL_SLUG,
		'parent_url'      => bp_displayed_user_domain() . bp_docs_get_docs_slug() . '/',
		'parent_slug'     => bp_docs_get_docs_slug(),
		'screen_function' => array( buddypress()->bp_docs, 'template_loader' ),
		'position'        => 50,
		'user_has_access' => bp_is_my_profile(),
	) );
}

/**
 * Ensure that Personal tab shows folders from the current user, and don't show
 * folders on Started and Edited pages.
 */
function bpdpt_filter_bp_docs_get_folders_args( $r ) {
	if ( bp_is_user() && bp_is_current_action( BP_DOCS_PERSONAL_SLUG ) ) {
		$r['user_id'] = bp_displayed_user_id();
	}

	if ( ! bp_docs_is_folder_manage_view() && ( bp_docs_is_started_by() || bp_docs_is_edited_by() ) ) {
		$r['include'] = array( 0 );
	}

	return $r;
}

/**
 * Ensure that Personal tab shows Docs from the current user.
 */
function bpdpt_filter_bp_docs_has_docs_args( $r ) {
	if ( bp_is_user() && bp_is_current_action( BP_DOCS_PERSONAL_SLUG ) ) {
		$r['author_id'] = bp_displayed_user_id();
		$r['group_id'] = array();
	}

	return $r;
}

/**
 * Remove the Group column from the Personal page.
 */
function bpdpt_remove_group_column() {
	if ( bp_is_user() && bp_is_current_action( BP_DOCS_PERSONAL_SLUG ) ) {
		remove_filter( 'bp_docs_loop_additional_th', array( buddypress()->bp_docs->groups_integration, 'groups_th' ), 5 );
		remove_filter( 'bp_docs_loop_additional_td', array( buddypress()->bp_docs->groups_integration, 'groups_td' ), 5 );
	}
}

/**
 * Remove the Group column from the Personal page.
 */
function bpdpt_remove_author_column() {
	if ( bp_is_user() && bp_is_current_action( BP_DOCS_PERSONAL_SLUG ) ) {
		remove_filter( 'bp_docs_loop_additional_th', array( buddypress()->bp_docs->groups_integration, 'groups_th' ), 5 );
		remove_filter( 'bp_docs_loop_additional_td', array( buddypress()->bp_docs->groups_integration, 'groups_td' ), 5 );
	}
}

/**
 * Enqueue assets
 */
function bpdpt_enqueue_assets() {
	if ( bp_is_user() && bp_is_current_action( BP_DOCS_PERSONAL_SLUG ) ) {
		wp_enqueue_style( 'bpdpt', plugins_url( 'buddypress-docs-personal-tab/bpdpt.css' ) );
		wp_enqueue_script( 'bpdpt', plugins_url( 'buddypress-docs-personal-tab/bpdpt.js' ), array( 'bp-docs-folders' ) );
	}
}

/**
 * Add Personal information to directory breadcrumbs.
 *
 * @since 1.9.0
 *
 * @param array $crumbs
 * @return array
 */
function bp_docs_personal_directory_breadcrumb( $crumbs ) {
	if ( bp_is_user() && bp_is_current_action( BP_DOCS_PERSONAL_SLUG ) ) {
		$user_crumbs = array(
			sprintf(
				'<a href="%s">%s</a>',
				bp_displayed_user_domain() . bp_docs_get_docs_slug() . '/' . BP_DOCS_PERSONAL_SLUG . '/',
				__( 'Personal', 'bp-docs' )
			),
		);

		$crumbs = array_merge( $user_crumbs, $crumbs );
	}

	return $crumbs;
}

/**
 * Add top-level breadcrumb item to single Doc.
 *
 * This doesn't appear in the breadcrumbs by default because the Docs
 * themselves are not actually "personal". It's only on this implementation
 * that a Doc without a group affiliation counts as "personal".
 */
function bpdpt_user_single_breadcrumb( $crumbs, $doc = null ) {

	if ( is_a( $doc, 'WP_Post' ) ) {
		$doc_id = $doc->ID;
	} else if ( bp_docs_is_existing_doc() ) {
		$doc_id = get_queried_object_id();
	}

	// Only continue if there's no folder
	if ( ! empty( $doc_id ) ) {
		$folder_id = bp_docs_get_doc_folder( $doc_id );
	}

	if ( ! empty( $folder_id ) ) {
		return $crumbs;
	}

	// Only continue if there's no group
	$group_id = bp_docs_get_associated_group_id( $doc->ID );

	if ( ! empty( $group_id ) ) {
		return $crumbs;
	}

	$user_id = $doc->post_author;

	$user_crumbs = array(
		sprintf(
			'<a href="%s">%s&#8217;s Docs</a>',
			bp_core_get_user_domain( $user_id ) . bp_docs_get_slug() . '/',
			bp_core_get_user_displayname( $user_id )
		),
	);

	$crumbs = array_merge( $user_crumbs, $crumbs );

	return $crumbs;
}

/**
 * If there's a folder selected, set Current Action to 'personal'.
 *
 * This helps set the breadcrumbs and ensure the proper 'current' state for
 * nav items.
 */
function bpdpt_current_action() {
	if (
		( bp_docs_is_started_by() && ! empty( $_GET['folder'] ) )
		||
		( bp_docs_is_docs_component() && bp_is_my_profile() && ! empty( $_GET['view'] ) && 'manage' === $_GET['view'] )
	) {
		buddypress()->current_action = BP_DOCS_PERSONAL_SLUG;
	}
}

/**
 * Don't show the Global option on the folder type selector dropdown.
 */
function bpdpt_folder_type_selector( $s, $r ) {
	$s = preg_replace( '|<option.*?value=\"global.*?/option>|', '', $s );
	return $s;
}

/**
 * When 'global' is selected in folder selector, force to 'me'.
 */
function bpdpt_folder_selector_args( $r ) {
	if ( empty( $r['group_id'] ) ) {
		$r['user_id'] = bp_loggedin_user_id();
	}

	return $r;
}

/**
 * Copied from buddypress-docs because the created/edited checks are hardcoded.
 */
function bpdpt_get_user_terms( $terms ) {
	global $wpdb;

	if ( ! bp_is_user() ) {
		return $terms;
	}

	$query_args = array(
		'post_type'         => bp_docs_get_post_type_name(),
		'update_meta_cache' => false,
		'update_term_cache' => true,
		'showposts'         => '-1',
		'posts_per_page'    => '-1',
		'author_id'         => bp_displayed_user_id(),
		'group_id'          => array(),
	);

	$user_doc_query = new WP_Query( $query_args );

	$terms = array();
	foreach ( $user_doc_query->posts as $p ) {
		$p_terms = get_the_terms( $p->ID, buddypress()->bp_docs->docs_tag_tax_name );
		if ( $p_terms ) {
			foreach ( $p_terms as $p_term ) {
				if ( ! isset( $terms[ $p_term->slug ] ) ) {
					$terms[ $p_term->slug ] = array(
						'name' => $p_term->name,
						'posts' => array(),
					);
				}

				if ( ! in_array( $p->ID, $terms[ $p_term->slug ]['posts'] ) ) {
					$terms[ $p_term->slug ]['posts'][] = $p->ID;
				}
			}
		}
	}

	foreach ( $terms as &$t ) {
		$t['count'] = count( $t['posts'] );
	}

	if ( empty( $terms ) ) {
		$terms = array();
	}

	return $terms;
}

/**
 * On user Doc directories, modify the pagination base so that pagination
 * works within the directory.
 *
 * @package BuddyPress_Docs
 * @subpackage Users
 * @since 1.9.0
 */
function bpdpt_filter_bp_docs_page_links_base_url( $base_url, $wp_rewrite_pag_base  ) {
	if ( bp_is_user() && bp_is_current_action( BP_DOCS_PERSONAL_SLUG ) ) {
		$base_url = user_trailingslashit( bp_displayed_user_domain() . bp_docs_get_docs_slug() . '/'. BP_DOCS_PERSONAL_SLUG . '/' . $wp_rewrite_pag_base . '/%#%/' );
	}
	return $base_url;
}
