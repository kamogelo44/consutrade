<?php
/**
 * ConsuTrade - SellerVerification
 *
 * Domain class representing a seller's verification status and trust score.
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

class SellerVerification
{
    /** @var int */
    private $verificationId;

    /** @var int */
    private $sellerId;

    /** @var bool */
    private $emailVerified;

    /** @var bool */
    private $phoneVerified;

    /** @var bool */
    private $documentVerified;

    /** @var bool */
    private $locationVerified;

    /** @var string */
    private $documentPath;

    /** @var string */
    private $documentType;

    /** @var int */
    private $verificationScore;

    /** @var bool */
    private $autoVerified;

    /** @var string */
    private $verifiedAt;

    /** @var string */
    private $emailVerifiedAt;

    /** @var string */
    private $phoneVerifiedAt;

    /** @var string */
    private $lastCheck;

    /**
     * Constructor.
     *
     * @param array $data Associative array of verification data from the database
     */
    public function __construct(array $data)
    {
        $this->verificationId   = (int) ($data['verification_id']    ?? 0);
        $this->sellerId         = (int) ($data['seller_id']          ?? 0);
        $this->emailVerified    = (bool) ($data['email_verified']    ?? false);
        $this->phoneVerified    = (bool) ($data['phone_verified']    ?? false);
        $this->documentVerified = (bool) ($data['document_verified'] ?? false);
        $this->locationVerified = (bool) ($data['location_verified'] ?? false);
        $this->documentPath     = (string) ($data['document_path']   ?? '');
        $this->documentType     = (string) ($data['document_type']   ?? '');
        $this->verificationScore = (int) ($data['verification_score'] ?? 0);
        $this->autoVerified     = (bool) ($data['auto_verified']     ?? false);
        $this->verifiedAt       = (string) ($data['verified_at']     ?? '');
        $this->emailVerifiedAt  = (string) ($data['email_verified_at'] ?? '');
        $this->phoneVerifiedAt  = (string) ($data['phone_verified_at'] ?? '');
        $this->lastCheck        = (string) ($data['last_check']       ?? '');
    }

    /**
     * Returns the verification score (0–100).
     *
     * @return int
     */
    public function getScore(): int
    {
        return $this->verificationScore;
    }

    /**
     * Checks whether all verification checks have passed.
     * Email, phone, document, and location must all be verified.
     *
     * @return bool
     */
    public function isFullyVerified(): bool
    {
        return $this->emailVerified
            && $this->phoneVerified
            && $this->documentVerified
            && $this->locationVerified;
    }

    /**
     * Returns a breakdown of each verification check as an associative array.
     *
     * @return array
     */
    public function getStatus(): array
    {
        return [
            'email_verified'    => $this->emailVerified,
            'phone_verified'    => $this->phoneVerified,
            'document_verified' => $this->documentVerified,
            'location_verified' => $this->locationVerified,
            'auto_verified'     => $this->autoVerified,
            'score'             => $this->verificationScore,
            'fully_verified'    => $this->isFullyVerified(),
        ];
    }
}