<?php
/*
 * ConsuTrade - Manage Users (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all users for admin management with verify/unverify functionality
 */

require_once dirname(__DIR__) . '/init.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ConsuTrade Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-clean.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar-clean.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
    <script>var baseUrl = '<?php echo $baseUrl; ?>';</script>
    <style>
        /* Page specific styles - only what's not in dashboard-clean.css */
        
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
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            min-width: 150px;
        }
        .filter-group select:focus, .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
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
        
        /* Action Buttons - properly sized */
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
            white-space: nowrap;
        }
        .verify-btn { background: var(--success-light); color: var(--success); border: 1px solid var(--success); }
        .verify-btn:hover { background: var(--success); color: white; }
        .unverify-btn { background: var(--warning-light); color: var(--warning); border: 1px solid var(--warning); }
        .unverify-btn:hover { background: var(--warning); color: white; }
        .delete-btn { background: var(--error-light); color: var(--error); border: 1px solid var(--error); }
        .delete-btn:hover { background: var(--error); color: white; }
        
        /* Verified badge (uses same colors as role badges) */
        .verified-badge, .unverified-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }
        .verified-badge { background: var(--success-light); color: var(--success); }
        .unverified-badge { background: var(--warning-light); color: var(--warning); }
        
        .page-header {
            margin-bottom: var(--spacing-xl);
        }
        .page-header h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
        }
        .page-header p {
            color: var(--gray-medium);
        }
        
        /* Error/Empty states */
        .error-cell, .empty-cell, .loading-cell {
            text-align: center;
            padding: var(--spacing-xl);
        }
        .loading-cell { color: var(--gray-medium); }
        .error-cell { color: var(--error); background: var(--error-light); border-left: 4px solid var(--error); }
        .empty-cell { color: var(--gray-medium); }
        
        /* Responsive overrides */
        @media (max-width: 768px) {
            .filters-bar { flex-direction: column; align-items: stretch; }
            .filter-group { width: 100%; }
            .filter-btn-small { align-self: stretch; }
            .action-buttons { flex-direction: column; }
            .action-btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="admin-main-content">
    <div class="dashboard-content">
        <div class="page-header">
            <h1>Manage Users</h1>
            <p>View and manage all registered users on ConsuTrade.</p>
        </div>

        <!-- Filter Bar -->
        <div class="filters-bar">
            <div class="filter-group">
                <label for="roleFilter">Filter by Role</label>
                <select id="roleFilter">
                    <option value="all">All Users</option>
                    <option value="buyer">Buyers</option>
                    <option value="seller">Sellers</option>
                    <option value="admin">Admins</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="searchInput">Search</label>
                <input type="text" id="searchInput" placeholder="Name or email...">
            </div>
            <button id="searchBtn" class="filter-btn-small">Search</button>
            <button id="resetBtn" class="filter-btn-small reset">Reset</button>
        </div>

        <!-- Users Table - reuses data-table styles from dashboard-clean.css -->
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
                    <tr><td colspan="8" class="loading-cell">Loading users...</td</tr>
                </tbody>
            </table>
        </div>
        
        <div class="pagination" id="pagination"></div>
    </div>
</main>

<script>
var currentPage = 1, currentRole = 'all', currentSearch = '';

$(function() {
    loadUsers();
    
    $('#roleFilter').on('change', function() {
        currentRole = $(this).val();
        currentPage = 1;
        loadUsers();
    });
    
    $('#searchBtn').on('click', function() {
        currentSearch = $('#searchInput').val().trim();
        currentPage = 1;
        loadUsers();
    });
    
    $('#resetBtn').on('click', function() {
        $('#roleFilter').val('all');
        $('#searchInput').val('');
        currentRole = 'all';
        currentSearch = '';
        currentPage = 1;
        loadUsers();
    });
    
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            currentSearch = $(this).val().trim();
            currentPage = 1;
            loadUsers();
        }
    });
});

function loadUsers() {
    $('#usersTable').html('<tr><td colspan="8" class="loading-cell">Loading users...</td</tr>');
    
    $.ajax({
        url: baseUrl + 'admin/php/get-all-users.php',
        type: 'GET',
        dataType: 'json',
        data: { page: currentPage, role: currentRole, search: currentSearch },
        success: function(data) {
            if (data.success && data.users && data.users.length) {
                displayUsers(data.users);
                displayPagination(data.total_pages, data.current_page);
            } else {
                $('#usersTable').html('<tr><td colspan="8" class="empty-cell">No users found</td</tr>');
                $('#pagination').empty();
            }
        },
        error: function() {
            $('#usersTable').html('<tr><td colspan="8" class="error-cell">Error loading users. Please refresh.</td</tr>');
        }
    });
}

