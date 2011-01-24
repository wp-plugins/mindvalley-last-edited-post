<?php
/*
Plugin Name: MindValley Show Last Edited
Plugin URI: http://mindvalley.com
Description: Add a metabox that shows a list of last edited posts / pages by current user at the post edit page.
Author: MindValley
Version: 0.1
*/


class MVShowLastEdit{
	
	function MVShowLastEdit(){
		// WP 3.0+
		// add_action('add_meta_boxes', array( &$this, 'add_custom_metabox'));
		
		// backwards compatible
		add_action('admin_init', array( &$this, 'add_custom_metabox'), 1);
		
		if( is_admin() )
			$this->enqueue_scripts_styles();
	}
	
	function enqueue_scripts_styles(){
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-tooltip', plugins_url('/jquery.tooltip.min.js', __FILE__), array('jquery'));
		wp_register_style('jquery-tooltip', plugins_url('/jquery.tooltip.css', __FILE__));
        wp_enqueue_style('jquery-tooltip');
	}
	
	function add_custom_metabox(){
		add_meta_box( 'mv_showlastedit', __( 'Last Edited', 'mindvalley' ), 
                array(&$this, 'inner_custom_metabox'), 'post', 'side', 'high' );
		add_meta_box( 'mv_showlastedit', __( 'Last Edited', 'mindvalley' ), 
				array(&$this, 'inner_custom_metabox'), 'page', 'side', 'high' );
	}
	
	function inner_custom_metabox() {
		global $current_user,$post;
		
		$original_post = $post;
		
		$args = array(
			'author'		=> $current_user->ID,
			'numberposts'   => 10,
			'orderby'       => 'modified',
			'order'         => 'DESC',
			'post_type'     => $post->post_type,
			'post_status'	=> 'any',
			'exclude'		=> $post->ID);
			
		$posts = get_posts( $args );
		
		if(!empty($posts)){
			echo '<ol>';
			foreach($posts as $post){
				$edit_link = get_edit_post_link($post->ID);
				?>
				<li>
                	<a class="title" href="<?php echo get_permalink( $post->ID )?>" target="_blank" rev="#mv_sle_fn<?php echo $post->ID?>"><?php echo (strlen($post->post_title) > 25) ? substr($post->post_title,0,25) . " ..." : $post->post_title;?></a>
				<?php 
					if(!empty($edit_link)){
						?>
                        	<a href="<?php echo $edit_link?>" target="_blank" style="float:right;">[EDIT]</a>
                        <?php				
					}
				?>
                <div id="mv_sle_fn<?php echo $post->ID?>" style="display:none">
					<h3><?php echo $post->post_title;?></h3>
                    <?php
						$output = '';
						$findparent = $post;
						$i = 0;
						while ($findparent->post_parent)	{
							$findparent = get_post($findparent->post_parent);
							
							if(empty($output))
								$output = '<strong>' . $findparent->post_title . '</strong>';
							else
								$output = '<strong>' . $findparent->post_title . '</strong>' . ' > ' . $output;
							
						} 
						if(!empty($output))
							echo "<strong>Parents:</strong> " . $output;
						
					?>
                    <br />[ID: <?php echo $post->ID?>]
                    <br /><br />
                    <?php 
						if(has_excerpt($post->ID))
							echo htmlspecialchars($post->post_excerpt);
						else
							echo htmlspecialchars($post->post_content);
					?>
                </div>
                </li>
				<?	
			}
			echo '</ol>';
		}
        ?>
        <script language="javascript">
			jQuery('#mv_showlastedit a.title').tooltip({ 
				bodyHandler: function() { 
					return jQuery(jQuery(this).attr("rev")).html(); 
				},
				track:true, 
				showURL: false 
			});
		</script>
        <?php
		
		$post = $original_post;
		
	}
	
	
	function get_ancestor_tree($args = ''){
		$defaults = array(
			'depth' => 0, 'show_date' => '',
			'date_format' => get_option('date_format'),
			'child_of' => 0, 'exclude' => '',
			'title_li' => __('Pages'), 'echo' => 1,
			'authors' => '', 'sort_column' => 'menu_order, post_title',
			'link_before' => '', 'link_after' => '', 'walker' => '',
		);
	
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		$output = '';
		$current_page = 0;
	
		// sanitize, mostly to keep spaces out
		$r['exclude'] = preg_replace('/[^0-9,]/', '', $r['exclude']);
	
		// Allow plugins to filter an array of excluded pages (but don't put a nullstring into the array)
		$exclude_array = ( $r['exclude'] ) ? explode(',', $r['exclude']) : array();
		$r['exclude'] = implode( ',', apply_filters('wp_list_pages_excludes', $exclude_array) );
	
		// Query pages.
		$r['hierarchical'] = 0;
		$pages = get_pages($r);
	
		if ( !empty($pages) ) {
			if ( $r['title_li'] )
				$output .= '<li class="pagenav">' . $r['title_li'] . '<ul>';
	
			global $wp_query;
			if ( is_page() || is_attachment() || $wp_query->is_posts_page )
				$current_page = $wp_query->get_queried_object_id();
			$output .= walk_page_tree($pages, $r['depth'], $current_page, $r);
	
			if ( $r['title_li'] )
				$output .= '</ul></li>';
		}
	
		$output = apply_filters('wp_list_pages', $output, $r);
	
		if ( $r['echo'] )
			echo $output;
		else
			return $output;	
	}
}

new MVShowLastEdit();