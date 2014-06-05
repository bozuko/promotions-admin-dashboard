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
    
    wp_enqueue_script('promotions-dashboard', PROMOTIONS_DASHBOARD_URI.'/assets/javascripts/dashboard.js', array('jquery'), '1.0', true);
    
    // check for the current promotion
    $this->promotions = get_posts(array(
      'post_type'     => 'promotion'
    ));
    
    usort( $this->promotions, function($a,$b){
      $fn = Snap::inst('Promotions_Functions');
      $now = $fn->now();
      $a_start = $fn->get_start( $a->ID );
      $b_start = $fn->get_start( $b->ID );
      
      if( $now > $a_start && $now < $b_start ){
        return 1;
      }
      if( $now > $b_start && $now < $a_start ){
        return -1;
      }
      return $a_start > $b_start ? 1 : -1;
      
    });
    
    if( ($promotion_id = @$_GET['promotion_id']) ){
      $this->promotion_id = $promotion_id;
    }
    if( !$this->promotion_id && count( $this->promotions ) ){
      $fn = Snap::inst('Promotions_Functions');
      $now = $fn->now();
      foreach( $this->promotions as $p ){
        $s = $fn->get_start( $p->ID );
        $e = $fn->get_end( $p->ID );
        if( $now >= $s ){
          $this->promotion_id = $p->ID;
        }
      }
      if( !$this->promotion_id ){
        $this->promotion_id = $this->promotions[0]->ID;
      }
    }
    $this->analytics = Snap::inst('Promotions_Analytics');
    
    // add our own
    
    add_meta_box('dashboard_promotions_main', 'Promotion', array(&$this, 'main_widget'), 'dashboard', 'normal', 'high');
    if( !$this->promotion_id ){
      return;
    }
    
    // how about some charts, eh?
    add_meta_box('dashboard_promotions_charts', 'Analytics', array(&$this, 'charts'), 'dashboard', 'side', 'high');
    
    // what about the download box?
    if( current_user_can('download_promotion_entries') ){
      add_meta_box('dashboard_promotions_download', 'Download', array(&$this, 'download'), 'dashboard','normal');
    }
    
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
    
    do_action('promotions/analytics/statistics', $this->promotion_id );
  }
  
  public function charts()
  {
    $chart_lib = PROMOTIONS_DASHBOARD_URI.'assets/DevExpressChartJS-13.2.9/Lib/js';
    wp_enqueue_script('globalize', $chart_lib.'/globalize.min.js', array('jquery'), '13.2.9', true);
    wp_enqueue_script('chartjs', $chart_lib.'/dx.chartjs.js', array('globalize'), '13.2.9', true);
    wp_enqueue_script('promotions-charts', PROMOTIONS_DASHBOARD_URI.'/assets/javascripts/promotions-charts.js', array('chartjs'), '1.0', true);
    
    $entry_metrics = array(
      'registration_entries'
    );
    if( Snap::inst('Promotions_Functions')->is_enabled('returnuser', $this->promotion_id ) ){
      $entry_metrics[] = 'return_entries';
    }
    
    $charts = apply_filters( 'promotions/dashboard/charts', array('combined-entries' => array(
      'title'   => 'Entries',
      'metrics' => apply_filters('promotions/dashboard/charts/entries/metrics', $entry_metrics)
    )), $this->promotion_id );
    
    
    $start = Snap::inst('Promotions_Functions')->get_start( $this->promotion_id );
    $end = Snap::inst('Promotions_Functions')->get_end( $this->promotion_id );
    $now = Snap::inst('Promotions_Functions')->now();
    
    if( $now < $end ) $end = $now;
    
    foreach( $charts as $name => $config ){
      $this->chart( $name, $config, $start, $end );
    }
    
  }
  
  public function chart( $name, $config, $start, $end )
  {
    if( @$config['javascript'] ){
      wp_enqueue_script('chart-'.$name, $config['javascript'] );
    }
    
    if( @$config['metrics'] ){
      $config['data'] = array();
      
      $metrics = Snap::inst('Promotions_Analytics')->get_metrics();
      $config['metric_configs']= array();
      
      foreach( $config['metrics'] as $metric ){
        
        $config['metric_configs'][$metric] = $metrics[$metric];
        $config['data'][$metric] = array(
          'hour'      => $this->analytics->get( $this->promotion_id, $metric, 'hour'),
          'day'       => $this->analytics->get( $this->promotion_id, $metric, 'day'),
          'week'      => $this->analytics->get( $this->promotion_id, $metric, 'week')
        );
      }
    }
    
    ?>
    <div class="chart-wrapper">
      <h2 class="promotions-heading"><?= $config['title'] ?></h2>
      <div class="chart-container"
           data-chart="<?= $name ?>"
           data-chart-config="<?= esc_attr(json_encode( $config )) ?>"
      ></div>
    </div>
    <?php
  }
  
  public function download()
  {
    ?>
    <form action="?" method="POST">
      <?php
      do_action('promotions/download/form', $this->promotion_id);
      wp_nonce_field('download_promotion_entries', '_action');
      ?>
      <input type="hidden" name="promotion" value="<?= $this->promotion_id ?>" />
      <button class="button button-primary">Download Entries</button>
    </form>
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
    <div class="big-stats big-stats-two">
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
    
    $weekly_stats = array(
      'registrations'   => 'Registrations',
      'entries'         => 'Entries'
    );
    
    $weekly_stats = apply_filters('promotions/analytics/weekly_statistics', $weekly_stats, $this->promotion_id);
    
    $map = array();
    
    foreach( $weekly_stats as $name => $label ){
      $stats = $this->analytics->get($this->promotion_id, $name, 'week');
      $map[$name] = array();
      foreach( $stats as $stat ){
        $map[$name][$stat->unit] = $stat->total;
      }
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
          <?php
          foreach( $weekly_stats as $label ){
            ?>
            <th><?= $label ?></th>
            <?php
          }
          ?>
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
            <?php
            foreach( $weekly_stats as $name => $label ) {
              ?>
            <td><?= isset($map[$name][$key]) ? $map[$name][$key] : '0' ?></td>
              <?php
            }
            ?>
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
