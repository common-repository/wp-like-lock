<?php
/**
* Plugin Name: WP Like Lock
* Plugin URI: http://www.wpcube.co.uk/likelockpro
* Version: 1.0.5
* Author: <a href="http://www.wpcube.co.uk/">WP Cube</a>
* Description: Adds a Facebook Like button to Page, Post or Custom Post Type content, requiring it to be clicked in order to access the content.
*/

/**
* WP Like Lock Class
* 
* @package WordPress
* @subpackage Like Lock
* @author Tim Carr
* @version 1.0.5
* @copyright n7 Studios
*/
class WPLikeLock {
    /**
    * Constructor.
    */
    function WPLikeLock() {
        // Plugin Details
        $this->plugin->name = 'wp-like-lock';
        $this->plugin->displayName = 'WP Like Lock';
        $this->plugin->url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));  
        
        // Post Types and Taxonomies to ignore
        $this->ignoreTypes = array('attachment','revision','nav_menu_item');
		$this->ignoreTaxonomies = array('post_tag', 'nav_menu', 'link_category', 'post_format');        

        if (is_admin()) {
            add_action('init', array(&$this, 'InitPlugin'), 99);
            add_action('admin_menu', array(&$this, 'AddAdminMenu'));
            add_action('wp_dashboard_setup', array(&$this, 'DashboardWidget'));
			add_action('wp_network_dashboard_setup', array(&$this, 'DashboardWidget'));
        } else {
        	$this->settings = get_option($this->plugin->name);
        	add_filter('the_content', array(&$this, 'LockContent'));
        	add_action('init', array(&$this, 'FrontendScriptsAndCSS'));
        	add_filter('wp_footer', array(&$this, 'FrontendFooter'));
        }
    }
    
    /**
	* Adds a dashboard widget to list WP Cube Products + News
	*
	* Checks if another WP Cube plugin has already created this widget - if so, doesn't duplicate it
	*/
	function DashboardWidget() {
		global $wp_meta_boxes;
		
		if (isset($wp_meta_boxes['dashboard']['normal']['core']['wp_cube'])) return; // Another plugin has already registered this widget
		wp_add_dashboard_widget('wp_cube', 'WP Cube', array(&$this, 'OutputDashboardWidget'));
	}
	
	/**
	* Called by DashboardWidget(), includes dashboard.php to output the Dashboard Widget
	*/
	function OutputDashboardWidget() {
		$result = wp_remote_get('http://www.wpcube.co.uk/feed/?post_type=lum-product');
		if ($result['response']['code'] == 200) {
			$xml = simplexml_load_string($result['body']);
			$products = $xml->channel;
		}
		
		include_once(WP_PLUGIN_DIR.'/'.$this->plugin->name.'/views/dashboard.php');
	}
    
    /**
    * Initialises the plugin within the WordPress Administration
    *
    * Registers a button for the TinyMCE rich text editor and sets up a new shortcode
    */
    function InitPlugin() {
    	// CSS
    	wp_enqueue_style($this->plugin->name.'-admin-css', $this->plugin->url.'css/admin.css');
    	
    	// Javascript
    	wp_enqueue_script($this->plugin->name.'-tzcheckbox', $this->plugin->url.'js/jquery.tzCheckbox.js');
    	wp_enqueue_script($this->plugin->name.'-admin', $this->plugin->url.'js/admin.js', array('jquery'));
    }
    
    /**
    * Adds a single option panel to Wordpress Administration
    */
    function AddAdminMenu() {
        add_menu_page($this->plugin->displayName, $this->plugin->displayName, 'switch_themes', $this->plugin->name, array(&$this, 'AdminPanel'), $this->plugin->url.'images/icons/small.png');
    }
	
	/**
    * Outputs the plugin Admin Panel in Wordpress Admin
    */
    function AdminPanel() {
        // Save Settings
        if (isset($_POST['submit'])) {
            update_option($this->plugin->name, $_POST[$this->plugin->name]);
            $this->message = __('Settings Updated.'); 
        }
        
        // Load form
        $this->settings = get_option($this->plugin->name); 
        include_once(WP_PLUGIN_DIR.'/'.$this->plugin->name.'/admin/settings.php');  
    }
    
    /**
    * Enqueue any JS and CSS for the WordPress Frontend
    */
    function FrontendScriptsAndCSS() { 
    	// JS
    	wp_enqueue_script('jquery');
    	wp_enqueue_script($this->plugin->name.'-frontend', $this->plugin->url.'/js/frontend.js', false, false, true);
    	
    	// CSS
    	wp_enqueue_style($this->plugin->name.'-frontend', $this->plugin->url.'/css/frontend.css');
    }
    
    /**
    * Checks if the content needs to be locked
    *
    * @param string $content Content
    * @return string Content w/ Like Lock if required
    */
    function LockContent($content) {
    	// Check if content needs locking
    	if (is_front_page()) return $content; // Don't do anything on the front page of a web site
    	$lockRequired = $this->CheckIfContentLockRequired();

		$content = '<div class="'.$this->plugin->name.'-content'.($lockRequired ? ' hidden' : '').'">'.$content.'</div>
					<div class="'.$this->plugin->name.'-box">
						<div class="'.$this->plugin->name.'-box-text'.(!$lockRequired ? ' hidden' : '').'">
							'.(!empty($this->settings['text']) ? '<p>'.stripslashes($this->settings['text']).'</p>' : '').'
						</div>
						<div class="fb-like" data-href="'.$post->guid.'" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false"></div>
					</div>';
		
    	return $content;
    }  
    
    /**
    * Checks if a content lock on the current Post is required:
    *
    * 1. Content already unlocked (cookie check) (if $checkCookies == true)
    * 2. Global Lock on Post Type
    * 3. Global Lock on Post Type Taxonomy
    * 
    * @param bool $checkCookies Check Cookies (default: true)
    * @return bool Content Lock Required
    */
    function CheckIfContentLockRequired($checkCookies = true) {
		global $post;
		
		wp_reset_query();

    	// Global settings require content to be locked?
    	if (!is_array($this->settings)) return; // No settings defined
    	if (!is_singular()) return; // Not a single Post
    
    	// 1. Check if content is already unlocked
    	if ($checkCookies AND $_COOKIE[$this->plugin->name.'-'.$post->ID] == '1') {
    		return false; // No need to lock content
    	}
    	
    	// 2. Check if a post type lock is enabled
    	$type = get_post_type($post->ID);
    	if (is_array($this->settings['enabled']) AND $this->settings['enabled'][$type] == '1') {
    		// Post type enabled, regardless of taxonomies
    		return true;
    	}
    	
    	// 3. Check if a post type taxonomy lock is enabled
    	if (is_array($this->settings['taxonomies'])) {    	
	    	// Get all terms assigned to this Post
	    	// Check if we need to display ratings here
			$taxonomies = get_taxonomies();
			$ignoreTaxonomies = array('post_tag', 'nav_menu', 'link_category', 'post_format');
			foreach ($taxonomies as $key=>$taxonomyProgName) {
				if (in_array($taxonomyProgName, $this->ignoreTaxonomies)) continue; // Skip ignored taxonomies
				if (!is_array($this->settings['taxonomies'][$taxonomyProgName])) continue; // Skip this taxonomy
				
				// Get terms and build array of term IDs
				unset($terms, $termIDs);
				$terms = wp_get_post_terms($post->ID, $taxonomyProgName);
				foreach ($terms as $key=>$term) $termIDs[] = $term->term_id;

				// Check if any of the post term IDs have been selected within the plugin
				if ($termIDs) {
					foreach ($this->settings['taxonomies'][$taxonomyProgName] as $termID=>$intVal) {
						if (in_array($termID, $termIDs)) {
							return true;
		    				break;
		    			}	
					}
				}
	    	}
    	}
    	
    	// If here, no lock required
    	return false;
    }
    
    /**
    * Loads social media scripts if a lock button will be displayed on the content
    */
    function FrontendFooter() {
    	global $post;

   		// Include social sharing scripts if required
   		$result = $this->CheckIfContentLockRequired(false);
   		if ($this->CheckIfContentLockRequired(false)) {
			?>
			<!-- Facebook Activity -->
	   		<div id="fb-root"></div>
			<script type="text/javascript">
				var postID = '<?php echo $post->ID; ?>';
			
				<!--
			    var fbAsyncInit = function() {
			        var APP_ID = '<?php echo $this->settings['facebookAppID']; ?>';
			 
			 		// Init
			        FB.init({
			            appId  : APP_ID,
			            status : true, // check login status
			            cookie : true, // enable cookies to allow the server to access the session
			            xfbml  : true  // parse XFBML
			        });

			 		// User clicks Like
			 		// url = URL that has been liked
			        FB.Event.subscribe('edge.create', function(url) {
			        	createCookie('wp-like-lock-'+postID, 1, 30);  
			        	jQuery('div.wp-like-lock-content').removeClass('hidden'); // Show content
			        	jQuery('div.wp-like-lock-box-text').addClass('hidden'); // Hide text
			        });
			 
			 		// User clicks Unlike
			 		// url = URL that has been unliked
			        FB.Event.subscribe('edge.remove', function(url) {
			        	eraseCookie('wp-like-lock-'+postID);  
			        	jQuery('div.wp-like-lock-content').addClass('hidden'); // Hide content
			        	jQuery('div.wp-like-lock-box-text').removeClass('hidden'); // Show text
			        });
			    };
			 
			    (function() {
			        var e = document.createElement('script');
			        e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
			        e.async = true;
			        document.getElementById('fb-root').appendChild(e);
			    }());
				//-->
			</script>
			<?php	
		}	
    }  
}
$wpLL = new WPLikeLock(); // Invoke class
?>
