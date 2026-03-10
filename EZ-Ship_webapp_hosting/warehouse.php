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
// Authenticates Session User has applicable role and begins session.
require_once __DIR__ . '/includes/auth.php';
$authorized = enforceRole('warehouse');
?>

<?php if ($authorized): ?>
    <main class="content">
        <?php
        // Includes the subheader for user specific details.
        require_once __DIR__ . '/includes/subheader.php';
        // Stores session data variables for use in the page.
        $username = htmlspecialchars($_SESSION['username']);
        $role     = htmlspecialchars($_SESSION['role']);
        $userId   = $_SESSION['user_id'];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $pid = trim($_POST["pid"] ?? "");
            if (!empty($pid)) {
                // ===============================================================
                // Checks current parcel is logged with client in parcel_location.
                // ===============================================================
                // Gets the current parcel location for the parcel id.
                $checkStmt = $db->prepare("
                    SELECT pl
                    FROM parcel_location
                    WHERE pid = :pid
                ");
                $checkStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                $checkStmt->execute();
                $parcelLoc = $checkStmt->fetch(PDO::FETCH_ASSOC);
                // Pre-check: Parcel location exists
                if (!$parcelLoc) {
                    $error = "Parcel ID not associated with any location. Check Parcel ID and try again.";
                } else {
                    // Get pl value and convert to integer.
                    $pl = (int)$parcelLoc['pl'];
                    // Verify pl exists with a registered client in the client table.                    
                    $clientCheck = $db->prepare("
                        SELECT cid
                        FROM client
                        WHERE cid = :pl
                    ");
                    $clientCheck->bindValue(':pl', $pl, PDO::PARAM_INT);
                    $clientCheck->execute();

                    // Pre-check: Matching client exists
                    if (!$clientCheck->fetch(PDO::FETCH_ASSOC)) {
                        $error = "Parcel ID is no longer logged with a client. Check Parcel ID and try again.";
                    }
                }
                // =================================================
                // Only proceed with updates if no pre-check errors.
                // =================================================
                if (empty($error)) {
                    try {
                        // Begins transaction.
                        $db->beginTransaction();
                        // ===============================================================================
                        // Updates all applicable tables to reflect the parcel's arrival at the warehouse.
                        // ===============================================================================
                        // Updates the parcel_location to the warehouse.
                        $stmt1 = $db->prepare("UPDATE parcel_location SET pl = :pl WHERE pid = :pid");
                        $stmt1->bindValue(':pl', $userId, PDO::PARAM_INT);
                        $stmt1->bindValue(':pid', $pid, PDO::PARAM_INT);
                        $stmt1->execute();
                        // Logs the time of the parcel's arrival at the warehouse.
                        $stmt2 = $db->prepare("UPDATE parcel_time SET pwt = NOW() WHERE pid = :pid");
                        $stmt2->bindValue(':pid', $pid, PDO::PARAM_INT);
                        $stmt2->execute();
                        // Updates parcel_routing in case parcel was delivered to different warehouse than assigned.
                        $stmt3 = $db->prepare("UPDATE parcel_routing SET wid = :wid WHERE pid = :pid");
                        $stmt3->bindValue(':wid', $userId, PDO::PARAM_INT);
                        $stmt3->bindValue(':pid', $pid, PDO::PARAM_INT);
                        $stmt3->execute();
                        // Updates Warehouse Storage attributes based on parcel delivered.
                        // 1. Gets parcel size from parcel table on matching parcel id.
                        $psStmt = $db->prepare("SELECT ps FROM parcels WHERE pid = :pid");
                        $psStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                        $psStmt->execute();
                        $parcel = $psStmt->fetch(PDO::FETCH_ASSOC);
                        if ($parcel) {
                            $parcelSize = $parcel['ps'];
                            // Update warehouse_storage where wid = $userId
                            $wsStmt = $db->prepare("UPDATE warehouse_storage 
                                SET wp = wp + 1,
                                wes = wes - :ps,
                                wcs = wcs + :ps
                                WHERE wid = :wid");
                            $wsStmt->bindValue(':ps', $parcelSize, PDO::PARAM_INT);
                            $wsStmt->bindValue(':wid', $userId, PDO::PARAM_INT);
                            $wsStmt->execute();
                        }
                        // Commit transaction
                        $db->commit();
                        $success = "Parcel has arrived successfully to the warehouse.";
                    } 
                    catch (PDOException $e) {
                        $db->rollBack();
                        $error = "Failed to update parcel/warehouse: " . $e->getMessage();
                    }
                }
            }
        }
        // ==============================================================
        // Gathers data and statistics on warehouse capacity and storage.
        // ==============================================================
        // Fetch warehouse stats for the logged-in user.
        $statsStmt = $db->prepare("
            SELECT wid, wp, wes, wcs
            FROM warehouse_storage
            WHERE wid = :wid
        ");
        $statsStmt->bindValue(':wid', $userId, PDO::PARAM_INT);
        $statsStmt->execute();
        $warehouse = $statsStmt->fetch(PDO::FETCH_ASSOC);

        if ($warehouse) {
            $totalCapacity = $warehouse['wes'] + $warehouse['wcs'];

            if ($totalCapacity > 0) {
                $occupiedPct = round(($warehouse['wcs'] / $totalCapacity) * 100);
                $remainingPct = round(($warehouse['wes'] / $totalCapacity) * 100);


                // Cap remaining percentage at 0 if negative
                if ($remainingPct < 0) {
                    $remainingPct = 0;
                }
            } else {
                // If totalCapacity is zero (edge case), consider fully occupied
                $occupiedPct = 100;
                $remainingPct = 0;
            }
        } else {
            $occupiedPct = $remainingPct = 0;
        }
        // ==========================================
        // Fetch parcels delivered to this warehouse.
        // ==========================================
        $inventoryStmt = $db->prepare("SELECT pl.pid, pt.pwt, d.dn, pl.driver_res
            FROM parcel_location AS pl
            INNER JOIN parcel_time AS pt ON pl.pid = pt.pid
            LEFT JOIN driver AS d ON pl.driver_res = d.did
            WHERE pl.pl = :wid
            ORDER BY pt.pwt DESC
        ");
        $inventoryStmt->bindValue(':wid', $userId, PDO::PARAM_INT);
        $inventoryStmt->execute();
        $parcels = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);
        // Displays any success or error messages related to parcel delivery.
        require_once __DIR__ . '/includes/messages.php';
        ?>

        <div class="warehouse-dashboard">
            <!-- Warehouse Stats Table -->
            <div class="warehouse-stats-container">
                <h3>Warehouse Stats</h3>
                <table class="warehouse-stats-table">
                    <thead>
                        <tr>
                            <th>Warehouse ID</th>
                            <th>Parcels Stored (wp)</th>
                            <th>Occupied (%)</th>
                            <th>Remaining Capacity (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($warehouse['wid']); ?></td>
                            <td><?php echo htmlspecialchars($warehouse['wp']); ?></td>
                            <td><?php echo number_format($occupiedPct, 0); ?>%</td>
                            <td><?php echo number_format($remainingPct, 0); ?>%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Parcel Form -->
            <div class="warehouse-form-container">
                <form method="POST" action="" class="warehouse-parcel-form">
                    <h3>Enter Parcels Delivered From Clients</h3>
                    <label for="pid">Parcel ID:</label>
                    <input type="number" name="pid" id="pid" required>
                    <button type="submit">Submit</button>
                </form>
            </div>

            <!-- Parcel Inventory Table -->
            <div class="warehouse-inventory-container">
                <h3>Warehouse Parcel Inventory</h3>
                <table class="warehouse-inventory-table">
                    <thead>
                        <tr>
                            <th>Parcel ID</th>
                            <th>Inventoried At</th>
                            <th>Driver Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($parcels)): ?>
                            <?php foreach ($parcels as $parcel): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($parcel['pid']); ?></td>
                                    <td><?php echo htmlspecialchars($parcel['pwt']); ?></td>
                                    <td>
                                        <?php
                                            if (empty($parcel['driver_res'])) {
                                                echo "Searching...";
                                            } else {
                                                echo htmlspecialchars($parcel['dn']) . " inbound";
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No parcels currently in this warehouse.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="/assets/js/main.js"></script>
</body>
</html>