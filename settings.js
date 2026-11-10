/* Settings UI for the quick colour toggles */
$(function () {
    var $rows = $('#acf-rows');
    if (!$rows.length) { return; }

    function preview($tr) {
        $tr.find('.acf-prev').attr('class', 'acf-prev acf-ic-' + $tr.find('select[name="acf_icon[]"]').val()
            + ' acf-' + $tr.find('select[name="acf_color[]"]').val()
            + ($tr.find('select[name="acf_tint[]"]').val() === '1' ? ' acf-tint' : ''));
    }

    $rows.on('change', 'select', function () { preview($(this).closest('tr')); });
    $rows.on('click', '.acf-del', function (e) { e.preventDefault(); $(this).closest('tr').remove(); });

    $('.acf-add').on('click', function (e) {
        e.preventDefault();
        var tpl = document.getElementById('acf-row-tpl'),
            $tr = $(tpl.content.firstElementChild.cloneNode(true));
        $rows.append($tr);
        preview($tr);
        $tr.find('input').focus();
    });

    $rows.find('tr').each(function () { preview($(this)); });
});
