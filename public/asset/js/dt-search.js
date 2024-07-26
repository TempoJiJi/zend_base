var usersTable;

function _resetDtSearch(_table = null) {
    if (_table != null) {
        dt = _table;
    } else {
        dt = usersTable;
    }

    let s = $('.dt-search');
    $(s).each(function(i, obj) {
        if (typeof $(this).val() !== 'undefined') {
            $(this).val('');
            if ($(this).is('select')) {
                $(this).formSelect();
            }
        }
    });

    // reset all search columns
    dt.search('').columns().search('');

    // prevent double draw, daterangepicker on change event will redraw the table
    if (typeof _start_date === 'undefined' || _end_date === 'undefined') {
        dt.draw();
    } else {
        // this one will redraw table
        $('#search_date_range').trigger('cancel.daterangepicker');
    }
}

function _dtSearch(_table = null) {

    if (_table != null) {
        dt = _table;
    } else {
        dt = usersTable;
    }

    let s = $('.dt-search');
    $(s).each(function(i, obj) {
        if (typeof $(this).val() !== 'undefined') {
            let _col = $(this).data('dt-col');
            dt.columns(_col).search($(this).val())
        }
    });

    dt.draw();
}