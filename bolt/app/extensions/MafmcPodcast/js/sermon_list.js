jQuery(document).ready(function() {
    jQuery('audio').mediaelementplayer(/* Options */);

    var wrapper = jQuery('#audio_player-wrapper');
    if (!(jQuery.browser.msie && jQuery.browser.version < 8)) {
        if (wrapper.scrollToFixed) {
            wrapper.scrollToFixed({
                spacerClass: "audio_player-spacer",
                //preFixed: function() { jQuery(this).addClass("audio-header") },
                //postFixed: function() { jQuery(this).removeClass("audio-header") },
            });
        }
        seriesScrollToFixed();
    }
});

function seriesScrollToFixed() {
    if (!(jQuery.browser.msie && jQuery.browser.version < 8)) {
        if ( ! /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
            var wrapper = jQuery('#audio_player-wrapper');
            var series = jQuery('.sermon-series');
            series.each(function(i) {
                var s = jQuery(series[i]);

                var ele = s.find(".sermon-series-title-row");
                if (jQuery.isScrollToFixed(ele)) {
                    ele.trigger('detach.ScrollToFixed');
                    s.find(".sermon-series-scroll-spacer").remove();
                    ele.css({
                        "display": "",
                        "position": "",
                        "width": "",
                        "margin-left": "",
                        "z-index": "",
                        "left": "",
                        "top": ""
                    });
                }
                if (ele.scrollToFixed) {
                    ele.scrollToFixed({
                        marginTop: function() { return wrapper.outerHeight(true) },
                        limit: function() { return s.offset().top + s.innerHeight() - ele.outerHeight() - 20 },
                        zIndex: 999,
                        spacerClass: "sermon-series-scroll-spacer",
                        fixed: function() { s.find(".sermon-series-scroll-spacer").css("display", "table-row"); },
                        preAbsolute: function() { ele.css("display", "none"); },
                        preFixed: function() { ele.css("display", "table-row").css("position", ""); }
                    });
                }

            });
        }
    }
}

function play(audio_file, sermon_id) {
    var wrapper = jQuery('#audio_player-wrapper');
    var player = wrapper.find('audio')[0].player;
    var cur_time = player.getCurrentTime();
    player.setSrc(audio_file);
    player.play();

    var sermon = jQuery('#' + sermon_id);

    var height = wrapper.outerHeight(true);
    if (sermon.length) {
        wrapper.find("#sermon-now-playing-title").html( sermon.find(".sermon-title").html() );
        wrapper.find("#sermon-now-playing-year").html( sermon.find(".sermon-date-year").html() );
        wrapper.find("#sermon-now-playing-month").html( sermon.find(".sermon-date-month").html() );
        wrapper.find("#sermon-now-playing-day").html( parseInt(sermon.find(".sermon-date-day").html()) );
        wrapper.find("#sermon-now-playing-speaker").html( sermon.find(".sermon-speaker").html() );
        if (height != wrapper.outerHeight(true)) {
            //if (wrapper.hasClass("scroll-to-fixed-fixed")) {
                wrapper.trigger("resize");
            //}
            seriesScrollToFixed();
        }
    }
}
