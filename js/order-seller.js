$(function() {
    var $table = $('#ordersTable');
    var $pagination = $('#pagination');
    var $searchInput = $('#searchInput');
    var $statusFilter = $('#statusFilter');
    var page = 1, status = 'all', search = '';

    function loadOrders() {
        window.loadOrders(
            'php/endpoints/orders/get-my-orders.php',
            $table, $pagination, page, status, search, 'seller',
            function(newPage) { page = newPage; loadOrders(); }
        );
    }

    $statusFilter.on('change', function() { status = this.value; page = 1; loadOrders(); });
    $('#searchBtn').on('click', function() { search = $searchInput.val().trim(); page = 1; loadOrders(); });
    $searchInput.on('keypress', function(e) { if (e.which === 13) $('#searchBtn').click(); });

    loadOrders();
});