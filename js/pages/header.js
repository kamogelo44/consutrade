/**
 * ConsuTrade - Header Behavior
 * Sticky scroll with hide/show, search suggestions, notification toggle
 * Depends on: jQuery, utils.js, ui.js
 */

$(function() {
    // ============================================================
    // STICKY HEADER WITH SCROLL EFFECT
    // ============================================================
    
    var $header = $('.site-header');
    var lastScrollTop = 0;
    var scrollThreshold = 80;
    var isHidden = false;
    var scrollTimeout = null;
    
    function handleScroll() {
        var currentScroll = $(window).scrollTop();
        var windowHeight = $(window).height();
        var documentHeight = $(document).height();
        
        // Don't hide at bottom of page
        if (currentScroll + windowHeight >= documentHeight - 50) {
            if (isHidden) {
                $header.removeClass('hidden');
                isHidden = false;
            }
            return;
        }
        
        // Add scrolled class for shadow
        if (currentScroll > scrollThreshold) {
            $header.addClass('scrolled');
        } else {
            $header.removeClass('scrolled');
        }
        
        // Hide/show on scroll direction
        if (currentScroll > lastScrollTop && currentScroll > scrollThreshold) {
            // Scrolling down - hide header
            if (!isHidden) {
                $header.addClass('hidden');
                isHidden = true;
            }
        } else if (currentScroll < lastScrollTop) {
            // Scrolling up - show header
            if (isHidden) {
                $header.removeClass('hidden');
                isHidden = false;
            }
        }
        
        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    }
    
    // Throttled scroll handler using requestAnimationFrame
    $(window).on('scroll', function() {
        if (scrollTimeout) {
            window.cancelAnimationFrame(scrollTimeout);
        }
        scrollTimeout = window.requestAnimationFrame(handleScroll);
    });
    
    // Show header on mouse move near top of page
    $(document).on('mousemove', function(e) {
        if (e.clientY < 50 && isHidden) {
            $header.removeClass('hidden');
            isHidden = false;
        }
    });
    
    // Show header on touch start near top (mobile)
    $(document).on('touchstart', function(e) {
        var touch = e.originalEvent.touches[0];
        if (touch.clientY < 60 && isHidden) {
            $header.removeClass('hidden');
            isHidden = false;
        }
    });
    
    // ============================================================
    // SEARCH SUGGESTIONS
    // ============================================================
    
    var $searchWrapper = $('.search-wrapper');
    var $searchInput = $searchWrapper.find('input[type="search"]');
    var $suggestions = $('<div class="search-suggestions"></div>');
    var searchTimeout = null;
    
    if ($searchInput.length) {
        // Insert suggestions container after the form
        $searchWrapper.find('form').append($suggestions);
        
        $searchInput.on('input', function() {
            var query = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                $suggestions.removeClass('active').empty();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: baseUrl + 'php/endpoints/products/search-suggestions.php',
                    type: 'GET',
                    data: { q: query },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.suggestions && data.suggestions.length > 0) {
                            var html = '';
                            data.suggestions.forEach(function(item) {
                                var image = fixImageUrl(item.image) || baseUrl + 'images/default-product.png';
                                var price = parseFloat(item.price || 0).toFixed(2);
                                html += '<a href="' + baseUrl + 'product-details.php?id=' + item.id + '" class="search-suggestion-item">' +
                                            '<img src="' + image + '" alt="' + escapeHtml(item.name) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                                            '<div>' +
                                                '<strong>' + escapeHtml(item.name) + '</strong>' +
                                                '<span style="display:block;font-size:var(--font-xs);color:var(--gray-light);">R ' + price + '</span>' +
                                            '</div>' +
                                        '</a>';
                            });
                            $suggestions.html(html).addClass('active');
                        } else {
                            $suggestions.removeClass('active').empty();
                        }
                    },
                    error: function() {
                        $suggestions.removeClass('active').empty();
                    }
                });
            }, 300);
        });
        
        // Close suggestions on Escape key
        $searchInput.on('keydown', function(e) {
            if (e.key === 'Escape') {
                $suggestions.removeClass('active').empty();
                $(this).blur();
            }
        });
        
        // Close suggestions on click outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-wrapper').length) {
                $suggestions.removeClass('active').empty();
            }
        });
        
        // Close suggestions on form submit
        $searchWrapper.find('form').on('submit', function() {
            $suggestions.removeClass('active').empty();
        });
    }
    
    // ============================================================
    // LANGUAGE DROPDOWN - Close on outside click
    // ============================================================
    
    var $langBtn = $('#languageBtn');
    var $langMenu = $('#languageMenu');
    
    if ($langBtn.length && $langMenu.length) {
        $langBtn.on('click', function(e) {
            e.stopPropagation();
            $langMenu.toggleClass('active');
        });
        
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.language-dropdown').length) {
                $langMenu.removeClass('active');
            }
        });
    }
    
    // ============================================================
    // ACCOUNT DROPDOWN - Close on outside click
    // ============================================================
    
    var $accountBtn = $('#accountBtn');
    var $accountDropdown = $('#accountDropdown');
    
    if ($accountBtn.length && $accountDropdown.length) {
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.account-dropdown').length) {
                $accountDropdown.removeClass('active');
            }
        });
    }
    
    // ============================================================
    // MOBILE MENU - Close on resize to desktop
    // ============================================================
    
    var $mobileMenu = $('#mobileMenu');
    var $menuToggle = $('#menuToggle');
    var $menuOverlay = $('#menuOverlay');
    
    function closeMobileMenu() {
        $mobileMenu.removeClass('active');
        $menuToggle.removeClass('active');
        $menuOverlay.removeClass('active');
        $('body').css('overflow', '');
    }
    
    $(window).on('resize', function() {
        if ($(window).width() > 768 && $mobileMenu.hasClass('active')) {
            closeMobileMenu();
        }
    });
    
    // ============================================================
    // MOBILE SEARCH - Close on outside click
    // ============================================================
    
    var $mobileSearchContainer = $('#mobileSearch');
    
    $(document).on('click', function(e) {
        if ($mobileSearchContainer.length && $mobileSearchContainer.hasClass('active')) {
            if (!$(e.target).closest('#mobileSearchIcon').length && 
                !$(e.target).closest('#mobileSearch').length) {
                $mobileSearchContainer.removeClass('active');
            }
        }
    });
    
    // ============================================================
    // ACTIVE LINK - Highlight current page
    // ============================================================
    
    function updateActiveLink() {
        var path = window.location.pathname;
        var currentPage = path.substring(path.lastIndexOf('/') + 1) || 'index.php';
        var currentPageBase = currentPage.split('?')[0];
        
        $('.main-nav a, .mobile-nav-links a').each(function() {
            var $link = $(this);
            var href = $link.attr('href');
            if (!href) return;
            
            var hrefPage = href.substring(href.lastIndexOf('/') + 1);
            if (hrefPage.indexOf('?') !== -1) {
                hrefPage = hrefPage.substring(0, hrefPage.indexOf('?'));
            }
            
            $link.removeClass('active');
            if (hrefPage === currentPageBase) {
                $link.addClass('active');
            }
        });
    }
    
    updateActiveLink();
    
    // ============================================================
    // NOTIFICATION BELL (prep for future)
    // ============================================================
    
    var $notifBtn = $('#notifBtn');
    var $notifMenu = $('#notifMenu');
    
    if ($notifBtn.length && $notifMenu.length) {
        $notifBtn.on('click', function(e) {
            e.stopPropagation();
            $notifMenu.toggleClass('active');
            
            // Load notifications if opening
            if ($notifMenu.hasClass('active') && isLoggedIn) {
                loadNotifications();
            }
        });
        
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.notifications-dropdown').length) {
                $notifMenu.removeClass('active');
            }
        });
    }
    
    // ============================================================
    // CART BADGE - Initial load from session
    // ============================================================
    
    // If user is logged in, cart count is handled by cart.js
    // If not logged in, ensure badge shows 0
    if (!isLoggedIn) {
        $('.cart-badge').text('0');
    }
});

/**
 * Load notifications - placeholder for future implementation
 * This function is called from the notification bell click
 */
function loadNotifications() {
    // This will be implemented when notification system is ready
    var $menu = $('#notifMenu');
    var $list = $menu.find('.notif-list');
    
    if (!$list.length) {
        $menu.html('<div class="notif-empty">No notifications yet</div>');
        return;
    }
    
    // Show loading state
    $list.html('<div class="notif-empty">Loading...</div>');
    
    // TODO: Implement actual notification fetching
    // For now, show empty state
    setTimeout(function() {
        $list.html('<div class="notif-empty">No notifications yet</div>');
    }, 500);
}