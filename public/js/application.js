$(document).ajaxStart(function() {
	$("#progressbar").show();
});

$(document).ajaxStop(function() {
	$("#progressbar").hide();
});

$('[data-toggle="popover"]').popover({
	'trigger':'hover',
	'placement':'bottom'
});

$('button.tracktweet').click(function(e) {
	$('#twitterurl').val($(this).val()).focus();
});

$('#grabretweets').click(function() {
	$.ajax({
		dataType: "json",
		url: "/cron",
		data: function(data) {
			console.log(data);
		},
		progress: function(e) { // This might not be necessary, as it does things ON load and not while in transmission.
			if(e.lengthComputable) {
				var pct = (e.loaded / e.total) * 100;

				console.log(pct);
			} else {
				console.log(e);
			}
		}
	});
});

$(document).ready(function() {
	$("#progressbar").hide();
});