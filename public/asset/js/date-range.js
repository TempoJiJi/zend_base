var tzoffset = (new Date()).getTimezoneOffset() * 60000; //offset in milliseconds
var _start_date = (new Date(Date.now() - tzoffset)).toISOString().slice(0, -1).split('T')[0];
var _end_date = (new Date(Date.now() - tzoffset)).toISOString().slice(0, -1).split('T')[0];

$(document).ready(function() {
    $('#search_date_range').daterangepicker(
        {
            locale: {cancelLabel: 'Clear'}
        },
        function(start, end, label) {
            _start_date = start.format('YYYY-MM-DD');
            _end_date = end.format('YYYY-MM-DD');
        }
    ).val('');
    $('#search_date_range').on('cancel.daterangepicker', function(ev, picker) {
        _start_date = _end_date = '';
        $(this).val('').change();
    });
});