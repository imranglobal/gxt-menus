gxt-menus
=========

This plugin will ensure better caching around the final markup of the menus. Also adds extra ability to configure the cache that is generated by this plugin.

This plugin enables a drop in replacement for [wp_nav_menu()](http://codex.wordpress.org/Function_Reference/wp_nav_menu) by using gxt_nav_menu(). 



###Usage:
```
$args = array( 
              'theme_location' => 'primary', 
              'items_wrap' => '<ul><li id="item-id">Menu: </li>%3$s</ul>'
              );
gxt_nav_menu( $args );
              
```

###Additional parameters:
Alongside the regular parameters of [wp_nav_menu()](http://codex.wordpress.org/Function_Reference/wp_nav_menu) there are 2 additional parameters that can be passed into gxt_nav_menu().

```
$args = array(
              ...                          // Regular wp_nav_menu() args
              'vary_by_url'    => TRUE,
              'vary_by_key'    => FALSE
        );

```

#####$vary_by_url

This parameter ensures that a new menu cache is generated for pages on which this is set to TRUE. Best caching results are achieved when set to FALSE.
Can be set to TRUE conditionally on certain pages too. Use when "current item" of menu is not needed.

**Example:** New cache on category pages only
```
$args['vary_by_url'] = is_category() ? TRUE : FALSE;
```

**Example:** New cache on archive pages only
```
$args['vary_by_url'] = is_archive() ? TRUE : FALSE;
```

**Example:** New cache on custom taxonomy & tag pages only
```
$args['vary_by_url'] = is_tax( 'person' ) || is_tag() ? TRUE : FALSE;
```

If this is not set, by default it will generate new cache when any one of is_home(), is_category(), is_tag() & is_page() returns TRUE.

**Note:** Setting this to TRUE for is_single() OR is_singular() may create a large cache footprint resulting in fewer benefits of the plugin.

#####$vary_by_key

Some Wordpress installs are set up to display the pages based on cookies, user-agents etc. Pass those values into this parameter.

```
$args['vary_by_key'] = $_COOKIE['location'];
// OR
$args['vary_by_key'] = $_SERVER['HTTP_USER_AGENT'];
```


