<?php
// Includes the configuration and database connection files.
require_once __DIR__ . '/../includes/config.php'; // Handles Global Variables and Starting Session.
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $error = "";

    // --- Security validation before hashing ---
    if (strlen($username) < 6) {
        $error = "Username must be at least 6 characters.";
    } 
    elseif (strlen($password) < 12) {
        $error = "Password must be at least 12 characters.";
    } 
    elseif (!preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/\d/', $password) ||
            !preg_match('/[\W_]/', $password)) {
        $error = "Password must include uppercase, lowercase, number, and special character.";
    }
    if (empty($error)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? '';
        
        try {
            $db->beginTransaction();

            // Insert into users table
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $role]);

            // Get the generated uid
            $uid = $db->lastInsertId();

            // Insert into role-specific table
            switch ($role) {
                case 'client':
                    $stmt2 = $db->prepare("INSERT INTO client (cid, cn, cl) VALUES (?, ?, ?)");
                    $stmt2->execute([$uid, $_POST['cn'], $_POST['cl']]);
                    break;

                case 'warehouse':
                    $stmt2 = $db->prepare("INSERT INTO warehouse (wid, wn, wl, wcs, wmps) VALUES (?, ?, ?, ?, ?)");
                    $stmt2->execute([$uid, $_POST['wn'], $_POST['wl'], $_POST['wcs'], $_POST['wmps']]);
                    $stmt3 =$db->prepare("INSERT INTO warehouse_storage (wid, wp, wes, wcs) VALUES (?,0,?,0)");
                    $stmt3->execute([$uid, $_POST['wcs']]);
                    break;

                case 'driver':
                    $stmt2 = $db->prepare("INSERT INTO driver (did, dn, dv, dmd) VALUES (?, ?, ?, ?)");
                    $stmt2->execute([$uid, $_POST['dn'], $_POST['dv'], $_POST['dmd']]);
                    break;

                case 'recipient':
                    $stmt2 = $db->prepare("INSERT INTO recipient (rid, rl, rn, rdi) VALUES (?, ?, ?, ?)");
                    $stmt2->execute([$uid, $_POST['rl'], $_POST['rn'], $_POST['rdi']]);
                    break;

                default:
                    throw new Exception("Invalid role selected");
            }
            $db->commit();
            $success = "Account created successfully! You can now log in.";
        } 
        catch (PDOException $e) {
            $db->rollBack();
            // Check for duplicate password entry error.
            if ($e->getCode() == 23000) {
                $error = "That username is already taken. Please choose another.";
            } 
            else {
                // Generic error for other database issues
                $error = "Signup failed due to a server error. Please try again later.";
                // Optional: log the real error somewhere for debugging
                error_log($e->getMessage());
            }
        }
    }
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $username =$_POST['username'];
        $password = $_POST['password'];

        $stmt = $db->prepare("SELECT uid, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "No user found for: " . htmlspecialchars($username);
        }

        if ($user) {
            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $uid = $user['uid'];
                $role = $user['role'];

                $_SESSION['user_id'] = $uid;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                // Redirect based on role
                switch ($role) {
                    case 'client':      header("Location: client.php"); exit();
                    case 'warehouse':   header("Location: warehouse.php"); exit();
                    case 'driver':      header("Location: driver.php"); exit();
                    case 'recipient':   header("Location: recipient.php"); exit();
                    default:
                        session_destroy();
                        $error = "Invalid user role.";
                }
            } 
            else {
                // Password incorrect
                $error = "Invalid password.";
            }
        } 
        else {
            // Username not found
            $error = "Invalid username: " . htmlspecialchars($username);
        }
    }
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
require_once __DIR__ . '/../includes/messages.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-left">
        <!-- SIGN UP FORMS -->
        <h2>Sign Up</h2>
        <!-- Governs the form that is populated on screen -->
        <select id="signup-role-select">
            <option value="">Select Role</option>
            <option value="client">Client</option>
            <option value="warehouse">Warehouse</option>
            <option value="driver">Driver</option>
            <option value="recipient">Recipient</option>
        </select>

        <!-- CLIENT FORM -->
        <form class="role-form" id="form-client" method="post" style="display:none;">
            <input type="hidden" name="role" value="client"> <!-- Hidden client value -->
            <input type="text" name="username" placeholder="Username" required minlength="6">
            <input type="password" name="password" placeholder="Password" required minlength="12"
                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+"
                title="Password must be at least 8 characters, include uppercase, lowercase, number, and special character">
            <input type="text" name="cn" placeholder="Company Name" required>
            <input type="text" name="cl" placeholder="Company Address" required>
            <button type="submit" name="signup">Sign Up</button>
        </form>

        <!-- WAREHOUSE FORM -->
        <form class="role-form" id="form-warehouse" method="post" style="display:none;">
            <input type="hidden" name="role" value="warehouse"> <!-- Hidden Warehouse Value -->
            <input type="text" name="username" placeholder="Username" required minlength="6">
            <input type="password" name="password" placeholder="Password" required minlength="12"
                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+"
                title="Password must be at least 8 characters, include uppercase, lowercase, number, and special character">
            <input type="text" name="wn" placeholder="Warehouse Name" required>
            <input type="text" name="wl" placeholder="Warehouse Address" required>
            <label for="wcs"><h3>Select Your Approximate Warehouse Size</h3></label>
                <select name="wcs" id="wcs" required>
                    <option value="">-- Select Warehouse Size --</option>
                    <option value="340000">Small - A Single Shelf (12 Cubic Feet)</option>
                    <option value="1020000">Medium - Multiple Shelves (36 Cubic Feet)</option>
                    <option value="3060000">Large - A Storage Closet (108 Cubic Feet)</option>
                    <option value="9180000">Extra Large - An Entire Room (324 Cubic Feet)</option>
                </select>
            <label for="wmps"><h3>Select Your Maximum Parcel Size Accepted</h3></label>
                <select name="wmps" id="wmps" required>
                    <option value="">-- Select Maximum Parcel Size --</option>
                    <option value="3375">XS - An Item No Bigger Than 6 Inches</option>
                    <option value="27000">S - An Item No Bigger Than 1 Foot</option>
                    <option value="216000">M - An Item No Bigger Than 2 Feet</option>
                    <option value="729000">L - Items No Bigger Than 3 Feet</option>
                    <option value="1728000">XL - Items No Bigger Than 4 Feet</option>
                </select>
            <button type="submit" name="signup">Sign Up</button>
        </form>

        <!-- DRIVER FORM -->
        <form class="role-form" id="form-driver" method="post" style="display:none;">
            <input type="hidden" name="role" value="driver"> <!-- Hidden driver value -->
            <input type="text" name="username" placeholder="Username" required minlength="6">
            <input type="password" name="password" placeholder="Password" required minlength="12"
                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+"
                title="Password must be at least 8 characters, include uppercase, lowercase, number, and special character">
            <input type="text" name="dn" placeholder="Driver Name" required>
            <input type="text" name="dv" placeholder="Vehicle Year, Make & Model" required>
            <label for="dmd"><h3>Select The Maximum Parcel Size You Can Deliver</h3></label>
                <select name="dmd" id="dmd" required>
                    <option value="">-- Select Maximum Deliverable Size --</option>
                    <option value="3375">XS - You Can Deliver Items Up To 6x6x6 Inches</option>
                    <option value="27000">S - You Can Deliver Items Up To 1x1x1 Foot</option>
                    <option value="216000">M - You Can Deliver Items Up To 2x2x2 Feet</option>
                    <option value="729000">L - You Can Deliver Items Up To 3x3x3 Feet</option>
                    <option value="1728000">XL - You Can Deliver Items Up To 4x4x4 Feet</option>
                </select>
            <button type="submit" name="signup">Sign Up</button>
        </form>

        <!-- RECIPIENT FORM -->
        <form class="role-form" id="form-recipient" method="post" style="display:none;">
            <input type="hidden" name="role" value="recipient"> <!-- hidden recipient value -->
            <input type="text" name="username" placeholder="Username" required minlength="6">
            <input type="password" name="password" placeholder="Password" required minlength="12"
                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+"
                title="Password must be at least 12 characters, include uppercase, lowercase, number, and special character">
            <input type="text" name="rn" placeholder="First and Last Name" required>
            <input type="text" name="rl" placeholder="Address" required>
            <input type="text" name="rdi" placeholder="Delivery Instructions (Optional)">
            <button type="submit" name="signup">Sign Up</button>
        </form>
    </div>

    <div class="auth-right">
        <!-- LOGIN FORM -->
        <form class="auth-form" method="post">
            <h2>Login</h2>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>

</div>
</body>

<!-- Imports The Java Script File -->
<script src="/capstone_webapp/assets/js/main.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

</html>