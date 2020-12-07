/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Claus-Justus Heine
 * @copyright 2020 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

var RoundCube = RoundCube || {};
if (!RoundCube.appName) {
    const state = OCP.InitialState.loadState('roundcube', 'initial');
    RoundCube = $.extend({}, state);
    RoundCube.refreshTimer = false;
    console.debug("RoundCube", RoundCube);
}

/**
 * This is only for larry/melanie2_larry_mobile skins.
 * For other skins we don't do anything.
 */
RoundCube.hideTopLine = function() {
    const rcf = $("#roundcubeFrame");
    if (rcf[0].contentWindow.rcmail.env.skin.includes("classic")) {
	// just remove the logout button
	const rcfContents = rcf.contents();
	rcfContents.find('.button-logout').remove();
	return;
    }
    if (rcf[0].contentWindow.rcmail.env.skin.includes("elastic")) {
	// just remove the logout button
	const rcfContents = rcf.contents();
	rcfContents.find('.special-buttons .logout').remove();
	return;
    }
    if (!rcf[0].contentWindow.rcmail.env.skin.includes("larry")) {
	return;
    }
    const rcfContents = rcf.contents();
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
RoundCube.fillHeight = function(selector) {
    var height = $(window).height() - selector.offset().top;
    selector.css('height', height);
    if (selector.outerHeight() > selector.height()) {
	selector.css('height', height - (selector.outerHeight() - selector.height()));
    }
}
/**
 * Fills width of window (more precise than width: 100%;)
 */
RoundCube.fillWidth = function(selector) {
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
RoundCube.resizeIframe = function() {
    var selector = $('#roundcubeFrame');
    if (selector.length === 0) {
	return;
    }
    RoundCube.fillHeight(selector);
    RoundCube.fillWidth(selector);
}

$('#roundcubeFrame').ready(function() {
    $('#roundcubeFrame').load(function() {
	if (!RoundCube.showTopline) {
            RoundCube.hideTopLine();
        }
	RoundCube.resizeIframe();
	// Fade in roundcube nice with timeout to let iframe load
	$("#roundcubeLoaderContainer").fadeOut(500);
    });
});

$(function() {
    window.onresize = RoundCube.resizeIframe;
});
