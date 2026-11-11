<?php

/**
 * Apple Mail colour flags for Roundcube.
 *
 * Apple Mail stores flag colours as standard IMAP keywords:
 *   \Flagged (+ nothing)                      = red
 *   $MailFlagBit0                             = orange
 *   $MailFlagBit1                             = yellow
 *   $MailFlagBit0 $MailFlagBit1               = green
 *   $MailFlagBit2                             = blue (turquoise)
 *   $MailFlagBit0 $MailFlagBit2               = purple
 *   $MailFlagBit1 $MailFlagBit2               = gray
 * The colour keywords are only valid while \Flagged is set (draft-ietf-mailmaint-messageflag).
 *
 * Adds: coloured rows/flags in the message list, colour entries in the "Mark" menu and
 * user-defined "quick colour toggles" (toolbar buttons) configured under Settings.
 */
class applecolorflags extends rcube_plugin
{
    public $task = 'mail|settings';

    /** name => [bit mask (b0=1, b1=2, b2=4), css colour] */
    const COLORS = [
        'red'       => [0, '#e5372f'],
        'orange'    => [1, '#f5a31a'],
        'yellow'    => [2, '#e6c200'],
        'green'     => [3, '#2fb94f'],
        'turquoise' => [4, '#1fa9c4'],
        'purple'    => [5, '#a550d6'],
        'gray'      => [6, '#8e8e93'],
    ];

    /** Bundled toolbar icons: name => SVG path (24x24 viewBox, Material Design icons, Apache-2.0) */
    const ICONS = [
        // Paperless-ngx leaf (docs/assets/logo_leaf.svg of github.com/paperless-ngx/paperless-ngx), 1000x1000 viewBox
        'paperless' => 'M341,949.1c-6.9-20.3-20.7-61.2-21.9-61-199.6-88.9-182.5-229.8-134.3-347.5,30,137.2,268.8,148.9,146.2,336-.9,2.2,10,27.8,19.5,51.3,22.7-51.9,58.6-115.5,55.8-120.8C178,398.7,724.9,299,807.1,18.5c83,251.5,53.1,659.8-377.4,814.9-2,1.4-63.5,148.6-66.9,150.2-.2-2.1-33.2,2.9-30.1-8.7,1.6-7,4.8-16.2,8.2-25.6h0v-.2h.1ZM323.1,846.2c48.3-71.9-12.7-120.8-56.9-152.2,81.2,107.4,66.4,120.8,56.9,152.2h0Z',
        'flag'     => 'M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z',
        'star'     => 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z',
        'tag'      => 'M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z',
        'bookmark' => 'M17 3H7c-1.1 0-1.99.9-1.99 2L5 21l7-3 7 3V5c0-1.1-.9-2-2-2z',
        'check'    => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z',
        'document' => 'M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z',
        'receipt'  => 'M18 17H6v-2h12v2zm0-4H6v-2h12v2zm0-4H6V7h12v2zM3 22l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1.5-1.5L21 22V2l-1.5 1.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2 4.5 3.5 3 2v20z',
        'attach'   => 'M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z',
        'euro'     => 'M15 18.5c-2.51 0-4.68-1.42-5.76-3.5H15l1-2H8.58c-.05-.33-.08-.66-.08-1s.03-.67.08-1H15l1-2H9.24C10.32 6.92 12.5 5.5 15 5.5c1.61 0 3.09.59 4.23 1.57L21 5.3C19.41 3.87 17.3 3 15 3c-3.92 0-7.24 2.51-8.48 6H3l-1 2h4.06c-.04.33-.06.66-.06 1s.02.67.06 1H3l-1 2h4.52c1.24 3.49 4.56 6 8.48 6 2.31 0 4.41-.87 6-2.3l-1.78-1.77c-1.13.98-2.6 1.57-4.22 1.57z',
        'folder'   => 'M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z',
    ];

    /** Icons that don't use the default 24x24 viewBox */
    const ICON_VIEWBOX = ['paperless' => '0 0 1000 1000'];

    const PREF = 'applecolorflags_toggles';
    const MAX_TOGGLES = 20;
    const MAX_NAME = 30;

    private $rc;

