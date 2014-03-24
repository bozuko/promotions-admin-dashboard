<?php
/*
Plugin Name: Promotions: Admin Dashboard
Plugin URI: http://bozuko.com
Description: The admin dashboard provides a role for users to gain access only to relevant promotion statistics and information.
Version: 2.0.0
Author: Bozuko
Author URI: http://bozuko.com
License: Proprietary
*/

add_action('promotions/plugins/load', function()
{
  define('PROMOTIONS_DASHBOARD_DIR', dirname(__FILE__));
  define('PROMOTIONS_DASHBOARD_URI', plugins_url('/', __FILE__));
  
  Snap_Loader::register( 'PromotionsDashboard', PROMOTIONS_DASHBOARD_DIR . '/lib' );
  Snap::inst('PromotionsDashboard_Plugin');
}, 100);