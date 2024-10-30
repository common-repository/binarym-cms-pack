<?php
/*
Plugin Name: BinaryM CMS Pack
Plugin URI: http://binarym.com/2011/binarym-cms-pack-wordpress-plugin/
Description: Some functionality that we find useful for most of our projects.
Version: 0.1.0
Author: Matt McInvale - BinaryM Inc.
Author URI: http://binarym.com/


*/

require(dirname(__FILE__)  . '/widgets.php');

add_shortcode('links', 'getLinks');

function getLinks($attributes) {

	extract(shortcode_atts(array(
		'limit' => '-1',
		'category' => '',
		'exclude_category' => '',
		'category_name' => '',
		'orderby' => 'rating',
		'order' => 'DESC',
		'show_description' => '0'
	), $attributes));

	if (!empty($category)) $bookmarks .= '&category=' . $category;
	if (!empty($exclude_category)) $bookmarks .= '&exclude_category=' . $exclude_category;
	if (!empty($category_name)) $bookmarks .= '&category_name=' . $category_name;

	$bookmarks .= '&limit=' . $limit;
	$bookmarks .= '&orderby=' . $orderby;
	$bookmarks .= '&order=' . $order;
	$bookmarks .= '&show_description=' . $show_description;

	$links = '<ul class="links">';
	//$links .= $bookmarks;
	$links .= wp_list_bookmarks('echo=0&categorize=0&title_li=' . $bookmarks);
	$links .= '</ul>';

	return $links;
}

add_shortcode('childpages', 'getChildPages');

function getChildPages($attributes) {

	extract(shortcode_atts(array(
		'readmore' => 'more'
	), $attributes));

	global $post;
	$allPages = get_pages('child_of='.$post->ID.'&parent='.$post->ID.'&sort_column=menu_order');

	foreach($allPages as $page) {

		$output .= '
	<div class="childpage">
		<h3><a href="'. get_permalink($page->ID).'">'. $page->post_title.'</a></h3>
		<p>'. $page->post_excerpt.'... <a href="'. get_permalink($page->ID).'">'.$readmore.'</a></p>
	</div>';

	}

	return $output;
}

function childNav($post) {
	
	// are you a grandchild? $grandchild will be 2 if so.
	$grandchild = count($post->ancestors);
	
	$postType = get_post_type($post);
	
	// if they have an ancestor, we need to return the top level
	if ($grandchild > 0) {
	  	$the_page = get_page(end($post->ancestors));
		$parent_link = get_permalink(end($post->ancestors));
	  	$parent_title = $the_page->post_title;
	
		$children = wp_list_pages("depth=".$instance['depth']."&title_li=&child_of=".end($post->ancestors)."&echo=0&sort_column=menu_order&post_type=" . $postType);		
	}
	else {
		$children = wp_list_pages("depth=".$instance['depth']."&title_li=&child_of=".$post->ID."&echo=0&sort_column=menu_order&post_type=" . $postType);
	  	$parent_title = $post->post_title;
		$parent_link = get_permalink($post->ID);
	}

				
	if ($children) { 
		echo '<h3><a href="'.$parent_link.'">' . $parent_title . '</a></h3>' . "\n";
	?>
		<ul id="childNavigation"> 
			<?php echo $children; ?> 
		</ul> 
	<?php
	}
}

add_shortcode('postsby', 'postsByVariables');

function postsByVariables($attributes) {

	extract(shortcode_atts(array(
		'showposts' => '5',
		'category_name' => '',
		'cat' => '',
		'tag' => '',
		'post_type' => 'post',
		'taxonomy' => '',
		'term' => ''
	), $attributes));

	$query = 'showposts='.$showposts.'&post_type=' .$post_type;

	if (!empty($taxonomy) && !empty($term)) {
		$query .= '&' . $taxonomy . '=' . $term;

	} elseif ($category_name != '') {
		$query .= '&category_name='. $category_name;
	}
	if ($cat != '') {
		$query .= '&cat='. $cat;
	}
	if ($tag != '') {
		$tag = str_replace(' ', '-', $tag);
		$query .= '&tag='. $tag;
	}

	$getPosts = new WP_Query();
	$getPosts->query($query);

	if ($getPosts->have_posts()) {

		foreach($getPosts->posts as $post) {
			$post_content = apply_filters('the_content', $post->post_content);
		
		$output .= '
			<div class="childpost post">
				<h3><a href="'. get_permalink($post->ID) .'">'. $post->post_title .'</a></h3>
				'. $post_content .'
			</div>';
		}
	}

	return $output;

}

