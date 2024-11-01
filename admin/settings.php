<div class="wrap">
    <div id="<?php echo $this->plugin->name; ?>-title" class="icon32"></div> 
    <h2><?php echo $this->plugin->displayName; ?> &raquo; <?php _e('Settings'); ?></h2>
           
    <?php    
    if ($this->message != '') {
        ?>
        <div class="updated"><p><?php echo $this->message; ?></p></div>  
        <?php
    }
    if ($this->errorMessage != '') {
        ?>
        <div class="error"><p><?php echo $this->errorMessage; ?></p></div>  
        <?php
    }
    ?>        
        
    <form id="post" name="post" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="<?php echo $this->plugin->name; ?>">
        <div id="poststuff" class="metabox-holder">
            <!-- Content -->
            <div id="post-body">
                <div id="post-body-content">
                    <div id="normal-sortables" class="meta-box-sortables ui-sortable" style="position: relative;">                        
                        <!-- Global Lock Settings -->
                        <div class="postbox">
                            <h3 class="hndle"><?php _e('Global Lock Settings'); ?></h3>
                            <div class="inside">
                            	<p class="description">
                            		<?php _e('If you wish to hide all content on a given Post Type, Post Type Taxonomy or Post Type Taxonomy Term, use the below settings.'); ?>
                            		<br />
                            		<?php _e('You can still lock individual content items, or parts of them, using the Like Lock button within the TinyMCE Editor.'); ?>
                            	</p>
                            	
                            	<p><strong><?php _e('Facebook: Application ID'); ?></strong></p>
	                            <p>
	                                <label class="screen-reader-text" for="label"><?php _e('Facebook: Application ID'); ?></label>
	                                <input type="text" name="<?php echo $this->plugin->name; ?>[facebookAppID]" value="<?php echo $this->settings['facebookAppID']; ?>" class="widefat" />   
	                            </p>
	                            <p class="description">
	                            	<?php _e('This can be a Facebook Page or Application ID. Get your ID from'); ?>
	                            	<a href="https://developers.facebook.com/apps" target="_blank">https://developers.facebook.com/apps</a>
	                            </p>
                            	
                            	
                            	<p><strong><?php _e('Lock Text'); ?></strong></p>
	                            <p>
	                                <label class="screen-reader-text" for="label"><?php _e('Lock Text'); ?></label>
	                                <input type="text" name="<?php echo $this->plugin->name; ?>[text]" value="<?php echo $this->settings['text']; ?>" class="widefat" />   
	                            </p>
	                            <p class="description"><?php _e('The text to display before the Like button, explaining that the site visitor must click Like to view the content.'); ?></p>
                            	
                            	<?php
                            	// Go through all Post Types
                            	$types = get_post_types('', 'names');
                            	foreach ($types as $key=>$type) {
                            		if (in_array($type, $this->ignoreTypes)) continue; // Skip ignored Post Types
                            		$postType = get_post_type_object($type);
                            		?>
                            		<p><strong><?php _e($postType->label.': Lock All Content on '.$postType->label); ?></strong></p>
                            		<p>
                            			<label class="screen-reader-text" for="label"><?php _e($postType->label.': Enable on All '.$postType->label); ?></label>
	                                    <input type="checkbox" name="<?php echo $this->plugin->name; ?>[enabled][<?php echo $type; ?>]" value="1"<?php echo ($this->settings['enabled'][$type] == 1 ? ' checked' : ''); ?> />   
	                                </p>
	                                <p class="description"><?php _e('If selected, locks all content until "Liked" for '.$postType->label.'. Overrides any taxonomy settings below.'); ?></p>
	                                
                            		<?php
                            		// Go through all taxonomies for this Post Type
                            		$taxonomies = get_object_taxonomies($type);
                            		foreach ($taxonomies as $taxKey=>$taxonomyProgName) {
										if (in_array($taxonomyProgName, $this->ignoreTaxonomies)) continue; // Skip ignored taxonomies
										
										// Go through this taxonomies terms
										$taxonomy = get_taxonomy($taxonomyProgName);
										$terms = get_terms($taxonomyProgName, array('hide_empty' => 0));
										?>
										<p><strong><?php _e($postType->label.': Lock Content on the below '.$taxonomy->label); ?></strong></p>
										<?php
										foreach ($terms as $termKey=>$term) {
	                                        ?>
	                                        <p>
	                                        	<input type="checkbox" name="<?php echo $this->plugin->name; ?>[taxonomies][<?php echo $taxonomyProgName; ?>][<?php echo $term->term_id; ?>]" value="1"<?php echo ($this->settings['taxonomies'][$taxonomyProgName][$term->term_id] == 1 ? ' checked' : ''); ?> />       
	                                       		<strong><?php echo $term->name; ?></strong>
	                                       	</p>
	                                        <?php
										}
									}	
                            	}
                            	?>
                            </div>
                        </div>
                        
                        <!-- Go Pro -->
                        <div class="postbox">
                            <h3 class="hndle"><?php _e('Pro Settings and Support'); ?></h3>
                            <div class="inside">
                            	<p><?php echo __('Upgrade to '.$this->plugin->displayName.' Pro to configure additional options, including:'); ?></p>
                            	<ul>
                            		<li><strong><?php _e('Multiple Social Networks'); ?>: </strong><?php _e('Choose from Facebook, Twitter, LinkedIn and/or Google+ share buttons for site visitors to unlock your content with.'); ?></li>
                            		<li><strong><?php _e('Lock Partial Content'); ?>: </strong><?php _e('TinyMCE plugin button to lock part of your Page, Post or Custom Post Type content.'); ?></li>
                            		<li><strong><?php _e('Lock Text Editor'); ?>: </strong><?php _e('TinyMCE lock text editor, providing more customisation and styling for the Lock text.'); ?></li>
                            		<li><strong><?php _e('Lock Text Styling'); ?>: </strong><?php _e('Define the lock text message font, size, background, border and spacing.'); ?></li>
                            		<li><strong><?php _e('Support'); ?>: </strong><?php _e('Access to support ticket system and knowledgebase.'); ?></li>
                            		<li><strong><?php _e('Seamless Upgrade'); ?>: </strong><?php _e('Retain all current settings and ratings when upgrading to Pro.'); ?></li>
                            	</ul>
                            	<p><a href="http://www.wpcube.co.uk/plugins/wp-like-lock-pro/" target="_blank" class="button"><?php _e('Upgrade Now'); ?></a></p>
                            </div>
                        </div>
                        
                        <!-- Save -->
                        <div class="submit">
                            <input type="submit" name="submit" value="<?php _e('Save'); ?>" /> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>