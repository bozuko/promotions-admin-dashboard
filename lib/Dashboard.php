<?php

class PromotionsDashboard_Dashboard extends Snap_Wordpress_Plugin
{
  /**
   * @wp.action
   */
  public function wp_dashboard_setup()
  {
    //Completely remove various dashboard widgets (remember they can also be HIDDEN from admin)
    remove_meta_box( 'dashboard_quick_press',   'dashboard', 'side' );      //Quick Press widget
    remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );      //Recent Drafts
    remove_meta_box( 'dashboard_primary',       'dashboard', 'side' );      //WordPress.com Blog
    remove_meta_box( 'dashboard_secondary',     'dashboard', 'side' );      //Other WordPress News
    remove_meta_box( 'dashboard_right_now',     'dashboard', 'normal' );    //At a Glance
    remove_meta_box( 'dashboard_incoming_links','dashboard', 'normal' );    //Incoming Links
    remove_meta_box( 'dashboard_plugins',       'dashboard', 'normal' );    //Plugins
    remove_meta_box( 'dashboard_activity',      'dashboard', 'normal' );    //Plugins
    
    wp_enqueue_script('promotions-dashboard', PROMOTIONS_DASHBOARD_URI.'/assets/javascripts/dashboard.js', array('jquery'));
    
    // add our own
    wp_add_dashboard_widget('dashboard_promotions_welcome', 'Welcome', array(&$this, 'welcome_box'), 'core', 'high');
  }
  
  public function welcome_box()
  {
    ?>
    <p>This is the welcome message.</p>
    <?php
  }
}
