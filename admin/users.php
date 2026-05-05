<?php
/*
 * ConsuTrade - Manage Users (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all users for admin management
 */

require_once dirname(__DIR__) . '/init.php';

// Check if admin is logged in using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'admin') {
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
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="admin-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<main class="dashboard-main">
    <div class="dashboard-content">
        <div class="dashboard-header">
            <h1>Manage Users</h1>
            <p>View and manage all registered users on ConsuTrade.</p>
        </div>

        <!-- Filter Bar -->
        <div class="filters-bar">
            <div class="filter-group">
                <label for="role-filter">Filter by Role:</label>
                <select id="role-filter">
                    <option value="all">All Users</option>
                    <option value="buyer">Buyers</option>
                    <option value="seller">Sellers</option>
                    <option value="admin">Admins</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="search-input">Search:</label>
                <input type="text" id="search-input" placeholder="Name or email...">
            </div>
            <button id="search-btn" class="filter-btn-small">Search</button>
            <button id="reset-btn" class="filter-btn-small reset">Reset</button>
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
                <tbody id="users-table">
                    <tr><td colspan="8" style="text-align: center;">Loading users...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
    </div>
</main>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
/*
 * ConsuTrade - Admin Users Management
 * Author: Kamogelo Phale
 */
var currentPage = 1;
var currentRole = 'all';
var currentSearch = '';

$(document).ready(function() {
    loadUsers();
    
    $('#role-filter').on('change', function() {
        currentRole = $(this).val();
        currentPage = 1;
        loadUsers();
    });
    
    $('#search-btn').on('click', function() {
        currentSearch = $('#search-input').val().trim();
        currentPage = 1;
        loadUsers();
    });
    
    $('#reset-btn').on('click', function() {
        $('#role-filter').val('all');
        $('#search-input').val('');
        currentRole = 'all';
        currentSearch = '';
        currentPage = 1;
        loadUsers();
    });
    
    $('#search-input').on('keypress', function(e) {
        if (e.which === 13) {
            currentSearch = $(this).val().trim();
            currentPage = 1;
            loadUsers();
        }
    });
    
    function loadUsers() {
        var $tbody = $('#users-table');
        $tbody.html('<tr><td colspan="8" style="text-align: center;">Loading users...</td></tr>');
        
        $.ajax({
            url: baseUrl + 'admin/php/get-all-users.php',
            type: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                role: currentRole,
                search: currentSearch
            },
            success: function(data) {
                if (data.success && data.users && data.users.length) {
                    $tbody.empty();
                    $.each(data.users, function(i, user) {
                        var roleClass = '';
                        if (user.role === 'admin') roleClass = 'role-admin';
                        else if (user.role === 'seller') roleClass = 'role-seller';
                        else roleClass = 'role-buyer';
                        
                        var verifiedBadge = user.is_verified ? 
                            '<span class="verified-badge-small">✓ Verified</span>' : 
                            '<span class="unverified-badge-small">Not Verified</span>';
                        
                        var actionsHtml = `<button class="action-btn view-btn" onclick="viewUser(${user.user_id})">View</button>`;
                        
                        // Only show delete for non-admin users and not current admin
                        if (user.role !== 'admin' || user.user_id !== <?php echo $current_user_id; ?>) {
                            actionsHtml += `<button class="action-btn delete-btn" onclick="deleteUser(${user.user_id})">Delete</button>`;
                        }
                        
                        $tbody.append(`
                            <tr>
                                <td>${user.user_id}</td>
                                <td>${escapeHtml(user.full_name)}</td>
                                <td>${escapeHtml(user.email)}</td>
                                <td>${escapeHtml(user.phone || '-')}</td>
                                <td><span class="role-badge ${roleClass}">${user.role}</span></td>
                                <td>${verifiedBadge}</td>
                                <td>${user.created_at}</td>
                                <td>${actionsHtml}</td>
                            </tr>
                        `);
                    });
                    displayPagination(data.total_pages, data.current_page);
                } else {
                    $tbody.html('<tr><td colspan="8" style="text-align: center;">No users found</td></tr>');
                    $('#pagination').empty();
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="8" style="text-align: center;">Error loading users</td></tr>');
            }
        });
    }
    
    function displayPagination(totalPages, currentPageNum) {
        var $pagination = $('#pagination');
        if (totalPages <= 1) {
            $pagination.empty();
            return;
        }
        
        var html = '';
        if (currentPageNum > 1) {
            html += '<button class="page-btn" onclick="goToPage(' + (currentPageNum - 1) + ')">← Previous</button>';
        }
        
        for (var i = 1; i <= totalPages; i++) {
            if (i === currentPageNum) {
                html += '<button class="page-btn active" disabled>' + i + '</button>';
            } else if (Math.abs(i - currentPageNum) <= 2 || i === 1 || i === totalPages) {
                html += '<button class="page-btn" onclick="goToPage(' + i + ')">' + i + '</button>';
            } else if (Math.abs(i - currentPageNum) === 3) {
                html += '<span class="page-dots">...</span>';
            }
        }
        
        if (currentPageNum < totalPages) {
            html += '<button class="page-btn" onclick="goToPage(' + (currentPageNum + 1) + ')">Next →</button>';
        }
        
        $pagination.html(html);
    }
    
    window.goToPage = function(page) {
        currentPage = page;
        loadUsers();
        $('html, body').animate({ scrollTop: 0 }, 'smooth');
    };
    
    window.viewUser = function(userId) {
        window.location.href = 'view-user.php?id=' + userId;
    };
    
    window.deleteUser = function(userId) {
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
                error: function() {
                    showErrorToast('Something went wrong');
                }
            });
        }
    };
});
</script>
</body>
</html>