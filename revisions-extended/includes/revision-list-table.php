<?php

namespace RevisionsExtended\Admin;

use WP_List_Table, WP_Post, WP_Query;

defined( 'WPINC' ) || die();


class Revision_List_Table extends WP_List_Table {
	/**
	 * Checks the current user's permissions
	 *
	 * @return bool
	 */
	public function ajax_user_can() {
		global $typenow;
		$parent_post_type = $typenow ?: 'post';

		return current_user_can( get_post_type_object( $parent_post_type )->cap->edit_posts );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @uses WP_List_Table::set_pagination_args()
	 *
	 * @return void
	 */
	public function prepare_items() {
		$post_type = 'revision';
		$per_page  = $this->get_items_per_page( 'edit_' . $post_type . '_per_page' );

		/** This filter is documented in wp-admin/includes/post.php */
		$per_page = apply_filters( 'edit_posts_per_page', $per_page, $post_type );

		$query_args = array(
			'post_type'      => 'revision',
			'post_status'    => 'future',
			'posts_per_page' => $per_page,
			'order'          => 'desc',
			'orderby'        => 'date ID',
		);

		$query = new WP_Query( $query_args );

		$this->items = $query->get_posts();

		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Gets a list of columns.
	 *
	 * The format is:
	 * - `'internal-name' => 'Title'`
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'    => '<input type="checkbox" />',
			'title' => _x( 'Title', 'column name', 'revisions-extended' ),
		);
	}

	/**
	 * Render the checkbox ("cb") column.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_cb( $post ) {
		$show = current_user_can( 'edit_post', $post->ID );

		if ( $show ) :
			?>
			<label class="screen-reader-text" for="cb-select-<?php the_ID(); ?>">
				<?php
				/* translators: %s: Post title. */
				printf( __( 'Select %s' ), _draft_or_post_title() );
				?>
			</label>
			<input id="cb-select-<?php the_ID(); ?>" type="checkbox" name="post[]" value="<?php the_ID(); ?>" />
			<div class="locked-indicator">
				<span class="locked-indicator-icon" aria-hidden="true"></span>
				<span class="screen-reader-text">
				<?php
				printf(
				/* translators: %s: Post title. */
					__( '&#8220;%s&#8221; is locked' ),
					_draft_or_post_title( $post )
				);
				?>
				</span>
			</div>
		<?php
		endif;
	}

	/**
	 * Render the Title column.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_title( $post ) {
		$can_edit_post = current_user_can( 'edit_post', $post->ID );

		if ( $can_edit_post ) {
			$lock_holder = wp_check_post_lock( $post->ID );

			if ( $lock_holder ) {
				$lock_holder   = get_userdata( $lock_holder );
				$locked_avatar = get_avatar( $lock_holder->ID, 18 );
				/* translators: %s: User's display name. */
				$locked_text = esc_html( sprintf( __( '%s is currently editing' ), $lock_holder->display_name ) );
			} else {
				$locked_avatar = '';
				$locked_text   = '';
			}

			echo '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
		}

		echo '<strong>';

		$title = _draft_or_post_title( $post );

		if ( $can_edit_post ) {
			$edit_url = add_query_arg(
				array(
					'post'   => $post->ID,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			);

			printf(
				'<a class="row-title" href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url( $edit_url ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( '&#8220;%s&#8221; (Edit)' ), $title ) ),
				$title
			);
		} else {
			printf(
				'<span>%s</span>',
				$title
			);
		}

		_post_states( $post );

		echo "</strong>\n";

		get_inline_data( $post );
	}
}