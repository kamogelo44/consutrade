<?php
/*
 * ConsuTrade - Modal Error Display Component
 * Author: Kamogelo Phale
 * 
 * Displays registration/login errors in modals when present
 */
?>
<?php if (!empty($registerErrors) || !empty($loginErrors)): ?>
    <script>
        $(function() {
            <?php if (!empty($registerErrors)): ?>
                openModal($('#register-modal'));
                if (typeof displayModalErrors === 'function') {
                    displayModalErrors('#register-modal', <?php echo json_encode($registerErrors); ?>, <?php echo json_encode($registerFormData); ?>);
                }
            <?php endif; ?>

            <?php if (!empty($loginErrors)): ?>
                openModal($('#login-modal'));
                if (typeof displayModalErrors === 'function') {
                    displayModalErrors('#login-modal', <?php echo json_encode($loginErrors); ?>, {
                        email: <?php echo json_encode($loginEmail); ?>
                    });
                }
            <?php endif; ?>
        });
    </script>
<?php endif; ?>