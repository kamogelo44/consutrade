<?php
/*
 * ConsuTrade - Flagged Listings (Admin Page)
 * Author: Kamogelo Phale
 * 
 * Displays all pending product reports for admin review
 */

require_once dirname(__DIR__) . '/init.php';

// Redirect if not admin
if (!$auth->isAdmin()) {
    header('Location: ' . $baseUrl . 'admin/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flagged Listings - ConsuTrade Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        .admin-main-content {
            margin-left: 280px;
            padding: var(--spacing-xl);
            min-height: 100vh;
            background: var(--gray-bg);
            transition: margin-left var(--transition-normal);
        }

        .dashboard-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .page-header h1 img {
            width: 28px;
            height: 28px;
        }

        .page-header p {
            color: var(--gray-medium);
        }

        .pending-count {
            background-color: var(--warning);
            color: var(--white);
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            margin-left: var(--spacing-md);
        }

        /* Report Card */
        .report-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            overflow: hidden;
            transition: all var(--transition-fast);
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .report-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--gray-bg);
            border-bottom: 1px solid var(--border-light);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .report-product-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .report-product-image {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            overflow: hidden;
            background: var(--gray-lighter);
            flex-shrink: 0;
        }

        .report-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .report-product-title {
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            font-size: var(--font-md);
        }

        .report-product-title a {
            color: inherit;
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .report-product-title a:hover {
            color: var(--primary-color);
        }

        .report-product-price {
            font-size: var(--font-sm);
            color: var(--primary-color);
            font-weight: var(--font-medium);
        }

        .report-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .report-badge.pending {
            background: var(--warning-light);
            color: var(--warning-dark);
        }

        .report-card-body {
            padding: var(--spacing-lg);
        }

        .report-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-label {
            font-size: var(--font-xs);
            color: var(--gray-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: var(--font-md);
            color: var(--gray-dark);
        }

        .detail-value.reason {
            font-weight: var(--font-semibold);
            color: var(--warning-dark);
        }

        .report-description {
            background: var(--gray-bg);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }

        .report-description p {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            line-height: 1.5;
        }

        .report-actions {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            border-top: 1px solid var(--border-light);
            padding-top: var(--spacing-lg);
            margin-top: var(--spacing-sm);
        }

        .btn-dismiss,
        .btn-suspend,
        .btn-view-product {
            padding: 8px 20px;
            border-radius: var(--radius-md);
            font-weight: var(--font-medium);
            font-size: var(--font-sm);
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-dismiss {
            background: var(--gray-bg);
            color: var(--gray-dark);
            border: 1px solid var(--border-medium);
        }

        .btn-dismiss:hover {
            background: var(--border-light);
            transform: translateY(-2px);
        }

        .btn-suspend {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .btn-suspend:hover {
            background: var(--error);
            color: white;
            transform: translateY(-2px);
        }

        .btn-view-product {
            background: var(--primary-fade);
            color: var(--primary-color);
            text-decoration: none;
        }

        .btn-view-product:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: var(--z-modal);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-light);
        }

        .modal-close:hover {
            color: var(--error);
        }

        .modal-body {
            padding: var(--spacing-lg);
        }

        .modal-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--border-light);
        }

        .admin-notes-textarea {
            width: 100%;
            min-height: 100px;
            padding: var(--spacing-md);
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: var(--font-md);
            resize: vertical;
            margin: var(--spacing-md) 0;
        }

        .admin-notes-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .modal-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
        }

        /* Empty state */
        .empty-products {
            text-align: center;
            padding: 60px;
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
        }

        .empty-products img {
            width: 80px;
            height: 80px;
            opacity: 0.4;
            margin-bottom: var(--spacing-lg);
        }

        .empty-products h3 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
        }

        .empty-products p {
            color: var(--gray-medium);
        }

        /* Loading */
        .loading-spinner {
            text-align: center;
            padding: 60px;
            color: var(--gray-medium);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-xl);
            flex-wrap: wrap;
        }

        .page-btn {
            padding: 8px 14px;
            border: 1px solid var(--border-light);
            background: var(--white);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-size: var(--font-sm);
        }

        .page-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            cursor: default;
        }

        .page-dots {
            padding: 8px 4px;
            color: var(--gray-light);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .admin-main-content {
                margin-left: 0;
                padding: var(--spacing-md);
                padding-top: 70px;
            }
        }

        @media (max-width: 768px) {
            .report-card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .report-details {
                grid-template-columns: 1fr;
            }

            .report-actions {
                flex-direction: column;
            }

            .report-actions button,
            .report-actions a {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .admin-main-content {
                padding: var(--spacing-sm);
                padding-top: 60px;
            }

            .page-header h1 {
                font-size: var(--font-xl);
            }

            .modal-content {
                width: 95%;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>
                    <img src="<?php echo $baseUrl; ?>images/icons/warning-svgrepo-com.svg" alt="Flagged">
                    Flagged Listings
                    <span class="pending-count" id="pendingCount">Loading...</span>
                </h1>
                <p>Review and moderate product reports from buyers</p>
            </div>

            <div id="reportsContainer">
                <div class="loading-spinner">Loading flagged listings...</div>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <!-- Admin Notes Modal -->
    <div id="adminNotesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Review Report</h3>
                <button class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
                <textarea id="adminNotes" class="admin-notes-textarea" placeholder="Add notes about this decision (optional)..."></textarea>
                <input type="hidden" id="currentReportId" value="">
                <input type="hidden" id="currentAction" value="">
            </div>
            <div class="modal-footer">
                <div class="modal-actions">
                    <button id="modalCancelBtn" class="btn-dismiss">Cancel</button>
                    <button id="modalConfirmBtn" class="btn-suspend">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Use existing baseUrl from main.js if available
        var baseUrl = window.baseUrl || '<?php echo $baseUrl; ?>';

        var currentPage = 1;
        var totalPages = 1;
        var $reportsContainer = null;
        var $paginationContainer = null;
        var $pendingCount = null;
        var $adminNotesModal = null;
        var $adminNotes = null;
        var $currentReportId = null;
        var $currentAction = null;
        var $modalTitle = null;
        var $modalMessage = null;
        var $modalConfirmBtn = null;

        function cacheElements() {
            $reportsContainer = $('#reportsContainer');
            $paginationContainer = $('#pagination');
            $pendingCount = $('#pendingCount');
            $adminNotesModal = $('#adminNotesModal');
            $adminNotes = $('#adminNotes');
            $currentReportId = $('#currentReportId');
            $currentAction = $('#currentAction');
            $modalTitle = $('#modalTitle');
            $modalMessage = $('#modalMessage');
            $modalConfirmBtn = $('#modalConfirmBtn');
        }

        function getReasonLabel(reason) {
            var labels = {
                'fake_product': 'Fake Product',
                'wrong_description': 'Wrong Description',
                'counterfeit': 'Counterfeit Item',
                'scam': 'Potential Scam',
                'other': 'Other Issue'
            };
            return labels[reason] || reason;
        }

        function loadFlaggedListings() {
            $reportsContainer.html('<div class="loading-spinner">Loading flagged listings...</div>');

            $.ajax({
                url: baseUrl + 'php/endpoints/get-flagged-listings.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    page: currentPage,
                    limit: 10
                },
                success: function(data) {
                    if (data.success) {
                        totalPages = data.total_pages;
                        $pendingCount.text(data.total + ' pending');
                        displayReports(data.reports);
                        displayPagination();
                    } else {
                        $reportsContainer.html('<div class="empty-products"><p>Unable to load reports. ' + (data.message || 'Please try again.') + '</p></div>');
                        $paginationContainer.empty();
                    }
                },
                error: function() {
                    $reportsContainer.html('<div class="empty-products" style="color: var(--error);">Error loading flagged listings. Please refresh the page.</div>');
                    $paginationContainer.empty();
                }
            });
        }

        function displayReports(reports) {
            if (!reports || reports.length === 0) {
                $reportsContainer.html(
                    '<div class="empty-products">' +
                    '<img src="' + baseUrl + 'images/icons/warning-svgrepo-com.svg" alt="No reports">' +
                    '<h3>No Pending Reports</h3>' +
                    '<p>All product reports have been reviewed. Check back later for new reports.</p>' +
                    '</div>'
                );
                return;
            }

            $reportsContainer.empty();

            for (var i = 0; i < reports.length; i++) {
                var report = reports[i];
                var productUrl = baseUrl + 'product-details.php?id=' + report.product_id;
                var imagePath = report.product_image || baseUrl + 'images/default-product.png';

                var card = $('<div>').addClass('report-card');
                card.html(
                    '<div class="report-card-header">' +
                    '<div class="report-product-info">' +
                    '<div class="report-product-image">' +
                    '<img src="' + imagePath + '" alt="' + escapeHtml(report.product_title) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                    '</div>' +
                    '<div>' +
                    '<div class="report-product-title">' +
                    '<a href="' + productUrl + '" target="_blank">' + escapeHtml(report.product_title) + '</a>' +
                    '</div>' +
                    '<div class="report-product-price">R ' + parseFloat(report.product_price).toFixed(2) + '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="report-badge pending">Pending Review</div>' +
                    '</div>' +
                    '<div class="report-card-body">' +
                    '<div class="report-details">' +
                    '<div class="detail-item">' +
                    '<span class="detail-label">Reported By</span>' +
                    '<span class="detail-value">' + escapeHtml(report.reporter_name) + '</span>' +
                    '</div>' +
                    '<div class="detail-item">' +
                    '<span class="detail-label">Seller</span>' +
                    '<span class="detail-value">' + escapeHtml(report.seller_name) + '</span>' +
                    '</div>' +
                    '<div class="detail-item">' +
                    '<span class="detail-label">Report Date</span>' +
                    '<span class="detail-value">' + escapeHtml(report.created_at) + '</span>' +
                    '</div>' +
                    '<div class="detail-item">' +
                    '<span class="detail-label">Reason</span>' +
                    '<span class="detail-value reason">' + escapeHtml(report.reason_label) + '</span>' +
                    '</div>' +
                    '</div>'
                );

                if (report.description && report.description.trim() !== '') {
                    card.append(
                        '<div class="report-description">' +
                        '<p><strong>Additional Details:</strong><br>' + escapeHtml(report.description) + '</p>' +
                        '</div>'
                    );
                }

                card.append(
                    '<div class="report-actions">' +
                    '<a href="' + productUrl + '" class="btn-view-product" target="_blank">' +
                    '<img src="' + baseUrl + 'images/icons/eye-open-svgrepo-com.svg" width="14" height="14" alt="View"> View Product' +
                    '</a>' +
                    '<button class="btn-dismiss" onclick="openAdminNotesModal(' + report.report_id + ', \'dismiss\')">' +
                    '<img src="' + baseUrl + 'images/icons/dismiss-svgrepo-com.svg" width="14" height="14" alt="Dismiss"> Dismiss Report' +
                    '</button>' +
                    '<button class="btn-suspend" onclick="openAdminNotesModal(' + report.report_id + ', \'suspend\')">' +
                    '<img src="' + baseUrl + 'images/icons/ban-svgrepo-com.svg" width="14" height="14" alt="Suspend"> Suspend Product' +
                    '</button>' +
                    '</div>' +
                    '</div>'
                );

                $reportsContainer.append(card);
            }
        }

        function displayPagination() {
            if (totalPages <= 1) {
                $paginationContainer.empty();
                return;
            }

            var html = '';
            if (currentPage > 1) {
                html += '<button class="page-btn" onclick="goToPage(' + (currentPage - 1) + ')">← Previous</button>';
            }

            for (var i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    html += '<button class="page-btn active" disabled>' + i + '</button>';
                } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
                    html += '<button class="page-btn" onclick="goToPage(' + i + ')">' + i + '</button>';
                } else if (Math.abs(i - currentPage) === 3) {
                    html += '<span class="page-dots">...</span>';
                }
            }

            if (currentPage < totalPages) {
                html += '<button class="page-btn" onclick="goToPage(' + (currentPage + 1) + ')">Next →</button>';
            }

            $paginationContainer.html(html);
        }

        function goToPage(page) {
            currentPage = page;
            loadFlaggedListings();
            $('html, body').animate({
                scrollTop: 0
            }, 'smooth');
        }

        function openAdminNotesModal(reportId, action) {
            $currentReportId.val(reportId);
            $currentAction.val(action);
            $adminNotes.val('');

            if (action === 'dismiss') {
                $modalTitle.text('Dismiss Report');
                $modalMessage.html('<p>Are you sure you want to dismiss this report? The product will remain active.</p>');
                $modalConfirmBtn.removeClass('btn-suspend').addClass('btn-dismiss').text('Dismiss Report');
            } else {
                $modalTitle.text('Suspend Product');
                $modalMessage.html('<p><strong>Warning:</strong> Suspending this product will remove it from the marketplace. The seller will be notified.</p><p>Are you sure you want to suspend this product?</p>');
                $modalConfirmBtn.removeClass('btn-dismiss').addClass('btn-suspend').text('Suspend Product');
            }

            $adminNotesModal.addClass('active');
            $('body').css('overflow', 'hidden');
        }

        function closeAdminNotesModal() {
            $adminNotesModal.removeClass('active');
            $('body').css('overflow', '');
            $adminNotes.val('');
            $currentReportId.val('');
            $currentAction.val('');
        }

        function submitReportAction() {
            var reportId = parseInt($currentReportId.val());
            var action = $currentAction.val();
            var adminNotes = $adminNotes.val().trim();

            if (!reportId || !action) {
                closeAdminNotesModal();
                return;
            }

            $modalConfirmBtn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: baseUrl + 'php/endpoints/update-report-status.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    report_id: reportId,
                    action: action,
                    admin_notes: adminNotes
                }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        // Use existing toast function from main.js
                        if (typeof window.showSuccessToast === 'function') {
                            window.showSuccessToast(data.message);
                        } else if (typeof showSuccessToast === 'function') {
                            showSuccessToast(data.message);
                        } else {
                            alert(data.message);
                        }
                        currentPage = 1;
                        loadFlaggedListings();
                    } else {
                        if (typeof window.showErrorToast === 'function') {
                            window.showErrorToast(data.message || 'Failed to process report.');
                        } else if (typeof showErrorToast === 'function') {
                            showErrorToast(data.message || 'Failed to process report.');
                        } else {
                            alert('Error: ' + (data.message || 'Failed to process report.'));
                        }
                    }
                    closeAdminNotesModal();
                },
                error: function() {
                    if (typeof window.showErrorToast === 'function') {
                        window.showErrorToast('Something went wrong. Please try again.');
                    } else if (typeof showErrorToast === 'function') {
                        showErrorToast('Something went wrong. Please try again.');
                    } else {
                        alert('Something went wrong. Please try again.');
                    }
                    closeAdminNotesModal();
                },
                complete: function() {
                    $modalConfirmBtn.prop('disabled', false);
                    if (action === 'dismiss') {
                        $modalConfirmBtn.text('Dismiss Report');
                    } else {
                        $modalConfirmBtn.text('Suspend Product');
                    }
                }
            });
        }

        $(function() {
            cacheElements();
            loadFlaggedListings();

            // Modal close handlers
            $('#closeModalBtn').on('click', closeAdminNotesModal);
            $('#modalCancelBtn').on('click', closeAdminNotesModal);
            $adminNotesModal.on('click', function(e) {
                if ($(e.target).is($adminNotesModal)) {
                    closeAdminNotesModal();
                }
            });
            $('#modalConfirmBtn').on('click', submitReportAction);

            // Escape key to close modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $adminNotesModal.hasClass('active')) {
                    closeAdminNotesModal();
                }
            });
        });
    </script>

</body>

</html>