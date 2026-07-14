/**
 * ConsuTrade - Core Utilities
 * Pure functions - depends on baseUrl global, no jQuery needed
 */

/**
 * Translate text in JavaScript
 * Usage: t('home') or t('add_to_cart')
 * 
 * If translation not found, returns the key itself
 */
function t(key) {
    if (typeof translations !== 'undefined' && translations && translations[key]) {
        return translations[key];
    }
    return key;
}

/**
 * Escapes user input to prevent XSS attacks
 */
function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

/**
 * Capitalizes the first letter of a string
 */
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Maps database status values to CSS classes
 */
function getOrderStatusClass(status) {
    if (status == 'pending') return 'status-pending';
    if (status == 'processing') return 'status-processing';
    if (status == 'shipped') return 'status-shipped';
    if (status == 'completed') return 'status-completed';
    if (status == 'cancelled') return 'status-cancelled';
    return '';
}

/**
 * Human-readable status labels
 */
function getStatusLabel(status) {
    if (status == 'pending') return 'Pending';
    if (status == 'processing') return 'Processing';
    if (status == 'shipped') return 'Shipped';
    if (status == 'completed') return 'Completed';
    if (status == 'cancelled') return 'Cancelled';
    return capitalizeFirst(status);
}

/**
 * Fixes mixed absolute/relative image paths
 */
function fixImageUrl(url, defaultPath) {
    defaultPath = defaultPath || 'images/default-product.png';
    if (!url || url == '') return baseUrl + defaultPath;
    if (url.startsWith('http://') || url.startsWith('https://')) return url;

    var cleanUrl = url.startsWith('/') ? url.substring(1) : url;

    if (cleanUrl.startsWith('uploads/') || cleanUrl.startsWith('images/')) {
        return baseUrl + cleanUrl;
    }

    var uploadsIndex = cleanUrl.indexOf('uploads/');
    if (uploadsIndex !== -1) return baseUrl + cleanUrl.substring(uploadsIndex);

    var imagesIndex = cleanUrl.indexOf('images/');
    if (imagesIndex !== -1) return baseUrl + cleanUrl.substring(imagesIndex);

    if (!cleanUrl.includes('/')) return baseUrl + 'uploads/products/' + cleanUrl;

    return baseUrl + defaultPath;
}

/**
 * Generates empty state HTML
 */
function getEmptyStateHTML(icon, title, message, buttonText, buttonLink) {
    var html = '<div class="empty-state">' +
        '<img src="' + baseUrl + 'images/icons/' + icon + '" width="64" height="64" alt="' + escapeHtml(title) + '">' +
        '<h3>' + escapeHtml(title) + '</h3>' +
        '<p>' + escapeHtml(message) + '</p>';

    if (buttonText && buttonLink) {
        html += '<a href="' + buttonLink + '" class="view-all-btn">' + escapeHtml(buttonText) + '</a>';
    }

    html += '</div>';
    return html;
}