<?php

/**
 * ConsuTrade - Buyer
 *
 * Domain class representing a buyer user type.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class Buyer extends User
{
    /** @var CartRepository Cart repository instance */
    private $cartRepo;

    /** @var OrderRepository Order repository instance */
    private $orderRepo;

    /**
     * Constructor.
     *
     * @param array $data User data
     * @param CartRepository|null $cartRepo Cart repository
     * @param OrderRepository|null $orderRepo Order repository
     */
    public function __construct($data, $cartRepo = null, $orderRepo = null)
    {
        parent::__construct($data);
        $this->cartRepo = $cartRepo;
        $this->orderRepo = $orderRepo;
    }

    /**
     * Get the buyer's display name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->fullName;
    }

    /**
     * Get cart items for this buyer.
     *
     * @return array
     */
    public function getCartItems()
    {
        if (!$this->cartRepo) {
            return [];
        }
        return $this->cartRepo->getCartItems($this->userId);
    }

    /**
     * Get cart count for this buyer.
     *
     * @return int
     */
    public function getCartCount()
    {
        if (!$this->cartRepo) {
            return 0;
        }
        return $this->cartRepo->getCartCount($this->userId);
    }

    /**
     * Get orders for this buyer.
     *
     * @param string $filter Status filter
     * @param string $search Search term
     * @return array
     */
    public function getOrders($filter = 'all', $search = '')
    {
        if (!$this->orderRepo) {
            return [];
        }
        return $this->orderRepo->getBuyerOrders($this->userId, $filter, $search);
    }

    /**
     * Get buyer statistics.
     *
     * @return array
     */
    public function getStats()
    {
        if (!$this->orderRepo) {
            return [
                'total_orders' => 0,
                'total_spent' => 0,
                'pending_orders' => 0,
                'completed_orders' => 0
            ];
        }
        return $this->orderRepo->getBuyerStats($this->userId);
    }
}
