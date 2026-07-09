/**
 * ConsuTrade - Orders Module
 * Order modals, status updates, order tables
 * Depends on: jQuery, core/ui.js, core/utils.js
 */

// ============================================================
// ORDER DETAILS MODAL
// ============================================================

function openOrderModal(orderId) {
    var $modal = $('#orderModal');
    var $modalBody = $('#orderModalBody');
    var $modalFooter = $('#orderModalFooter');

    if (!$modal.length) return;

    $modal.addClass('active');
    $modalBody.html('<div class="loading-spinner">Loading order details...</div>');
    $modalFooter.empty();

    $.ajax({
        url: baseUrl + 'php/endpoints/orders/get-order-details.php?order_id=' + orderId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.order) {
                var order = data.order;
                var userRole = currentUserRole || 'buyer';

                var itemsHtml = '';
                if (order.items && order.items.length > 0) {
                    for (var i = 0; i < order.items.length; i++) {
                        var item = order.items[i];
                        var imagePath = fixImageUrl(item.image_url);
                        itemsHtml +=
                            '<div class="order-item">' +
                                '<div class="order-item-img">' +
                                    '<img src="' + imagePath + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                                '</div>' +
                                '<div class="order-item-details">' +
                                    '<h4>' + escapeHtml(item.product_name) + '</h4>' +
                                    '<p>Quantity: ' + item.quantity + '</p>' +
                                '</div>' +
                                '<div class="order-item-price">R ' + parseFloat(item.price).toFixed(2) + '</div>' +
                            '</div>';
                    }
                }

                var paymentHtml = '';
                if (order.transaction) {
                    var tx = order.transaction;
                    var statusClass = tx.status === 'completed' ? 'completed' : (tx.status === 'pending' ? 'pending' : 'failed');
                    paymentHtml =
                        '<div class="payment-details">' +
                            '<h4>Payment Information</h4>' +
                            '<div class="info-row"><span class="info-label">Transaction Reference:</span><span class="info-value">' + escapeHtml(tx.reference) + '</span></div>' +
                            '<div class="info-row"><span class="info-label">Payment Status:</span><span class="info-value"><span class="payment-status-badge ' + statusClass + '">' + capitalizeFirst(tx.status) + '</span></span></div>' +
                            '<div class="info-row"><span class="info-label">Amount Paid:</span><span class="info-value">R ' + parseFloat(tx.amount).toFixed(2) + '</span></div>' +
                            '<div class="info-row"><span class="info-label">Paid On:</span><span class="info-value">' + escapeHtml(tx.paid_at) + '</span></div>' +
                        '</div>';
                } else {
                    paymentHtml =
                        '<div class="payment-details" style="border-left-color: var(--warning);">' +
                            '<h4>Payment Information</h4>' +
                            '<p style="color: var(--gray-medium); font-size: var(--font-sm);">Payment is pending confirmation.</p>' +
                        '</div>';
                }

                $modalBody.html(
                    '<div class="order-info-section">' +
                        '<div class="info-row"><span class="info-label">Order Number:</span><span class="info-value">#' + order.order_id + '</span></div>' +
                        '<div class="info-row"><span class="info-label">Order Date:</span><span class="info-value">' + order.created_at + '</span></div>' +
                        '<div class="info-row"><span class="info-label">Order Status:</span><span class="info-value"><span class="order-status-badge status-' + order.status + '">' + getStatusLabel(order.status) + '</span></span></div>' +
                        '<div class="info-row"><span class="info-label">' + (order.buyer_name ? 'Seller' : 'Customer') + ':</span><span class="info-value">' + escapeHtml(order.other_party_name) + '</span></div>' +
                        (order.shipping_address ? '<div class="info-row"><span class="info-label">Shipping Address:</span><span class="info-value">' + escapeHtml(order.shipping_address) + '</span></div>' : '') +
                    '</div>' +
                    '<h4>Order Items</h4>' +
                    '<div class="order-items-list">' + (itemsHtml || '<p>No items found.</p>') + '</div>' +
                    '<div class="order-total-section">' +
                        '<div class="total-row"><span>Subtotal:</span><span>R ' + parseFloat(order.subtotal || 0).toFixed(2) + '</span></div>' +
                        '<div class="total-row"><span>Delivery Fee:</span><span>R ' + parseFloat(order.delivery_fee || 0).toFixed(2) + '</span></div>' +
                        '<div class="total-row grand-total"><span>Total:</span><span>R ' + parseFloat(order.total || 0).toFixed(2) + '</span></div>' +
                    '</div>' +
                    paymentHtml
                );

                var actionButtons = '';

                if (userRole === 'seller') {
                    if (order.status === 'pending') {
                        actionButtons = '<button class="process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\'); closeOrderModal();">Process Order</button>';
                        actionButtons += '<button class="cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\'); closeOrderModal();">Cancel Order</button>';
                    } else if (order.status === 'processing') {
                        actionButtons = '<button class="ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\'); closeOrderModal();">Mark as Shipped</button>';
                    } else if (order.status === 'shipped') {
                        actionButtons = '<button class="complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\'); closeOrderModal();">Mark as Completed</button>';
                    }
                }

                if (userRole === 'admin') {
                    if (order.status === 'pending') {
                        actionButtons = '<button class="process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\'); closeOrderModal();">Process Order</button>';
                    } else if (order.status === 'processing') {
                        actionButtons = '<button class="ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\'); closeOrderModal();">Mark as Shipped</button>';
                    } else if (order.status === 'shipped') {
                        actionButtons = '<button class="complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\'); closeOrderModal();">Mark as Completed</button>';
                    }
                    if (order.status === 'pending' || order.status === 'processing') {
                        actionButtons += '<button class="cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\'); closeOrderModal();">Cancel Order</button>';
                    }
                }

                $modalFooter.html(actionButtons);
            } else {
                $modalBody.html('<p class="error">Unable to load order details.</p>');
            }
        },
        error: function() {
            $modalBody.html('<p class="error">Error loading order details.</p>');
        }
    });
}

