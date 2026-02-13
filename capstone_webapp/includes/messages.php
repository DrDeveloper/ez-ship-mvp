<?php
/**
 * Displays error or success messages if they exist.
 * Expects variables $error and/or $success to be defined in the including script.
 */
if (!empty($error)) : ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)) : ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>
