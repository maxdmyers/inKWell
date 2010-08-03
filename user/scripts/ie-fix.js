/**
 * Allows for IE's setInterval and setTimeout to pass parameters
 */
(function(f){
	window.setTimeout  = f(window.setTimeout);
	window.setInterval = f(window.setInterval);
})(function(f){
	return function(c,t){
		var a=[].slice.call(arguments,2);
		return f(function(){c.apply(this,a)},t)
	}
});

/**
 * Looks for a meta tag with the name "disable-ie-lt" whose value is then
 * compared with a version of i.e.  If the value is less than this a message
 * is displayed asking people to upgrade.
 */
$(function(){

	var disable-meta = $('meta[name="disable-ie-lt"]');

	if (disable-meta.length) {
		var version = intval(disable-meta[0].attr('content'))
	} else {
		return;
	}

	if ($.browser.msie && parseInt($.browser.version) < version) {

		var body = $('body');
		$('body').css({'width' : 'auto', 'margin': '0px', 'left': '0px'}).empty();

		$('<div>')
			.css({
				'position': 'absolute',
				'top': '0px',
				'left': '0px',
				'backgroundColor': 'black',
				'opacity': '0.75',
				'width': '100%',
				'height': $(window).height(),
				'zIndex': 5000
			})
			.appendTo('body');

		var error_message =
		'<div>' +
			'<h1>Sorry!</h1>' +
			'<h2>This page does not support Internet Explorer 6.</h2>' +
			'<p>If you would like to read our content please ' +
				'<a href="http://www.microsoft.com/windows/internet-explorer/default.aspx">upgrade your browser</a>.' +
			'</p>' +
			'<p>If you really want to make use of the full power of the web, try these ones: ' +
				'<a href="http://www.getfirefox.com">Mozilla Firefox</a>' +
				' or ' +
				'<a href="http://www.google.com/chrome">Google Chrome</a>' +
			'</p>' +
		'</div>';

		$(error_message)
			.css({
				'backgroundColor': 'white',
				'top': '50%',
				'left': '50%',
				'marginLeft': -210,
				'marginTop': 100,
				'width': 410,
				'padding': 10,
				'height': 200,
				'position': 'absolute',
				'zIndex': 6000
			})
			.appendTo('body');
	}
});
