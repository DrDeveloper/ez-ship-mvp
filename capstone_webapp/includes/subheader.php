<?php
// Safely read session variables
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
$role     = isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : '';
?>

<div class="dashboard-subheader">
    <div class="subheader-left">
        <h2><?php echo $role ? ucfirst($role) . ' Dashboard' : 'Dashboard'; ?></h2>
        <p>
            Welcome back,
            <strong><?php echo $username; ?></strong>
        </p>
    </div>

    <div class="subheader-right">
        <?php if ($role): ?>
            <span class="role-badge"><?php echo $role; ?></span>
        <?php endif; ?>
        <a href="../includes/logout.php" class="logout-btn">Logout</a>
    </div>
</div>
