<?php
// Includes the configuration and database connection files.
require_once __DIR__ . '/includes/config.php'; // Handles Global Variables and Starting Session.
require_once __DIR__ . '/includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <!-- Global CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<?php
require_once __DIR__ . '/includes/header.php';
?>
    <main>
        <?php
        // Initialize variables for error handling and parcel data.
        $error = null;
        // Initialize $parcel to null to avoid undefined variable issues in the view.
        $parcel = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pid'])) {
            $pid = filter_input(INPUT_POST, 'pid', FILTER_VALIDATE_INT);
            if (!$pid || $pid <= 0) {
                $error = "Invalid Parcel ID.";
            } 
            else {
                try {
                    $sql = "SELECT p.pid, p.pd, p.ps, p.pv, p.pt,
                            COALESCE(pt.prt, pt.pdt, pt.pwt, pt.pct) AS LastUpdateTime,
                            CASE 
                                WHEN pt.prt IS NOT NULL THEN 'Delivered'
                                WHEN pt.pdt IS NOT NULL THEN 'Out for Delivery'
                                WHEN pt.pwt IS NOT NULL THEN 'At Warehouse'
                                ELSE 'Created'
                            END AS status,
                            CASE 
                                WHEN pt.prt IS NOT NULL THEN 'Delivered'
                                WHEN u.role = 'client' THEN c.cn
                                WHEN u.role = 'warehouse' THEN w.wn
                                WHEN u.role = 'driver' THEN d.dn
                                ELSE 'Unknown Location'
                            END AS current_location
                        FROM parcels p
                        LEFT JOIN parcel_time pt ON p.pid = pt.pid
                        LEFT JOIN parcel_location pl ON p.pid = pl.pid
                        LEFT JOIN users u ON pl.pl = u.uid
                        LEFT JOIN client c ON u.uid = c.cid
                        LEFT JOIN warehouse w ON u.uid = w.wid
                        LEFT JOIN driver d ON u.uid = d.did
                        WHERE p.pid = :pid
                    ";
                    $stmt = $db->prepare($sql);
                    $stmt->execute(['pid' => $pid]);
                    $parcel = $stmt->fetch();
                    if (!$parcel) {
                        $error = "Parcel $pid not found. Please check the PID and try again.";
                    }
                    else {$success = "Parcel $pid found. Displaying tracking information.";}
                } 
                catch (PDOException $e) {
                    error_log($e->getMessage());
                    $error = "A system error occurred. Please try again later.";
                }
            }
        }
        require_once __DIR__ . '/includes/messages.php';
        ?>
        <div class="index-container">
            <!-- Form Section -->
            <div class="tracking-form">
                <form method="POST">
                    <label for="pid">Track A Parcel Enter Parcel ID:</label>
                    <input 
                        type="number" 
                        id="pid" 
                        name="pid" 
                        step="1" 
                        min="1"
                        required>
                    <button type="submit">Track Parcel</button>
                </form>
            </div>
            <!-- Result Section -->
            <div class="tracking-result">
                <?php if ($parcel): ?>
                    <div class="parcel-result">
                        <h3>Parcel Details ID: <?= htmlspecialchars($parcel['pid']) ?></h3>
                        <p><strong>Description:</strong> <?= htmlspecialchars($parcel['pd']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($parcel['status']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($parcel['current_location']) ?></p>
                        <p><strong>Last Update:</strong> <?= htmlspecialchars($parcel['LastUpdateTime']) ?></p>
                    </div>
                <?php else: ?>
                    <p>Please enter a Parcel ID to get started with tracking a parcel.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
<!-- Imports The Java Script File -->
<script src="/assets/js/main.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</html>