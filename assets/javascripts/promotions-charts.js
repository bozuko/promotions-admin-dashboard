jQuery(function($){
  // default interval
  var interval = 'day';
  /*
  $('.chart-container').each(function(){
    var series_data = $(this).data('series');
    
    if( !series_data.data[interval].length ){
      $(this).html('<p class="no-data">No data to display</p>');
      return;
    }
    
    
    $(this).dxChart({
      dataSource: convertDates(series_data.data[interval]),
      commonSeriesSettings : {
        argumentField : 'start'
      },
      commonAxisSettings : {
        valueMarginsEnabled: false
      },
      series : [{
        name: series_data.meta.label,
        valueField: 'total',
        type: 'splineArea',
        point: {
          size: 1
        },
        hoverMode: 'none'
      }],
      legend: {
        visible: false
      },
      tooltip: {
        enabled: true,
        argumentFormat: 'monthAndDay',
        customizeText: function() {
          return '<span style="font-size: 0.5em">'+this.argumentText + "</span><br/>" + this.valueText;
        }
      },
      argumentAxis : {
        label : {
          format : 'monthAndDay'
        }
      },
      
      seriesHoverChanged : function(){
        console.log(arguments);
      }
    });
  });
  */
  
  function CombinedEntriesChart( el ){
    this.$el = $(el);
    this.config = this.$el.data('chart-config');
    this.initData();
    this.render();
    $(window).on('mousemove', this.bind(this.onMouseMove, this));
  }
  
  $.extend( CombinedEntriesChart.prototype, {
    
    initData : function(){
      var source = this.config.data,
          metrics = [],
          j = 0
          
      
      this.data = [];
      
      for( var i in source ) metrics.push(i);
      
      while( this.hasMore( source, metrics ) ){
        this.data.push(this.nextRow( source, metrics ));
      }
      
    },
    
    hasMore : function( source, metrics ){
      for(var i=0; i<metrics.length; i++){
        if( source[metrics[i]][interval].length ){
          return true;
        }
      }
      return false;
    },
    
    nextRow : function( source, metrics ){
      var earliest = null,
          row = {};
          
      for( var i=0; i<metrics.length; i++){
        if( source[metrics[i]][interval].length ){
          var _earliest = source[metrics[i]][interval][0].start;
          if( !earliest || _earliest < earliest ){
            earliest = _earliest;
          }
        }
      }
      
      if( !earliest ) return false;
      
      var t = earliest.split(/[- :]/);
      row.start = new Date(t[0],t[1]-1,t[2],t[3],t[4],t[5]);
      
      for( var i=0; i<metrics.length; i++){
        if( source[metrics[i]][interval].length && source[metrics[i]][interval][0].start == earliest ){
          var v = source[metrics[i]][interval].shift();
          row[metrics[i]] = Number(v.total);
        }
        else {
          row[metrics[i]] = 0;
        }
      }
      
      return row;
    },
    
    render : function(){
      
      var series = [];
      
      for( var i in this.config.metric_configs ) (function(name, config){
        series.push({
          name: config.label,
          valueField : name
        });
      })(i, this.config.metric_configs[i]);
      
      this.$tooltip = $('<div class="chart-tooltip" style=""><div class="text">&nbsp;</div></div>').appendTo('body');
      
      var self = this;
      
      this.$el.dxChart({
        dataSource: this.data,
        commonSeriesSettings : {
          argumentField : 'start',
          type: series[0].length > 6 ? 'stackedArea' : "stackedBar"
        },
        series : series,
        legend: {
          visible: true,
          position: 'bottom',
          verticalAlignment: 'bottom',
          horizontalAlignment: 'center'
        },
        valueAxis : {
          title : {
            text : 'Entries'
          }
        },
        tooltip: {
          enabled: false
        },
        argumentAxis : {
          label : {
            format : 'monthAndDay'
          },
          tickInterval : 'day'
        },
        pointHoverChanged : function( point ){
          self.updateTooltip( point );
        }
      });
      
      window.chart = this.chart = this.$el.dxChart('instance');
    },
    
    bind : function( fn, scope ){
      return function(){
        fn.apply(scope, arguments);
      };
    },
    
    updateTooltip : function( point ){
      if( !point.isHovered() ){
        this.tooltipShowing = false;
        this.$tooltip.hide();
        return;
      }
      var argument = point.argument;
      var series = this.chart.series;
      var total = 0;
      var formatHelper = DevExpress.formatHelper;
      var html = '<h3>'+formatHelper.format(point.argument, 'monthAndDay')+'</h3><table>';
      $.each( series, function(i, s){
        var p = s.getPointByArg(argument);
        html+= '<tr><th>'+s.name+'</th><td>'+p.originalValue+'</td></tr>';
        total+= p.originalValue;
      });
      html+='<tr><th>Total</th><td>'+total+'</td></tr></table>';
      
      this.$tooltip.find('.text').html(html);
      this.$tooltip.show();
      this.tooltipShowing = true;
      this.updateTooltipPos();
    },
    
    updateTooltipPos : function(){
      var x = this.pageX;
      var y = this.pageY;
      x -= this.$tooltip.outerWidth();
      y -= this.$tooltip.outerHeight();
      
      if( x < 0 ){
        x = this.pageX;
      }
      if( y < 0 ){
        y = 0;
      }
      
      this.$tooltip.css({
        left: x,
        top: y
      });
    },
    
    onMouseMove : function(e){
      this.pageX = e.pageX;
      this.pageY = e.pageY;
      
      if( this.tooltipShowing ) this.updateTooltipPos();
    }
  });
  
  $('[data-chart="combined-entries"]').each( function(){
    new CombinedEntriesChart( this );
  });
  
  function convertDates( data ){
    var i;
    for(i=0; i<data.length; i++ ){
      var t = data[i].start.split(/[- :]/);
      data[i].start = new Date(t[0],t[1]-1,t[2],t[3],t[4],t[5]);
      data[i].total = Number(data[i].total);
    }
    return data;
  }
});