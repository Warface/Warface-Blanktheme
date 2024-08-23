<?php


/*------------------------------------*\
	External Modules/Files
\*------------------------------------*/

//Disable REST API
add_filter('rest_enabled', '__return_false');
add_filter('rest_jsonp_enabled', '__return_false');

// Disable REST API for non-authenticated users
add_filter('rest_authentication_errors', function( $result ) {
    if ( ! empty( $result ) ) {
        return $result;
    }
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
    }
    return $result;
});

//Block XLMRPC request
function add_xmlrpc_block_to_htaccess() {
    // Define the .htaccess file path
    $htaccess_file = ABSPATH . '.htaccess';
    
    // Define the rules to add
	$xmlrpc_block = "# Block WordPress xmlrpc.php requests\n";
    $xmlrpc_block .= "<Files xmlrpc.php>\n    Order Allow,Deny\n    Deny from all\n</Files>\n";
	$xmlrpc_block .= "# END xmlrpc.php block\n";

    // Check if .htaccess file is writable
    if (is_writable($htaccess_file)) {
        // Read the existing contents of the .htaccess file
        $htaccess_contents = file_get_contents($htaccess_file);

        // Check if the rules already exist
        if (strpos($htaccess_contents, $xmlrpc_block) === false) {
            // Append the rules to the .htaccess file
            file_put_contents($htaccess_file, $xmlrpc_block, FILE_APPEND | LOCK_EX);
        }
    } else {
        // If .htaccess is not writable, you can display an admin notice (optional)
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>The .htaccess file is not writable. Please update the file permissions.</p></div>';
        });
    }
}

// Hook the function to an appropriate action
add_action('admin_init', 'add_xmlrpc_block_to_htaccess');

// disable for posts
add_filter('use_block_editor_for_post', '__return_false', 10);

// disable for post types
add_filter('use_block_editor_for_post_type', '__return_false', 10);

//Classic Widget editor
function example_theme_support() {
    remove_theme_support( 'widgets-block-editor' );
}
add_action( 'after_setup_theme', 'example_theme_support' );


//Remove JQuery migrate
function remove_jquery_migrate( $scripts ) {
   if ( isset( $scripts->registered['jquery'] ) ) {
        $script = $scripts->registered['jquery'];
        if ( $script->deps ) { 
            // Check whether the script has any dependencies
                $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
        }
    }
}
add_action( 'wp_default_scripts', 'remove_jquery_migrate' );

//Put Yoast at bottom
add_filter( 'wpseo_metabox_prio', function() { return 'low'; } );

//Enable SVG upload for admin only
function add_svg_to_upload_mimes( $upload_mimes ) {
    $upload_mimes['svg'] = 'image/svg+xml';
    $upload_mimes['svgz'] = 'image/svg+xml';
    return $upload_mimes;
}
add_filter( 'upload_mimes', 'add_svg_to_upload_mimes', 10, 1 );
function allow_unfiltered_uploads_for_admins() {
    if ( current_user_can( 'manage_options' ) ) {
        define('ALLOW_UNFILTERED_UPLOADS', true);
    }
}
add_action( 'init', 'allow_unfiltered_uploads_for_admins' );

/*------------------------------------*\
	Theme Support
\*------------------------------------*/

/* if (!isset($content_width)){ $content_width = 900; } */

if (function_exists('add_theme_support')){
    // Add Menu Support
    add_theme_support('menus');

    // Add Thumbnail Theme Support
    add_theme_support('post-thumbnails');
    add_image_size('large', 700, '', true); // Large Thumbnail
    add_image_size('medium', 250, '', true); // Medium Thumbnail
    add_image_size('small', 120, '', true); // Small Thumbnail
    add_image_size('custom-size', 700, 200, true); // Custom Thumbnail Size call using the_post_thumbnail('custom-size');
    add_image_size('square', 800, 800, true);


    // Enables post and comment RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Localisation Support
    load_theme_textdomain('warface', get_template_directory() . '/languages');
}

/*------------------------------------*\
	Functions
\*------------------------------------*/

// Load HTML5 Blank scripts (header.php)
function warface_header_scripts(){
    
    if ($GLOBALS['pagenow'] != 'wp-login.php' && !is_admin()) {

    	
        wp_enqueue_script('bootstrap', get_template_directory_uri() . '/js/bootstrap.bundle.min.js', array('jquery'), '3.3.4'); // Bootstrap!
        wp_enqueue_script('warfacescripts', get_template_directory_uri() . '/js/scripts.js', array('jquery'), '1.0.0'); // Custom scripts
        
        
    }
}

// Load HTML5 Blank styles
function warface_styles(){
    
    wp_enqueue_style('normalize', get_template_directory_uri() . '/css/normalize.css', array(), '1.0', 'all');
    wp_enqueue_style('bootstrap-css', get_template_directory_uri() . '/css/bootstrap.min.css', array(), '3.3.4', 'all');
    wp_enqueue_style('warface', get_template_directory_uri() . '/style.css', array(), '1.0', 'all');
    
}

