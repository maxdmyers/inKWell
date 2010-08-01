$(function(){

	/**
	 * Tables which have selection elements, examples:
	 *
	 * thead > th.selection
	 * tbody > td.selection
	 *
	 * Will have usability enhnacements to select all / none and to click the
	 * entire cell to check it off.
	 */

	$('tbody td.selection input[type="checkbox"]').click(function(e){

		var row = $(this).parents('tr');

		if (row.hasClass('selected')) {
			row.removeClass('selected');
		} else {
			row.addClass('selected');
		}

		e.stopPropagation();
	});

	$('tbody td.selection').css('cursor', 'pointer').click(function(e){
		$(this).children('input[type="checkbox"]').click();
	});


	$('thead th.selection')
	.empty()
	.css({
		'white-space':'nowrap'
	})
	.append('( ')
		.append(
			$('<a>All</a>').click(function(){
				var checkboxes = $(this).parent()[0].checkboxes;

				checkboxes.each(function(){
					var checkbox = $(this);

					if (!checkbox.attr('checked')) {
						checkbox.click();
					}
				});
			})
		).append(' | ').append(
			$('<a>None</a>').click(function(){

				var checkboxes = $(this).parent()[0].checkboxes;

				checkboxes.each(function(){
					var checkbox = $(this);

					if (checkbox.attr('checked')) {
						checkbox.click();
					}
				});
			})
		).append(' | ').append(
			$('<a>Invert</a>').click(function(){

				var checkboxes = $(this).parent()[0].checkboxes;

				checkboxes.each(function(){
					var checkbox = $(this);

					checkbox.click();
				});
			})
		)
	.append(' )')
	.each(function(){
		this.checkboxes = $(this).parents('table').find('td.selection input[type="checkbox"]');
	});

});
