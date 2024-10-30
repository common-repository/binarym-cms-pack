<?php

class binaryChildPages extends WP_Widget {
	function binaryChildPages() {
		$widget_ops = array('classname' => 'binary_child_pages', 'description' => 'Displays a list of all child pages.' );
		$this->WP_Widget('child_pages', 'BinaryM: Child Pages', $widget_ops);
	}

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		global $post;
		
		// are you a grandchild? $grandchild will be 2 if so.
		$grandchild = count($post->ancestors);	
	
		// if they have an ancestor, we need to return the top level
		if ($grandchild > 0) {
		  	$the_page = get_page(end($post->ancestors));
			$parent_link = get_permalink(end($post->ancestors));
		  	$parent_title = $the_page->post_title;
	
			$children = wp_list_pages("depth=".$instance['depth']."&title_li=&child_of=".end($post->ancestors)."&echo=0&sort_column=menu_order");
			
		}
		else {
			$children = wp_list_pages("depth=".$instance['depth']."&title_li=&child_of=".$post->ID."&echo=0&sort_column=menu_order");
		  	$parent_title = $post->post_title;
			$parent_link = get_permalink($post->ID);
		}

				
		if ($children) { 
			echo $args['before_widget'];	
			echo $args['before_title'] . '<a href="'.$parent_link.'">' . $parent_title . '</a>' . $args['after_title'];
		?>
			<ul> 
				<?php echo $children; ?> 
			</ul> 
		<?php
			echo $args['after_widget'];
		}

	}
 
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['depth'] = strip_tags($new_instance['depth']);
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'depth' => '' ) );
		$depth = strip_tags($instance['depth']);

	?>
		<p><label for="<?php echo $this->get_field_id( 'depth' ); ?>">Depth <select name="<?php echo $this->get_field_name('depth'); ?>" id="<?php echo $this->get_field_id( 'depth' ); ?>" class="widefat"><option><?php echo stripslashes($depth); ?></option><option>1</option><option>2</option><option>3</option><option>4</option></select></label></p>

	<?php
	}
}



function register_binary_widgets(){
	register_widget('binaryChildPages');
}

add_action('widgets_init', 'register_binary_widgets');

?>
