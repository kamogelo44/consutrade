/**
 * ConsuTrade - Profile Page Init
 */
$(function() {
    var $deleteModal = $('#delete-modal');
    var $changePasswordModal = $('#change-password-modal');
    var $deletePassword = $('#delete-password');
    var $currentPassword = $('#current_password');
    var $newPassword = $('#new_password');
    var $confirmPassword = $('#confirm_password');
    var $profileAvatar = $('#profile-avatar');
    var $profileImageUpload = $('#profile-image-upload');
    var $avatarUploadBtn = $('.avatar-upload-btn');
    var $fullName = $('#full_name');
    var $phone = $('#phone');
    var $location = $('#location');

    // ============================================================
    // MODAL CONTROLS
    // ============================================================

    window.showDeleteModal = function() {
        $deleteModal.addClass('active');
    };

    window.closeDeleteModal = function() {
        $deleteModal.removeClass('active');
        $deletePassword.val('');
    };

    window.openChangePasswordModal = function() {
        $changePasswordModal.addClass('active');
        $currentPassword.val('');
        $newPassword.val('');
        $confirmPassword.val('');
    };

    window.closeChangePasswordModal = function() {
        $changePasswordModal.removeClass('active');
    };

    // ============================================================
    // PASSWORD TOGGLE
    // ============================================================

    window.togglePassword = function(fieldId, button) {
        var $input = $('#' + fieldId);
        var $img = $(button).find('img');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $img.attr('src', baseUrl + 'images/icons/eye-close-svgrepo-com.svg');
        } else {
            $input.attr('type', 'password');
            $img.attr('src', baseUrl + 'images/icons/eye-open-svgrepo-com.svg');
        }
    };

    // ============================================================
    // TAB SWITCHING
    // ============================================================

    function switchTab(tab) {
        if (history.pushState) history.pushState(null, null, '#' + tab);
        sessionStorage.setItem('activeProfileTab', tab);

        $('.profile-tab').removeClass('active');
        $('.profile-tab[data-tab="' + tab + '"]').addClass('active');
        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    }

    function activateTab() {
        var validTabs = ['buyer', 'seller', 'admin'];
        var tab = null;
        var hash = window.location.hash.replace('#', '');

        if (hash && validTabs.includes(hash)) tab = hash;
        if (!tab) tab = sessionStorage.getItem('activeProfileTab');
        if (tab && $('.profile-tab[data-tab="' + tab + '"]').length) {
            switchTab(tab);
            return;
        }
        if ($('.profile-tab[data-tab="' + profileDefaultTab + '"]').length) {
            switchTab(profileDefaultTab);
        }
    }

    $(document).on('click', '.profile-tab', function() {
        switchTab($(this).data('tab'));
    });

    $(window).on('hashchange', function() {
        var hash = window.location.hash.replace('#', '');
        if (hash) switchTab(hash);
    });

    activateTab();

    // ============================================================
    // LOAD STATS
    // ============================================================

function loadUserSstats() {
    // Load seller stats
    if ($('#stat-products').length) {
        $.ajax({
            url: baseUrl + 'php/endpoints/users/get-user-stats.php?user_id=' + profileUserId + '&role=seller',
            type: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(data) {
                if (data.success) {
                    $('#stat-products').text(data.total_products || 0);
                    $('#stat-sales').text(data.completed_orders || 0);
                    $('#stat-revenue').text('R ' + (data.total_revenue || 0).toFixed(2));
                    $('#stat-rating').text(data.avg_rating ? data.avg_rating.toFixed(1) + '/5' : 'No reviews yet');
                }
            },
            error: function() {
                $('.stat-value.highlight').text('-');
            }
        });
    }

    // Load buyer stats
    if ($('#stat-orders').length) {
        $.ajax({
            url: baseUrl + 'php/endpoints/users/get-user-stats.php?user_id=' + profileUserId + '&role=buyer',
            type: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(data) {
                if (data.success) {
                    $('#stat-orders').text(data.total_orders || 0);
                    $('#stat-spent').text('R ' + (data.total_spent || 0).toFixed(2));
                    if (data.reviews_written > 0) {
                        $('#stat-reviews').text(data.reviews_written);
                        $('#reviews-row').show();
                    }
                }
            },
            error: function() {
                $('#stat-orders').text('-');
                $('#stat-spent').text('-');
            }
        });
    }
}

    loadUserStats();

    // ============================================================
    // PROFILE IMAGE UPLOAD
    // ============================================================

    $profileImageUpload.on('change', function() {
        var file = this.files[0];
        if (!file) return;

        var validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showErrorToast('Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.');
            this.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showErrorToast('File is too large. Maximum size is 2MB.');
            this.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) { $profileAvatar.attr('src', e.target.result); };
        reader.readAsDataURL(file);

        var formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('profile_image', file);

        $avatarUploadBtn.html('<span style="font-size:14px;color:white;">...</span>');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/update-profile.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 30000,
            success: function(data) {
                $avatarUploadBtn.html('<img src="' + baseUrl + 'images/icons/camera-svgrepo-com.svg" alt="Upload">');
                if (data.success) showSuccessToast(data.message);
                else showErrorToast(data.message || 'Failed to upload image.');
            },
            error: function() {
                $avatarUploadBtn.html('<img src="' + baseUrl + 'images/icons/camera-svgrepo-com.svg" alt="Upload">');
                showErrorToast('Error uploading image.');
            }
        });
    });

    $avatarUploadBtn.on('click', function(e) {
        e.preventDefault();
        $profileImageUpload.click();
    });

    // ============================================================
    // EDIT PROFILE
    // ============================================================

    $('#profile-edit-form').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'update_profile');

        var $submitBtn = $(this).find('.save-btn');
        var originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/update-profile.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 15000,
            success: function(data) {
                $submitBtn.prop('disabled', false).text(originalText);
                if (data.success) {
                    showSuccessToast(data.message);
                    $('.profile-user-info h1').text($fullName.val());
                } else {
                    showErrorToast(data.message || 'Failed to update profile.');
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                showErrorToast('Error updating profile.');
            }
        });
    });

    // ============================================================
    // CHANGE PASSWORD
    // ============================================================

    $('#change-password-form').on('submit', function(e) {
        e.preventDefault();

        var newPassword = $newPassword.val();
        if (newPassword.length < 6) { showErrorToast('New password must be at least 6 characters.'); return; }
        if (newPassword !== $confirmPassword.val()) { showErrorToast('New passwords do not match.'); return; }

        var $submitBtn = $(this).find('.save-btn');
        var originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/change-password.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ current_password: $currentPassword.val(), new_password: newPassword }),
            dataType: 'json',
            timeout: 15000,
            success: function(data) {
                $submitBtn.prop('disabled', false).text(originalText);
                if (data.success) { showSuccessToast('Password changed!'); closeChangePasswordModal(); }
                else showErrorToast(data.message || 'Failed to change password.');
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).text(originalText);
                showErrorToast(xhr.status === 401 ? 'Current password is incorrect.' : 'Error changing password.');
            }
        });
    });

    // ============================================================
    // DELETE ACCOUNT
    // ============================================================

    $('#delete-account-form').on('submit', function(e) {
        e.preventDefault();

        var password = $deletePassword.val();
        if (!password) { showErrorToast('Please enter your password.'); return; }

        var $submitBtn = $(this).find('.delete-confirm-btn');
        $submitBtn.prop('disabled', true).text('Processing...');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/delete-account.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ password: password }),
            dataType: 'json',
            timeout: 15000,
            success: function(data) {
                if (data.success) {
                    showSuccessToast('Account deleted.');
                    setTimeout(function() { window.location.href = baseUrl; }, 2000);
                } else {
                    $submitBtn.prop('disabled', false).text('Confirm Delete');
                    showErrorToast(data.message || 'Failed to delete account.');
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).text('Confirm Delete');
                showErrorToast(xhr.status === 401 ? 'Invalid password.' : 'Error deleting account.');
            }
        });
    });
});