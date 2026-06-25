<?php

/**
 * TestPayFastService - Extends PayFastService to bypass signature checks
 * Used only for testing the ITN flow
 */

// Make sure we can find the real PayFastService
require_once __DIR__ . '/../php/classes/PayFastService.php';

class TestPayFastService extends PayFastService
{
    /**
     * Override handleItn to skip signature validation
     * This allows us to test the payment processing logic directly
     */
    public function handleItn(array $postData): array
    {
        // Skip verifySignature and validateWithPayFast
        return $this->processPayment($postData);
    }

    /**
     * Make processPayment accessible to tests
     */
    protected function processPayment(array $data): array
    {
        return parent::processPayment($data);
    }
}
