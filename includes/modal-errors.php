<?php if (!empty($registerErrors)): ?>
    <script>
        $(function() {
            openModal($('#register-modal'));
            displayModalErrors('#register-modal', <?php echo json_encode($registerErrors); ?>, <?php echo json_encode($registerFormData); ?>);
        });
    </script>
<?php endif; ?>

<?php if (!empty($loginErrors)): ?>
    <script>
        $(function() {
            openModal($('#login-modal'));
            displayModalErrors('#login-modal', <?php echo json_encode($loginErrors); ?>, {
                email: <?php echo json_encode($loginEmail); ?>
            });
        });
    </script>
<?php endif; ?>