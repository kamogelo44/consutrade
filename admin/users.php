<?php
/*
 * ConsuTrade - Manage Users (Admin)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/php/helpers.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();

// Get database connection
require_once dirname(__DIR__) . '/php/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ConsuTrade Admin</title>
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
<div class="dashboard-header">
    <h1>Manage Users</h1>
    <p>View and manage all registered users on ConsuTrade.</p>
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
                <th>Joined Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="users-table">
            <tr><td colspan="7" style="text-align: center;">Loading users...</td><td style="display: none;"></td><td style="display: none;"></td><td style="display: none;"></td><td style="display: none;"></td><td style="display: none;"></td><td style="display: none;"></td></tr>
        </tbody>
    </table>
</div>

</main>
</div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
$(document).ready(function() {
    loadAllUsers();
    
    function loadAllUsers() {
        var $tbody = $('#users-table');
        $tbody.html('<tr><td colspan="7" style="text-align: center;">Loading users...</td></tr>');
        
        $.ajax({
            url: baseUrl + 'admin/php/get-all-users.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.users && data.users.length) {
                    $tbody.empty();
                    $.each(data.users, function(i, user) {
                        var roleClass = '';
                        if (user.role === 'admin') roleClass = 'role-admin';
                        else if (user.role === 'seller') roleClass = 'role-seller';
                        else roleClass = 'role-buyer';
                        
                        $tbody.append(`
                            <tr>
                                <td>${user.user_id}</td>
                                <td>${escapeHtml(user.full_name)}</td>
                                <td>${escapeHtml(user.email)}</td>
                                <td>${escapeHtml(user.phone || '-')}</td>
                                <td><span class="role-badge ${roleClass}">${user.role}</span></td>
                                <td>${user.created_at}</td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewUser(${user.user_id})">View</button>
                                    ${user.role !== 'admin' ? `<button class="action-btn delete-btn" onclick="deleteUser(${user.user_id})">Delete</button>` : ''}
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    $tbody.html('<tr><td colspan="7" style="text-align: center;">No users found</td></tr>');
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="7" style="text-align: center;">Error loading users</td></tr>');
            }
        });
    }
    
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
                        alert('User deleted successfully');
                        loadAllUsers();
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function() {
                    alert('Something went wrong');
                }
            });
        }
    };
});
</script>
</body>
</html>