    public function init()
    {
        $this->rc = rcmail::get_instance();
        $this->add_texts('localization/', true);

        if ($this->rc->task == 'settings') {
            $this->add_hook('preferences_sections_list', [$this, 'prefs_section']);
            $this->add_hook('preferences_list', [$this, 'prefs_list']);
            $this->add_hook('preferences_save', [$this, 'prefs_save']);
            $this->include_script('settings.js');
            $this->include_stylesheet($this->local_skin_path() . '/applecolorflags.css');
            $this->add_icon_css();
            return;
        }

        $this->register_action('plugin.applecolorflags.set', [$this, 'set_color']);
        $this->add_hook('messages_list', [$this, 'messages_list']);

        if ($this->rc->action == '' || $this->rc->action == 'show') {
            $this->include_script('applecolorflags.js');
            $this->include_stylesheet($this->local_skin_path() . '/applecolorflags.css');
            $this->add_icon_css();

            // user-defined quick colour toggles -> toolbar buttons
            $toggles = $this->get_toggles();
            $texts   = [];
            foreach ($toggles as $i => $t) {
                $texts['applecolorflags.t' . $i] = $t['name'];
            }
            // labels are resolved inside add_button(), so register the custom texts first
            $this->rc->load_language(null, $texts);

            foreach ($toggles as $i => $t) {
                $key  = 'applecolorflags.t' . $i;
                $tint = $t['tint'] ? ' acf-tint' : '';
                $this->add_button([
                    'command'    => 'plugin.applecolorflags.quick',
                    'prop'       => $t['color'],
                    'type'       => 'link',
                    'class'      => "button acf-qbtn acf-ic-{$t['icon']} acf-{$t['color']}{$tint} disabled",
                    'classact'   => "button acf-qbtn acf-ic-{$t['icon']} acf-{$t['color']}{$tint}",
                    'innerclass' => 'inner',
                    'label'      => $key,
                    'title'      => $key,
                ], 'toolbar');
            }

            // colours go into the "Mark" submenu
            foreach (array_merge(self::COLORS, ['none' => [0, '']]) as $name => $def) {
                $this->add_button([
                    'command'    => 'plugin.applecolorflags.set',
                    'prop'       => $name,
                    'type'       => 'link-menuitem',
                    'label'      => 'applecolorflags.c_' . $name,
                    'class'      => 'acf-item acf-' . $name . ' disabled',
                    'classact'   => 'acf-item acf-' . $name . ' active',
                ], 'markmenu');
            }
        }
    }

    /** Default for users who never saved the setting. */
    private static function default_toggles()
    {
        return [['name' => 'Paperless', 'icon' => 'paperless', 'color' => 'green', 'tint' => true]];
    }

    /** Validated list of the user's quick toggles. */
    private function get_toggles()
    {
        $list = $this->rc->config->get(self::PREF);
        if (!is_array($list)) {
            $list = self::default_toggles();
        }

        $out = [];
        foreach ($list as $t) {
            $t = is_array($t) ? $t : [];
            $name  = self::clean_name($t['name'] ?? '');
            $color = $t['color'] ?? '';
            $icon  = $t['icon'] ?? '';
            if (!isset(self::COLORS[$color])) {
                continue;
            }
            $out[] = [
                'name'  => $name !== '' ? $name : $this->gettext('c_' . $color),
                'icon'  => isset(self::ICONS[$icon]) ? $icon : 'flag',
                'color' => $color,
                'tint'  => !isset($t['tint']) || !empty($t['tint']), // older entries: tinted
            ];
            if (count($out) >= self::MAX_TOGGLES) {
                break;
            }
        }

        return $out;
    }

    private static function clean_name($name)
    {
        $name = preg_replace('/[\x00-\x1f\x7f]+/u', ' ', (string) $name);
        return mb_substr(trim($name), 0, self::MAX_NAME);
    }

    /** Only HTML output supports add_header(); AJAX (json) requests don't render a page. */
    private function add_icon_css()
    {
        if (method_exists($this->rc->output, 'add_header')) {
            $this->rc->output->add_header($this->icon_css());
        }
    }

