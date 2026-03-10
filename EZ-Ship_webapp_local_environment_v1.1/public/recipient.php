<?php
// Includes the configuration and database connection files.
require_once __DIR__ . '/../includes/config.php'; // Handles Global Variables and Starting Session.
require_once __DIR__ . '/../includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <!-- Global CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/capstone_webapp/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<?php
require_once __DIR__ . '/../includes/header.php';
// Authenticates Session User has applicable role and begins session.
require_once __DIR__ . '/../includes/auth.php';

$authorized = enforceRole('recipient');
?>

<?php if ($authorized): ?>
    <main class="content">
        <?php
        // Includes the subheader and messages assets, and stores session data variables for use in the page.
        require_once __DIR__ . '/../includes/subheader.php';
        $username = htmlspecialchars($_SESSION['username']);
        $role     = htmlspecialchars($_SESSION['role']);
        $userId   = $_SESSION['user_id'];
        // ===============================================================================
        // Fetches parcels associated with recipient, along with related info for display.
        // ===============================================================================
        try {
            $stmt = $db->prepare("SELECT 
                pr.pid,
                -- Parcel Info
                p.pd,
                -- Client
                c.cn,
                -- Driver
                d.dn,
                -- Warehouse
                w.wn,
                -- Recipient
                r.rn,
                -- Location
                pl.pl AS current_location,
                -- Time Tracking
                pt.pct,
                pt.pwt,
                pt.pdt,
                pt.prt
                FROM parcel_routing pr
                LEFT JOIN parcels p ON pr.pid = p.pid
                LEFT JOIN client c ON pr.cid = c.cid
                LEFT JOIN driver d ON pr.did = d.did
                LEFT JOIN warehouse w ON pr.wid = w.wid
                LEFT JOIN recipient r ON pr.rid = r.rid
                LEFT JOIN parcel_location pl ON pr.pid = pl.pid
                LEFT JOIN parcel_time pt ON pr.pid = pt.pid
                WHERE pr.rid = :userId
                AND pr.recipient_type = 'R'
                ORDER BY pt.pct DESC
            ");

            $stmt->execute(['userId' => $userId]);
            $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $activeParcels = [];
            $deliveredParcels = [];
            // Loops through parcels to determine status of parcel.
            foreach ($parcels as $parcel) {

                // if prt is available, parcel is categorized as delivered.
                if (!empty($parcel['prt'])) {
                    $parcel['status_text'] = "Delivered";
                    $parcel['status_time'] = $parcel['prt'];
                    $deliveredParcels[] = $parcel;
                    continue;
                }
                // Label Created (not yet at warehouse)
                if (empty($parcel['pwt'])) {
                    $parcel['status_text'] = "Shipping label created by " . $parcel['cn'];
                    $parcel['status_time'] = $parcel['pct'];
                }
                // Awaiting Driver (at warehouse, not picked up)
                elseif (empty($parcel['pdt'])) {
                    $parcel['status_text'] = "En Route - Awaiting Driver Assignment";
                    $parcel['status_time'] = $parcel['pwt'];
                }
                // Out for Delivery is last option (picked up, not yet delivered).
                else {
                    $driverName = $parcel['dn'] ?? "Driver";
                    $parcel['status_text'] = "En Route with " . $driverName;
                    $parcel['status_time'] = $parcel['pdt'];
                }
                // Appends status of parcel to active parcels.
                $activeParcels[] = $parcel;
            }


        }
        catch (PDOException $e) {
            // Handle database errors gracefully
            echo "<p class='error'>An error occurred while fetching your parcels. Please try again later.</p>";
            error_log("Database Error: " . $e->getMessage());
        }
        require_once __DIR__ . '/../includes/messages.php';
        ?>
        <div class="recipient-container">
            <!-- Active Parcels Section -->
            <div class="recipient-section">
                <h2>En Route</h2>
                <?php if (!empty($activeParcels)): ?>
                    <div class="recipient-parcel-grid">
                        <?php foreach ($activeParcels as $parcel): ?>
                            <div class="parcel-card-active">
                                <h3>Parcel #<?= htmlspecialchars($parcel['pid']) ?></h3>
                                <p><strong>Description:</strong>
                                    <?= htmlspecialchars($parcel['pd']) ?>
                                </p>
                                <p><strong>Status:</strong>
                                    <?= htmlspecialchars($parcel['status_text']) ?>
                                </p>
                                <p><strong>Last Update:</strong>
                                    <?= htmlspecialchars($parcel['status_time']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No active parcels.</p>
                <?php endif; ?>
            </div>
            <!-- Delivered Parcels Section -->
            <div class="recipient-section">
                <h2>Delivered</h2>
                <?php if (!empty($deliveredParcels)): ?>
                    <div class="recipient-parcel-grid">
                        <?php foreach ($deliveredParcels as $parcel): ?>
                            <div class="parcel-card-delivered">
                                <h3>Parcel #<?= htmlspecialchars($parcel['pid']) ?></h3>
                                <p><strong>Description:</strong>
                                    <?= htmlspecialchars($parcel['pd']) ?>
                                </p>
                                <p><strong>Client:</strong>
                                    <?= htmlspecialchars($parcel['cn']) ?>
                                </p>
                                <p><strong>Delivered At:</strong>
                                    <?= htmlspecialchars($parcel['status_time']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No delivered parcels.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="/capstone_webapp/assets/js/main.js"></script>
</body>
</html>