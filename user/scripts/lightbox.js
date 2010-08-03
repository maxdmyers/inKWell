/**
 * Javascript for CSS Lightbox
 */

$(function() {

	$('div.lightbox > a')

		.each(function(){

			var lightbox_anchor = $(this);
			var trigger         = $('a[href="#' + lightbox_anchor.attr('id') + '"]');

			trigger.click(function(e){
				lightbox_anchor.fadeIn(500, function(){
					lightbox_anchor.css({
						'display':'block'
					});
				});
				e.preventDefault();
			});

		}).click(function(e){

			$(this).fadeOut(500, function(){
				$(this).css({
					'display':'none'
				});
			});
			e.preventDefault();
		});

});

