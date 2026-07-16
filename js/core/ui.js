/**
 * ConsuTrade - Core UI
 * Toast notifications, modals, pagination, DOM cache
 * Depends on: jQuery, utils.js
 */

// ============================================================
// DOM CACHE
// ============================================================

var $toastContainer = null;
var $registerModal = null;
var $loginModal = null;
var $deleteModal = null;
var $cartCountElements = null;

// ============================================================
// BANNER CONTROLS
// ============================================================

/**
 * Initialize banner close functionality with localStorage persistence
 * Used on product listings pages
 */
function initBannerControls() {
    var $banner = $('#listingsBanner');
    var $restoreWrapper = $('#bannerRestoreWrapper');
    var $closeBtn = $('#bannerCloseBtn');
    var $restoreBtn = $('#bannerRestoreBtn');
    var BANNER_HIDDEN_KEY = 'consutrade_banner_hidden';
    
    if (!$banner.length) return;
    
    var isHidden = localStorage.getItem(BANNER_HIDDEN_KEY) === 'true';
    
    if (isHidden) {
        $banner.addClass('hidden');
        // Show the restore wrapper by removing inline style and adding class
        $restoreWrapper.css('display', '');
        $restoreWrapper.addClass('visible');
    }
    
    $closeBtn.on('click', function() {
        $banner.addClass('hidden');
        // Show the restore wrapper
        $restoreWrapper.css('display', '');
        $restoreWrapper.addClass('visible');
        localStorage.setItem(BANNER_HIDDEN_KEY, 'true');
    });
    
    $restoreBtn.on('click', function() {
        $banner.removeClass('hidden');
        // Hide the restore wrapper
        $restoreWrapper.removeClass('visible');
        $restoreWrapper.css('display', 'none');
        localStorage.removeItem(BANNER_HIDDEN_KEY);
    });
}

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================

function showToast(message, type) {
    type = type || 'success';

    $('.toast-notification').remove();

    if (!$toastContainer) {
        $toastContainer = $('<div class="toast-container"></div>');
        $('body').append($toastContainer);
    }

    var iconMap = {
        'success': 'verified-svgrepo-com.svg',
        'error': 'not-verified-svgrepo-com.svg',
        'warning': 'warning-svgrepo-com.svg',
        'info': 'info-svgrepo-com.svg'
    };
    var iconFile = iconMap[type] || 'info-svgrepo-com.svg';
    var iconPath = baseUrl + 'images/icons/' + iconFile;

    var toast = $(
        '<div class="toast-notification toast-' + type + '">' +
            '<span class="toast-icon"><img src="' + iconPath + '" alt="' + type + '"></span>' +
            '<span class="toast-message">' + escapeHtml(message) + '</span>' +
        '</div>'
    );

    $toastContainer.append(toast);

    toast.on('click', function() {
        dismissToast(toast);
    });

    var timeoutId = setTimeout(function() {
        dismissToast(toast);
    }, 4000);

    toast.data('timeoutId', timeoutId);
}

function dismissToast(toast) {
    var timeoutId = toast.data('timeoutId');
    if (timeoutId) clearTimeout(timeoutId);

    toast.addClass('hiding');
    setTimeout(function() {
        toast.remove();
        if ($toastContainer && $toastContainer.children().length === 0) {
            $toastContainer.remove();
            $toastContainer = null;
        }
    }, 300);
}

function showSuccessToast(message) { showToast(message, 'success'); }
function showErrorToast(message) { showToast(message, 'error'); }
function showInfoToast(message) { showToast(message, 'info'); }
function showWarningToast(message) { showToast(message, 'warning'); }

// ============================================================
// MODAL CONTROLS
// ============================================================

function openModal($modal) {
    if (!$modal.length) return;

    clearModalErrors($modal.attr('id'));

    var $content = $modal.find('.modal-content');
    $modal.find('.error-container').hide().empty();
    $modal.find('.input-group').removeClass('error');
    $modal.find('.error-text').remove();
    $content.removeClass('animate-in animate-out');

    $modal.css('visibility', 'visible');
    $modal.addClass('active');

    $modal[0].offsetHeight;
    $content.addClass('animate-in');
    $('body').css('overflow', 'hidden');

    setTimeout(function() { $content.removeClass('animate-in'); }, 350);
}

