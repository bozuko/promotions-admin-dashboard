jQuery(function($){
  // default interval
  var interval = 'hour';
  
  $('.chart-container').each(function(){
    var series_data = $(this).data('series');
    
    if( !series_data.data[interval].length ){
      
    }
    
    
    $(this).dxChart({
      dataSource: convertDates(series_data.data[interval]),
      commonSeriesSettings : {
        argumentField : 'start'
      },
      series : [{
        name: series_data.meta.label,
        valueField: 'total',
        type: 'bar'
      }],
      legend: {
        visible: false
      },
      tooltip: {
        enabled: true
      },
      argumentAxis : {
        label : {
          format : 'shortTime'
        },
        tickInterval: 'hour'
      }
    });
  });
  
  function convertDates( data ){
    var i;
    for(i=0; i<data.length; i++ ){
      var t = data[i].start.split(/[- :]/);
      data[i].start = new Date(t[0],t[1]-1,t[2],t[3],t[4],t[5]);
    }
    return data;
  }
});