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
 *
 * References:
 * - Pressman, R.S. and Maxim, B.R., 2015. Software Engineering:
 *   A Practitioner's Approach. 8th ed. McGraw-Hill.
 * - Dennis, A., Wixom, B.H. and Tegarden, D., 2015. Systems Analysis
 *   and Design: An Object-Oriented Approach with UML. 6th ed.
 *   John Wiley and Sons.
 * - PHP Group, 2025. Classes and Objects. Available at:
 *   https://www.php.net/manual/en/language.oop5.php
 * - PHP-FIG, 2023. PSR-12: Extended Coding Style. Available at:
 *   https://www.php.fig.org/psr/psr-12/
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
        $this->cartRepo  = $cartRepo;
        $this->orderRepo = $orderRepo;
        $this->cartCount = 0;
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
        $this->cartCount = $this->cartRepo->getCartCount($this->userId);
    }

    public function placeOrder(array $cartItems): array
    {
        $result = $this->cartRepo->processCheckout($this->userId, $cartItems);

        if ($result['success']) {
            $this->cartCount = 0;
        }

        return $result;
    }

    public function cancelOrder(int $orderId): bool
    {
        return $this->orderRepo->cancelBuyerOrder($orderId, $this->userId);
    }

    public function submitReview(array $data): bool
    {
        return true; // Handled by ReviewRepository via endpoint
    }

    public function getOrders(string $filter = 'all', string $search = ''): array
    {
        return $this->orderRepo->getBuyerOrders($this->userId, $filter, $search);
    }

    public function getOrderDetails(int $orderId): ?array
    {
        return $this->orderRepo->getOrderDetails($orderId, $this->userId, 'buyer');
    }
}