<?php
/*
Plugin Name: Quick Edit
Plugin URI:  https://developer.wordpress.org/plugins/the-basics/
Description: Allows quick editing post content
Version:     0.1
Author:      Sergey Vlasov
Author URI:  https://vlasovweb.xyz/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wporg
Domain Path: /languages
*/

//adding styles
function quick_edit() {
	wp_register_style( 'quick-style', plugins_url( 'quick-style.css', __FILE__ ) );
	wp_enqueue_style( 'quick-style' );
}
add_action( 'admin_init', 'quick_edit' );

// Change the columns for the screen
function add_content_column( $cols ) {
	$mycol = array(
		"content_column" => __( "Content" ),
	);

	return array_merge( $cols, $mycol );
}
add_filter( "manage_posts_columns", "add_content_column" );

function display_content_column( $column, $post_id ) {
	if ( $column == "content_column" ) {
		echo wp_trim_words( get_the_content(), 10, '...' );
	}
}
add_action( "manage_posts_custom_column", "display_content_column", 10, 2 );

// Adding fieldset
function post_content_quick_edit( $column_name, $post_type ) {
	if ( 'content_column' == $column_name ) {
		static $printNonce = true;
		if ( $printNonce ) {
			$printNonce = false;
			wp_nonce_field( plugin_basename( __FILE__ ), 'posttype_edit_nonce' );
		} ?>
		<fieldset class="my-fieldset">
			<span class="title"><?php echo __( "Content" ); ?></span>
			<div class="inline-edit-col inline-edit-<?php echo $column_name ?>">
				<textarea id="post_content" class="post_content" name="post_content"><?php the_content(); ?></textarea>
			</div>
		</fieldset> <?php
	}
}
add_action( 'quick_edit_custom_box', 'post_content_quick_edit', 10, 2 );


// Saving function
function content_save_quick_edit_content( $post_id ) {
	// verify if this is an auto save routine. If it is our form has not been submitted,
	// so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	// Check permissions - Set to Post/Page, or custom permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
	}
	$slug = 'posttype';
	$_POST += array( "{$slug}_edit_nonce" => '' );
	if ( ! wp_verify_nonce( $_POST["{$slug}_edit_nonce"],
		plugin_basename( __FILE__ ) )
	) {
		return;
	}
	// saving
	if ( isset( $_REQUEST['post_content'] ) ) {
		global $wpdb;
		$wpdb->update( "wp_posts", array( 'post_content' => $_REQUEST['post_content'] ), array( 'ID' => $post_id ) );
	}
}
add_action( 'save_post', 'content_save_quick_edit_content' );

// Add ajax to update content
add_action( 'admin_footer-edit.php', 'admin_edit_content_update_foot' );
function admin_edit_content_update_foot() {
	$slug = 'post_content';
	$page = get_current_screen();
	if( $page->ID = "edit-post" ) {
		?>
		<script type="text/javascript">
			var $ = jQuery;
			var _edit = inlineEditPost.edit;
			inlineEditPost.edit = function (id) {
				var args = [].slice.call(arguments);
				_edit.apply(this, args);
				id = this.getId(id);

				$.post( ajaxurl, {action: 'load_content', id: id}, function(rs) {
					$("#post_content").text(rs);
				});
			}
		</script>
		<?php
	}

}

add_action('wp_ajax_load_content', 'load_content');
function load_content() {

	if ( isset($_POST['id']) ) {
		$post_id = $_POST['id'];
		$post = get_post($post_id);
		echo $post->post_content;
		wp_die();
	} else {
		wp_die();
	}

}