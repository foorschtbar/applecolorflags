/* Apple Mail colour flags for Roundcube */
(function () {
    var CMD_QUICK = 'plugin.applecolorflags.quick',
        CMD_SET = 'plugin.applecolorflags.set';

    function msg_color(uid) {
        var m = rcmail.env.messages && rcmail.env.messages[uid];
        return m && m.flags && m.flags.applecolor ? m.flags.applecolor : null;
    }

    function apply_row(uid, color) {
        var row = rcmail.message_list && rcmail.message_list.rows[uid],
            m = rcmail.env.messages && rcmail.env.messages[uid];

        if (m) {
            m.flags = m.flags || {};
            if (color) { m.flags.applecolor = color; } else { delete m.flags.applecolor; }
            m.flagged = color ? 1 : 0;
        }
        if (!row || !row.obj) { return; }

        var $r = $(row.obj);
        $r.removeClass(function (i, c) { return (c.match(/(^|\s)acf-\S+/g) || []).join(' '); });
        if (color) { $r.addClass('acf-' + color); }
        $r.toggleClass('flagged', !!color);
        row.flagged = !!color;
        $r.find('span.flag').children('span').attr('class', color ? 'flagged' : 'unflagged');
    }

    function selected_uids() {
        if (rcmail.message_list) {
            return rcmail.message_list.get_selection(false);
        }
        return rcmail.env.uid ? [rcmail.env.uid] : [];
    }

    function send(color) {
        var post = rcmail.selection_post_data();
        post._color = color;
        rcmail.http_post('plugin.applecolorflags.set', post, rcmail.set_busy(true, 'savingmessage'));
    }

    rcmail.addEventListener('insertrow', function (e) {
        var c = msg_color(e.uid);
        if (c) { $(e.row.obj).addClass('acf-' + c); }
    });

    rcmail.addEventListener('plugin.applecolorflags.done', function (r) {
        var uids = (r.uid === '*') ? Object.keys(rcmail.message_list ? rcmail.message_list.rows : {}) :
            String(r.uid || rcmail.env.uid || '').split(',');
        var color = r.color === 'none' ? null : r.color;
        $.each(uids, function (i, uid) { if (uid) { apply_row(uid, color); } });
    });


    // Refresh / check-recent: Roundcube only syncs standard flags, so derive colours from
    // the keyword flags it already delivers (lower-case keys, no "$").
    var MASKS = { 0: 'red', 1: 'orange', 2: 'yellow', 3: 'green', 4: 'turquoise', 5: 'purple', 6: 'gray' };
    function sync_recent(p) {
        $.each((p.response.env && p.response.env.recent_flags) || {}, function (uid, f) {
            var color = null;
            if (f.flagged) {
                color = MASKS[(f.mailflagbit0 ? 1 : 0) | (f.mailflagbit1 ? 2 : 0) | (f.mailflagbit2 ? 4 : 0)] || 'red';
            }
            if (msg_color(uid) !== color) { apply_row(uid, color); }
        });
    }
    rcmail.addEventListener('responsebeforerefresh', sync_recent);
    rcmail.addEventListener('responsebeforecheck-recent', sync_recent);

    rcmail.addEventListener('init', function () {
        // Quick colour toggle: the button's "prop" is the colour name
        rcmail.register_command(CMD_QUICK, function (color) {
            var uids = selected_uids(), all = uids.length > 0;
            $.each(uids, function (i, u) { if (msg_color(u) !== color) { all = false; } });
            send(all ? 'none' : color);
        }, false);

        rcmail.register_command(CMD_SET, function (color) { send(color); }, false);

        var update = function () {
            var on = selected_uids().length > 0 || !!rcmail.env.uid;
            rcmail.enable_command(CMD_QUICK, on);
            rcmail.enable_command(CMD_SET, on);
        };
        if (rcmail.message_list) {
            rcmail.message_list.addEventListener('select', update);
        }
        if (rcmail.env.uid) { update(); }
    });
}());
