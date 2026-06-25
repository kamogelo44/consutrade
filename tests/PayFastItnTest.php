<?php

/**
 * ConsuTrade - PayFast ITN Test
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/TestPayFastService.php';

class PayFastItnTest extends TestCase
{
    public function testItnSuccessfullyProcessesOrder()
    {
        global $userRepo, $productRepo, $orderRepo, $cartRepo, $transactionRepo, $conn;

        // Use unique emails with timestamp
        $timestamp = time();
        $buyerEmail = 'buyer_' . $timestamp . '@itntest.com';
        $sellerEmail = 'seller_' . $timestamp . '@itntest.com';

        // Create test buyer
        $buyerId = $userRepo->create([
            'full_name' => 'Test Buyer',
            'email' => $buyerEmail,
            'phone' => '0712345678',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'buyer'
        ]);
        $this->assertGreaterThan(0, $buyerId);

        // Create test seller
        $sellerId = $userRepo->create([
            'full_name' => 'Test Seller',
            'email' => $sellerEmail,
            'phone' => '0823456789',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'seller'
        ]);
        $this->assertGreaterThan(0, $sellerId);

        // Create test product
        $productData = [
            'seller_id' => $sellerId,
            'category_id' => 1,
            'title' => 'Test Product for ITN',
            'description' => 'Product used to test PayFast ITN',
            'price' => 100.00,
            'stock_quantity' => 10,
            'condition' => 'New',
            'location' => 'Test Location',
            'image_url' => 'test.jpg',
            'status' => 'active'
        ];
        $product = new Product($productData);
        $productId = $productRepo->create($product);
        $this->assertGreaterThan(0, $productId);

        // Add to cart
        $cartRepo->createItem($buyerId, $productId, 1);

        // Generate payment ID that will match what the ITN sends
        $paymentId = time() . '_' . $buyerId;

        // Create pending order with matching payment_id
        $orderId = $this->createPendingOrder($buyerId, $sellerId, $productId, 100.00, $paymentId);
        $this->assertGreaterThan(0, $orderId);

        // Verify order is pending
        $order = $orderRepo->findById($orderId, $buyerId, 'buyer');
        $this->assertEquals('pending', $order['status']);

        // Simulate ITN
        $testService = new TestPayFastService(
            $conn,
            $orderRepo,
            $productRepo,
            $cartRepo,
            $transactionRepo
        );

        $postData = [
            'm_payment_id' => $paymentId,
            'pf_payment_id' => 'PF-TEST-' . time(),
            'payment_status' => 'COMPLETE',
            'amount' => '100.00'
        ];

        $result = $testService->handleItn($postData);

        // Assertions
        $this->assertTrue($result['success'], 'ITN should succeed: ' . ($result['message'] ?? ''));
        $this->assertEquals('Payment processed successfully', $result['message']);

        // Check order status
        $updatedOrder = $orderRepo->findById($orderId, $buyerId, 'buyer');
        $this->assertEquals('processing', $updatedOrder['status']);

        // Check transaction was created
        $updatedTransaction = $transactionRepo->findByOrderId($orderId);
        $this->assertNotNull($updatedTransaction);
        $this->assertEquals('completed', $updatedTransaction->getStatus());

        // Check cart was cleared
        $cartAfter = $cartRepo->findByUser($buyerId);
        $this->assertCount(0, $cartAfter);

        // Check stock was decreased
        $updatedProduct = $productRepo->findById($productId);
        $this->assertEquals(9, $updatedProduct->getStockQuantity());
    }

    public function testItnRejectsInvalidOrder()
    {
        global $conn, $orderRepo, $productRepo, $cartRepo, $transactionRepo;

        $testService = new TestPayFastService(
            $conn,
            $orderRepo,
            $productRepo,
            $cartRepo,
            $transactionRepo
        );

        $postData = [
            'm_payment_id' => '999999_1',
            'pf_payment_id' => 'PF-TEST-' . time(),
            'payment_status' => 'COMPLETE',
            'amount' => '100.00'
        ];

        $result = $testService->handleItn($postData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No pending orders found', $result['message']);
    }

    public function testItnRejectsNonCompletePayment()
    {
        global $conn, $userRepo, $productRepo, $orderRepo, $cartRepo, $transactionRepo;

        $timestamp = time();
        $buyerEmail = 'buyer2_' . $timestamp . '@itntest.com';
        $sellerEmail = 'seller2_' . $timestamp . '@itntest.com';

        // Create test buyer
        $buyerId = $userRepo->create([
            'full_name' => 'Test Buyer 2',
            'email' => $buyerEmail,
            'phone' => '0712345678',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'buyer'
        ]);

        $sellerId = $userRepo->create([
            'full_name' => 'Test Seller 2',
            'email' => $sellerEmail,
            'phone' => '0823456789',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'seller'
        ]);

        $productData = [
            'seller_id' => $sellerId,
            'category_id' => 1,
            'title' => 'Test Product 2',
            'description' => 'Test description',
            'price' => 50.00,
            'stock_quantity' => 5,
            'condition' => 'New',
            'location' => 'Test',
            'image_url' => 'test.jpg',
            'status' => 'active'
        ];
        $product = new Product($productData);
        $productId = $productRepo->create($product);

        $paymentId = time() . '_' . $buyerId;
        $orderId = $this->createPendingOrder($buyerId, $sellerId, $productId, 50.00, $paymentId);

        $testService = new TestPayFastService(
            $conn,
            $orderRepo,
            $productRepo,
            $cartRepo,
            $transactionRepo
        );

        $postData = [
            'm_payment_id' => $paymentId,
            'pf_payment_id' => 'PF-TEST-' . time(),
            'payment_status' => 'CANCELLED',
            'amount' => '50.00'
        ];

        $result = $testService->handleItn($postData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Payment not complete', $result['message']);
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================
    private function createPendingOrder($buyerId, $sellerId, $productId, $total, $paymentId)
    {
        global $conn, $productRepo;

        // Insert order
        $sql = "INSERT INTO orders (buyer_id, seller_id, total_price, status, payment_id, created_at)
            VALUES (?, ?, ?, 'pending', ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iids', $buyerId, $sellerId, $total, $paymentId);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();

        // Insert order item
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, 1, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iid', $orderId, $productId, $total);
        $stmt->execute();
        $stmt->close();

        // Decrease stock (simulate real order placement)
        $quantity = 1; // as used above
        $productRepo->decreaseStock($productId, $quantity);

        return $orderId;
    }
}