function closeOrderModal() {
    $('#orderModal').removeClass('active');
}

// ============================================================
// ORDER STATUS UPDATE
// ============================================================

function updateOrderStatus(orderId, newStatus) {
    var confirmMsg = 'Are you sure you want to ' + newStatus + ' this order?';
    if (newStatus == 'cancelled') {
        confirmMsg = 'Are you sure you want to cancel this order? This action cannot be undone.';
    }

    if (confirm(confirmMsg)) {
        $.ajax({
            url: baseUrl + 'php/endpoints/orders/update-order-status.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ order_id: orderId, status: newStatus }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast(data.message || 'Order status updated successfully!');
                    if (typeof loadBuyerOrders === 'function') loadBuyerOrders();
                    else if (typeof loadSellerOrders === 'function') loadSellerOrders();
                    else if (typeof window.loadBuyerOrders === 'function') window.loadBuyerOrders();
                    else if (typeof window.loadSellerOrders === 'function') window.loadSellerOrders();
                    else setTimeout(function() { location.reload(); }, 1500);
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Something went wrong. Please try again.');
            }
        });
    }
}

// ============================================================
// ORDER LISTING (for order pages)
// ============================================================

function loadOrders(endpoint, $container, $pagination, page, status, search, userRole, onPageChange) {
    $container.html('<tr><td colspan="8"><div class="loading-spinner">Loading orders...</div></td></tr>');

    $.ajax({
        url: baseUrl + endpoint,
        type: 'GET',
        dataType: 'json',
        data: { page: page, status: status, search: search, type: userRole },
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                renderOrdersTable(data.orders, $container, userRole);
                if (typeof renderPagination === 'function') {
                    renderPagination($pagination, page, data.total_pages, function(newPage) {
                        if (typeof onPageChange === 'function') onPageChange(newPage);
                    });
                }
            } else {
                showOrdersEmptyState($container, status, search, userRole);
                $pagination.empty();
            }
        },
        error: function() {
            $container.html('<tr><td colspan="8"><div class="error-cell">Failed to load orders. Try refreshing.</div></td></tr>');
        }
    });
}

function renderOrdersTable(orders, $container, userRole) {
    $container.empty();

    for (var i = 0; i < orders.length; i++) {
        var order = orders[i];
        var statusClass = getOrderStatusClass(order.status);
        var statusLabel = getStatusLabel(order.status);

        var buttons = '<div class="action-buttons">';
        buttons += '<button class="action-btn view-btn" onclick="openOrderModal(' + order.order_id + ')">View</button>';

        if (userRole === 'seller') {
            if (order.status === 'pending') {
                buttons += '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process</button>';
            } else if (order.status === 'processing') {
                buttons += '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Ship</button>';
            } else if (order.status === 'shipped') {
                buttons += '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Complete</button>';
            }
            if (order.status === 'pending' || order.status === 'processing') {
                buttons += '<button class="action-btn cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\')">Cancel</button>';
            }
        }

        if (userRole === 'admin') {
            if (order.status === 'pending') {
                buttons += '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process</button>';
            } else if (order.status === 'processing') {
                buttons += '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Ship</button>';
            } else if (order.status === 'shipped') {
                buttons += '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Complete</button>';
            }
            if (order.status === 'pending' || order.status === 'processing') {
                buttons += '<button class="action-btn cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\')">Cancel</button>';
            }
        }

        buttons += '</div>';

        var row = '<tr>';
        row += '<td data-label="Order #">#' + order.order_id + '</td>';

        if (userRole === 'buyer') {
            row += '<td data-label="Seller">' + escapeHtml(order.seller_name) + '</td>';
        } else if (userRole === 'seller') {
            row += '<td data-label="Customer">' + escapeHtml(order.buyer_name) + '</td>';
        } else if (userRole === 'admin') {
            row += '<td data-label="Customer">' + escapeHtml(order.buyer_name) + '</td>';
            row += '<td data-label="Seller">' + escapeHtml(order.seller_name) + '</td>';
        }

        row += '<td data-label="Items">' + (order.item_count || 0) + '</td>';
        row += '<td data-label="Amount">R ' + parseFloat(order.total_price).toFixed(2) + '</td>';
        row += '<td data-label="Status"><span class="order-status-badge ' + statusClass + '">' + statusLabel + '</span></td>';
        row += '<td data-label="Date">' + escapeHtml(order.created_at) + '</td>';
        row += '<td data-label="Actions">' + buttons + '</td>';
        row += '</tr>';

        $container.append(row);
    }
}

