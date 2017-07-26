$(document).on('change blur', 'input', function() {
    // console.log('triggered checkin if new field should be created');
    var this_field = $(this);
    var placeholder = this_field.attr('placeholder');
    var cnt = 1;

    // delete unnecessary empty fields, except the we are working on 
    this_field.siblings(':text').each( function() {
       if($(this).val().length < 1) {
           console.log('deleting empty field with id ' + $(this).attr('id') +' and its script');
           var script_id = 's' + $(this).attr('id');
           $("#" + script_id).remove(); // delete attached script
           $(this).remove(); // delete input field
       }
    });

    function find_unused_id(f_parent, cnt) {
        var check_id = f_parent.attr('id') + '_' + cnt;
        console.log('checking if id ' + check_id + ' is already taken');
        if($("#" + check_id).length == 0) {
            // no field with id = check_id found, we can use this one
            return check_id;
        } else {
            return false;
        }
    };

    var new_id = find_unused_id(this_field.parent(), cnt);
    // add single empty field, find unique id for that
    while( new_id === false) {
        cnt++;
        new_id = find_unused_id(this_field.parent(), cnt);
    }


    console.log('adding new field with id ' + new_id + ' to ' + this_field.attr('id'));
    this_field.parent().append('<input placeholder="' + placeholder +'" class="datepicker" type="text" size="15" name="' + $(this).parent().attr('id') + '[]" id="' + new_id + '"/> <script id="s' + new_id + '"> $(function() { $("#' + new_id + '").dateRangePicker($.datepicker_settings); });</script>'); //add input box
    // console.log('submitting the form');
    // $( "#frm_duty" ).submit();
});

