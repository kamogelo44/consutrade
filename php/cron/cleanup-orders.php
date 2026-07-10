<?php
/*
 * ConsuTrade - Order Cleanup Cron Job
 * Run daily via cron: 0 3 * * * php /path/to/cleanup-orders.php
 */

require_once dirname(__DIR__, 2) . '/init.php';

$count = $orderRepo->cleanupOldOrders();
