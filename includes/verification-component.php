<?php
/*
 * ConsuTrade - Seller Verification Component
 * 
 * Reusable verification UI that adapts to the context it's included in.
 * Can be used in profile.php (seller tab) or seller-profile.php (standalone).
 * 
 * Expected variables from parent:
 *   - $currentUser (User object)
 *   - $baseUrl (string)
 * 
 * This component handles all verification states:
 *   - State 1: No document uploaded
 *   - State 2: Document pending review
 *   - State 3: Document verified
 */

// Get verification data from SellerVerification domain object
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

$isVerified = $currentUser->isVerified();
?>

<div class="verification-card">
    <div class="card-header">
        Verification Status
    </div>

    <?php if ($hasDocument && $documentVerified): ?>
        <!-- STATE 3: VERIFIED -->
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
                <div class="document-info">
                    <p class="info-text"><strong>Document:</strong> <?php echo ucfirst(str_replace('_', ' ', $documentType)); ?></p>

                    <?php if (!empty($documentPath)):
                        $docUrl = $baseUrl . $documentPath;
                        $filename = basename($documentPath);
                    ?>
                        <div class="document-file">
                            <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="24" height="24" alt="Document">
                            <a href="<?php echo $docUrl; ?>" target="_blank" class="file-link">
                                <?php echo htmlspecialchars($filename); ?>
                            </a>
                            <span class="file-size">
                                <?php
                                $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $documentPath;
                                if (file_exists($filePath)) {
                                    $size = filesize($filePath);
                                    if ($size < 1024) echo $size . ' B';
                                    elseif ($size < 1048576) echo round($size / 1024, 1) . ' KB';
                                    else echo round($size / 1048576, 1) . ' MB';
                                } else {
                                    echo 'File not found';
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="document-actions">
                        <?php if (!empty($documentPath)): ?>
                            <button class="preview-btn" onclick="window.open('<?php echo $docUrl; ?>', '_blank')">
                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" alt="View">
                                View Document
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="verification-status">
                    <p class="text-success">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16" height="16" class="status-icon" alt="Verified">
                        <strong>Status:</strong> Verified
                    </p>
                    <p><strong>Verified on:</strong> <?php echo date('d M Y, h:i A', strtotime($reviewedAt ?? 'now')); ?></p>
                    <p class="text-success">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16" height="16" class="status-icon" alt="Verified">
                        Your seller account is fully verified. You can now sell products with a verified badge.
                    </p>
                </div>
            </div>
        </div>

    <?php elseif ($hasDocument && !$documentVerified): ?>
        <!-- STATE 2: PENDING REVIEW -->
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
                <div class="document-info">
                    <p class="info-text"><strong>Document:</strong> <?php echo ucfirst(str_replace('_', ' ', $documentType)); ?></p>

                    <?php if (!empty($documentPath)):
                        $docUrl = $baseUrl . $documentPath;
                        $filename = basename($documentPath);
                    ?>
                        <div class="document-file">
                            <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="24" height="24" alt="Document">
                            <a href="<?php echo $docUrl; ?>" target="_blank" class="file-link">
                                <?php echo htmlspecialchars($filename); ?>
                            </a>
                            <span class="file-size">
                                <?php
                                $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $documentPath;
                                if (file_exists($filePath)) {
                                    $size = filesize($filePath);
                                    if ($size < 1024) echo $size . ' B';
                                    elseif ($size < 1048576) echo round($size / 1024, 1) . ' KB';
                                    else echo round($size / 1048576, 1) . ' MB';
                                } else {
                                    echo 'File not found';
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="document-actions">
                        <?php if (!empty($documentPath)): ?>
                            <button class="preview-btn" onclick="window.open('<?php echo $docUrl; ?>', '_blank')">
                                <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" alt="View">
                                View Document
                            </button>
                        <?php endif; ?>
                        <button class="replace-btn" onclick="document.getElementById('replaceDocInput').click()">
                            <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Replace">
                            Replace
                        </button>
                        <button class="delete-btn" onclick="deleteDocument()">
                            <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" alt="Delete">
                            Delete
                        </button>
                    </div>
                </div>
                <div class="verification-status">
                    <p class="text-warning">
                        <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" width="16" height="16" class="status-icon" alt="Pending">
                        <strong>Status:</strong> Pending Review
                    </p>
                    <p><strong>Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($submittedAt)); ?></p>
                    <p class="text-info">
                        <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" width="16" height="16" class="status-icon" alt="Pending">
                        Your document is being reviewed by an admin. You will be notified once verified.
                    </p>
                </div>
            </div>
        </div>

        <!-- Hidden replace file input -->
        <input type="file" id="replaceDocInput" accept="image/jpeg,image/jpg,image/png,application/pdf" style="display:none;">

    <?php else: ?>
        <!-- STATE 1: NO DOCUMENT -->
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
                    <!-- Drop Zone -->
                    <div class="drop-zone" id="dropZone">
                        <img src="<?php echo $baseUrl; ?>images/icons/document-svgrepo-com.svg" width="32" height="32" class="upload-icon" alt="Upload">
                        <p><strong>Drop your document here or click to upload</strong></p>
                        <p class="file-types">JPG, PNG, PDF &mdash; Max 5MB</p>
                        <p class="file-name-display" id="fileNameDisplay"></p>
                    </div>

                    <!-- Upload Form -->
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
                            <img src="<?php echo $baseUrl; ?>images/icons/upload-svgrepo-com.svg" alt="Upload">
                            Upload Document
                        </button>
                    </form>

                    <p class="help-text">Your document will be reviewed by an admin within 24 to 48 hours.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Verification Message -->
    <div id="verificationMessage"></div>
</div>

<style>
    /* Component-specific styles */
    .verification-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-light);
        padding: var(--spacing-xl);
        margin-bottom: var(--spacing-xl);
        box-shadow: var(--shadow-sm);
    }

    .verification-card .card-header {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        font-size: var(--font-xl);
        font-weight: var(--font-bold);
        margin-bottom: var(--spacing-lg);
        padding-bottom: var(--spacing-sm);
        border-bottom: 2px solid var(--primary-color);
    }

    .verification-card .card-header img {
        width: 24px;
        height: 24px;
    }

    .verification-state {
        padding: var(--spacing-lg);
        border-radius: var(--radius-md);
        background: var(--gray-bg-light);
    }

    .verification-state.verified {
        border-left: 4px solid var(--success);
    }

    .verification-state.pending {
        border-left: 4px solid var(--info);
    }

    .verification-state.no-document {
        border-left: 4px solid var(--warning);
    }

    .state-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-md);
        padding-bottom: var(--spacing-sm);
        border-bottom: 1px solid var(--border-light);
        flex-wrap: wrap;
        gap: var(--spacing-sm);
    }

    .state-header h3 {
        font-size: var(--font-lg);
        font-weight: var(--font-semibold);
        margin: 0;
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .state-header h3 img {
        width: 20px;
        height: 20px;
    }

    .state-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: var(--radius-round);
        font-size: var(--font-xs);
        font-weight: var(--font-medium);
    }

    .state-badge img {
        width: 14px;
        height: 14px;
    }

    .state-badge.verified {
        background: var(--success-light);
        color: var(--success);
    }

    .state-badge.pending {
        background: var(--info-light);
        color: var(--info);
    }

    .state-badge.not-verified {
        background: var(--warning-light);
        color: var(--warning);
    }

    .state-content {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .state-content .info-text {
        margin: var(--spacing-xs) 0;
    }

    .state-content .info-text strong {
        color: var(--dark-bg);
    }

    .document-file {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: var(--spacing-sm) var(--spacing-md);
        background: var(--white);
        border-radius: var(--radius-md);
        border: 1px solid var(--border-light);
        margin: var(--spacing-sm) 0;
        flex-wrap: wrap;
    }

    .document-file .file-link {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: var(--font-medium);
        word-break: break-all;
    }

    .document-file .file-link:hover {
        text-decoration: underline;
    }

    .document-file .file-size {
        font-size: var(--font-xs);
        color: var(--gray-medium);
    }

    .text-success {
        color: var(--success);
    }

    .text-warning {
        color: var(--warning);
    }

    .text-info {
        color: var(--info);
    }

    .text-muted {
        color: var(--gray-medium);
    }

    .status-icon {
        vertical-align: middle;
        margin-right: 6px;
    }

    .document-actions {
        display: flex;
        gap: var(--spacing-sm);
        flex-wrap: wrap;
        margin-top: var(--spacing-sm);
    }

    .document-actions button {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 16px;
        border-radius: var(--radius-sm);
        font-size: var(--font-xs);
        cursor: pointer;
        border: 1px solid var(--border-light);
        background: var(--white);
        transition: all var(--transition-fast);
        font-weight: var(--font-medium);
    }

    .document-actions button img {
        vertical-align: middle;
        width: 14px;
        height: 14px;
    }

    .document-actions .preview-btn {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .document-actions .preview-btn:hover {
        background: var(--primary-color);
        color: var(--white);
    }

    .document-actions .preview-btn:hover img {
        filter: brightness(0) invert(1);
    }

    .document-actions .replace-btn {
        border-color: var(--info);
        color: var(--info);
    }

    .document-actions .replace-btn:hover {
        background: var(--info);
        color: var(--white);
    }

    .document-actions .replace-btn:hover img {
        filter: brightness(0) invert(1);
    }

    .document-actions .delete-btn {
        border-color: var(--error);
        color: var(--error);
    }

    .document-actions .delete-btn:hover {
        background: var(--error);
        color: var(--white);
    }

    .document-actions .delete-btn:hover img {
        filter: brightness(0) invert(1);
    }

    .upload-area {
        margin-top: var(--spacing-md);
    }

    .drop-zone {
        border: 2px dashed var(--border-light);
        border-radius: var(--radius-md);
        padding: var(--spacing-xl);
        text-align: center;
        cursor: pointer;
        transition: all var(--transition-fast);
        margin-bottom: var(--spacing-md);
        background: var(--white);
    }

    .drop-zone:hover {
        border-color: var(--primary-color);
        background: var(--primary-fade);
    }

    .drop-zone.dragover {
        border-color: var(--primary-color);
        background: var(--primary-fade);
    }

    .drop-zone .file-types {
        font-size: var(--font-xs);
        color: var(--gray-medium);
        margin-top: var(--spacing-xs);
    }

    .drop-zone .upload-icon {
        opacity: 0.5;
        margin-bottom: 8px;
        width: 32px;
        height: 32px;
    }

    .file-name-display {
        color: var(--primary-color);
        font-weight: bold;
        margin-top: var(--spacing-sm);
        font-size: var(--font-sm);
    }

    .help-text {
        font-size: var(--font-xs);
        color: var(--gray-medium);
        margin-top: var(--spacing-sm);
        font-style: italic;
    }

    .upload-btn {
        background: var(--primary-color);
        color: var(--white);
        padding: 10px 24px;
        border: none;
        border-radius: var(--radius-md);
        cursor: pointer;
        font-weight: var(--font-medium);
        transition: all var(--transition-fast);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .upload-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .upload-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .upload-btn img {
        width: 16px;
        height: 16px;
        filter: brightness(0) invert(1);
    }

    #verificationMessage {
        margin-top: var(--spacing-md);
        padding: var(--spacing-md);
        border-radius: var(--radius-md);
        display: none;
    }

    #verificationMessage.success {
        background: var(--success-light);
        color: var(--success);
        display: block;
        border-left: 4px solid var(--success);
    }

    #verificationMessage.error {
        background: var(--error-light);
        color: var(--error);
        display: block;
        border-left: 4px solid var(--error);
    }

    .verification-status p {
        margin: var(--spacing-xs) 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .verification-card {
            padding: var(--spacing-lg);
        }

        .verification-state {
            padding: var(--spacing-md);
        }

        .state-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .document-actions {
            flex-direction: column;
            width: 100%;
        }

        .document-actions button {
            width: 100%;
            justify-content: center;
        }

        .drop-zone {
            padding: var(--spacing-lg);
        }
    }

    @media (max-width: 480px) {
        .verification-card {
            padding: var(--spacing-md);
        }

        .verification-state {
            padding: var(--spacing-sm);
        }
    }
