jQuery(function($){
  var $container = $('#dashboard-widgets')
    , $welcome = $container.find('#dashboard_promotions_welcome')
    , $chooser = $('#promotion-chooser')
    
  $chooser.on('change', function(){
    window.location = 'index.php?promotion_id='+$(this).find('option:selected').val();
  });
});