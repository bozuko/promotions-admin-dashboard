<?php

class PromotionsDashboard_Dashboard extends Snap_Wordpress_Plugin
{
  
  protected $promotion_id = false;
  protected $promotions = array();
  protected $analytics;
  
  /**
   * @wp.action     admin_enqueue_scripts
   */
  public function add_stylesheet()
  {
    wp_enqueue_style('promotions-dashboard', PROMOTIONS_DASHBOARD_URI.'/assets/stylesheets/dashboard.css');
  }
  
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
    
    // check for the current promotion
    $this->promotions = get_posts(array(
      'post_type'     => 'promotion'
    ));
    
    if( ($promotion_id = @$_GET['promotion_id']) ){
      $this->promotion_id = $promotion_id;
    }
    if( !$this->promotion_id && count( $this->promotions ) ){
      $this->promotion_id = $this->promotions[0]->ID;
    }
    $this->analytics = Snap::inst('Promotions_Analytics');
    
    // add our own
    add_meta_box('dashboard_promotions_main', 'Promotion', array(&$this, 'main_widget'), 'dashboard', 'core', 'high');
    if( !$this->promotion_id ){
      return;
    }
    
    // how about some charts, eh?
    add_meta_box('dashboard_promotions_charts', 'Analytics', array(&$this, 'charts'), 'dashboard', 'side', 'high');
    
  }
  
  public function main_widget()
  {
    if( !$this->promotion_id ){
      ?>
    <p>There are no promotions</p>
      <?php
      return;
    }
    ?>
    <select id="promotion-chooser" style="width: 100%;">
      <?php
      foreach( $this->promotions as $promotion ){
        $selected = $promotion->ID == $this->promotion_id ?
          'selected="selected"' :
          '';
        ?>
      <option <?= $selected ?> value="<?= $promotion->ID ?>">
        <?= $promotion->post_title ?>
      </option>
        <?php
      }
      ?>
    </select>
    <?php
    
    do_action('promotions/analytics/statistics');
  }
  
  public function charts()
  {
    $chart_lib = PROMOTIONS_DASHBOARD_URI.'/assets/DevExpressChartJS-13.2.9/Lib/js';
    wp_enqueue_script('globalize', $chart_lib.'/globalize.min.js', array('jquery'), '13.2.9', true);
    wp_enqueue_script('chartjs', $chart_lib.'/dx.chartjs.js', array('globalize'), '13.2.9', true);
    wp_enqueue_script('promotions-charts', PROMOTIONS_DASHBOARD_URI.'/assets/javascripts/promotions-charts.js', array('chartjs'), '1.0', true);
    
    // lets get our charts...
    $metrics = $this->analytics->get_metrics();
    
    $start = Snap::inst('Promotions_Functions')->get_start( $this->promotion_id );
    $end = Snap::inst('Promotions_Functions')->get_start( $this->promotion_id );
    $now = Snap::inst('Promotions_Functions')->now();
    
    if( $now < $end ) $end = $now;
    
    foreach( $metrics as $metric => $title ){
      $this->chart( $metric, $title, $start, $end );
    }
    
  }
  
  public function chart( $metric, $meta, $start, $end )
  {
    $analytics = 
    $series = array(
      'metric'    => $metric,
      'meta'      => $meta,
      'data'      => array(
        'hour'      => $this->analytics->get( $this->promotion_id, $metric, 'hour'),
        'day'       => $this->analytics->get( $this->promotion_id, $metric, 'day'),
        'week'      => $this->analytics->get( $this->promotion_id, $metric, 'week')
      )
    );
    ?>
    <div class="chart-wrapper">
      <h2 class="promotions-heading"><?= $meta['label'] ?></h2>
      <div class="chart-container" data-metric="<?= $metric ?>" data-series="<?= esc_attr(json_encode( $series )) ?>">
      </div>
    </div>
    <?php
  }
  
  /**
   * @wp.action     promotions/analytics/statistics
   */
  public function default_statistics()
  {
    $registrations = $this->analytics
      ->get_all( $this->promotion_id, 'registrations' );
      
    $entries = $this->analytics
      ->get_all( $this->promotion_id, 'entries' );
    ?>
    <div class="big-stats">
      <div class="big-stat">
        <div class="number">
        <?= $registrations ?>
        </div>
        <div class="text">Registrations</div>
      </div>
      <div class="big-stat">
        <div class="number">
          <?= $entries ?>
        </div>
        <div class="text">
          Entries
        </div>
      </div>
    </div>
    <?php
  }
  
  /**
   * @wp.action     promotions/analytics/statistics
   * @wp.priority   11
   */
  public function weekly_statistics()
  {
    
    $registrations = $this->analytics
      ->get( $this->promotion_id, 'registrations', 'week', 'all' );
      
    $entries = $this->analytics
      ->get( $this->promotion_id, 'registration_entries', 'week', 'all' );
      
    $reg_map = array();
    foreach( $registrations as $reg ){
      $reg_map[$reg->unit] = $reg->total;
    }
    $entry_map = array();
    foreach( $entries as $entry ){
      $entry_map[$entry->unit] = $entry->total;
    }
    
    $start = Snap::inst('Promotions_Functions')->get_start( $this->promotion_id );
    $end = Snap::inst('Promotions_Functions')->get_end( $this->promotion_id );
    $now = Snap::inst('Promotions_Functions')->now();
    
    if( $now < $end ) $end = $now;
    ?>
    <h2 class="promotions-heading">Weekly Statistics</h2>
    <table class="stats-table">
      <thead>
        <tr>
          <th>Week of</th>
          <th>Registrations</th>
          <th>Entries</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $current = $start;
        $current->setISODate( $start->format('Y'), $start->format('W') ); 
        $interval = new DateInterval('P7D');
        $format = Snap::inst('Promotions_Analytics')->get_interval_format('week');
        while( $current < $end ){
          $key = $current->format( $format );
          ?>
          <tr>
            <td><?= $current->format('M d, Y') ?></td>
            <td><?= isset($reg_map[$key]) ? $reg_map[$key] : '0' ?></td>
            <td><?= isset($entry_map[$key]) ? $entry_map[$key] : '0' ?></td>
          </tr>
          <?php
          $current->add( $interval );
        }
        ?>
      </tbody>
    </table>
    <?php
  }
}