function showOrdersEmptyState($container, status, search, userRole) {
    var title = '';
    var message = '';

    if (status !== 'all') {
        title = 'No ' + capitalizeFirst(status) + ' Orders';
        if (userRole === 'buyer') message = 'You don\'t have any ' + status + ' orders.';
        else if (userRole === 'seller') message = 'No ' + status + ' orders from customers.';
        else message = 'No ' + status + ' orders found.';
    } else if (search !== '') {
        title = 'No Orders Found';
        message = 'No orders matching "' + escapeHtml(search) + '" were found.';
    } else {
        title = 'No Orders Yet';
        if (userRole === 'buyer') message = 'You haven\'t placed any orders yet.';
        else if (userRole === 'seller') message = 'You haven\'t received any orders yet.';
        else message = 'No orders have been placed on the platform yet.';
    }

    var resetHtml = '';
    if (search !== '' || status !== 'all') {
        resetHtml = '<button class="reset-filters-btn view-all-btn" style="background: var(--primary-color); color: white; padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; margin-top: 16px;">Clear Filters</button>';
    }

    $container.html(
        '<tr><td colspan="8" style="text-align: center; padding: 60px;">' +
        '<div class="empty-state">' +
        '<img src="' + baseUrl + 'images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="No orders" style="opacity: 0.4;">' +
        '<h3>' + escapeHtml(title) + '</h3>' +
        '<p>' + escapeHtml(message) + '</p>' +
        resetHtml +
        '</div>' +
        '</td></tr>'
    );
}

// ============================================================
// DOCUMENT REVIEW MODAL (Admin)
// ============================================================

var currentReviewUserId = null;

function reviewDocuments(userId) {
    currentReviewUserId = userId;

    $('#docPreview').html('<div class="loading-spinner">Loading document...</div>');
    $('#docModal').addClass('active');

    $.ajax({
        url: baseUrl + 'php/endpoints/users/get-verification-document.php',
        type: 'GET',
        data: { user_id: userId },
        dataType: 'json',
        success: function(data) {
            if (data.success && data.has_document) {
                var ext = data.document_path.split('.').pop().toLowerCase();
                var docUrl = baseUrl + data.document_path;
                var docType = data.document_type || 'Document';
                var uploadedAt = data.uploaded_at || 'Unknown date';

                var html = '<div class="doc-preview-container">';
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                    html += '<img src="' + docUrl + '" alt="Verification Document">';
                } else {
                    html += '<iframe src="' + docUrl + '"></iframe>';
                }
                html += '</div>';
                html += '<div class="doc-info">';
                html += '<p><strong>Document Type:</strong> ' + capitalizeFirst(docType.replace(/_/g, ' ')) + '</p>';
                html += '<p><strong>Uploaded:</strong> ' + uploadedAt + '</p>';
                html += '</div>';

                $('#docPreview').html(html);
                $('#docModalFooter').show();
            } else {
                $('#docPreview').html('<p style="text-align:center; color: var(--warning);">No document uploaded by this seller.</p>');
                $('#docModalFooter').hide();
            }
        },
        error: function() {
            $('#docPreview').html('<p style="text-align:center; color: var(--error);">Error loading document.</p>');
            $('#docModalFooter').hide();
        }
    });
}

function closeDocModal() {
    $('#docModal').removeClass('active');
    currentReviewUserId = null;
}

function rejectSeller() {
    if (!currentReviewUserId) return;
    if (confirm('Reject this seller\'s verification document?')) {
        $.ajax({
            url: baseUrl + 'php/endpoints/users/update-user-verification.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ user_id: currentReviewUserId, verify: false }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast('Document rejected.');
                    closeDocModal();
                    if (typeof loadUsers === 'function') loadUsers();
                    else location.reload();
                } else {
                    showErrorToast(data.message);
                }
            }
        });
    }
}