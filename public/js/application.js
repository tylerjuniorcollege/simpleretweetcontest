$('[data-toggle="popover"]').popover({
	'trigger':'hover',
	'placement':'bottom'
});

$('button.tracktweet').click(function(e) {
	$('#twitterurl').val($(this).val()).focus();
});