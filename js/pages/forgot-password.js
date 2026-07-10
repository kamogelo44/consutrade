$(function() {
    // Login instead link
    $('#loginInsteadLink').on('click', function(e) {
        e.preventDefault();
        var email = $('#email').val().trim();
        if (email) {
            $('#login-email').val(email);
        }
        openModal($('#login-modal'));
    });

    // Forgot password form
    $('#forgotPasswordForm').on('submit', function(e) {
        e.preventDefault();

        var email = $('#email').val().trim();
        var $btn = $(this).find('.submit-btn');
        var $msg = $('#forgotMessage');

        if (!email) {
            $msg.removeClass('success-message error-message')
               .addClass('error-message')
               .text('Please enter your email address.')
               .show();
            return;
        }

        $btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/forgot-password.php',
            type: 'POST',
            data: { email: email },
            dataType: 'json',
            success: function(response) {
                $msg.removeClass('error-message success-message')
                   .addClass(response.success ? 'success-message' : 'error-message')
                   .text(response.message)
                   .show();

                if (response.success) {
                    $('#forgotPasswordForm').hide();
                }

                $btn.prop('disabled', false).text('Send Reset Link');
            },
            error: function() {
                $msg.removeClass('success-message')
                   .addClass('error-message')
                   .text('Something went wrong. Please try again.')
                   .show();
                $btn.prop('disabled', false).text('Send Reset Link');
            }
        });
    });
});