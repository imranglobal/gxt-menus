<?php
/**
* GX_Menus is a class that manages cache aroud menus
*/
class GX_Menus {
	
	function __construct(){
		add_action( 'wp_update_nav_menu', array( $this, 'invalidate_menu_cache' ), 10, 1 );
		add_action( 'update_option', array( $this, 'invalidate_theme_locations' ), 10, 1 );
	}
	public function get_menu_id_by_args( $args ) {
		// Look if menu is being pointed directly, if not look for theme_location
		if( isset( $args['menu'] ) ) {
			$menu = $this->get_nav_menu_object( $args['menu'] );
			if( $menu && $menu->term_id ) {
				return $menu->term_id;
			}
		} elseif( isset( $args['theme_location'] ) ) {
			
			$locations = $this->get_theme_locations();			
			if( isset( $locations[ $args['theme_location'] ] ) ) {
				$menu = $this->get_nav_menu_object( $locations[ $args['theme_location'] ] );
				if( $menu && $menu->term_id ) {
					return $menu->term_id;
				}
			}
		}
		return FALSE;
	}
	public function get_theme_locations( ) {
		$locations = wp_cache_get( 'gx_theme_menu_locations' );
		if( $locations === FALSE ) {
			$locations = get_nav_menu_locations();
			wp_cache_set( 'gx_theme_menu_locations', $locations );
		}
		return $locations;
	}
	
	/**
	* Cached version of wp_nav_menu_object() [ uses wpcom_vip_get_term_by ]
	* 
	* @param int $menu_id
	* @return object 
	*/
	public function get_nav_menu_object( $menu ) {
		if ( ! $menu ) {
			return false;
		}
			
		if( ! function_exists('wpcom_vip_get_term_by') ) {
			return wp_get_nav_menu_object( $menu );
		}
		$menu_obj = get_term( $menu, 'nav_menu' );
		if ( ! $menu_obj )
			$menu_obj = wpcom_vip_get_term_by( 'slug', $menu, 'nav_menu' );

		if ( ! $menu_obj )
			$menu_obj = wpcom_vip_get_term_by( 'name', $menu, 'nav_menu' );

		if ( ! $menu_obj )
			$menu_obj = false;

		return $menu_obj;
	}
	function invalidate_theme_locations( $option_name ){
		if( $option_name === 'nav_menu_locations' ) {
			wp_cache_delete( 'gx_theme_menu_locations' );
		}
	}
	function invalidate_menu_cache( $menu_id ) {
		wp_cache_delete( 'gx_menu_grp_' . $menu_id );	
		
		// EXCEPTION: Invalidate main menu cache on dependant menu changes	
		$menu = $this->get_nav_menu_object( $menu_id );
		do_action( 'gx_menu_changed', $menu );
		
		
	}
}
global $gxt_menus;
$gxt_menus = new GX_Menus();
/*
Use this instead of wp_nav_menu()
If menu html is not being shared across pages (default)

	$args['vary_by_url'] = TRUE

If menu html is same across all pages 

	$args['vary_by_url'] = FALSE

If menu is being shared on select pages

	$args['vary_by_url'] = is_category || is_tag() ? TRUE : FALSE;

*/
function gxt_nav_menu( $args ) {
	global $gxt_menus;
	$id = $gxt_menus->get_menu_id_by_args( $args );
	if( !$id ) {
		wp_nav_menu( $args );		
		return;
	}
	
	// Menu group
	$cache_group = wp_cache_get( 'gx_menu_grp_' . $id );
	
	if( $cache_group === FALSE ) {
		$cache_group = time();
		wp_cache_set( 'gx_menu_grp_' . $id, $cache_group );
	}
		
	$cache_args = $args;
	
	// Vary by URL
	$vary_by_url = FALSE;
	if( isset( $args['vary_by_url'] ) ) {
		$vary_by_url = $args['vary_by_url'];
	} else {
		// If no explicit vary by url, still we will vary on urls that are likely to be part of the menu. 
		if( is_home() || is_category() || is_tag() || is_page() ) {
			$vary_by_url = TRUE;
		}
	}
	
	if( $vary_by_url ) {
		global $wp;
		$cache_args['url'] = $wp->request;
	}
	
	// Vary by key
	$vary_by_key    = isset( $args['vary_by_key'] ) ? $args['vary_by_key'] : FALSE;
	if( $vary_by_key ) {
		$cache_args['key'] = $vary_by_key;
	}
	
	$cache_key = 'gx_m_' . $id . '_' . md5( maybe_serialize( $cache_args ) );
	$html      = wp_cache_get( $cache_key, $cache_group );
	if( $html === FALSE ) {
		$html = '';
		ob_start();
		wp_nav_menu( $args );
		$html = ob_get_clean();
		wp_cache_set( $cache_key, $html, $cache_group );	
		
	}
	if( isset( $args['echo'] ) && $args['echo'] === FALSE ) {
		return $html;
	}
	echo $html;
	
}
function gx_wp_nav_menu( $args, $vary_by_url = TRUE, $vary_by_key = FALSE ) {
	global $gxt_menus;
	$id = $gxt_menus->get_menu_id_by_args( $args );
	
	if( !$id ) {
		wp_nav_menu( $args );		
		return;
	}
	
	// Menu group
	$cache_group = wp_cache_get( 'gx_menu_grp_' . $id );
	
	if( $cache_group === FALSE ) {
		$cache_group = time();
		wp_cache_set( 'gx_menu_grp_' . $id, $cache_group );
	}
	
	
	$cache_args = $args;
	
	// If the menu accounts for current url ( i.e. current item highlights )
	if( $vary_by_url ) {
		global $wp;
		$cache_args['url'] = $wp->request;
	}
	if( $vary_by_key ) {
		$cache_args['key'] = $vary_by_key;
	}
	
	$cache_key = 'gx_m_' . $id . '_' . md5( maybe_serialize( $cache_args ) );
	$html      = wp_cache_get( $cache_key, $cache_group );
	if( $html === FALSE ) {
		$html = '';
		ob_start();
		wp_nav_menu( $args );
		$html = ob_get_clean();
		wp_cache_set( $cache_key, $html, $cache_group );	
		
	}
	if( isset( $args['echo'] ) && $args['echo'] === FALSE ) {
		return $html;
	}
	echo $html;
}
?>