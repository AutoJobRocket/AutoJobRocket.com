<?php
if (!function_exists('is_exists_term')){
	function is_exists_term($tx_name, $name, $parent_id = ''){

		$term = false;
		$pmxi_terms = array();

		if ( false !== ($pmxi_terms = get_transient("pmxi_{$tx_name}_terms")) ){
			$pmxi_terms = maybe_unserialize($pmxi_terms);
			
			foreach ($pmxi_terms as $t) {
				if ($t['name'] == $name and (int) $t['parent_id'] == (int) $parent_id){
					$term = array('term_id' => $t['term_id']);
					break;
				}
			}
		}			

		delete_option("{$tx_name}_children");

		if ( $term === false ){

			$siblings = get_terms($tx_name, array('fields' => 'all', 'get' => 'all', 'parent' => (int)$parent_id) );

			$defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '' );
	        $args = wp_parse_args(array('name' => $name, 'taxonomy' => $tx_name), $defaults);									        
	        $args = sanitize_term($args, $tx_name, 'db');
			
	        if (!empty($siblings)) 
	        	foreach ($siblings as $t) {
		        	$add_term = true;
		        	if (!empty($pmxi_terms)) 
		        		foreach ($pmxi_terms as $key => $value) {
			        		if ($value['name'] == $t->name and (int) $value['parent_id'] == (int) $parent_id){
			        			$add_term = false;
			        			break;
			        		}
			        	}

		        	if ($add_term){
		        		$pmxi_terms[] = array(
		        			'name' => $t->name,
		        			'parent_id' => (int) $parent_id,
		        			'term_id' => $t->term_id
		        		);
		        	}
		        }

		    set_transient("pmxi_{$tx_name}_terms", maybe_serialize($pmxi_terms) );

			if (!empty($siblings)) 
				foreach ($siblings as $t) {

					if ($t->name == wp_unslash($args['name'])){
						$term = array('term_id' => $t->term_id);
						break;
					}
				}

		}

		return $term;
	}
}
?>