$(function() {
    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();

        var password = $('#password').val();
        var confirmPassword = $('#confirm_password').val();
        var token = $('#resetToken').val();
        var $btn = $(this).find('.submit-btn');
        var $msg = $('#resetMessage');

        if (password.length < 8) {
            $msg.removeClass('success-message error-message')
               .addClass('error-message')
               .text('Password must be at least 8 characters.')
               .show();
            return;
        }

        if (password !== confirmPassword) {
            $msg.removeClass('success-message error-message')
               .addClass('error-message')
               .text('Passwords do not match.')
               .show();
            return;
        }

        $btn.prop('disabled', true).text('Resetting...');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/reset-password.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                token: token,
                password: password,
                confirm_password: confirmPassword
            }),
            dataType: 'json',
            success: function(response) {
                $msg.removeClass('error-message success-message')
                   .addClass(response.success ? 'success-message' : 'error-message')
                   .text(response.message)
                   .show();

                if (response.success) {
                    $('#resetPasswordForm').hide();
                    setTimeout(function() {
                        window.location.href = baseUrl + 'index.php';
                    }, 2000);
                }

                $btn.prop('disabled', false).text('Reset Password');
            },
            error: function() {
                $msg.removeClass('success-message')
                   .addClass('error-message')
                   .text('Something went wrong.')
                   .show();
                $btn.prop('disabled', false).text('Reset Password');
            }
        });
    });
});