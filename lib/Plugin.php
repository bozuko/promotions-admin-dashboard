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
  }
  
  /**
   * @wp.action init
   * @wp.priority 11
   */
  public function register_roles()
  {
    $roles = array(
      'promotions admin'  => 'Promotions Admin',
      'promotions client' => 'Promotions Client'
    );
    foreach( $roles as $role_name => $label ){
      $role = get_role( $role_name );
      if( !$role ) $role = add_role( $role_name, $label );
      $role->add_cap('read');
      $role->add_cap('analyze_promotions');
      if( $role_name == 'promotions admin' ){
        foreach( (array) get_post_type_object('promotion')->cap as $cap ){
          $role->add_cap( $cap );
        }
      }
      
    }
  }
  
}
