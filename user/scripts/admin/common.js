$(function(){

	$('body > .primary.nav > li > a').click(function(e){

		var duration          = 400;
		var target            = '.secondary.nav';
		var newly_selected    = $(this).parent();
		var expandable_target = newly_selected.find(target);

		if (expandable_target.length) {
			if (!newly_selected.hasClass('selected')) {

				var previously_selected = $('body > .primary.nav > li.selected');

				if (previously_selected.length) {
					previously_selected.find(target).slideUp(duration);
					previously_selected.removeClass('selected');
				}

				expandable_target.slideDown(duration, function(){
					newly_selected.addClass('selected');
				});

			}
			e.preventDefault();
		}
	});

	// Interface Enhancement: Fade out less important messages

	setTimeout(function(){
		$('.success').fadeOut(1000).hide(1000);
	}, 5000);

	setTimeout(function(){
		$('.alert').fadeOut(1000).hide(1000);
	}, 8000);


});
