<?php
// Includes the configuration and database connection files.
require_once __DIR__ . '/../includes/config.php'; // Handles Global Variables and Starting Session.
require_once __DIR__ . '/../includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
$authorized = enforceRole('client');
?>
<!-- If user is authorized as a client, display the main content of the page, 
    which includes the parcel submission form and related functionality. -->
<?php if ($authorized): ?>
    <main class="content">
        <?php
        // Includes the subheader user specific information.
        require_once __DIR__ . '/../includes/subheader.php'; 
        // Fetches success message from successful submission and unset it from session.
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);

        // Fetch all warehouses for the warehouse dropdown
        try {
            $warehouseStmt = $db->query("SELECT wid, wl, wn, wmps FROM warehouse ORDER BY wn ASC");
            $warehouses = $warehouseStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $warehouses = [];
            $error = "Error fetching warehouses: " . $e->getMessage();
        }
        // Map wmps values to readable parcel size
        function wmpsLabel($wmps) {
            switch ($wmps) {
                case 3375:     return "Accepts XS Parcels";
                case 27000:    return "Accepts XS-S Parcels";
                case 216000:   return "Accepts XS-M Parcels";
                case 729000:   return "Accepts XS-L Parcels";
                case 1728000:  return "Accepts All Parcels";
                default:       return "Accepts Parcels Up To " . $wmps;
            }
        }

        // Fetches all Recipient data from active EZ-Ship Users and Temp Recipients
        // Function to fetch all recipients (registered + temp)
        function fetchRecipients(PDO $db): array {
            // Fetch registered recipients
            $recipientsStmt = $db->prepare("SELECT rid, rn, rl FROM recipient ORDER BY rn ASC");
            $recipientsStmt->execute();
            $recipients = $recipientsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch temporary recipients
            $tempRecipientsStmt = $db->prepare("SELECT trid_num, rn, rl FROM temp_recipient ORDER BY created_at DESC");
            $tempRecipientsStmt->execute();
            $tempRecipients = $tempRecipientsStmt->fetchAll(PDO::FETCH_ASSOC);

            return ['recipients' => $recipients, 'tempRecipients' => $tempRecipients];
        }
        // Stores data for both registered recipients and temporary recipients in separate variables for use in form dropdowns.
        $recipientData = fetchRecipients($db);
        $recipients = $recipientData['recipients'];
        $tempRecipients = $recipientData['tempRecipients'];

        //Stores session data variables for use in parcel submission and other client-specific functionality.
        $username = htmlspecialchars($_SESSION['username']);
        $role     = htmlspecialchars($_SESSION['role']);
        $userId   = $_SESSION['user_id'];

        if (isset($_POST['submit_parcel'])) {
            $cid = $userId; // Logged-in client ID

            // Sanitize inputs and assign to variables.
            $ps = filter_var($_POST['ps'], FILTER_SANITIZE_NUMBER_INT);
            $pd = filter_var($_POST['pd'], FILTER_SANITIZE_STRING);
            $pv = filter_var($_POST['pv'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $pt = filter_var($_POST['pt'], FILTER_SANITIZE_STRING);
            $wid = filter_var($_POST['wid'], FILTER_SANITIZE_NUMBER_INT);
            $rid_R_T_N = $_POST['recipient'] ?? null; // Could be "new", existing rid, temp, or null

            // Handles New recipient fields.
            $new_rn = isset($_POST['new_rn']) ? trim($_POST['new_rn']) : null;
            $new_rl = isset($_POST['new_rl']) ? trim($_POST['new_rl']) : null;

            // Validation of proper form entries.
            if (empty($ps) || empty($pd) || empty($pv) || empty($pt) || empty($wid)) {
                $error = "All fields are required.";
            } 
            else {
                try {
                    $db->beginTransaction();

                    // --- 1) Handle recipient ---
                    $rid = null;
                    $recipient_type = null;

                    if ($rid_R_T_N === 'new') {
                        // Validate that both name and address are provided
                        if (empty($new_rn) || empty($new_rl)) {
                            throw new Exception("You must provide both name and address for a new recipient.");
                        }
                        // Insert new temp recipient
                        $stmt = $db->prepare("INSERT INTO temp_recipient (rn, rl) VALUES (?, ?)");
                        $stmt->execute([$new_rn, $new_rl]);
                        $rid = $db->lastInsertId();
                        $recipient_type = 'T';
                    } 
                    elseif (!empty($rid_R_T_N)) {
                        // Check if it is a temporary recipient
                        if (str_starts_with($rid_R_T_N, 'T')) {
                            $rid = intval(substr($rid_R_T_N, 1)); // remove 'T' prefix
                            $recipient_type = 'T';
                        } else {
                            $rid = intval($rid_R_T_N);
                            $recipient_type = 'R';
                        }
                    } 
                    else {
                        throw new Exception("You must select an existing recipient or add a new one.");
                    }

                    // 2) Insert into parcels table.
                    $stmt = $db->prepare("INSERT INTO parcels (ps, pd, pv, pt) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$ps, $pd, $pv, $pt]);
                    $pid = $db->lastInsertId();

                    // 3)Insert into parcel_routing table with either the new temp recipient ID or the existing recipient ID,
                    // along with the selected warehouse and client ID. Driver ID is NULL at this point since it hasn't been assigned yet. 
                    $stmt = $db->prepare("INSERT INTO parcel_routing (pid, cid, wid, did, rid, recipient_type)
                        VALUES (?, ?, ?, NULL, ?, ?)
                    ");
                    $stmt->execute([$pid, $cid, $wid, $rid, $recipient_type]);

                    // 4) Insert into parcel_time with current timestamp for pct (Parcel Creation Time).
                    $stmt = $db->prepare("INSERT INTO parcel_time (pid, pct) VALUES (?, NOW())");
                    $stmt->execute([$pid]);

                    // 5) Insert into parcel_location with the initial location set to as the client's ID 
                    // since they are the ones starting with the parcel and then dropping it off at the selected warehouse.
                    $stmt = $db->prepare("INSERT INTO parcel_location (pid, pl) VALUES (?, ?)");
                    $stmt->execute([$pid, $cid]);

                    // Commit transaction if all operations were successful.
                    if ($db->commit()) {

                        // Sets a success message for client to appear after redirect.
                        $_SESSION['success'] = "Parcel submitted successfully!";
                        // Redirect to the same page to prevent form resubmission
                        header("Location: client.php?success=1");
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
        }
        
        // Fetch all parcels for logged-in client
        try {
        $parcelStmt = $db->prepare("
            SELECT 
                p.pid, p.ps, p.pd, p.pv, p.pt,
                pr.wid,
                w.wn AS warehouse_name,
                pr.rid,
                pr.recipient_type,
                COALESCE(c.cn, w2.wn, d.dn, 'Delivered') AS current_location_name,
                r.rl AS r_rl,
                t.rl AS t_rl
            FROM parcel_routing pr
            JOIN parcels p ON pr.pid = p.pid
            JOIN warehouse w ON pr.wid = w.wid
            LEFT JOIN recipient r ON pr.rid = r.rid
            LEFT JOIN temp_recipient t ON pr.rid = t.trid_num
            LEFT JOIN parcel_location pl ON p.pid = pl.pid
            LEFT JOIN client c ON pl.pl = c.cid
            LEFT JOIN warehouse w2 ON pl.pl = w2.wid
            LEFT JOIN driver d ON pl.pl = d.did
            WHERE pr.cid = :cid
            ORDER BY p.pid DESC
        ");
        $parcelStmt->execute(['cid' => $userId]);
        $clientParcels = $parcelStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            $clientParcels = [];
            $error = "Error fetching parcels: " . $e->getMessage();
        }
        // Displays any success or error messages related to parcel submission or other actions taken on the page.
        require_once __DIR__ . '/../includes/messages.php';
        ?>

        <!-- Displays the parcel submission form with dynamic dropdowns for warehouses and recipients, 
            as well as conditional fields for adding a new recipient. -->
        <div class="parcel-container">
            <form action="client.php" method="post" class="parcel-form">
                <h2>Ship A Parcel</h2>

                <label for="ps"><h3>Select The Parcel Size</h3></label>
                    <select name="ps" id="ps" required>
                        <option value="">-- Select Parcel Size --</option>
                        <option value="3375">XS - Smaller Than 6 Inches</option>
                        <option value="27000">S - Smaller Than 1 Foot</option>
                        <option value="216000">M - Smaller Than 2 Feet</option>
                        <option value="729000">L - Smaller Than 3 Feet</option>
                        <option value="1728000">XL - Smaller Than 4 Feet</option>
                    </select>

                <label for="pd"><h3>Parcel Description</h3></label>
                <input type="text" name="pd" id="pd" maxlength="255" required>

                <label for="pv"><h3>Parcel Value</h3></label>
                <input type="number" step="0.01" name="pv" id="pv" required>

                <label for="pt"><h3>Parcel Type</h3></label>
                <select name="pt" id="pt" required>
                    <option value="Neutral" selected>Neutral (None)</option>
                    <option value="Fragile">Fragile</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Perishable">Perishable</option>
                    <option value="Liquid">Liquid</option>
                    <option value="Flammable">Flammable</option>
                    <option value="BioHazard">BioHazard</option>
                </select>

                <!-- Displays the available warehouse options based on ps in form selection -->
                <label for="wid"><h3>Select Drop Off Warehouse</h3></label>
                <select name="wid" id="wid" required>
                    <option value="">Choose a Warehouse</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo $wh['wid']; ?>" data-max-size="<?php echo $wh['wmps']; ?>">
                            <?php
                                echo htmlspecialchars($wh['wn'] . ' - ' . $wh['wl']);
                                echo ' -  (' . wmpsLabel($wh['wmps']) . ')';
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Displays the ability to input a new recipient or select an existing recipient -->
                <label for="recipient"><h3>Select Recipient</h3></label>
                <select name="recipient" id="recipient" required>
                    <option value="">Choose/Add Recipient</option>

                    <!-- Add New Recipient comes second -->
                    <option value="new">Add New Recipient</option>

                    <!-- Registered recipients -->
                    <?php foreach ($recipients as $rec): ?>
                        <option value="<?php echo $rec['rid']; ?>">
                            <?php echo htmlspecialchars($rec['rn'] . ' - ' . $rec['rl']); ?>
                        </option>
                    <?php endforeach; ?>

                    <!-- Temp recipients -->
                    <?php foreach ($tempRecipients as $temp): ?>
                        <option value="T<?php echo $temp['trid_num']; ?>">
                            <?php echo htmlspecialchars($temp['rn'] . ' - ' . $temp['rl']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- New recipient input fields (hidden by default) -->
                <div id="new-recipient-fields" style="display: none;">
                    <label for="new_rn">Recipient Name</label>
                    <input type="text" name="new_rn" id="new_rn" maxlength="150" placeholder="Enter recipient name">

                    <label for="new_rl">Recipient Address</label>
                    <input type="text" name="new_rl" id="new_rl" maxlength="255" placeholder="Enter recipient address">
                </div>

                <button type="submit" name="submit_parcel">Submit Parcel</button>
            </form>
        
            <div class="parcel-right">
                <h2>Your Parcels</h2>
                <?php if (!empty($clientParcels)) : ?>
                    <table class="parcel-table">
                        <thead>
                            <tr>
                                <th>Parcel ID</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Size</th>
                                <th>Destination Warehouse</th>
                                <th>Destination Recipient</th>
                                <th>Current Location</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clientParcels as $parcel) : ?>
                            <tr>
                                <td><?= htmlspecialchars($parcel['pid']) ?></td>
                                <td><?= htmlspecialchars($parcel['pd']) ?></td>
                                <td><?= htmlspecialchars($parcel['pt']) ?></td>
                                <td>$<?= htmlspecialchars($parcel['pv']) ?></td>
                                <td><?= htmlspecialchars($parcel['ps']) ?></td>
                                <td><?= htmlspecialchars($parcel['warehouse_name']) ?></td>
                                <td>
                                <?= htmlspecialchars(
                                        $parcel['recipient_type'] === 'R'
                                            ? $parcel['r_rl']   // address from recipient table
                                            : $parcel['t_rl']   // address from temp_recipient table
                                    ) ?>
                                </td>
                                <td><?= htmlspecialchars($parcel['current_location_name']) ?></td>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>No parcels found.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<!-- Imports The Java Script File -->
<script src="/capstone_webapp/assets/js/main.js"></script>
</body>
</html>