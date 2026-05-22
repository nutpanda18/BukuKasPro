<?php
require_once dirname(__DIR__) . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Prepare query to fetch user from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Verify the encrypted password match
        if ($user && password_verify($password, $user['password'])) {
            
            // Calculate cookie life cycle expiration timestamp (1 day = 24 hours)
            $cookie_expire = time() + (86400 * 1); 

            // ==========================================================================
            // CENTRALIZED COOKIE INJECTION ENGINE
            // ==========================================================================
            // Using the "/" argument guarantees these credentials can be accessed 
            // inside both the 'proses/' folder and 'views/' folder cleanly.
            setcookie('user_id', $user['id'], $cookie_expire, "/");
            setcookie('nama', $user['nama'], $cookie_expire, "/");
            setcookie('role', $user['role'], $cookie_expire, "/");

            // DYNAMIC ROUTING SYSTEM BASED ON USER ROLE (RBAC via COOKIES)
            switch ($user['role']) {
                case 'owner':
                    header("Location: ../views/dashboard_owner.php");
                    break;
                case 'finance':
                    header("Location: ../views/dashboard_finance.php");
                    break;
                case 'cashier':
                    header("Location: ../views/dashboard_cashier.php");
                    break;
                default:
                    // Clean up cookie traits instantly if a ghost/unassigned role hits the gateway
                    setcookie('user_id', '', time() - 3600, "/");
                    setcookie('nama', '', time() - 3600, "/");
                    setcookie('role', '', time() - 3600, "/");
                    die("Error: Akun Anda tidak memiliki peran akses valid.");
            }
            exit;
            
        } else {
            // Authentication failure script
            echo "<script>alert('Gagal! Identitas akun salah.'); window.location='../login.php';</script>";
            exit;
        }
    } catch (PDOException $e) {
        die("Proses login bermasalah: " . $e->getMessage());
    }
} else {
    echo "Please submit the login form.";
}
?>