    /** CSS for the bundled icons (rendered as masks so they can take the flag colour). */
    private function icon_css()
    {
        $css = '';
        foreach (self::ICONS as $name => $path) {
            $vb  = self::ICON_VIEWBOX[$name] ?? '0 0 24 24';
            $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='{$vb}'><path d='{$path}'/></svg>";
            $url = 'url("data:image/svg+xml,' . rawurlencode($svg) . '")';
            $css .= ".acf-ic-{$name}:before,.acf-prev.acf-ic-{$name}{-webkit-mask-image:{$url};mask-image:{$url};}";
        }

        return '<style>' . $css . '</style>';
    }

    // ---- Settings UI -------------------------------------------------------------------------

    public function prefs_section($args)
    {
        $args['list']['applecolorflags'] = [
            'id'      => 'applecolorflags',
            'section' => rcube::Q($this->gettext('section')),
        ];

        return $args;
    }

    private function toggle_row($t)
    {
        $name = new html_inputfield(['name' => 'acf_name[]', 'maxlength' => self::MAX_NAME,
            'size' => 20, 'class' => 'form-control']);

        $icons = new html_select(['name' => 'acf_icon[]', 'class' => 'custom-select']);
        foreach (array_keys(self::ICONS) as $i) {
            $icons->add($this->gettext('i_' . $i), $i);
        }

        $colors = new html_select(['name' => 'acf_color[]', 'class' => 'custom-select']);
        foreach (array_keys(self::COLORS) as $c) {
            $colors->add($this->gettext('c_' . $c), $c);
        }

        $tint = new html_select(['name' => 'acf_tint[]', 'class' => 'custom-select']);
        $tint->add($this->gettext('tint_color'), '1');
        $tint->add($this->gettext('tint_plain'), '0');

        return html::tag('tr', ['class' => 'acf-row'],
            html::tag('td', null, $name->show($t['name']))
            . html::tag('td', null, $icons->show($t['icon']))
            . html::tag('td', null, $colors->show($t['color']))
            . html::tag('td', null, $tint->show($t['tint'] ? '1' : '0'))
            . html::tag('td', null, html::span(['class' => 'acf-prev'], ''))
            . html::tag('td', null, html::a([
                'href' => '#', 'class' => 'acf-del', 'title' => $this->gettext('remove'),
            ], '&#x2715;'))
        );
    }

    public function prefs_list($args)
    {
        if ($args['section'] != 'applecolorflags') {
            return $args;
        }

        $rows = '';
        foreach ($this->get_toggles() as $t) {
            $rows .= $this->toggle_row($t);
        }

        $blank = $this->toggle_row(['name' => '', 'icon' => 'flag', 'color' => 'green', 'tint' => true]);

        $head = html::tag('thead', null, html::tag('tr', null,
            html::tag('th', null, rcube::Q($this->gettext('name')))
            . html::tag('th', null, rcube::Q($this->gettext('icon')))
            . html::tag('th', null, rcube::Q($this->gettext('color')))
            . html::tag('th', null, rcube::Q($this->gettext('tint')))
            . html::tag('th', null, '') . html::tag('th', null, '')));

        $table = html::tag('table', ['class' => 'acf-table', 'id' => 'acf-table'],
            $head . html::tag('tbody', ['id' => 'acf-rows'], $rows));

        $content = html::p(['class' => 'hint'], rcube::Q($this->gettext('hint')))
            . $table
            . html::a(['href' => '#', 'class' => 'button acf-add'], rcube::Q($this->gettext('add')))
            . '<template id="acf-row-tpl">' . $blank . '</template>';

        $args['blocks']['acf'] = [
            'name'    => rcube::Q($this->gettext('section')),
            'content' => $content,
        ];

        return $args;
    }

    public function prefs_save($args)
    {
        if ($args['section'] != 'applecolorflags') {
            return $args;
        }

        $names  = (array) ($_POST['acf_name'] ?? []);
        $icons  = (array) ($_POST['acf_icon'] ?? []);
        $colors = (array) ($_POST['acf_color'] ?? []);
        $tints  = (array) ($_POST['acf_tint'] ?? []);
        $list   = [];

        foreach ($colors as $i => $color) {
            if (!is_string($color) || !isset(self::COLORS[$color])) {
                continue;
            }
            $icon = $icons[$i] ?? '';
            $list[] = [
                'name'  => self::clean_name(is_string($names[$i] ?? null) ? $names[$i] : ''),
                'icon'  => is_string($icon) && isset(self::ICONS[$icon]) ? $icon : 'flag',
                'color' => $color,
                'tint'  => ($tints[$i] ?? '1') !== '0',
            ];
            if (count($list) >= self::MAX_TOGGLES) {
                break;
            }
        }

        $args['prefs'][self::PREF] = $list;

        return $args;
    }

