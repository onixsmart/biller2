$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();

    // registration form steps start
    if ($('#business_register_form').length) {
        var form = $('#business_register_form').show();
        form.submit({ function(event, currentIndex) {
                form.validate().settings.ignore = ':disabled';
                return form.valid();
            }
        });
    }
    // registration form steps end

    //Date picker
    $('.start-date-picker').datepicker({
        autoclose: true,
        endDate: 'today',
    });

    $('form#business_register_form').validate({
        errorPlacement: function(error, element) {
            if (element.parent('.input-group').length) {
                error.insertAfter(element.parent());
            } else if (element.parent().hasClass('checkbox')) {
                error.insertAfter(element.closest('.checkbox'));
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            name: 'required',
            email: {
                email: true,
                remote: {
                    url: '/business/register/check-email',
                    type: 'post',
                    data: {
                        email: function() {
                            return $('#email').val();
                        },
                    },
                },
            },
            password: {
                required: true,
                minlength: 5,
            },
            confirm_password: {
                equalTo: '#password',
            },
            username: {
                required: true,
                minlength: 4,
                remote: {
                    url: '/business/register/check-username',
                    type: 'post',
                    data: {
                        username: function() {
                            return $('#username').val();
                        },
                    },
                },
            },
            website: {
                url: true,
            },
        },
        messages: {
            name: LANG.specify_business_name,
            password: {
                minlength: LANG.password_min_length,
            },
            confirm_password: {
                equalTo: LANG.password_mismatch,
            },
            username: {
                remote: LANG.invalid_username,
            },
            email: {
                remote: LANG.email_taken,
            },
        },
    });

    $('#business_logo').fileinput({
        showUpload: false,
        showPreview: false,
        browseLabel: LANG.file_browse_label,
        removeLabel: LANG.remove,
    });
});
