<?php
function enforceRole($requiredRole)
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        // Show message inside main content
        echo '<main class="content access-denied">
                <h2>Access Denied</h2>
                <p>Please Log-In or Sign-Up as a ' . htmlspecialchars($requiredRole) . '.</p>
                <a href="/landing_page.php" class="nav-link">
                    Log-In or Sign-Up
                </a>
              </main>';
        return false; // caller decides what to show
    }
    return true;
}
