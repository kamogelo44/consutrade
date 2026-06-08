<?php
/*
 * ConsuTrade - Manage Users (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all users for admin management with verify/unverify functionality
 */

require_once dirname(__DIR__) . '/init.php';

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
        /* Users Page Specific Styles */

        /* Filters Bar */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
            background: var(--white);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }

        .filter-group label {
            font-size: var(--font-sm);
            font-weight: var(--font-semibold);
            color: var(--gray-dark);
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            min-width: 150px;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Role Badges (using components.css pattern) */
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .role-admin {
            background: var(--error-light);
            color: var(--error);
        }

        .role-seller {
            background: var(--primary-fade);
            color: var(--primary-color);
        }

        .role-buyer {
            background: var(--info-light);
            color: var(--info);
        }

        /* Verification Badges (using components.css pattern) */
        .verified-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
            background: var(--success-light);
            color: var(--success);
        }

        .unverified-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
            background: var(--warning-light);
            color: var(--warning);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: var(--font-xs);
            cursor: pointer;
            border: none;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .verify-btn {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .verify-btn:hover {
            background: var(--success);
            color: white;
            transform: translateY(-1px);
        }

        .unverify-btn {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .unverify-btn:hover {
            background: var(--warning);
            color: white;
            transform: translateY(-1px);
        }

        .delete-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .delete-btn:hover {
            background: var(--error);
            color: white;
            transform: translateY(-1px);
        }

        .filter-btn-small {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .filter-btn-small:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .filter-btn-small.reset {
            background: var(--gray-bg-light);
            color: var(--gray-dark);
            border: 1px solid var(--border-light);
        }

        .filter-btn-small.reset:hover {
            background: var(--gray-lighter);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                width: 100%;
            }

            .filter-btn-small {
                align-self: stretch;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                text-align: center;
            }

            /* Mobile table view */
            .data-table,
            .data-table tbody,
            .data-table tr,
            .data-table td {
                display: block;
            }

            .data-table thead {
                display: none;
            }

            .data-table tr {
                border: 1px solid var(--border-light);
                margin-bottom: var(--spacing-md);
                border-radius: var(--radius-md);
            }

            .data-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: var(--spacing-sm);
                border-bottom: 1px solid var(--border-light);
            }

            .data-table td:last-child {
                border-bottom: none;
            }

            .data-table td:before {
                content: attr(data-label);
                font-weight: var(--font-bold);
                color: var(--gray-dark);
                min-width: 120px;
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

            <div class="filters-bar">
                <div class="filter-group">
                    <label for="roleFilter">Filter by Role</label>
                    <select id="roleFilter">
                        <option value="all">All Users</option>
                        <option value="buyer">Buyers</option>
                        <option value="seller">Sellers</option>
                        <option value="admin">Admins</option>
                        <option value="pending">Pending Verification</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="searchInput">Search</label>
                    <input type="text" id="searchInput" placeholder="Name or email...">
                </div>
                <button id="searchBtn" class="filter-btn-small">Search</button>
                <button id="resetBtn" class="filter-btn-small reset">Reset</button>
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
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <tr>
                            <td colspan="8" class="loading-cell">Loading users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <script>
        // Page state variables - tracking filters and pagination
        var $usersTable = null,
            $pagination = null,
            $roleFilter = null,
            $searchBtn = null,
            $resetBtn = null,
            $searchInput = null,
            currentPage = 1,
            currentRole = 'all',
            currentSearch = '';

        // Cache DOM elements on load - saves repeatedly querying the DOM
        function cacheElements() {
            $usersTable = $('#usersTable');
            $pagination = $('#pagination');
            $roleFilter = $('#roleFilter');
            $searchBtn = $('#searchBtn');
            $resetBtn = $('#resetBtn');
            $searchInput = $('#searchInput');
        }

        $(document).ready(function() {
            cacheElements();
            loadUsers();

            // Role filter changed - reset to page 1 and reload
            $roleFilter.on('change', function() {
                currentRole = $(this).val();
                currentPage = 1;
                loadUsers();
            });

            // Search button clicked
            $searchBtn.on('click', function() {
                currentSearch = $searchInput.val().trim();
                currentPage = 1;
                loadUsers();
            });

            // Reset all filters
            $resetBtn.on('click', function() {
                $roleFilter.val('all');
                $searchInput.val('');
                currentRole = 'all';
                currentSearch = '';
                currentPage = 1;
                loadUsers();
            });

            // Enter key in search field triggers search
            $searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    currentSearch = $searchInput.val().trim();
                    currentPage = 1;
                    loadUsers();
                }
            });
        });

        // Fetch users from server with current filters
        function loadUsers() {
            $usersTable.html('<tr><td colspan="8" class="loading-cell">Loading users...</td></tr>');

            $.ajax({
                url: baseUrl + 'php/endpoints/get-users.php',
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
                        // Use the global pagination function from main.js
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
                        // Show empty state with helpful message based on active filters
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
                            '<tr><td colspan="8" style="text-align: center; padding: 60px;">' +
                            '<div class="empty-state">' +
                            '<img src="' + baseUrl + 'images/icons/users-svgrepo-com.svg" width="64" height="64" alt="No users" style="opacity: 0.4;">' +
                            '<h3 style="font-size: 20px; font-weight: bold; margin-top: 16px; margin-bottom: 8px;">' + escapeHtml(emptyTitle) + '</h3>' +
                            '<p style="color: var(--gray-medium);">' + escapeHtml(emptyMessage) + '</p>' +
                            resetButtonHtml +
                            '</div>' +
                            '</td></tr>'
                        );
                        $pagination.empty();
                    }
                },
                error: function() {
                    $usersTable.html('<tr><td colspan="8" class="error-cell">Error loading users. Please refresh the page.</td></tr>');
                }
            });
        }

        // Render user rows in the table
        function displayUsers(users) {
            $usersTable.empty();

            $.each(users, function(i, user) {
                var roleClass = user.role === 'admin' ? 'role-admin' : (user.role === 'seller' ? 'role-seller' : 'role-buyer');
                var verifiedBadge = user.id_verified ? '<span class="verified-badge">Verified</span>' : '<span class="unverified-badge">Not Verified</span>';
                var actionButtons = '<div class="action-buttons">';

                // Sellers have verification actions
                if (user.role === 'seller') {
                    if (user.has_document && !user.id_verified) {
                        actionButtons += '<button class="action-btn verify-btn" onclick="reviewDocuments(' + user.user_id + ')">Review Docs</button>';
                    } else if (user.id_verified) {
                        actionButtons += '<button class="action-btn unverify-btn" onclick="toggleUserVerification(' + user.user_id + ', false)">Unverify</button>';
                    } else {
                        actionButtons += '<button class="action-btn verify-btn" onclick="toggleUserVerification(' + user.user_id + ', true)">Verify</button>';
                    }
                }

                // Don't allow deleting yourself or other admins
                if (user.role !== 'admin' || user.user_id !== currentUserId) {
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
                    '<td data-label="Joined Date">' + user.created_at + '</td>' +
                    '<td data-label="Actions">' + actionButtons + '</td>' +
                    '</tr>'
                );
            });
        }

        // Redirect to document review page for pending sellers
        function reviewDocuments(userId) {
            window.location.href = baseUrl + 'admin/verify-seller.php?seller_id=' + userId;
        }

        // Toggle seller verification status
        function toggleUserVerification(userId, verify) {
            var confirmMsg = verify ? 'Verify this seller?' : 'Remove verification from this seller?';
            if (confirm(confirmMsg)) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/update-user-verification.php',
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

        // Delete user account - permanent action
        function deleteUser(userId) {
            if (confirm('Delete this user? This cannot be undone.')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/delete-user.php',
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