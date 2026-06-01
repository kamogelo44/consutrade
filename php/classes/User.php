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

    /** @var string */
    protected $status;

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
        $this->status       = (string) ($data['status']       ?? 'active');
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
     * Returns the user's password hash.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
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
     * Returns the user's account status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Checks if the user can log in (account is active).
     *
     * @return bool
     */
    public function canLogin(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get profile image URL with fallback to default
     *
     * @return string
     */
    public function getProfileImageUrl(): string
    {
        $baseUrl = getBaseUrl();

        if (!empty($this->profileImage)) {
            // Check if file exists using dynamic path detection
            $fullPath = $this->getFullPath($this->profileImage);
            if (file_exists($fullPath)) {
                return $baseUrl . $this->profileImage;
            }
        }

        return $baseUrl . 'images/icons/profile-svgrepo-com.svg';
    }

    /**
     * Helper to get full system path for a file
     *
     * @param string $filePath Relative file path
     * @return string
     */
    private function getFullPath(string $filePath): string
    {
        $basePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/',
            $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/',
            dirname(__DIR__, 2) . '/',
            __DIR__ . '/../../',
        ];

        foreach ($basePaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . ltrim($filePath, '/');
            if (file_exists(dirname($fullPath))) {
                return $fullPath;
            }
        }

        return $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($filePath, '/');
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
     * Returns serializable data for session storage.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'user_id' => $this->userId,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,
            'role' => $this->role,
            'id_verified' => $this->idVerified,
            'profile_image' => $this->profileImage,
            'created_at' => $this->createdAt,
            'status' => $this->status
        ];
    }

    /**
     * Restores object from serialized data.
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->userId = $data['user_id'];
        $this->fullName = $data['full_name'];
        $this->email = $data['email'];
        $this->phone = $data['phone'];
        $this->location = $data['location'];
        $this->role = $data['role'];
        $this->idVerified = $data['id_verified'];
        $this->profileImage = $data['profile_image'];
        $this->createdAt = $data['created_at'];
        $this->status = $data['status'];
    }

    /**
     * Returns the user's display name for templates.
     *
     * @return string
     */
    abstract public function getDisplayName(): string;
}
