<?php
/*
Plugin Name: 365project.org Widget
Plugin URI: 
Description: Show photos from a 365project.org feed
Author: Kailey Lampert
Author URI: kaileylampert.com
Version: 1.0
*/

add_filter( 'plugin_action_links_'. plugin_basename( __FILE__ ), 'tsfproject_plugin_action_links', 10, 4 );
function tsfproject_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
	$actions['widgets'] = '<a href="'. admin_url('widgets.php') .'">'. __( 'Widgets', '365projectorg-widget' ) .'</a>';
	return $actions;
}

add_action( 'widgets_init', 'tsfproject_register_widget' );
function tsfproject_register_widget() {
	register_widget( 'tsfproject_widget' );
}
class tsfproject_widget extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'tsfproject-widget', 'description' => __( 'Display images from 365project.org feeds', '365projectorg-widget' ) );
		$control_ops = array( 'width' => 300 );
		parent::WP_Widget( 'tsfproject_widget', __( '365Project.org Widget', '365projectorg-widget' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );
		echo $before_widget;

		echo $instance['hide_title'] ? '' : $before_title . esc_html( $instance['title'] ) . $after_title;

		$url = esc_url( $instance['feed'] );
		$timeout = intval( $instance['timeout'] );
		$limit = intval( $instance['limit'] );

		$transient_id = md5( $widget_id . $url . $limit );

		if ( false === ( $html = get_transient( $transient_id ) ) ) {

			$body = wp_remote_retrieve_body( wp_remote_get( $url ) );
			$body = simplexml_load_string( $body );

			$html = '';

			$i = 0;
			foreach( $body->channel->item as $item ) {

				$description = $item->description;

				preg_match_all( '/(<a.*<\/a>)/', $description, $matches );

				$html .= $matches[0][0];
				++$i;
				if ( $i == $limit ) break;
			}
			set_transient( $transient_id, $html, $timeout );
			//$html .= '<!--just cached-->';
		}
		echo $html;

		echo $after_widget;

	} //end widget()

	function update($new_instance, $old_instance) {

		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['hide_title'] = (bool) $new_instance['hide_title'] ? 1 : 0;
		$instance['feed'] = strip_tags( $new_instance['feed'] );
		$instance['timeout'] = strip_tags( $new_instance['timeout'] );
		$instance['limit'] = strip_tags( $new_instance['limit'] );
		return $instance;

	} //end update()

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '365project.org', 'hide_title' => 0, 'feed' => 'http://365project.org/browse/latest/feed', 'timeout' => (60*10), 'limit' => 3 ) );
		extract( $instance );
		?>
		<p style="width:63%;float:left;">
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', '365projectorg-widget' );?>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>
		<p style="width:33%;float:right;padding-top:20px;height:20px;">
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_title'); ?>" name="<?php echo $this->get_field_name('hide_title'); ?>"<?php checked( $hide_title ); ?> />
			<label for="<?php echo $this->get_field_id('hide_title'); ?>"><?php _e('Hide Title?', '365projectorg-widget' );?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'feed' ); ?>"><?php _e( 'Feed:', '365projectorg-widget' );?>
				<input class="widefat" id="<?php echo $this->get_field_id('feed'); ?>" name="<?php echo $this->get_field_name('feed'); ?>" type="text" value="<?php echo $feed; ?>" />
			</label>
			<span class="description"><?php printf( __( 'e.g. %s', '365projectorg-widget' ), '<code>http://365project.org/browse/latest/feed</code>' ); ?></span>
		</p>
		<p style="width:48%;float:left;">
			<label for="<?php echo $this->get_field_id( 'timeout' ); ?>"><?php _e( 'Timeout (in seconds):', '365projectorg-widget' );?>
				<input class="widefat" id="<?php echo $this->get_field_id('timeout'); ?>" name="<?php echo $this->get_field_name('timeout'); ?>" type="text" value="<?php echo $timeout; ?>" />
			</label>
			<span class="description"><?php _e( 'How long should the widget be cached?', '365projectorg-widget' ); ?></span>
		</p>
		<p style="width:48%;float:right;">
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Limit:', '365projectorg-widget' );?>
				<input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" />
			</label>
			<span class="description"><?php _e( 'How many items to show?', '365projectorg-widget' ); ?></span>
		</p>
		<?php
	} //end form()
}