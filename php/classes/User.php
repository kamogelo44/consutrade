<?php
/**
 * ConsuTrade - User
 *
 * Abstract base class for all user types (Buyer, Seller, Admin).
 * Contains shared properties and methods common to every user.
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

abstract class User
{
    /** @var int */
    protected $userId;

    /** @var string */
    protected $fullName;

    /** @var string */
    protected $email;

    /** @var string */
    protected $password;

    /** @var string */
    protected $phone;

    /** @var string */
    protected $location;

    /** @var string */
    protected $role;

    /** @var bool */
    protected $idVerified;

    /** @var string */
    protected $profileImage;

    /** @var string */
    protected $createdAt;

    /**
     * Constructor.
     *
     * @param array $data Associative array of user data from the database
     */
    public function __construct(array $data)
    {
        $this->userId       = (int) ($data['user_id']       ?? 0);
        $this->fullName     = (string) ($data['full_name']   ?? '');
        $this->email        = (string) ($data['email']       ?? '');
        $this->password     = (string) ($data['password']    ?? '');
        $this->phone        = (string) ($data['phone']       ?? '');
        $this->location     = (string) ($data['location']    ?? '');
        $this->role         = (string) ($data['role']        ?? 'buyer');
        $this->idVerified   = (bool) ($data['id_verified']   ?? false);
        $this->profileImage = (string) ($data['profile_image'] ?? '');
        $this->createdAt    = (string) ($data['created_at']   ?? '');
    }

    /**
     * Returns the user ID.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Returns the user's full name.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Returns the user's email address.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Returns the user's role.
     *
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Checks whether the user's identity has been verified.
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->idVerified;
    }

    /**
     * Returns the user's creation date.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Returns the user's location.
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Returns the user's phone number.
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Returns the user's profile image path.
     *
     * @return string
     */
    public function getProfileImage(): string
    {
        return $this->profileImage;
    }

    /**
     * Returns the user's display name for templates.
     *
     * @return string
     */
    abstract public function getDisplayName(): string;
}