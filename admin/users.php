<?php
/*
 * ConsuTrade - Manage Users (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all users for admin management with verify/unverify functionality
 */

require_once dirname(__DIR__) . '/init.php';

// Check maintenance mode (one line!)
checkMaintenanceMode();

if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ConsuTrade Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <style>
        /* ========== PAGE-SPECIFIC STYLES ONLY ========== */

        /* Document Review Modal */
        #docModal .modal-content {
            max-width: 700px;
            border-radius: var(--radius-lg);
        }

        #docModal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-light);
        }

        #docModal .modal-header h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin: 0;
        }

        #docModal .modal-body {
            padding: var(--spacing-lg);
            max-height: 500px;
            overflow-y: auto;
        }

        #docModal .modal-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--border-light);
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .doc-preview-container {
            text-align: center;
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
        }

        .doc-preview-container img {
            max-width: 100%;
            max-height: 400px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-light);
        }

        .doc-preview-container iframe {
            width: 100%;
            height: 400px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
        }

        .doc-info {
            margin-top: var(--spacing-md);
            padding: var(--spacing-sm);
            background: var(--white);
            border-radius: var(--radius-sm);
            font-size: var(--font-sm);
        }

        .doc-info strong {
            color: var(--dark-bg);
        }

        .btn-verify {
            background: var(--success);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-bold);
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-verify:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
        }

        .btn-reject {
            background: var(--error);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-bold);
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-reject:hover {
            background: var(--error-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray-bg);
            color: var(--gray-dark);
            padding: 10px 24px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .btn-secondary:hover {
            background: var(--gray-lighter);
        }

        @media (max-width: 768px) {
            #docModal .modal-content {
                max-width: 100%;
                margin: 10px;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: var(--font-xl);
            }
        }
    </style>
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>Manage Users</h1>
                <p>View and manage all registered users on ConsuTrade.</p>
            </div>

            <!-- ========== USING COMPONENTS.CSS FILTERS BAR ========== -->
            <div class="filters-bar">
                <div class="status-filters">
                    <select id="roleFilter" class="filter-btn" style="appearance: auto; padding: 8px 16px;">
                        <option value="all">All Users</option>
                        <option value="buyer">Buyers</option>
                        <option value="seller">Sellers</option>
                        <option value="admin">Admins</option>
                        <option value="pending">Pending Verification</option>
                    </select>
                </div>

                <div class="search-bar">
                    <form id="searchForm" onsubmit="return false;">
                        <input type="text" id="searchInput" placeholder="Name or email...">
                        <button id="searchBtn" style="padding: 8px 12px; background: var(--primary-color); border: none; border-radius: var(--radius-md); cursor: pointer;">
                            <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search" style="width: 16px; height: 16px; filter: brightness(0) invert(1);">
                        </button>
                        <button id="resetBtn" class="filter-btn" style="background: var(--gray-bg); color: var(--gray-dark);">Reset</button>
                    </form>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Verified</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <tr>
                            <td colspan="9" class="loading-cell">Loading users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <!-- Document Review Modal -->
    <div id="docModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="docModalTitle">Review Verification Documents</h2>
                <button class="btn-close" onclick="closeDocModal()">&times;</button>
            </div>
            <div class="modal-body" id="docModalBody">
                <div id="docPreview">
                    <p class="loading-spinner">Loading document...</p>
                </div>
            </div>
            <div class="modal-footer" id="docModalFooter">
                <!-- Footer buttons are dynamically added -->
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // DOM CACHE - Store all jQuery selectors for performance
        // ============================================================

        /**
         * DOM element references for the manage users page.
         * All elements are cached once and reused throughout the page.
         */
        var $usersTable = null,
            $pagination = null,
            $roleFilter = null,
            $searchBtn = null,
            $resetBtn = null,
            $searchInput = null,
            $docModal = null,
            $docModalTitle = null,
            $docPreview = null,
            $docModalFooter = null,
            currentPage = 1,
            currentRole = 'all',
            currentSearch = '',
            currentReviewUserId = null;

        /**
         * Caches all DOM elements used on the manage users page.
         * Called once on page load to store jQuery references.
         */
        function cacheElements() {
            $usersTable = $('#usersTable');
            $pagination = $('#pagination');
            $roleFilter = $('#roleFilter');
            $searchBtn = $('#searchBtn');
            $resetBtn = $('#resetBtn');
            $searchInput = $('#searchInput');
            $docModal = $('#docModal');
            $docModalTitle = $('#docModalTitle');
            $docPreview = $('#docPreview');
            $docModalFooter = $('#docModalFooter');
        }

        // ============================================================
        // DOCUMENT READY
        // ============================================================

        $(document).ready(function() {
            cacheElements();
            loadUsers();

            $roleFilter.on('change', function() {
                currentRole = $(this).val();
                currentPage = 1;
                loadUsers();
            });

            $searchBtn.on('click', function() {
                currentSearch = $searchInput.val().trim();
                currentPage = 1;
                loadUsers();
            });

            $resetBtn.on('click', function() {
                $roleFilter.val('all');
                $searchInput.val('');
                currentRole = 'all';
                currentSearch = '';
                currentPage = 1;
                loadUsers();
            });

            $searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    currentSearch = $searchInput.val().trim();
                    currentPage = 1;
                    loadUsers();
                }
            });

            // Close modal when clicking outside
            $docModal.on('click', function(e) {
                if ($(e.target).is($docModal)) {
                    closeDocModal();
                }
            });

            // Close modal with Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $docModal.hasClass('active')) {
                    closeDocModal();
                }
            });
        });

        // ============================================================
        // LOAD USERS
        // ============================================================

        /**
         * Fetches users from the server with current filters and pagination.
         * Updates the table with the response data.
         */
        function loadUsers() {
            $usersTable.html('<tr><td colspan="9" class="loading-cell">Loading users...</td></tr>');

            $.ajax({
                url: baseUrl + 'php/endpoints/users/get-users.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    page: currentPage,
                    role: currentRole,
                    search: currentSearch
                },
                success: function(data) {
                    if (data.success && data.users && data.users.length) {
                        displayUsers(data.users);
                        if (typeof renderPagination === 'function') {
                            renderPagination($pagination, currentPage, data.total_pages, function(page) {
                                currentPage = page;
                                loadUsers();
                                $('html, body').animate({
                                    scrollTop: 0
                                }, 'smooth');
                            });
                        } else {
                            $pagination.empty();
                        }
                    } else {
                        showEmptyState();
                        $pagination.empty();
                    }
                },
                error: function() {
                    $usersTable.html('<tr><td colspan="9" class="error-cell">Error loading users. Please refresh the page.</td></tr>');
                }
            });
        }

        // ============================================================
        // DISPLAY USERS
        // ============================================================

        /**
         * Renders user data in the table with role-specific action buttons.
         *
         * @param {Array} users - Array of user objects from the server
         */
        function displayUsers(users) {
            $usersTable.empty();

            $.each(users, function(i, user) {
                var roleClass = user.role === 'admin' ? 'role-admin' : (user.role === 'seller' ? 'role-seller' : 'role-buyer');
                var verifiedBadge = '';
                if (user.id_verified) {
                    verifiedBadge = '<span class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"> Verified</span>';
                } else if (user.has_document) {
                    verifiedBadge = '<span class="unverified-badge-card" style="background: var(--info-light); color: var(--info); border-color: var(--info);"><img src="' + baseUrl + 'images/icons/clock-svgrepo-com.svg" width="14" height="14"> Pending</span>';
                } else {
                    verifiedBadge = '<span class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"> Not Verified</span>';
                }

                var statusBadge = '';
                if (user.status === 'active') {
                    statusBadge = '<span class="status-badge active">Active</span>';
                } else if (user.status === 'suspended') {
                    statusBadge = '<span class="status-badge suspended">Suspended</span>';
                } else if (user.status === 'banned') {
                    statusBadge = '<span class="status-badge banned">Banned</span>';
                } else {
                    statusBadge = '<span class="status-badge active">Active</span>';
                }

                var isCurrentAdmin = (user.role === 'admin' && user.user_id === currentUserId);

                var actionButtons = '<div class="action-buttons">';

                if (!isCurrentAdmin) {
                    if (user.status === 'active') {
                        actionButtons += '<button class="action-btn suspend-btn" onclick="updateUserStatus(' + user.user_id + ', \'suspended\')">Suspend</button>';
                        actionButtons += '<button class="action-btn ban-btn" onclick="updateUserStatus(' + user.user_id + ', \'banned\')">Ban</button>';
                    } else {
                        actionButtons += '<button class="action-btn activate-btn" onclick="updateUserStatus(' + user.user_id + ', \'active\')">Activate</button>';
                    }
                }

                // Seller verification actions based on state
                if (user.role === 'seller') {
                    if (user.has_document && !user.id_verified) {
                        actionButtons += '<button class="action-btn verify-btn" onclick="openReviewModal(' + user.user_id + ')">Review Docs</button>';
                    } else if (user.id_verified) {
                        actionButtons += '<button class="action-btn unverify-btn" onclick="toggleUserVerification(' + user.user_id + ', false)">Unverify</button>';
                    } else {
                        actionButtons += '<button class="action-btn verify-btn" onclick="openVerifyModal(' + user.user_id + ')">Verify Seller</button>';
                    }
                }

                if (user.role !== 'admin') {
                    actionButtons += '<button class="action-btn delete-btn" onclick="deleteUser(' + user.user_id + ')">Delete</button>';
                }

                actionButtons += '</div>';

                if (actionButtons === '<div class="action-buttons"></div>') {
                    actionButtons = '<div class="action-buttons">-</div>';
                }

                $usersTable.append(
                    '<tr>' +
                    '<td data-label="ID">' + user.user_id + '</td>' +
                    '<td data-label="Full Name">' + (typeof escapeHtml === 'function' ? escapeHtml(user.full_name) : user.full_name) + '</td>' +
                    '<td data-label="Email">' + (typeof escapeHtml === 'function' ? escapeHtml(user.email) : user.email) + '</td>' +
                    '<td data-label="Phone">' + (typeof escapeHtml === 'function' ? escapeHtml(user.phone || '-') : (user.phone || '-')) + '</td>' +
                    '<td data-label="Role"><span class="role-badge ' + roleClass + '">' + user.role + '</span></td>' +
                    '<td data-label="Verified">' + verifiedBadge + '</td>' +
                    '<td data-label="Status">' + statusBadge + '</td>' +
                    '<td data-label="Joined Date">' + user.created_at + '</td>' +
                    '<td data-label="Actions">' + actionButtons + '</td>' +
                    '</tr>'
                );
            });
        }

        // ============================================================
        // EMPTY STATE
        // ============================================================

        /**
         * Displays an empty state message when no users match the current filters.
         * Shows different messages based on the current role filter.
         */
        function showEmptyState() {
            var emptyTitle = '';
            var emptyMessage = '';

            if (currentRole === 'pending') {
                emptyTitle = 'No Pending Verifications';
                emptyMessage = 'All sellers have been verified. No pending verifications at this time.';
            } else if (currentRole === 'seller') {
                emptyTitle = 'No Sellers Found';
                emptyMessage = 'There are no registered sellers' + (currentSearch ? ' matching "' + escapeHtml(currentSearch) + '"' : '') + '.';
            } else if (currentRole === 'buyer') {
                emptyTitle = 'No Buyers Found';
                emptyMessage = 'There are no registered buyers' + (currentSearch ? ' matching "' + escapeHtml(currentSearch) + '"' : '') + '.';
            } else if (currentRole === 'admin') {
                emptyTitle = 'No Admins Found';
                emptyMessage = 'There are no admin accounts' + (currentSearch ? ' matching "' + escapeHtml(currentSearch) + '"' : '') + '.';
            } else {
                emptyTitle = 'No Users Found';
                emptyMessage = currentSearch ? 'No users found matching "' + escapeHtml(currentSearch) + '".' : 'No users are registered on the platform yet.';
            }

            var resetButtonHtml = '';
            if (currentSearch !== '' || currentRole !== 'all') {
                resetButtonHtml = '<button onclick="$resetBtn.click()" class="view-all-btn" style="background: var(--primary-color); color: white; padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; margin-top: 16px;">Clear Filters</button>';
            }

            $usersTable.html(
                '<tr><td colspan="9" style="text-align: center; padding: 60px;">' +
                '<div class="empty-state">' +
                '<img src="' + baseUrl + 'images/icons/users-svgrepo-com.svg" width="64" height="64" alt="No users" style="opacity: 0.4;">' +
                '<h3 style="font-size: 20px; font-weight: bold; margin-top: 16px; margin-bottom: 8px;">' + escapeHtml(emptyTitle) + '</h3>' +
                '<p style="color: var(--gray-medium);">' + escapeHtml(emptyMessage) + '</p>' +
                resetButtonHtml +
                '</div>' +
                '</td>' +
                '</tr>'
            );
        }

        // ============================================================
        // DOCUMENT REVIEW MODAL FUNCTIONS
        // ============================================================

        /**
         * Opens the document review modal for a seller with existing document.
         *
         * @param {number} userId - The seller's user ID
         */
        function openReviewModal(userId) {
            currentReviewUserId = userId;

            $docModalTitle.text('Review Verification Documents');
            $docPreview.html('<div class="loading-spinner">Loading document...</div>');
            $docModal.addClass('active');

            $.ajax({
                url: baseUrl + 'php/endpoints/users/get-verification-document.php',
                type: 'GET',
                data: {
                    user_id: userId
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success && data.has_document) {
                        var ext = data.document_path.split('.').pop().toLowerCase();
                        var docUrl = baseUrl + data.document_path;
                        var docType = data.document_type || 'Document';
                        var uploadedAt = data.uploaded_at || 'Unknown date';

                        var html = '<div class="doc-preview-container">';
                        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                            html += '<img src="' + docUrl + '" alt="Verification Document" onerror="this.onerror=null; showDocumentNotFound()">';
                        } else {
                            html += '<iframe src="' + docUrl + '" onerror="this.style.display=\'none\'; showDocumentNotFound()"></iframe>';
                        }
                        html += '</div>';
                        html += '<div class="doc-info">';
                        html += '<p><strong>Document Type:</strong> ' + capitalizeFirst(docType.replace(/_/g, ' ')) + '</p>';
                        html += '<p><strong>Uploaded:</strong> ' + uploadedAt + '</p>';
                        html += '</div>';

                        $docPreview.html(html);
                        $docModalFooter.html(
                            '<button class="btn-verify" onclick="verifySeller()">' +
                            '<img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="16" height="16" style="filter: brightness(0) invert(1); vertical-align: middle; margin-right: 6px;">' +
                            'Verify Seller' +
                            '</button>' +
                            '<button class="btn-reject" onclick="rejectSeller()">' +
                            '<img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="16" height="16" style="filter: brightness(0) invert(1); vertical-align: middle; margin-right: 6px;">' +
                            'Reject' +
                            '</button>'
                        );
                    } else {
                        showDocumentNotFound();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    showDocumentNotFound();
                }
            });
        }

        /**
         * Shows document not found message in the modal.
         */
        function showDocumentNotFound() {
            $docPreview.html(
                '<div style="text-align: center; padding: 40px 20px;">' +
                '<img src="' + baseUrl + 'images/icons/document-svgrepo-com.svg" width="64" height="64" style="opacity: 0.3; margin-bottom: 16px;">' +
                '<h3 style="font-size: 18px; margin-bottom: 8px;">Document Not Found</h3>' +
                '<p style="color: var(--gray-medium);">No document found for this seller.</p>' +
                '<p style="color: var(--gray-medium); font-size: var(--font-sm);">You can verify them manually using the "Verify Seller" button.</p>' +
                '</div>'
            );
            $docModalFooter.html('');
        }

        /**
         * Opens the verification modal for a seller with no document.
         * Allows admin to manually verify the seller.
         *
         * @param {number} userId - The seller's user ID
         */
        function openVerifyModal(userId) {
            currentReviewUserId = userId;

            $docModalTitle.text('Verify Seller');
            $docPreview.html(
                '<div style="text-align: center; padding: 40px 20px;">' +
                '<img src="' + baseUrl + 'images/icons/document-svgrepo-com.svg" width="64" height="64" style="opacity: 0.3; margin-bottom: 16px;">' +
                '<h3 style="font-size: 18px; margin-bottom: 8px;">No Document Uploaded</h3>' +
                '<p style="color: var(--gray-medium);">This seller has not uploaded any verification document.</p>' +
                '<p style="color: var(--gray-medium); font-size: var(--font-sm);">You can manually verify them below.</p>' +
                '</div>'
            );
            $docModalFooter.html(
                '<button class="btn-verify" onclick="verifySeller()">' +
                '<img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="16" height="16" style="filter: brightness(0) invert(1); vertical-align: middle; margin-right: 6px;">' +
                'Verify Seller' +
                '</button>'
            );
            $docModal.addClass('active');
        }

        /**
         * Closes the document review modal.
         */
        function closeDocModal() {
            $docModal.removeClass('active');
            currentReviewUserId = null;
            $docModalFooter.empty();
        }

        /**
         * Verifies the current seller.
         */
        function verifySeller() {
            if (!currentReviewUserId) return;
            if (confirm('Verify this seller?')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/users/update-user-verification.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        user_id: currentReviewUserId,
                        verify: true
                    }),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast('Seller verified successfully!');
                            closeDocModal();
                            loadUsers();
                        } else {
                            showErrorToast(data.message);
                        }
                    }
                });
            }
        }

        /**
         * Rejects the current seller's verification document.
         */
        function rejectSeller() {
            if (!currentReviewUserId) return;
            if (confirm('Reject this seller\'s verification document?')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/users/update-user-verification.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        user_id: currentReviewUserId,
                        verify: false
                    }),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast('Document rejected.');
                            closeDocModal();
                            loadUsers();
                        } else {
                            showErrorToast(data.message);
                        }
                    }
                });
            }
        }

        // ============================================================
        // USER ACTIONS (Page-specific)
        // ============================================================

        /**
         * Toggles user verification status (verify/unverify).
         *
         * @param {number} userId - The user ID to update
         * @param {boolean} verify - True to verify, false to unverify
         */
        function toggleUserVerification(userId, verify) {
            var confirmMsg = verify ? 'Verify this seller?' : 'Remove verification from this seller?';
            if (confirm(confirmMsg)) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/users/update-user-verification.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        user_id: userId,
                        verify: verify
                    }),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            if (typeof showSuccessToast === 'function') {
                                showSuccessToast(data.message);
                            } else {
                                alert(data.message);
                            }
                            loadUsers();
                        } else {
                            if (typeof showErrorToast === 'function') {
                                showErrorToast('Error: ' + data.message);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        }
                    },
                    error: function() {
                        if (typeof showErrorToast === 'function') {
                            showErrorToast('Something went wrong');
                        } else {
                            alert('Something went wrong');
                        }
                    }
                });
            }
        }

        /**
         * Updates a user's account status (suspend, ban, activate).
         *
         * @param {number} userId - The user ID to update
         * @param {string} status - The new status ('suspended', 'banned', 'active')
         */
        function updateUserStatus(userId, status) {
            var confirmMsg = '';
            if (status === 'suspended') {
                confirmMsg = 'Suspend this user? They will not be able to log in.';
            } else if (status === 'banned') {
                confirmMsg = 'Ban this user? This action is permanent.';
            } else {
                confirmMsg = 'Activate this user? They will be able to log in again.';
            }

            if (confirm(confirmMsg)) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/users/update-user-status.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        user_id: userId,
                        status: status
                    }),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast(data.message);
                            loadUsers();
                        } else {
                            showErrorToast(data.message);
                        }
                    },
                    error: function() {
                        showErrorToast('Something went wrong');
                    }
                });
            }
        }

        /**
         * Deletes a user account permanently.
         *
         * @param {number} userId - The user ID to delete
         */
        function deleteUser(userId) {
            if (confirm('Delete this user? This cannot be undone.')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/users/delete-user.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        user_id: userId
                    }),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            if (typeof showSuccessToast === 'function') {
                                showSuccessToast('User deleted successfully');
                            } else {
                                alert('User deleted successfully');
                            }
                            loadUsers();
                        } else {
                            if (typeof showErrorToast === 'function') {
                                showErrorToast('Error: ' + data.message);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        }
                    },
                    error: function() {
                        if (typeof showErrorToast === 'function') {
                            showErrorToast('Something went wrong');
                        } else {
                            alert('Something went wrong');
                        }
                    }
                });
            }
        }
    </script>

</body>

</html>