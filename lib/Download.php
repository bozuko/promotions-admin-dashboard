<?php

class PromotionsDashboard_Download extends Snap_Wordpress_Plugin
{
  protected $promotion = false;
  protected $delimiter = ',';
  protected $string_enclosure = '"';
  protected $fp = null;
  protected $chunk_size = 500;
  protected $is_download = false;
  
  public function __construct()
  {
    parent::__construct();
    $this->init();
  }
  protected function init()
  {
    if( !$_SERVER['REQUEST_METHOD'] == 'POST' || !isset($_POST['_action']) ) return;
    if( !wp_verify_nonce($_POST['_action'], 'download_promotion_entries') ) return;
    
    if( !current_user_can('download_promotion_entries') ) {
      wp_die("I'm sorry Dave, I'm afraid I can't do that.");
    }
    
    $promotion = get_post( $_POST['promotion'] );
    if( !$promotion || $promotion->post_type != 'promotion' ){
      $this->error = 'Invalid promotion';
      return;
    }
    
    $this->is_download = true;
    $this->promotion = $promotion;
    
    global $post;
    $post = $promotion;
    setup_postdata( $post );
  }
  
  /**
   * @wp.action     promotions/register_fields
   * @wp.priority   1000
   */
  public function download()
  {
    if( !$this->is_download ) return;
    
    
    $now = Snap::inst('Promotions_Functions')->now();
    
    // lets set the headers
    header('Content-Type: text/csv');
    $filename = $this->promotion->post_name.'-'.$now->format('Y-m-d').'.csv';
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    
    $this->fp = fopen('php://output', $w);
    $this->do_download();
    fclose( $this->fp );
    exit;
  }
  
  protected function do_download()
  {
    // get the fields...
    $form = Snap::inst('Promotions_PostType_Promotion')->get_registration_form( $this->promotion->ID );
    $export = array('entry.post_date' => 'Timestamp');
    foreach( $form->get_fields() as $field ){
      if( $field->get_config('no_save') ) continue;
      
      $label = $field->get_config('export_label');
      if( !$label ) $label = $field->get_config('label', $field->get_name() );
      $export['registration.meta.'.$field->get_name()] = $label;
    }
    
    $export['entry.meta.entry_type'] = 'Entry Type';
    
    $this->export = apply_filters('promotions/download/export_fields', $export, $this->promotion->ID );
    
    // do this in chunks...
    $i = 0;
    global $wpdb;
    $sql = <<<SQL
SELECT `entry`.`ID` FROM {$wpdb->posts} `entry`

  LEFT JOIN {$wpdb->posts} `reg`
    ON `entry`.`post_parent` = `reg`.`ID`
  
  WHERE `entry`.`post_type` = 'entry'
    AND `reg`.`post_parent` = %d
  
    AND `entry`.`ID` > %d 
  ORDER BY `entry`.`ID` ASC
  LIMIT %d
SQL;

    $this->current_id = 0;
    
    $this->do_headers();
    $wpdb->show_errors( true );
    
    ini_set('memory_limit', '256M');
    set_time_limit(0);
    
    global $wp_actions;
    
    while( ($ids = $wpdb->get_col($wpdb->prepare($sql, $this->promotion->ID, $this->current_id, $this->chunk_size) )) ){
      foreach( $ids as $id){
        $this->do_row( $id );
        $this->current_id = $id;
      }
      wp_cache_flush();
      $wpdb->queries = array();
      $wp_actions = array();
    }
  }
  
  protected function do_headers()
  {
    fputcsv( $this->fp, $this->export, $this->delimiter, $this->string_enclosure);
  }
  
  protected function do_row( $id )
  {
    $entry = get_post( $id );
    $registration = get_post( $entry->post_parent );
    $values = array();
    foreach( $this->export as $key => $label ){
      $parts = explode('.', $key);
      $object = array_shift( $parts );
      $obj = $$object;
      if( count( $parts ) == 1 ){
        $property = array_shift($parts);
        $values[] = isset( $obj->$property ) ? $obj->$property : '';
      }
      else {
        if( $parts[0] == 'meta' ){
          $property = $parts[1];
          $values[] = get_post_meta( $obj->ID, $property, true);
        }
      }
    }
    
    fputcsv( $this->fp, $values, $this->delimiter, $this->string_enclosure );
  }
}
