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
    <meta http-equiv="refresh" content="60"><!-- Auto-refreshes the page every 60 seconds to update parcel availability and statuses. -->
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

$authorized = enforceRole('driver');
?>

<?php if ($authorized): ?>
    <main class="content">
        <?php
        // Includes the subheader and messages assets, and stores session data variables for use in the page.
        require_once __DIR__ . '/../includes/subheader.php';
        // Fetches success message from successful submission and unset it from session.
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);
        // Store session data in local variables for easier access and security.
        $username = htmlspecialchars($_SESSION['username']);
        $role     = htmlspecialchars($_SESSION['role']);
        $userId   = $_SESSION['user_id'];
        // =================================================================================================
        // Fetches all parcel information in warehouses and allows for drivers to place parcel reservations.
        // =================================================================================================
        // Handle reservation if reservation form is submitted.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_pid'])) {
            $parcelId = (int) $_POST['reserve_pid'];
            try {
                $db->beginTransaction();
                // Check if driver already has a reserved parcel that hasn't been delivered.
                $stmtCheck = $db->prepare("SELECT COUNT(*) 
                    FROM parcel_location 
                    WHERE driver_res = :userId
                    AND recipient_type IS NULL
                ");
                $stmtCheck->execute([':userId' => $userId]);
                $reservedCount = (int) $stmtCheck->fetchColumn();
                // Checks if the driver already has a reserved parcel.
                if ($reservedCount >= 1) { // CHANGE THIS NUMBER TO ADJUST HOW MANY PARCELS A DRIVER CAN RESERVE AT ONCE.
                    $error = "You can only reserve 1 parcel at a time."; // UPDATE ERROR MESSAGE TO MATCH THE NUMBER OF RESERVATIONS ALLOWED.
                    $db->rollBack(); // Roll back transaction if driver already has a reserved parcel.
                } 
                else {
                    // Reserve parcel
                    $stmtReserve = $db->prepare("UPDATE parcel_location SET driver_res = :userId
                        WHERE pid = :pid AND driver_res IS NULL");
                    $stmtReserve->execute([':userId' => $userId, ':pid' => $parcelId]);
                    // Checks to ensure reservation was successful by verifying that a row was updated (i.e., the parcel was not already reserved by another driver).
                    if ($stmtReserve->rowCount() > 0) {
                        $success = "Parcel #$parcelId reserved successfully.";
                        $db->commit(); // Commit transaction after successful reservation.
                    }
                    else {
                        $error = "Parcel could not be reserved (Possibly reserved by another driver).";
                        $db->rollBack(); // Roll back transaction if reservation failed.
                    }
                }
            }
            catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = "Error reserving parcel: " . $e->getMessage();
            }
        }
        // Fetches the driver's maximum delivery size and retrieves all parcels that are ready for pickup and within the driver's delivery size limit, ordered by warehouse delivery time.
        try {
            $stmtDriver = $db->prepare("SELECT dmd FROM driver WHERE did = :userId");
            $stmtDriver->execute([':userId' => $userId]);
            $dmd = (int) $stmtDriver->fetchColumn();
            // Gathering parcel info in warehouses.
            // parcel size must be less than or equal to the driver's max delivery size.
            $stmt = $db->prepare("SELECT p.pid, p.ps, p.pt, pt.pwt, w.wn, w.wl
                FROM parcels p
                JOIN parcel_time pt ON p.pid = pt.pid
                JOIN parcel_routing pr ON p.pid = pr.pid
                JOIN parcel_location pl ON p.pid = pl.pid
                JOIN warehouse w ON pr.wid = w.wid
                WHERE pt.pdt IS NULL         -- Not yet picked up by and driver. ('pt.pdt IS NULL' is redundant with 'pr.did IS NULL')
                AND pt.pwt IS NOT NULL       -- Warehouse time must exist (Client must have delivered parcel to warehouse).
                AND pr.did IS NULL           -- Not being delivered by another driver. ('pt.pdt IS NULL' is redundant with 'pr.did IS NULL')
                AND pl.driver_res IS NULL    -- Not reserved by another driver.
                AND p.ps <= :dmd             -- within the drivers maximum vehicle parcel delivery capacity.
                ORDER BY pt.pwt ASC          -- Orders parcels by warehouse delivery time, prioritizing parcels that have been waiting longer in the warehouse.
            ");
            $stmt->execute([':dmd' => $dmd]);
            // Fetches all parcels that are ready for pickup and within the driver's delivery size limit, ordered by drop off time at warehouse.
            $out_standing_parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        catch (Exception $e) {
            $error = $e->getMessage();
        }
        // Fetch the parcel currently reserved by this driver (if any)
        $reserved_parcel = null;
        try {
            $stmtReserved = $db->prepare("SELECT p.pid, p.ps, p.pt, w.wn, w.wl
                FROM parcels p
                JOIN parcel_routing pr ON p.pid = pr.pid    
                JOIN parcel_location pl ON p.pid = pl.pid   
                JOIN warehouse w ON pr.wid = w.wid          -- To get warehouse info for the reserved parcel.
                WHERE pl.driver_res = :userId               -- To query for parcels reserved by this driver.
                AND pr.did IS NULL                          -- To query for only parcel IDs that haven't been assigned to a driver yet.
            ");
            $stmtReserved->execute([':userId' => $userId]);
            $reserved_parcels = $stmtReserved->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = "Failed to fetch reserved parcel: " . $e->getMessage();
        }
        // Function to convert parcel size in cubic centimeters to a human-readable label.
        function parcelSizeLabel($ps) {
            return match(true) {
                $ps <= 3375     => 'XS',
                $ps <= 27000    => 'S',
                $ps <= 216000   => 'M',
                $ps <= 729000   => 'L',
                $ps <= 1728000  => 'XL',
                default         => 'Unknown',
            };
        }
        // ==============================================================================
        // Removes a parcel from the warehouse and assigns it to the driver for delivery.
        // ==============================================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_parcel'])) {
            $pid = intval($_POST['pid']);
            try {
                // Check if the parcel exists and is assigned to a valid warehouse.
                $checkStmt = $db->prepare("
                    SELECT parloc.pl AS wid, p.ps
                    FROM parcel_location parloc
                    JOIN parcels p ON parloc.pid = p.pid
                    JOIN warehouse w ON parloc.pl = w.wid
                    WHERE parloc.pid = :pid
                ");
                $checkStmt->execute(['pid' => $pid]);
                $data = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if (!$data) {
                    throw new Exception("Parcel $pid is not registered to the warehouse. Please check Parcel ID.");
                }
                $warehouse_id = $data['wid'];
                $parcel_size  = $data['ps'];
                $db->beginTransaction();
                // 1. Update parcel_location to drivers id.
                $stmt = $db->prepare("UPDATE parcel_location SET pl = :userId WHERE pid = :pid");
                $stmt->execute(['userId' => $userId, 'pid' => $pid]);
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Failed to update parcel_location for Parcel ID $pid.");
                }
                // 2. Update parcel_routing to set driver ID to the current carrier.
                $stmt2 = $db->prepare("UPDATE parcel_routing SET did = :userId WHERE pid = :pid");
                $stmt2->execute(['userId' => $userId, 'pid' => $pid]);
                if ($stmt2->rowCount() === 0) {
                    throw new Exception("Failed to update parcel_routing for Parcel ID $pid.");
                }
                // 3. Update parcel_time to set drivers parcel pickup time.
                $stmt3 = $db->prepare("UPDATE parcel_time SET pdt = NOW() WHERE pid = :pid");
                $stmt3->execute(['pid' => $pid]);
                if ($stmt3->rowCount() === 0) {
                    throw new Exception("Failed to update parcel_time for Parcel ID $pid.");
                }
                // 4. Update warehouse_storage to reflect warehouse storage changes after parcel is removed by driver for delivery.
                $stmt4 = $db->prepare("
                    UPDATE warehouse_storage
                    SET 
                        wp = wp - 1,
                        wes = wes + :ps,
                        wcs = wcs - :ps
                    WHERE wid = :wid
                ");
                $stmt4->execute([
                    'ps'  => $parcel_size,
                    'wid' => $warehouse_id
                ]);
                if ($stmt4->rowCount() === 0) {
                    throw new Exception("Failed to update warehouse_storage for Warehouse ID $warehouse_id.");
                }
                // Commit transaction if all operations were successful.
                if ($db->commit()) {
                    // Sets a success message for client to appear after redirect.
                    $_SESSION['success'] = "Parcel $pid is ready to be delivered.";
                    // Redirect to the same page to prevent form resubmission
                    header("Location: driver.php?success=1");
                    exit;
                }
            } 
            catch (Exception $e) {
                // Roll back transaction if it was started.
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                // Set error message for client.
                $error = $e->getMessage();
            }
        }
        // ======================================================================
        // Fetches parcel routing information for parcels assigned to the driver.
        // ======================================================================
        try {
            // Fetch all parcels assigned to this driver with recipient info
            $stmt = $db->prepare("
                SELECT ploc.pid, pr.recipient_type, pr.rid
                FROM parcel_location ploc
                JOIN parcel_routing pr ON ploc.pid = pr.pid
                WHERE ploc.pl = :userId
                ORDER BY ploc.pid ASC
            ");
            $stmt->execute(['userId' => $userId]);
            $parcelsToDeliver = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $parcel_delivery_info = [];
            // Process each parcel to unify recipient fields
            foreach ($parcelsToDeliver as $parcel) {
                if ($parcel['recipient_type'] === 'T') {
                    $recStmt = $db->prepare("SELECT rn, rl FROM temp_recipient WHERE trid_num = :rid");
                    $recStmt->execute(['rid' => $parcel['rid']]);
                    $recipientInfo = $recStmt->fetch(PDO::FETCH_ASSOC);
                    // Store info into $parcel_delivery_info
                    $parcel_delivery_info[] = [
                        'pid' => $parcel['pid'],
                        'recipient_name' => $recipientInfo['rn'] ?? 'Not Provided',
                        'recipient_location' => $recipientInfo['rl'] ?? 'Not Provided',
                        'recipient_delivery_info' => 'N/A'  // No delivery Instructions for temp recipients.
                    ];
                } 
                elseif ($parcel['recipient_type'] === 'R') {
                    $recStmt = $db->prepare("SELECT rn, rl, rdi FROM recipient WHERE rid = :rid");
                    $recStmt->execute(['rid' => $parcel['rid']]);
                    $recipientInfo = $recStmt->fetch(PDO::FETCH_ASSOC);
                    // Store info into $parcel_delivery_info
                    $parcel_delivery_info[] = [
                        'pid' => $parcel['pid'],
                        'recipient_name' => $recipientInfo['rn'] ?? 'Not Provided',
                        'recipient_location' => $recipientInfo['rl'] ?? 'Not Provided',
                        'recipient_delivery_info' => $recipientInfo['rdi'] ?? 'Not Provided'
                    ];
                }
            }
        }
        catch (Exception $e) {
            $error = "Failed to fetch parcels for delivery: " . $e->getMessage();
        }
        // ==========================================================
        // Handles form submission for marking a parcel as delivered.
        // ==========================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deliver_parcel'])) {
            $pid = intval($_POST['pid']); // parcel ID from the form
            try {
                $db->beginTransaction();
                // Checks if the parcel id being delivered is assigned to the logged-In driver.
                $checkStmt = $db->prepare("SELECT pid FROM parcel_location WHERE pid = :pid AND pl = :userId");
                $checkStmt->execute(['pid' => $pid, 'userId' => $userId]);
                $parcelVerify = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if (!$parcelVerify) {
                    throw new Exception("Parcel ID $pid is not assigned to you. Double Check Parcel ID and Try Again.");
                }
                // Proceeds with delivery if the parcel is assigned to the driver.
                if ($parcelVerify) {
                    // 1. Fetches the rid from parcel_routing for the given pid.
                    $routingStmt = $db->prepare("SELECT rid, recipient_type FROM parcel_routing WHERE pid = :pid");
                    $routingStmt->execute(['pid' => $pid]);
                    $parcelRouting = $routingStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$parcelRouting) {
                        throw new Exception("Failed to fetch routing info for Parcel ID $pid.");
                    }
                    $rid = $parcelRouting['rid'];
                    $recipient_type = $parcelRouting['recipient_type'];
                    // 2. Update parcel_location to assign the parcel to the recipient
                    $updateStmt = $db->prepare("UPDATE parcel_location SET pl = :rid, 
                        recipient_type = :recipient_type WHERE pid = :pid");
                    $updateStmt->execute(['rid' => $rid, 'recipient_type' => $recipient_type, 'pid' => $pid]);
                    if ($updateStmt->rowCount() === 0) {
                        throw new Exception("Failed to update parcel_location for Parcel ID $pid. 
                        Parcel may not exist or is already updated.");
                    }
                    // After updating parcel_location to the recipient
                    $timeStmt = $db->prepare("UPDATE parcel_time SET prt = NOW() WHERE pid = :pid");
                    $timeStmt->execute(['pid' => $pid]);
                    if ($timeStmt->rowCount() === 0) {
                        throw new Exception("Failed to update delivery time for Parcel ID $pid.");
                    }
                    // Commit transaction if all operations were successful.
                    if ($db->commit()) {
                        // Sets a success message for client to appear after redirect.
                        $_SESSION['success'] = "Parcel $pid was successfully delivered.";
                        // Redirect to the same page to prevent form resubmission
                        header("Location: driver.php?success=1");
                        exit;
                    }
                }
            } 
            catch (Exception $e) {
                $db->rollback();
                $error = $e->getMessage();
            }
        }
        // Displays any success or error messages related to parcel manipulation or other actions taken on the page.
        require_once __DIR__ . '/../includes/messages.php';
        ?>
        <div class="driver-container">
            <!-- Reserved Parcel Section -->
            <div class="parcel-reservation">
                <h2>Reserved Parcel(s)</h2>
                <?php if (!empty($reserved_parcels)): ?>
                    <table class="driver-parcel-table">
                        <thead>
                            <tr>
                                <th>Parcel ID</th>
                                <th>Size</th>
                                <th>Type</th>
                                <th>Warehouse</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reserved_parcels as $parcel): ?>
                                <tr>
                                    <td><?= htmlspecialchars($parcel['pid']) ?></td>
                                    <td><?= parcelSizeLabel($parcel['ps']) ?></td>
                                    <td><?= htmlspecialchars($parcel['pt']) ?></td>
                                    <td><?= htmlspecialchars($parcel['wn']) ?></td>
                                    <td>
                                        <?php 
                                            $address = $parcel['wl'];
                                            $encodedAddress = urlencode($address);
                                        ?>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?= $encodedAddress ?>" 
                                        target="_blank" 
                                        rel="noopener noreferrer">
                                            <?= htmlspecialchars($address) ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No reserved parcels at this time.</p>
                <?php endif; ?>

                <!-- Available Parcels Section -->
                <h2>Available Parcels</h2>
                <?php if (empty($out_standing_parcels)): ?>
                    <p>No parcels available at this time.</p>
                <?php else: ?>
                    <div class="parcel-list">
                        <?php foreach ($out_standing_parcels as $parcel): ?>
                            <div class="parcel-card">
                                <h3>Parcel #<?= $parcel['pid'] ?></h3>
                                <p><strong>Size:</strong> <?= parcelSizeLabel($parcel['ps']) ?></p>
                                <p><strong>Type:</strong> <?= htmlspecialchars($parcel['pt']) ?></p>
                                <p><strong>Warehouse:</strong> <?= htmlspecialchars($parcel['wn']) ?> 
                                    <td>
                                        <?php 
                                            $address = $parcel['wl'];
                                            $encodedAddress = urlencode($address);
                                        ?>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?= $encodedAddress ?>" 
                                        target="_blank" 
                                        rel="noopener noreferrer">
                                            <?= htmlspecialchars($address) ?>
                                        </a>
                                    </td>
                                </p>
                                <p><strong>Warehouse Time:</strong> <?= $parcel['pwt'] ?></p>

                                <form method="POST" action="">
                                    <input type="hidden" name="reserve_pid" value="<?= $parcel['pid'] ?>">
                                    <button type="submit">Reserve</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Form for drivers to remove a parcel from the warehouse and assign it to themselves for delivery. -->
            <div class="remove-parcel">    
                <form method="POST" id="process_remove" class="driver-parcel-form">
                    <h3>Remove Parcel from Warehouse</h3>

                    <label for="remove_pid">Parcel ID:</label>
                    <input type="number" name="pid" id="remove_pid" required>

                    <button type="submit" name="remove_parcel">
                        Remove Parcel
                    </button>
                </form>
            </div>
            <?php if (!empty($parcel_delivery_info)): ?>
                <div class="assigned-parcels">
                    <!-- Displays a table of parcels that are currently assigned to the driver for delivery, including recipient information and delivery instructions. -->
                    <h2>Parcels Assigned for Delivery</h2>
                        <table class="driver-parcel-table">
                            <thead>
                                <tr>
                                    <th>Parcel ID</th>
                                    <th>Recipient Name</th>
                                    <th>Delivery Location</th>
                                    <th>Delivery Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parcel_delivery_info as $parcel): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($parcel['pid']) ?></td>
                                        <td><?= htmlspecialchars($parcel['recipient_name']) ?></td>
                                        <td>
                                            <?php 
                                                $address = $parcel['recipient_location'];
                                                $encodedAddress = urlencode($address);
                                            ?>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $encodedAddress ?>" 
                                            target="_blank" 
                                            rel="noopener noreferrer">
                                                <?= htmlspecialchars($address) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($parcel['recipient_delivery_info'])?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <!-- Form for drivers to mark a parcel as delivered, which updates the parcel's status and assigns it to the recipient. -->
                    <form method="POST" id="process_delivered" class="driver-parcel-form">
                        <h3>Mark Parcel as Delivered</h3>

                        <label for="deliver_pid">Parcel ID:</label>
                        <input type="number" name="pid" id="deliver_pid" required>

                        <button type="submit" name="deliver_parcel">
                            Mark Delivered
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="/capstone_webapp/assets/js/main.js"></script>
</body>

</html>