add_shortcode('files', 'doAttachedFiles');

function doAttachedFiles($attributes) {
	global $post;
	
	extract(shortcode_atts(array(
		'post_mime_type' => '',
		'numberposts' => -1
	), $attributes));
	
	$fileArray =& get_children('post_type=attachment&post_mime_type='. $post_mime_type .'&orderby=menu_order&order=asc&numberposts='.$numberposts.'&post_parent=' . $post->ID );
	
	$output = '<div id="attachedFiles">'."\n";

	foreach($fileArray as $file) {
			$classArray = explode('/', $file->post_mime_type);
			$output .= '<h4 class="'.$classArray[1].'"><a href="'.$file->guid.'" class="'.$classArray[1].'">'. $file->post_title .'</a></h4>' . "\n";	  
	}

	$output .= '</div><!-- attachedFiles -->'."\n\n";

	return $output;

}


add_shortcode('iframe', 'doIframeShortcode');

function doIframeShortcode($attributes) {

	extract(shortcode_atts(array(
		'src' => '',
		'width' => '',
		'height' => '',
		'class' => ''
	), $attributes));

	if (empty($src)) return;

	if ($width > 0 && $height > 0) $wh = 'width="'.$width.'" height="'.$height.'"';

	$output = '<iframe class="binaryIframe '.$class.'" '.$wh.' src="'.$src.'"></iframe>';

	return $output;
}


add_post_type_support( 'page', 'excerpt' );


// http://madething.org/post/1529181499/solved-moving-image-attachments-between-pages-in
/**
* THESE FUNCTIONS ALLOW FOR ATTACHMENTS THAT BELONG TO PAGES TO BE REASSIGNED BETWEEN PAGES ON THE MEDIA EDIT SCREEN
*
*/

/**
 *
 * @param array $form_fields
 * @param object $post
 * @return array
 */
function my_image_attachment_fields_to_edit($form_fields, $post) {
	// only activate for images that already attached to pages, ignore images attached to posts
	if (get_post_type($post->post_parent) == 'page') {
		// get the list of pages for our select box
		$all_pages = get_pages();
		$select_code = get_pages_as_select_field($post, $all_pages);
		// $form_fields is a special array of fields to include in the attachment form
		// $post is the attachment record in the database
		// $post->post_type == 'attachment'
		// (attachments are treated as posts in WordPress)
		// add our custom field to the $form_fields array
		// input type="text" name/id="attachments[$attachment->ID][custom1]"
		$form_fields["post_parent"] = array(
			"label" => __("Attatched to page"),
			"input" => "html", 
			"html" => $select_code
		);
	}
	return $form_fields;
}

/**
 *
 * @param object $post
 * @param object $all_pages
 * @return string
 */
function get_pages_as_select_field($post, $all_pages) {
	
		$content = "<select name='attachments[{$post->ID}][post_parent]' id='attachments[{$post->ID}][post_parent]'>";
		foreach ($all_pages as $page) {
			if ($page->ID == $post->post_parent) {
				$selected = ' SELECTED ';
			} else {
				$selected = ' ';
			}
			$option_line = "<option" . $selected . "value='" . $page->ID . "'>" . $page->post_title . "</option>";
			$content = $content . $option_line;
		}		
		$content = $content . "</select>";
		return $content;
}

// attach our function to the correct hook
add_filter("attachment_fields_to_edit", "my_image_attachment_fields_to_edit", null, 2);

/**
 * @param array $post
 * @param array $attachment
 * @return array
 */
function my_image_attachment_fields_to_save($post, $attachment) {
	if( isset($attachment['post_parent']) ){
		if( trim($attachment['post_parent']) == '' ){
			// adding our custom error
			$post['errors']['post_parent']['errors'][] = __('No value found for post_parent.');
		}else{
			$post['post_parent'] = $attachment['post_parent'];
		}
	}
	return $post;
}
add_filter("attachment_fields_to_save", "my_image_attachment_fields_to_save", null, 2);

?>
