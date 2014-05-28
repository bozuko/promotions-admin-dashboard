<?php

/**
 * @promotions.plugin.name
 */
class PromotionsDashboard_Plugin extends Promotions_Plugin_Base
{
  
  /**
   * @wp.action     promotions/init
   */
  public function init()
  {
    Snap::inst('PromotionsDashboard_Dashboard');
    if( is_admin() ){
      Snap::inst('PromotionsDashboard_Download');
    }
  }
  
  /**
   * @wp.action init
   * @wp.priority 11
   */
  public function register_roles()
  {
    $roles = array(
      'promotions admin'  => 'Promotions Admin',
      'promotions client' => 'Promotions Client',
      'promotions manager'=> 'Promotions Manager'
    );
    
    $admin = get_role('administrator');
    $admin->add_cap('download_promotion_entries');
    $admin->add_cap('analyze_promotions');
    
    foreach( $roles as $role_name => $label ){
      $role = get_role( $role_name );
      if( !$role ) $role = add_role( $role_name, $label );
      $role->add_cap('read');
      $role->add_cap('analyze_promotions');
      if( $role_name != 'promotions client' ){
        $role->add_cap('download_promotion_entries');
      }
      if( $role_name == 'promotions admin' ){
        foreach( (array) get_post_type_object('promotion')->cap as $cap ){
          $role->add_cap( $cap );
        }
      }
      
    }
  }
  
  /**
   * @wp.filter   login_redirect
   */
  public function redirect_promotion_users( $redirect_to, $requested_redirect_to, $user )
  {
    if( is_wp_error( $user ) ) return $redirect_to;
    if( !array_intersect( array('promotions admin', 'promotions client', 'promotions manager'), $user->roles ) ){
      return $redirect_to;
    }
    if( $user->has_cap('analyze_promotions') ){
      $redirect_to = admin_url('index.php');
    }
    return $redirect_to;
  }
  
  
}
