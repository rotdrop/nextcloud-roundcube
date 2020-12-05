// Declare namespace
var Roundcube = Roundcube || {};

/**
 * This is only for larry/melanie2_larry_mobile skins.
 * For other skins we don't do anything.
 */
Roundcube.hideTopLine = function() {
	var rcf = $("#roundcubeFrame");
	if (!rcf[0].contentWindow.rcmail.env.skin.includes("larry")) {
		return;
	}
	var rcfContents = rcf.contents();
	// User shouldn't be able to logout from rc, but from outer app:
	// 1. #topline has a logout button which we don't want, so remove it and
	// adjust the top attribute of #mainscreen. Reduce height if no toolbar.
	// 2. Also remove button to show/hide the #topline and adjust the #taskbar.
	// 3. Remove other logout buttons.
	var toplineHeight = rcfContents.find("#topline").outerHeight(),
		mainscreenTop = parseInt(rcfContents.find('#mainscreen').css('top')),
		toolbarHeight = 40,
		newMainscreenTop = mainscreenTop - toplineHeight;
	rcfContents.find("#topline").remove(); // [1]
	if (rcfContents.find("#mainscreen .toolbar").length === 0) {
		newMainscreenTop -= toolbarHeight;
	}
	rcfContents.find('#mainscreen').css('top', newMainscreenTop); // [1]
	rcfContents.find('#taskbar .minmodetoggle').remove(); // [2]
	rcfContents.find('#taskbar').css('padding-right', 0); // [2]
	rcfContents.find('.button-logout').remove(); // [3]
};

/**
 * Fills height of window (more precise than height: 100%;)
 */
Roundcube.fillHeight = function(selector) {
	var height = $(window).height() - selector.offset().top;
	selector.css('height', height);
	if (selector.outerHeight() > selector.height()) {
		selector.css('height', height - (selector.outerHeight() - selector.height()));
	}
}
/**
 * Fills width of window (more precise than width: 100%;)
 */
Roundcube.fillWidth = function(selector) {
	var width = $(window).width() - selector.offset().left;
	selector.css('width', width);
	if (selector.outerWidth() > selector.width()) {
		selector.css('width', width - (selector.outerWidth() - selector.width()));
	}
}
/**
 * Fills height and width of RC window.
 * More precise than height/width: 100%.
 */
Roundcube.resizeIframe = function() {
	var selector = $('#roundcubeFrame');
	if (selector.length === 0) {
		return;
	}
	Roundcube.fillHeight(selector);
	Roundcube.fillWidth(selector);
}

$('#roundcubeFrame').ready(function() {
    if (!$('#roundcubeFrame').hasClass('showTopLine')) {
        $('#roundcubeFrame').load(function() {
            Roundcube.hideTopLine();
        });
    }
	Roundcube.resizeIframe();
});

$(document).ready(function() {
	// Fade in roundcube nice with timeout to let iframe load
	$("#roundcubeLoaderContainer").fadeOut(1500);
	window.onresize = Roundcube.resizeIframe;
});
