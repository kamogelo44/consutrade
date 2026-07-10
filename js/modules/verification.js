/**
 * ConsuTrade - Verification Component JS
 */
$(function() {
    var $dropZone = $('#dropZone');
    var $fileNameDisplay = $('#fileNameDisplay');
    var $verificationDoc = $('#verificationDoc');
    var $verificationForm = $('#verificationForm');
    var $verificationMessage = $('#verificationMessage');

    $('#resendVerificationBtn').on('click', function() {
        var $btn = $(this);
        var $msg = $('#resendMessage');
        $btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/resend-verification.php',
            type: 'POST',
            data: { email: $btn.data('email') },
            dataType: 'json',
            success: function(res) {
                $msg.text(res.message).css('color', res.success ? 'var(--success)' : 'var(--error)').show();
                if (res.success) $btn.hide();
                else $btn.prop('disabled', false).text('Resend Verification Email');
            },
            error: function() {
                $msg.text('Something went wrong.').css('color', 'var(--error)').show();
                $btn.prop('disabled', false).text('Resend Verification Email');
            }
        });
    });

    if ($dropZone.length && $verificationDoc.length) {
        $dropZone.on('click', function() { $verificationDoc.click(); });

        $verificationDoc.on('change', function(e) {
            var file = this.files[0];
            if (file) {
                $fileNameDisplay.text('Document: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
                $dropZone.css({ borderColor: 'var(--success)', background: 'var(--success-light)' });
                setTimeout(function() { $verificationForm.submit(); }, 500);
            }
        });

        $dropZone.on('dragover', function(e) {
            e.preventDefault();
            $(this).css({ borderColor: 'var(--primary-color)', background: 'var(--primary-fade)' });
        });

        $dropZone.on('dragleave', function(e) {
            e.preventDefault();
            $(this).css({ borderColor: 'var(--border-light)', background: 'transparent' });
        });

        $dropZone.on('drop', function(e) {
            e.preventDefault();
            $(this).css({ borderColor: 'var(--border-light)', background: 'transparent' });
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                $verificationDoc[0].files = files;
                $verificationDoc.trigger('change');
            }
        });
    }

    if ($verificationForm.length) {
        $verificationForm.on('submit', function(e) {
            e.preventDefault();
            var file = $verificationDoc[0].files[0];
            if (!file) { showMessage('Please select a document.', true); return; }

            var formData = new FormData(this);
            var $btn = $(this).find('.upload-btn');
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Uploading...');

            $.ajax({
                url: baseUrl + 'php/endpoints/users/upload-verification.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    if (data.success) { showMessage(data.message, false); setTimeout(function() { location.reload(); }, 2000); }
                    else { showMessage(data.message, true); }
                },
                error: function() { showMessage('Could not upload document.', true); },
                complete: function() { $btn.prop('disabled', false).text(originalText); }
            });
        });
    }

    window.deleteDocument = function() {
        if (confirm('Delete your verification document?')) {
            $.ajax({
                url: baseUrl + 'php/endpoints/users/delete-verification.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({}),
                dataType: 'json',
                success: function(data) {
                    if (data.success) { showMessage('Document deleted.', false); setTimeout(function() { location.reload(); }, 1500); }
                    else { showMessage(data.message, true); }
                },
                error: function() { showMessage('Could not delete document.', true); }
            });
        }
    };

    $(document).on('change', '#replaceDocInput', function() {
        var file = this.files[0];
        if (file) {
            var formData = new FormData();
            formData.append('document', file);
            formData.append('document_type', $('#documentType').val() || 'id');

            $.ajax({
                url: baseUrl + 'php/endpoints/users/upload-verification.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    if (data.success) { showMessage('Document replaced.', false); setTimeout(function() { location.reload(); }, 1500); }
                    else { showMessage(data.message, true); }
                },
                error: function() { showMessage('Could not replace document.', true); }
            });
        }
    });

    function showMessage(message, isError) {
        if (!$verificationMessage.length) return;
        $verificationMessage.removeClass('success error').addClass(isError ? 'error' : 'success').text(message).show();
        setTimeout(function() { $verificationMessage.fadeOut(500, function() { $(this).removeClass('success error').hide().text(''); }); }, 5000);
    }
});