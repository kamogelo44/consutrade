<?php

/**
 * ConsuTrade - Buyer
 *
 * Represents a buyer user. Extends User with cart, ordering, and review capabilities.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 */

class Buyer extends User
{
    /** @var int */
    private $cartCount;

    /** @var CartRepository */
    private $cartRepo;

    /** @var OrderRepository */
    private $orderRepo;

    /**
     * Constructor.
     *
     * @param array           $data     User data from database
     * @param CartRepository  $cartRepo
     * @param OrderRepository $orderRepo
     */
    public function __construct(array $data, CartRepository $cartRepo, OrderRepository $orderRepo)
    {
        parent::__construct($data);
        $this->cartRepo = $cartRepo;
        $this->orderRepo = $orderRepo;
        $this->cartCount = 0;
    }

    /**
     * Ensure repositories are available (fixes unserialized objects)
     */
    private function ensureRepositories(): void
    {
        if ($this->orderRepo === null && isset($GLOBALS['orderRepo'])) {
            $this->orderRepo = $GLOBALS['orderRepo'];
        }
        if ($this->cartRepo === null && isset($GLOBALS['cartRepo'])) {
            $this->cartRepo = $GLOBALS['cartRepo'];
        }
    }

    public function getDisplayName(): string
    {
        return $this->fullName;
    }

    public function getCartCount(): int
    {
        return $this->cartCount;
    }

    public function refreshCartCount(): void
    {
        $this->ensureRepositories();
        $this->cartCount = $this->cartRepo->getCartCount($this->userId);
    }

    public function placeOrder(array $cartItems): array
    {
        $this->ensureRepositories();
        $result = $this->cartRepo->processCheckout($this->userId, $cartItems);

        if ($result['success']) {
            $this->cartCount = 0;
        }

        return $result;
    }

    public function cancelOrder(int $orderId): bool
    {
        $this->ensureRepositories();
        return $this->orderRepo->cancelBuyerOrder($orderId, $this->userId);
    }

    public function submitReview(array $data): bool
    {
        return true;
    }

    public function getOrders(string $filter = 'all', string $search = ''): array
    {
        $this->ensureRepositories();
        return $this->orderRepo->getBuyerOrders($this->userId, $filter, $search);
    }

    public function getOrderDetails(int $orderId): ?array
    {
        $this->ensureRepositories();
        return $this->orderRepo->getOrderDetails($orderId, $this->userId, 'buyer');
    }
}