// Register HTML5 Blank Navigation
function register_html5_menu(){
    
    // Using array to specify more menus if needed
    register_nav_menus( array( 
        'header-menu' => __('Header Menu', 'warface'),   // Main Navigation
        'footer-menu' => __('Footer Menu', 'warface'), // Sidebar Navigation
    ));
}


// Remove Injected classes, ID's and Page ID's from Navigation <li> items
function my_css_attributes_filter($var){ return is_array($var) ? array() : ''; }

// Remove invalid rel attribute values in the categorylist
function remove_category_rel_from_category_list($thelist){ return str_replace('rel="category tag"', 'rel="tag"', $thelist); }

// Add page slug to body class, love this - Credit: Starkers Wordpress Theme
function add_slug_to_body_class($classes){
    
    global $post;
    
    if (is_home()) {
        $key = array_search('blog', $classes);
        if ($key > -1) {
            unset($classes[$key]);
        }
    } elseif (is_page()) {
        $classes[] = sanitize_html_class($post->post_name);
    } elseif (is_singular()) {
        $classes[] = sanitize_html_class($post->post_name);
    }

    return $classes;
}

// If Dynamic Sidebar Exists
if (function_exists('register_sidebar')){
    
    // Define Sidebar Widget Area 1
    register_sidebar(array(
        'name' => __('Widget Area 1', 'warface'),
        'description' => __('Description for this widget-area...', 'warface'),
        'id' => 'widget-area-1',
        'before_widget' => '<div id="%1$s" class="%2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3>',
        'after_title' => '</h3>'
    ));

    // Define Sidebar Widget Area 2
    register_sidebar(array(
        'name' => __('Widget Area 2', 'warface'),
        'description' => __('Description for this widget-area...', 'warface'),
        'id' => 'widget-area-2',
        'before_widget' => '<div id="%1$s" class="%2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3>',
        'after_title' => '</h3>'
    ));
}

// Remove Admin bar
function remove_admin_bar(){ return false; }

// Remove 'text/css' from our enqueued stylesheet
function html5_style_remove($tag){ return preg_replace('~\s+type=["\'][^"\']++["\']~', '', $tag); }

// Remove thumbnail width and height dimensions that prevent fluid images in the_thumbnail
function remove_thumbnail_dimensions( $html ){
    
    $html = preg_replace('/(width|height)=\"\d*\"\s/', "", $html);
    return $html;
}





/*------------------------------------*\
	Actions + Filters + ShortCodes
\*------------------------------------*/

// Add Actions
add_action('init', 'warface_header_scripts'); // Add Custom Scripts to wp_head
add_action('wp_enqueue_scripts', 'warface_styles'); // Add Theme Stylesheet
add_action('init', 'register_html5_menu'); // Add HTML5 Blank Menu

// Remove Actions
remove_action('wp_head', 'feed_links_extra', 3); // Display the links to the extra feeds such as category feeds
remove_action('wp_head', 'feed_links', 2); // Display the links to the general feeds: Post and Comment Feed
remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint, EditURI link
remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
remove_action('wp_head', 'index_rel_link'); // Index link
remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
remove_action('wp_head', 'start_post_rel_link', 10, 0); // Start link
remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Display relational links for the posts adjacent to the current post.
remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook, WP version
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
//remove wp-json
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
remove_action('template_redirect', 'rest_output_link_header', 11, 0);

// Add Filters

add_filter('body_class', 'add_slug_to_body_class'); // Add slug to body class (Starkers build)
add_filter('widget_text', 'do_shortcode'); // Allow shortcodes in Dynamic Sidebar
add_filter('widget_text', 'shortcode_unautop'); // Remove <p> tags in Dynamic Sidebars (better!)
add_filter('the_category', 'remove_category_rel_from_category_list'); // Remove invalid rel attribute
add_filter('the_excerpt', 'shortcode_unautop'); // Remove auto <p> tags in Excerpt (Manual Excerpts only)
add_filter('the_excerpt', 'do_shortcode'); // Allows Shortcodes to be executed in Excerpt (Manual Excerpts only)
add_filter('show_admin_bar', 'remove_admin_bar'); // Remove Admin bar
add_filter('style_loader_tag', 'html5_style_remove'); // Remove 'text/css' from enqueued stylesheet
add_filter('post_thumbnail_html', 'remove_thumbnail_dimensions', 10); // Remove width and height dynamic attributes to thumbnails
add_filter('image_send_to_editor', 'remove_thumbnail_dimensions', 10); // Remove width and height dynamic attributes to post images


remove_filter('the_excerpt', 'wpautop'); // Remove <p> tags from Excerpt altogether



?>
