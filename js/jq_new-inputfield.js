$(document).on('change', 'input', function() {
    console.log('triggered checkin if new field should be created');
    var emptyfields = 0;
    var filledfields = 0;
    var placeholder = '';

    // delete unnecessary empty fields
    $(this).parent().children(':text').each( function() {
       if($(this).val().length < 1) {
           console.log('deleting an empty field');
           $(this).remove();
       } else {
           placeholder = $(this).attr('placeholder');
       }
    });


    console.log('NOW i will add a SINGLE empty field');
    // var allfields = $(this).parent().children(':text').length;
    var field_id = 110;
    $(this).parent().append('<input placeholder="' + placeholder +'" class="datepicker" type="text" size="15" name="' + $(this).parent().attr('id') + '[]" id="' + $(this).parent().attr('id') + '_' + field_id +'"/> <script> $(function() { $("#'+ $(this).parent().attr('id') + '_' + field_id + '").dateRangePicker(); });</script>'); //add input box
});

