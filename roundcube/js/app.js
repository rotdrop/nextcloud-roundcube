// Declare namespace
var Roundcube = Roundcube || {};

Roundcube.pageY = function(elem, offset) {
	var sum = offset + elem.offsetTop;
	return elem.offsetParent ? Roundcube.pageY(elem.offsetParent, sum) : sum;
}
Roundcube.resizeIframe = function() {
	var buffer = 0; // Scroll bar buffer
	var height = document.documentElement.clientHeight;
	height -= Roundcube.pageY(document.getElementById('roundcubeFrame'), 0) + buffer;
	$('#roundcubeFrame').height(Math.max(0, height));
}

$('#roundcubeFrame').ready(function() {
	Roundcube.resizeIframe();
});

$(document).ready(function() {
	window.onresize = Roundcube.resizeIframe;
});