    // ---- Message list ------------------------------------------------------------------------

    /** Derive the colour name from a message's flags (keys are upper-case, without $ or \). */
    public static function color_of($flags)
    {
        if (!is_array($flags) || empty($flags['FLAGGED'])) {
            return null;
        }

        $mask = (!empty($flags['MAILFLAGBIT0']) ? 1 : 0)
            | (!empty($flags['MAILFLAGBIT1']) ? 2 : 0)
            | (!empty($flags['MAILFLAGBIT2']) ? 4 : 0);

        foreach (self::COLORS as $name => $def) {
            if ($def[0] === $mask) {
                return $name;
            }
        }

        return 'red'; // 7 is unassigned -> plain flag
    }

    public function messages_list($args)
    {
        foreach ((array) ($args['messages'] ?? []) as $message) {
            $color = self::color_of($message->flags ?? []);
            if ($color) {
                $message->list_flags['extra_flags']['applecolor'] = $color;
            }
        }

        return $args;
    }

    /** AJAX: set colour for the selected messages. */
    public function set_color()
    {
        $color = rcube_utils::get_input_value('_color', rcube_utils::INPUT_POST);
        $mbox  = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_POST);

        if ($color !== 'none' && !isset(self::COLORS[$color])) {
            $this->rc->output->show_message('errorsaving', 'error');
            $this->rc->output->send();
            return;
        }

        $storage     = $this->rc->get_storage();
        $multifolder = false;
        $uids        = rcmail::get_uids(null, null, $multifolder, rcube_utils::INPUT_POST);
        $ok          = !empty($uids);
        if (!$ok) {
            rcube::write_log('errors', 'applecolorflags: no uids resolved, POST=' . json_encode($_POST));
        }

        foreach ((array) $uids as $folder => $folder_uids) {
            if (empty($folder_uids)) {
                continue;
            }

            if ($color === 'none') {
                $ok = $this->store($storage, $folder, $folder_uids, false, 0) && $ok;
            } else {
                $ok = $this->store($storage, $folder, $folder_uids, true, self::COLORS[$color][0]) && $ok;
            }
        }

        if ($ok) {
            $this->rc->output->command('plugin.applecolorflags.done', [
                'uid'   => rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST),
                'color' => $color,
            ]);
        } else {
            $this->rc->output->show_message('errorsaving', 'error');
        }

        $this->rc->output->send();
    }

    private function store($storage, $folder, $uids, $flagged, $mask)
    {
        if (!$storage->check_connection()) {
            return false;
        }

        $conn = $storage->conn;
        $uids = is_array($uids) ? $uids : [$uids];
        $ok   = true;

        $log = function ($what, $res) use ($conn, $folder, $uids) {
            if (!$res) {
                rcube::write_log('errors', sprintf('applecolorflags: %s failed (folder=%s uids=%s): %s',
                    $what, $folder, implode(',', $uids), json_encode([$conn->error, $conn->errornum])));
            }
            return $res;
        };

        // Colour keywords first (exact case, as Apple Mail writes them).
        for ($bit = 0; $bit < 3; $bit++) {
            $kw = '$MailFlagBit' . $bit;
            if ($flagged && ($mask & (1 << $bit))) {
                $ok = $log("+$kw", $conn->flag($folder, $uids, $kw)) && $ok;
            } else {
                $ok = $log("-$kw", $conn->unflag($folder, $uids, $kw)) && $ok;
            }
        }

        $ok = $log($flagged ? '+FLAGGED' : '-FLAGGED',
            $storage->set_flag($uids, $flagged ? 'FLAGGED' : 'UNFLAGGED', $folder)) && $ok;

        return $ok;
    }
}
