<?php
/*
 * ConsuTrade - Seller Verification Component
 * 
 * Expected variables: $currentUser, $baseUrl
 */

$verificationObj = $currentUser->getVerification();
$hasDocument = false;
$documentType = '';
$documentVerified = false;
$documentPath = '';
$submittedAt = '';
$reviewedAt = '';

if ($verificationObj) {
    $hasDocument = true;
    $documentType = $verificationObj->getDocumentType();
    $documentVerified = $verificationObj->isVerified();
    $documentPath = $verificationObj->getDocumentPath();
    $submittedAt = $verificationObj->getSubmittedAt();
    $reviewedAt = $verificationObj->getReviewedAt();
}

$isEmailVerified = $currentUser->isEmailVerified();
?>

<div class="verification-card">
    <div class="card-header">Verification Status</div>

    <?php if (!$isEmailVerified): ?>
        <div class="verification-state no-document">
            <div class="state-header">
                <h3>
                    <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" alt="Email">
                    Email Verification Required
                </h3>
                <span class="state-badge not-verified">
                    <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" alt="Not Verified">
                    Not Verified
                </span>
            </div>
            <div class="state-content">
                <p class="text-muted">Verify your email address before submitting seller verification documents.</p>
                <button class="upload-btn" id="resendVerificationBtn" data-email="<?php echo $currentUser->getEmail(); ?>">
                    Resend Verification Email
                </button>
                <p class="help-text" id="resendMessage" style="display:none;"></p>
            </div>
        </div>

    <?php elseif ($hasDocument && $documentVerified): ?>
        <div class="verification-state verified">
            <div class="state-header">
                <h3>
                    <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" alt="Document">
                    Verification Document
                </h3>
                <span class="state-badge verified">
                    <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" alt="Verified">
                    Verified
                </span>
            </div>
            <div class="state-content">
                <p class="info-text"><strong>Document:</strong> <?php echo ucfirst(str_replace('_', ' ', $documentType)); ?></p>
                <?php if (!empty($documentPath)): ?>
                    <div class="document-file">
                        <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="24" height="24" alt="Document">
                        <a href="<?php echo $baseUrl . $documentPath; ?>" target="_blank" class="file-link">
                            <?php echo htmlspecialchars(basename($documentPath)); ?>
                        </a>
                    </div>
                    <div class="document-actions">
                        <button class="preview-btn" onclick="window.open('<?php echo $baseUrl . $documentPath; ?>', '_blank')">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" alt="View"> View Document
                        </button>
                    </div>
                <?php endif; ?>
                <div class="verification-status">
                    <p class="text-success"><strong>Status:</strong> Verified</p>
                    <p><strong>Verified on:</strong> <?php echo date('d M Y, h:i A', strtotime($reviewedAt ?? 'now')); ?></p>
                    <p class="text-success">Your seller account is fully verified.</p>
                </div>
            </div>
        </div>

    <?php elseif ($hasDocument && !$documentVerified): ?>
        <div class="verification-state pending">
            <div class="state-header">
                <h3>
                    <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" alt="Document">
                    Verification Document
                </h3>
                <span class="state-badge pending">
                    <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" alt="Pending">
                    Pending Review
                </span>
            </div>
            <div class="state-content">
                <p class="info-text"><strong>Document:</strong> <?php echo ucfirst(str_replace('_', ' ', $documentType)); ?></p>
                <?php if (!empty($documentPath)): ?>
                    <div class="document-file">
                        <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="24" height="24" alt="Document">
                        <a href="<?php echo $baseUrl . $documentPath; ?>" target="_blank" class="file-link">
                            <?php echo htmlspecialchars(basename($documentPath)); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="document-actions">
                    <?php if (!empty($documentPath)): ?>
                        <button class="preview-btn" onclick="window.open('<?php echo $baseUrl . $documentPath; ?>', '_blank')">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" alt="View"> View
                        </button>
                    <?php endif; ?>
                    <button class="replace-btn" onclick="document.getElementById('replaceDocInput').click()">
                        <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Replace"> Replace
                    </button>
                    <button class="delete-btn" onclick="deleteDocument()">
                        <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" alt="Delete"> Delete
                    </button>
                </div>
                <div class="verification-status">
                    <p class="text-warning"><strong>Status:</strong> Pending Review</p>
                    <p><strong>Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($submittedAt)); ?></p>
                    <p class="text-info">Your document is being reviewed by an admin.</p>
                </div>
            </div>
        </div>
        <input type="file" id="replaceDocInput" accept="image/jpeg,image/jpg,image/png,application/pdf" style="display:none;">

    <?php else: ?>
        <div class="verification-state no-document">
            <div class="state-header">
                <h3>
                    <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" alt="Document">
                    Verification Document
                </h3>
                <span class="state-badge not-verified">
                    <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" alt="Not Verified">
                    Not Verified
                </span>
            </div>
            <div class="state-content">
                <p class="text-muted">Upload your ID and proof of address to get verified.</p>
                <div class="upload-area">
                    <div class="drop-zone" id="dropZone">
                        <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="32" height="32" class="upload-icon" alt="Upload">
                        <p><strong>Drop your document here or click to upload</strong></p>
                        <p class="file-types">JPG, PNG, PDF &mdash; Max 5MB</p>
                        <p class="file-name-display" id="fileNameDisplay"></p>
                    </div>
                    <form id="verificationForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="documentType">Document Type</label>
                            <select name="document_type" id="documentType" required>
                                <option value="">Select Document Type</option>
                                <option value="id">South African ID Document</option>
                                <option value="proof_address">Proof of Address</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: none;">
                            <input type="file" name="document" id="verificationDoc" accept="image/jpeg,image/jpg,image/png,application/pdf" required>
                        </div>
                        <button type="submit" class="upload-btn" id="uploadBtn">
                            <img src="<?php echo $baseUrl; ?>images/icons/upload-svgrepo-com.svg" alt="Upload"> Upload Document
                        </button>
                    </form>
                    <p class="help-text">Your document will be reviewed by an admin within 24 to 48 hours.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div id="verificationMessage"></div>
</div>