function closeModal($modal) {
    if (!$modal.length) return;

    var $content = $modal.find('.modal-content');
    $modal.find('.error-container').hide().empty();
    $modal.find('.input-group').removeClass('error');
    $modal.find('.error-text').remove();
    $content.removeClass('animate-in');
    $modal[0].offsetHeight;
    $content.addClass('animate-out');

    setTimeout(function() {
        $modal.removeClass('active');
        $modal.css('visibility', 'hidden');
        $content.removeClass('animate-out');
        $('body').css('overflow', '');
    }, 280);
}

function clearModalErrors(modalId) {
    var $modal = $(modalId);
    $modal.find('.error-container').hide().empty();
    $modal.find('.input-group').removeClass('error');
    $modal.find('.error-text').remove();
}

function displayModalErrors(modalId, errors, formData) {
    var $modal = $(modalId);
    if (!formData) formData = {};

    if (modalId == '#register-modal') {
        if (formData.full_name) $('#register-full-name').val(formData.full_name);
        if (formData.email) $('#register-email').val(formData.email);
        if (formData.phone) $('#register-phone').val(formData.phone);

        $('#register-error-container').hide().empty();
        $('.input-group', $modal).removeClass('error');
        $('.error-text', $modal).remove();

        var errorMessages = [];
        if (errors.general && errors.general.trim()) {
            errorMessages.push(errors.general);
        }

        for (var field in errors) {
            var message = errors[field];
            if (field != 'general' && message && message.trim()) {
                errorMessages.push(message);

                var inputId = 'register-' + field;
                var $input = $('#' + inputId);
                if ($input.length) {
                    $input.closest('.input-group').addClass('error');
                }
            }
        }

        if (errorMessages.length > 0) {
            $('#register-error-container').show().html(errorMessages.join('<br>'));
        }

    } else if (modalId == '#login-modal') {
        if (formData.email) $('#login-email').val(formData.email);

        $('#login-error-container').hide().empty();
        $('.input-group', $modal).removeClass('error');

        if (errors.general && errors.general.trim()) {
            $('#login-error-container').show().text(errors.general);
        } else if (typeof errors == 'string' && errors.trim()) {
            $('#login-error-container').show().text(errors);
        }

        for (var field2 in errors) {
            var msg = errors[field2];
            if (field2 != 'general' && msg && msg.trim()) {
                var $input2 = $('#login-' + field2);
                if ($input2.length) {
                    $input2.closest('.input-group').addClass('error');
                }
            }
        }
    }
}

// ============================================================
// PAGINATION
// ============================================================

function renderPagination($container, currentPage, totalPages, onPageChange) {
    if (!$container.length || totalPages <= 1) {
        $container.empty();
        return;
    }

    var html = '';

    if (currentPage > 1) {
        html += '<button class="page-btn" data-page="' + (currentPage - 1) + '">← ' + t('previous') + '</button>';
    }

    for (var i = 1; i <= totalPages; i++) {
        if (i == currentPage) {
            html += '<button class="page-btn active" disabled>' + i + '</button>';
        } else if (Math.abs(i - currentPage) <= 2 || i == 1 || i == totalPages) {
            html += '<button class="page-btn" data-page="' + i + '">' + i + '</button>';
        } else if (Math.abs(i - currentPage) == 3) {
            html += '<span class="page-dots">...</span>';
        }
    }

    if (currentPage < totalPages) {
        html += '<button class="page-btn" data-page="' + (currentPage + 1) + '">' + t('next') + ' →</button>'; 
    }

    $container.html(html);

    $container.find('.page-btn[data-page]').off('click').on('click', function() {
        var page = parseInt($(this).data('page'));
        if (!isNaN(page) && typeof onPageChange == 'function') {
            onPageChange(page);
        }
    });
}

// ============================================================
// ERROR CLEARING ON INPUT
// ============================================================

/**
 * Initialize error clearing on input focus and keypress.
 * Removes error styling when user starts typing.
 */
