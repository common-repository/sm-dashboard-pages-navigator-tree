<?php
/**
 * Navigator
 *
 * @package    WordPress
 * @subpackage Sm_dashboard_pages_navigator_tree
 */

namespace SM\Pages_Navigator\Admin;

/**
 * Class Navigator
 *
 * @package SM\Pages_Navigator\Admin
 */
class Navigator {
	/**
	 * Initialize by registering dashboard widgets into core.
	 */
	public static function register_widgets() {
		wp_add_dashboard_widget( 'sm-pagetree', 'Page Navigator', [ get_called_class(), 'list_sm_pagetree' ] );
	}

	/**
	 * Wrapper function for building the page tree
	 */
	public static function list_sm_pagetree() {
		// get and combine child pages and revision.
		$memstart2 = \memory_get_usage();
		$output    = '<div id="smPagetree"><p><a href="#" id="expand">Expand All</a> | <a href="#" id="collapse">Collapse All</a></p>' . static::get_sm_pagetree( 0, 0 ) . '</div>' . PHP_EOL;
		$memend2   = \memory_get_usage();
		$mem_usage = (float) ( $memend2 - $memstart2 );
		if ( defined( 'WP_DEBUG' ) && 'true' === WP_DEBUG ) {
			$output .= '<span id="sm_nav_memory_used">Memory Used: ' . static::meg( $mem_usage ) . ' of ' . static::meg( $memend2 ) . '</span>';
		}

		echo wp_kses( $output, 'post' );
	}

	/**
	 * BUILD PAGE TREE - PRIMARY FUNCTION
	 *
	 * @param int $parent_id Parent ID for recursion.
	 * @param int $lvl       Level for recursion.
	 *
	 * @return string
	 */
	public static function get_sm_pagetree( $parent_id, $lvl ) {
		$output               = '';
		$child_count          = '';
		$pages                = get_pages(
			[
				'child_of'    => $parent_id,
				'parent'      => $parent_id,
				'post_type'   => 'page',
				'post_status' => [ 'publish', 'pending', 'draft', 'private' ],
			]
		);
		$post_revisions_query = new \WP_Query(
			[
				'post_parent' => $parent_id,
				'post_type'   => 'revision',
				'post_status' => 'pending',
			]
		);
		$post_revisions       = $post_revisions_query->posts;
		$pages                = array_merge( (array) $post_revisions, (array) $pages );

		if ( $pages ) {
			if ( $lvl < 1 ) {
				$output .= "<ul id=\"simpletree\" class='level" . ( $lvl ++ ) . "'>" . PHP_EOL;
			} else {
				$output .= "<ul class='treebranch level" . ( $lvl ++ ) . "'>" . PHP_EOL;
			}

			// loop through pages and add them to treebranch.
			foreach ( $pages as $page ) {
				$children = [];

				// if branch has children branches, create a new treebranch, otherwise create a treeleaf.
				if ( $child_count > 0 ) {
					$output .= "<li id=\"$page->ID\" class=\"treebranch\">" . PHP_EOL;
				} else {
					$output .= "<li id=\"$page->ID\" class=\"treeleaf\">" . PHP_EOL;
				}

				// begin setting up treeleaf leaflet content.
				$output .= "<div class='treeleaflet'>" . PHP_EOL;
				$output .= "<span class=\"leafname\">$page->post_title</span>";

				// show child count if there are children.
				if ( $child_count > 0 ) {
					$output .= '<span class="childCount"> (' . $child_count . ')</span> ';
				}

				// if its not a revision.
				if ( 'revision' !== $page->post_type ) {

					// display status.
					$output .= " <span class=\"status $page->post_status\">$page->post_status</span>";

					// show excluded if it is.
					if ( 'yes' === get_post_meta( $page->ID, '_sm_sitemap_exclude_completely', true ) && 'publish' === $page->post_status ) {
						$output .= ' <span class="status excluded">no sitemap</span>';
					}
					$output .= '<span class="action-links">  - ';

					// view link.
					if ( empty( $page_template ) || 'tpl-404.php' !== $page_template ) {
						$output .= '<a class="viewPage" href="' . get_permalink( $page->ID ) . '">view</a> ' . PHP_EOL;
					} else {
						$output .= 'Placeholder Page ';
					}

					$rev_author_id    = $page->post_author;
					$post_type_object = get_post_type_object( $page->post_type );

					if ( current_user_can( 'edit_others_pages' ) || ( $rev_author_id === $GLOBALS['current_user']->ID && current_user_can( 'edit_pages' ) ) ) {
						$output .= '| <a class="editPage" href="' . admin_url( sprintf( $post_type_object->_edit_link . '&action=edit', $page->ID ) ) . '">edit</a> ' . PHP_EOL;
					}

					$output .= '</span>';
					$output .= '</div>' . PHP_EOL;
				} elseif ( 'revision' === $page->post_type ) { // if its a revision.
					// display revision status.
					$output .= " <span class=\"status $page->post_type\">$page->post_type</span>";
					$output .= '<span class="action-links"> - ';
					$output .= "<a class=\"viewPage\" href=\"/?p=$page->ID&amp;post_type=revision&amp;preview=true\">preview</a>" . PHP_EOL;

					$rev_author_id = $page->post_author;

					$current_user    = wp_get_current_user();
					$current_user_id = $current_user->ID;

					// if current user not revision editor do not allow to make changes.
					if ( $rev_author_id === $current_user_id && current_user_can( 'edit_others_revisions' ) ) {
						$output .= " | <a class=\"editPage\" href=\"/wp-admin/admin.php?page=rvy-revisions&amp;revision=$page->ID&amp;action=edit\">edit</a>" . PHP_EOL;
					}

					$output .= '</span>';
				}

				// recall function to see if child pages have children.
				unset( $pages );
				$output .= static::get_sm_pagetree( $page->ID, $lvl );
				$output .= '</li>' . PHP_EOL;
			}
			$output .= '</ul>' . PHP_EOL;
		}

		return $output;
	}

	/**
	 * Converts bytes to Megabtypes
	 *
	 * @param int $mem_usage Memory usage value in bytes.
	 *
	 * @return string
	 */
	public static function meg( $mem_usage ) {
		$output = '';
		if ( $mem_usage < 1024 ) {
			$output .= $mem_usage . ' bytes';
		} elseif ( $mem_usage < 1048576 ) {
			$output .= round( $mem_usage / 1024, 2 ) . ' kilobytes';
		} else {
			$output .= round( $mem_usage / 1048576, 2 ) . ' megabytes';
		}

		return $output;
	}
}
