<?php
session_start();

if (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
    if (defined('DB_HOST') && defined('DB_NAME') && DB_HOST != '' && DB_NAME != '') {
        // Simple check to ensure testing connection passes
        $testConnection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$testConnection->connect_error) {
            die("Application is already installed ! Please delete install.php for security reasons.");
        }
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 2) {
    // Collect variables
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];
    
    $smtp_host = $_POST['smtp_host'];
    $smtp_user = $_POST['smtp_user'];
    $smtp_pass = $_POST['smtp_pass'];
    $smtp_port = $_POST['smtp_port'];
    $smtp_secure = $_POST['smtp_secure'];
    $smtp_from_email = $_POST['smtp_from_email'];
    $smtp_from_name = $_POST['smtp_from_name'];
    
    $admin_email = $_POST['admin_email'];
    $site_url = $_POST['site_url'];

    // Test DB Connection
    $db = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($db->connect_error) {
        $error = "Database connection failed ! Please check your credentials. (" . $db->connect_error . ")";
    } else {
        // Try creating includes folder if not exist
        if (!file_exists(__DIR__ . '/includes')) {
            mkdir(__DIR__ . '/includes', 0755, true);
        }

        // Generate Config File
        $config_content = "<?php\n"
        . "// Database Configuration\n"
        . "define('DB_HOST', '" . addslashes($db_host) . "');\n"
        . "define('DB_USER', '" . addslashes($db_user) . "');\n"
        . "define('DB_PASS', '" . addslashes($db_pass) . "');\n"
        . "define('DB_NAME', '" . addslashes($db_name) . "');\n\n"
        . "// Email (SMTP) Configuration\n"
        . "define('SMTP_HOST', '" . addslashes($smtp_host) . "');\n"
        . "define('SMTP_USER', '" . addslashes($smtp_user) . "');\n"
        . "define('SMTP_PASS', '" . addslashes($smtp_pass) . "');\n"
        . "define('SMTP_PORT', " . (int)$smtp_port . ");\n"
        . "define('SMTP_SECURE', '" . addslashes($smtp_secure) . "');\n"
        . "define('SMTP_FROM_EMAIL', '" . addslashes($smtp_from_email) . "');\n"
        . "define('SMTP_FROM_NAME', '" . addslashes($smtp_from_name) . "');\n\n"
        . "// System Configuration\n"
        . "define('ADMIN_EMAIL', '" . addslashes($admin_email) . "');\n"
        . "define('SITE_URL', '" . addslashes($site_url) . "');\n";
        
        if (file_put_contents(__DIR__ . '/includes/config.php', $config_content)) {
            // Import SQL execution
            if (file_exists('database.sql')) {
                $sql = file_get_contents('database.sql');
                $db->multi_query($sql);
                // Clear out multi query buffer
                while ($db->next_result()) {;}
            }
            
            // Redirect to step 3
            header("Location: install.php?step=3");
            exit;
        } else {
            $error = "Failed to write config.php! Check folder permissions.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment System - Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f7fa; font-family: 'Poppins', sans-serif; }
        .installer-container { max-width: 800px; margin: 40px auto; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: none; }
        .card-header { background: #0d6efd; color: white; border-radius: 15px 15px 0 0 !important; padding: 20px; }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="card">
            <div class="card-header text-center">
                <h3><i class="fas fa-magic me-2"></i> System Installer</h3>
            </div>
            <div class="card-body p-4">
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                    <div class="text-center">
                        <h4 class="mb-4">Welcome to the Appointment System Setup Wizard!</h4>
                        <p>This wizard will help you configure your database and email parameters.</p>
                        <p>Before proceeding, please make sure you have your MySQL Database Details and SMTP Email Credentials ready.</p>
                        <a href="?step=2" class="btn btn-primary btn-lg mt-3">Start Installation</a>
                    </div>
                
                <?php elseif ($step == 2): ?>
                    <form method="POST" action="?step=2">
                        <!-- Database Setup -->
                        <h5 class="mb-3 text-primary border-bottom pb-2"><i class="fas fa-database me-2"></i> Database Setup</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Database Host</label>
                                <input type="text" name="db_host" class="form-control" value="localhost" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database Name</label>
                                <input type="text" name="db_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database User</label>
                                <input type="text" name="db_user" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database Password</label>
                                <input type="password" name="db_pass" class="form-control">
                            </div>
                        </div>

                        <!-- General Setup -->
                        <h5 class="mb-3 text-primary border-bottom pb-2"><i class="fas fa-globe me-2"></i> General Setup</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Site URL <small class="text-muted">(with http://)</small></label>
                                <input type="url" name="site_url" class="form-control" placeholder="https://yourdomain.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Admin Email <small class="text-muted">(Receives notifications)</small></label>
                                <input type="email" name="admin_email" class="form-control" required>
                            </div>
                        </div>

                        <!-- Email Setup -->
                        <h5 class="mb-3 text-primary border-bottom pb-2"><i class="fas fa-envelope me-2"></i> Email (SMTP) Setup</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control" placeholder="smtp.gmail.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" name="smtp_port" class="form-control" placeholder="465" value="465" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" name="smtp_user" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" name="smtp_pass" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Encryption</label>
                                <select name="smtp_secure" class="form-select">
                                    <option value="ssl">SSL (Port 465)</option>
                                    <option value="tls">TLS (Port 587)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">From Email Address</label>
                                <input type="email" name="smtp_from_email" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">From Name</label>
                                <input type="text" name="smtp_from_name" class="form-control" value="Appointment System" required>
                            </div>
                        </div>

                        <div class="text-center mt-4 border-top pt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-5">Install System</button>
                        </div>
                    </form>

                <?php elseif ($step == 3): ?>
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem; margin-bottom: 20px;"></i>
                        <h4 class="mb-3 text-success">Installation Completed Automatically!</h4>
                        <p>Your database schema has been imported and configuration file created.</p>
                        
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-shield-alt me-2"></i> <strong>Important Security Notice:</strong>
                            <p class="mb-0 mt-2">Please delete <code>install.php</code> and <code>database.sql</code> from your server root directories to prevent unauthorized re-installation.</p>
                        </div>

                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary me-2"><i class="fas fa-home me-2"></i> Go to Homepage</a>
                            <a href="admin.php" class="btn btn-dark"><i class="fas fa-user-shield me-2"></i> Admin Panel</a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>