function initErrorClearingOnInput() {
    // Clear error state on input focus
    $(document).on('focus', '.input-group.error input', function() {
        var $group = $(this).closest('.input-group');
        $group.removeClass('error');
        $group.find('.error-text').remove();
    });

    // Clear error state on input keypress
    $(document).on('input', '.input-group.error input', function() {
        var $group = $(this).closest('.input-group');
        $group.removeClass('error');
        $group.find('.error-text').remove();
    });
}

// ============================================================
// LISTINGS page
// ============================================================

var FILTERS_HIDDEN_KEY = 'consutrade_filters_hidden';
var LOCATION_DEBOUNCE_MS = 400;

/**
 * Initialize collapsible filter sidebar (desktop only)
 * State persisted in localStorage
 */
/**
 * Initialize collapsible filter sidebar with morph transition
 * State persisted in localStorage
 * The sidebar morphs from full width to a thin bar instead of disappearing
 */
function initFilterCollapse() {
    var $sidebar = $('#filterSidebar');
    var $toggle = $('#filterToggleBtn');
    if (!$sidebar.length || !$toggle.length) return;

    var $label = $toggle.find('.filter-toggle-label');
    var $arrow = $toggle.find('.filter-arrow');

    function apply(hidden) {
        // Toggle collapsed class on sidebar (morphs the whole sidebar)
        $sidebar.toggleClass('collapsed', hidden);
        
        // Update button text and arrow
        if ($label.length) {
            $label.text(hidden ? 'Show' : 'Hide');
        }
        if ($arrow.length) {
            $arrow.text(hidden ? '→' : '←');
        }
        
        // Update aria-expanded
        $toggle.attr('aria-expanded', String(!hidden));
    }

    // Restore persisted state
    var startHidden = false;
    try {
        startHidden = localStorage.getItem(FILTERS_HIDDEN_KEY) === 'true';
    } catch (e) { /* storage unavailable */ }
    apply(startHidden);

    // Toggle on click
    $toggle.on('click', function() {
        var hidden = !$sidebar.hasClass('collapsed');
        apply(hidden);
        try {
            localStorage.setItem(FILTERS_HIDDEN_KEY, hidden ? 'true' : 'false');
        } catch (e) { /* ignore */ }
    });
}

/**
 * Initialize instant filtering (no Apply button needed)
 * Depends on products.js globals: loadProducts, collectFilters, currentPage
 */
function initInstantFiltering() {
    // Guard: products.js globals must be present
    if (typeof window.loadProducts !== 'function' || typeof collectFilters !== 'function') {
        return;
    }

    function applyNow() {
        collectFilters();
        currentPage = 1;
        initialCategory = '';
        window.loadProducts();

        // On mobile, close filter overlay
        if (window.matchMedia('(max-width: 768px)').matches) {
            $('#filterSidebar').removeClass('active');
        }
    }

    // Checkboxes + radios: react immediately
    $(document).on('change',
        'input[name="category[]"], input[name="price_range"]',
        applyNow
    );

    // Location text: debounce
    var locTimer = null;
    $(document).on('input', '#search-location', function() {
        clearTimeout(locTimer);
        locTimer = setTimeout(applyNow, LOCATION_DEBOUNCE_MS);
    });

    // Clear all inline (inside sidebar)
    $('#clearFiltersInline').on('click', function() {
        $('#clearFiltersBtn').trigger('click');
    });
}

/**
 * Initialize live product count
 */
function initLiveCount() {
    var grid = document.getElementById('products-grid');
    var countEl = document.getElementById('listingsCount');
    if (!grid || !countEl) return;

    function update() {
        var cards = grid.querySelectorAll('.prod-card:not(.skeleton-card)');
        var hasSkeleton = grid.querySelector('.skeleton-card');
        var hasEmptyState = grid.querySelector('.empty-state');

        if (hasSkeleton && cards.length === 0) {
            countEl.textContent = 'Loading products…';
            return;
        }
        if (hasEmptyState || cards.length === 0) {
            countEl.textContent = 'No products match your filters';
            return;
        }

        var n = cards.length;
        countEl.textContent = 'Showing ' + n + ' product' + (n === 1 ? '' : 's');
    }

    var observer = new MutationObserver(update);
    observer.observe(grid, { childList: true });
    update();
}