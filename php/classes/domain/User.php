<?php

/**
 * ConsuTrade - User
 *
 * Abstract base class for all user types (Buyer, Seller, Admin).
 * Contains shared properties and methods common to every user.
 *
 * @author Kamogelo Phale
 * @version 3.1.0
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

    /** @var array */
    protected $roles = [];

    /** @var bool */
    protected $idVerified;

    /** @var bool */
    protected $emailVerified;

    /** @var string */
    protected $profileImage;

    /** @var string */
    protected $createdAt;

    /** @var string */
    protected $status;

    /** @var bool */
    protected bool $isDemo;

    /** @var string|null */
    protected $lastActive;

    /**
     * Constructor.
     *
     * @param array $data Associative array of user data from the database
     */
    public function __construct($data)
    {
        $this->userId       = (int) ($data['user_id']       ?? 0);
        $this->fullName     = (string) ($data['full_name']   ?? '');
        $this->email        = (string) ($data['email']       ?? '');
        $this->password     = (string) ($data['password']    ?? '');
        $this->phone        = (string) ($data['phone']       ?? '');
        $this->location     = (string) ($data['location']    ?? '');
        $this->roles        = (array)  ($data['roles']       ?? ['buyer']);
        $this->idVerified   = (bool) ($data['id_verified']   ?? false);
        $this->emailVerified = (bool) ($data['email_verified'] ?? false);
        $this->profileImage = (string) ($data['profile_image'] ?? '');
        $this->createdAt    = (string) ($data['created_at']   ?? '');
        $this->status       = (string) ($data['status']       ?? 'active');
        $this->isDemo = (bool)($data['is_demo'] ?? false);
        $this->lastActive = $data['last_active'] ?? null;
    }

    /**
     * Returns the user ID.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Returns the user's full name.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Returns the user's email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Returns the user's password hash.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the user's roles as array.
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Check if user has a specific role.
     *
     * @param string $role Role to check (admin, seller, buyer)
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    /**
     * Get primary role (first in priority order).
     * Priority: admin > seller > buyer
     *
     * @return string
     */
    public function getPrimaryRole(): string
    {
        $priority = ['admin', 'seller', 'buyer'];
        foreach ($priority as $role) {
            if (in_array($role, $this->roles)) {
                return $role;
            }
        }
        return 'buyer';
    }

    /**
     * Get the user's role (legacy compatibility).
     *
     * @return string
     */
    public function getRole()
    {
        return $this->getPrimaryRole();
    }

    /**
     * Checks whether the user's identity has been verified.
     *
     * @return bool
     */
    public function isVerified()
    {
        return $this->idVerified;
    }

    /**
     * Check if user's email is verified.
     *
     * @return bool
     */
    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    /**
     * Returns the user's account status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Checks if the user can log in (account is active).
     *
     * @return bool
     */
    public function canLogin()
    {
        return $this->status === 'active' && !empty($this->roles);
    }

    /**
     * Get profile image URL with fallback to default
     *
     * @return string
     */
    public function getProfileImageUrl()
    {
        $baseUrl = getBaseUrl();

        if (!empty($this->profileImage)) {
            $fullPath = $this->getFullPath($this->profileImage);
            if (file_exists($fullPath)) {
                return $baseUrl . $this->profileImage;
            }
        }

        return $baseUrl . 'images/icons/profile-svgrepo-com.svg';
    }

    /**
     * Returns the user's last active timestamp.
     *
     * @return string|null
     */
    public function getLastActive(): ?string
    {
        return $this->lastActive;
    }

    /**
     * Checks if the user is currently online (active within last 15 minutes).
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        if (!$this->lastActive) {
            return false;
        }
        return (time() - strtotime($this->lastActive)) < 900;
    }

    /**
     * Helper to get full system path for a file
     *
     * @param string $filePath Relative file path
     * @return string
     */
    private function getFullPath($filePath)
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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Returns the user's location.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Returns the user's phone number.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Returns the user's profile image path.
     *
     * @return string
     */
    public function getProfileImage()
    {
        return $this->profileImage;
    }

    public function isDemo(): bool
    {
        return $this->isDemo;
    }

    /**
     * Returns serializable data for session storage.
     *
     * @return array
     */
    public function __serialize()
    {
        return [
            'user_id' => $this->userId,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,
            'roles' => $this->roles,
            'id_verified' => $this->idVerified,
            'email_verified' => $this->emailVerified,
            'profile_image' => $this->profileImage,
            'created_at' => $this->createdAt,
            'status' => $this->status,
            'is_demo' => $this->isDemo,
            'last_active' => $this->lastActive
        ];
    }

    /**
     * Restores object from serialized data.
     *
     * @param array $data
     * @return void
     */
    public function __unserialize($data)
    {
        $this->userId = $data['user_id'];
        $this->fullName = $data['full_name'];
        $this->email = $data['email'];
        $this->phone = $data['phone'];
        $this->location = $data['location'];
        $this->roles = $data['roles'] ?? ['buyer'];
        $this->idVerified = $data['id_verified'];
        $this->emailVerified = (bool)($data['email_verified'] ?? false);
        $this->profileImage = $data['profile_image'];
        $this->createdAt = $data['created_at'];
        $this->status = $data['status'];
        $this->isDemo = (bool)($data['is_demo'] ?? false);
        $this->lastActive = $data['last_active'] ?? null;
    }

    /**
     * Returns the user's display name for templates.
     *
     * @return string
     */
    abstract public function getDisplayName();
}
