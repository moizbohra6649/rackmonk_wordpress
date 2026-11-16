(function ($) {
	"use strict";


	function ajax_loadmore_active($scope, $) {
         //One
        var $container = $scope.find('.xhosting-post-grid-loadmore');
        if ($container.length > 0) {
           $container.on('click',function(event){
              event.preventDefault();

              var $that = $(this);
              var ajaxjsondata = $that.data('json_grid_meta');
              var xhosting_json_data = Object (ajaxjsondata);

              var contentwrap = $scope.find('.grid-loadmore-content'), // item contentwrap
                 postperpage = parseInt(xhosting_json_data.posts_per_page), // post per page number
                 showallposts = parseInt(xhosting_json_data.total_post); // total posts count

                 var items = contentwrap.find('.xhs-grid-item'),
                 totalpostnumber = parseInt(items.length),
                 paged =  parseInt( totalpostnumber / postperpage ) + 1; // paged number

                 $.ajax({
                    url: xhosting_ajax.ajax_url,
                    type: 'POST',
                    data: {
                       action: 'xhosting_post_ajax_loading',
                       ajax_json_data: ajaxjsondata,
                       paged:paged
                    },
                    beforeSend: function(){

                       $('<i class="fa fa-spinner fa-spin" style="margin-left:10px"></i>').appendTo( "#xhosting-post-grid-loadmore" ).fadeIn(100);
                    },
                    complete:function(){
                       $scope.find('.xhosting-post-grid-loadmore .fa-spinner ').remove();
                    }
                 })

                 .done(function(data) {

                       var $pstitems = $(data);
                       $scope.find('.grid-loadmore-content').append( $pstitems );
                       var newLenght  = contentwrap.find('.pfy-grid-item').length;

                       if(showallposts <= newLenght){
                          $scope.find('.xhosting-post-grid-loadmore').fadeOut(300,function(){
                             $scope.find('.xhosting-post-grid-loadmore').remove();
                          });
                       }

                 })

                 .fail(function() {
                    $scope.find('.xhosting-post-grid-loadmore').remove();
                 });

           });
     }

     


     }

   
    
	

	$(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/xhosting-post-ajax.default', ajax_loadmore_active);

	});

 
	
})(jQuery);