</style>

<script>
    (function initVerification() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initVerification, 50);
            return;
        }

        var $dropZone = $('#dropZone');
        var $fileNameDisplay = $('#fileNameDisplay');
        var $verificationDoc = $('#verificationDoc');
        var $verificationForm = $('#verificationForm');
        var $verificationMessage = $('#verificationMessage');

        if ($dropZone.length && $verificationDoc.length) {
            $dropZone.on('click', function() {
                $verificationDoc.click();
            });

            $verificationDoc.on('change', function(e) {
                var file = this.files[0];
                if (file) {
                    $fileNameDisplay.text('Document: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
                    $dropZone.css('border-color', 'var(--success)');
                    $dropZone.css('background', 'var(--success-light)');
                    setTimeout(function() {
                        $verificationForm.submit();
                    }, 500);
                }
            });

            $dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).css('border-color', 'var(--primary-color)');
                $(this).css('background', 'var(--primary-fade)');
            });

            $dropZone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).css('border-color', 'var(--border-light)');
                $(this).css('background', 'transparent');
            });

            $dropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).css('border-color', 'var(--border-light)');
                $(this).css('background', 'transparent');

                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $verificationDoc[0].files = files;
                    $verificationDoc.trigger('change');
                }
            });
        }

        if ($verificationForm.length) {
            $verificationForm.on('submit', function(e) {
                e.preventDefault();

                var file = $verificationDoc[0].files[0];
                if (!file) {
                    showVerificationMessage('Please select a document to upload.', true);
                    return;
                }

                var formData = new FormData(this);
                var $btn = $(this).find('.upload-btn');
                var originalText = $btn.text();
                $btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: baseUrl + 'php/endpoints/users/upload-verification.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showVerificationMessage(data.message, false);
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showVerificationMessage(data.message, true);
                        }
                    },
                    error: function() {
                        showVerificationMessage('Could not upload document.', true);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        }

        window.deleteDocument = function() {
            if (confirm('Are you sure you want to delete your verification document?')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/users/delete-verification.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({}),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showVerificationMessage('Document deleted successfully.', false);
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showVerificationMessage(data.message, true);
                        }
                    },
                    error: function() {
                        showVerificationMessage('Could not delete document.', true);
                    }
                });
            }
        };

        $(document).on('change', '#replaceDocInput', function() {
            var file = this.files[0];
            if (file) {
                var formData = new FormData();
                formData.append('document', file);
                formData.append('document_type', $('#documentType').val() || 'id');

                $.ajax({
                    url: baseUrl + 'php/endpoints/users/upload-verification.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showVerificationMessage('Document replaced successfully.', false);
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showVerificationMessage(data.message, true);
                        }
                    },
                    error: function() {
                        showVerificationMessage('Could not replace document.', true);
                    }
                });
            }
        });

        function showVerificationMessage(message, isError) {
            if (!$verificationMessage.length) return;
            $verificationMessage.removeClass('success error');
            $verificationMessage.addClass(isError ? 'error' : 'success');
            $verificationMessage.text(message).show();

            setTimeout(function() {
                $verificationMessage.fadeOut(500, function() {
                    $verificationMessage.removeClass('success error').hide().text('');
                });
            }, 5000);
        }
    })();
</script>