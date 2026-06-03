<?php
require_once dirname(__DIR__, 2) . '/init.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $orderRepo = new OrderRepository($db);
    $productRepo = new ProductRepository($db);
    $cartRepo = new CartRepository($db);
    $transactionRepo = new TransactionRepository($db);

    $payfastService = new PayFastService(
        $db,
        $orderRepo,
        $productRepo,
        $cartRepo,
        $transactionRepo
    );

    $result = $payfastService->handleItn($_POST);

    if ($result['success']) {
        http_response_code(200);
        echo "OK";
    } else {
        http_response_code(400);
        echo $result['message'];
    }
} catch (Exception $e) {
    error_log("PayFast Error: " . $e->getMessage());
    http_response_code(500);
    echo "Error";
}
