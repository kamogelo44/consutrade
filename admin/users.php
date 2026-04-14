<?php
/*
 * ConsuTrade - Manage Users
 * Author: Kamogelo Phale
 * 
 * Admin page for managing all users
 */

require_once 'admin-header.php';
?>

<div class="dashboard-welcome">
    <h1>Manage Users</h1>
    <p>View and manage all registered users on ConsuTrade.</p>
</div>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Joined Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6" style="text-align: center;">Loading users...</td>
            </tr>
        </tbody>
    </table>
</div>

</main>

<script src="<?php echo $baseUrl; ?>js/admin.js"></script>
</body>
</html>