function displayUsers(users) {
    var $tbody = $('#usersTable');
    $tbody.empty();
    
    $.each(users, function(i, user) {
        // Use existing role badge classes from dashboard-clean.css
        var roleClass = user.role === 'admin' ? 'role-admin' : (user.role === 'seller' ? 'role-seller' : 'role-buyer');
        
        // Verified badge
        var verifiedBadge = user.is_verified ? 
            '<span class="verified-badge">Verified</span>' : 
            '<span class="unverified-badge">Not Verified</span>';
        
        // Action buttons - properly sized
        var actionButtons = '<div class="action-buttons">';
        
        // Verify/Unverify button (only for sellers)
        if (user.role === 'seller') {
            if (user.is_verified) {
                actionButtons += '<button class="action-btn unverify-btn" onclick="toggleUserVerification(' + user.user_id + ', false)">Unverify</button>';
            } else {
                actionButtons += '<button class="action-btn verify-btn" onclick="toggleUserVerification(' + user.user_id + ', true)">Verify</button>';
            }
        }
        
        // Delete button (don't allow deleting own admin account)
        if (user.role !== 'admin' || user.user_id !== <?php echo $current_user_id; ?>) {
            actionButtons += '<button class="action-btn delete-btn" onclick="deleteUser(' + user.user_id + ')">Delete</button>';
        }
        
        actionButtons += '</div>';
        
        // If no actions, show dash
        if (actionButtons === '<div class="action-buttons"></div>') {
            actionButtons = '<div class="action-buttons">-</div>';
        }
        
        $tbody.append(`
            <tr>
                <td data-label="ID">${user.user_id}</td>
                <td data-label="Full Name">${escapeHtml(user.full_name)}</td>
                <td data-label="Email">${escapeHtml(user.email)}</td>
                <td data-label="Phone">${escapeHtml(user.phone || '-')}</td>
                <td data-label="Role"><span class="role-badge ${roleClass}">${user.role}</span></td>
                <td data-label="Verified">${verifiedBadge}</td>
                <td data-label="Joined Date">${user.created_at}</td>
                <td data-label="Actions">${actionButtons}</td>
            `
        );
    });
}

function displayPagination(totalPages, currentPageNum) {
    if (totalPages <= 1) { $('#pagination').empty(); return; }
    
    var html = '';
    if (currentPageNum > 1) html += `<button class="page-btn" onclick="goToPage(${currentPageNum - 1})">← Previous</button>`;
    
    for (var i = 1; i <= totalPages; i++) {
        if (i === currentPageNum) {
            html += `<button class="page-btn active" disabled>${i}</button>`;
        } else if (Math.abs(i - currentPageNum) <= 2 || i === 1 || i === totalPages) {
            html += `<button class="page-btn" onclick="goToPage(${i})">${i}</button>`;
        } else if (Math.abs(i - currentPageNum) === 3) {
            html += `<span class="page-dots">...</span>`;
        }
    }
    
    if (currentPageNum < totalPages) html += `<button class="page-btn" onclick="goToPage(${currentPageNum + 1})">Next →</button>`;
    $('#pagination').html(html);
}

function goToPage(page) { 
    currentPage = page; 
    loadUsers(); 
    $('html, body').animate({ scrollTop: 0 }, 'smooth'); 
}

function toggleUserVerification(userId, verify) {
    var action = verify ? 'verify' : 'unverify';
    var confirmMsg = verify ? 'Verify this seller? They will get a verified badge.' : 'Remove verification from this seller?';
    
    if (confirm(confirmMsg)) {
        $.ajax({
            url: baseUrl + 'admin/php/update-user-verification.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ user_id: userId, verify: verify }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast(data.message);
                    loadUsers();
                } else {
                    showErrorToast('Error: ' + data.message);
                }
            },
            error: function() { showErrorToast('Something went wrong'); }
        });
    }
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        $.ajax({
            url: baseUrl + 'admin/php/delete-user.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ user_id: userId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast('User deleted successfully');
                    loadUsers();
                } else {
                    showErrorToast('Error: ' + data.message);
                }
            },
            error: function() { showErrorToast('Something went wrong'); }
        });
    }
}
</script>

</body>
</html>