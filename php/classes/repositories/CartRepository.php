<?php

/**
 * ConsuTrade - CartRepository
 *
 * Handles cart data access operations only.
 * All business logic moved to CartService.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class CartRepository
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ============================================================
    // CREATE (C)
    // ============================================================

    public function createItem(int $userId, int $productId, int $quantity): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iii', $userId, $productId, $quantity);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // READ (R)
    // ============================================================

    public function findItemByProduct(int $userId, int $productId): ?array
    {
        $sql = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }

        $stmt->close();
        return null;
    }

    /**
     * Get product ID from a cart item.
     * Used to verify ownership before updating quantity.
     */
    public function findProductIdByCartId(int $cartId, int $userId): ?int
    {
        $stmt = $this->db->prepare("SELECT product_id FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $cartId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['product_id'] : null;
    }

    public function findByUser(int $userId): array
    {
        $sql = "SELECT c.cart_id, c.quantity, c.added_at,
            p.product_id, p.title, p.price, p.image_url, p.seller_id, p.stock_quantity,
            u.full_name as seller_name, u.id_verified as is_verified
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            JOIN users u ON p.seller_id = u.user_id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $row['subtotal'] = $row['price'] * $row['quantity'];
            $items[] = $row;
        }
        $stmt->close();

        return $items;
    }

    public function countItems(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int) ($row['total'] ?? 0);
        $stmt->close();
        return $count;
    }

    public function getCartSubtotal(int $userId): float
    {
        $sql = "SELECT SUM(p.price * c.quantity) as subtotal
                FROM cart c
                JOIN products p ON c.product_id = p.product_id
                WHERE c.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (float) ($row['subtotal'] ?? 0);
    }

    // ============================================================
    // UPDATE (U)
    // ============================================================

    public function updateQuantity(int $cartId, int $userId, int $qty): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?"
        );
        $stmt->bind_param('iii', $qty, $cartId, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // DELETE (D)
    // ============================================================

    public function deleteItemByProduct(int $productId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM cart WHERE product_id = ? AND user_id = ?"
        );
        $stmt->bind_param('ii', $productId, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteAllByUser(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
