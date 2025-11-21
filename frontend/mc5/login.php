<?php
session_start();
require_once '../../config.php';

// Se già loggato, redirect
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$message = "";
$messageType = "danger";

// Gestione messaggi da URL
if (isset($_GET['logged_out'])) {
    $message = "Logout effettuato con successo.";
    $messageType = "success";
}

if (isset($_GET['session_expired'])) {
    $message = "La tua sessione è scaduta. Effettua nuovamente il login.";
    $messageType = "warning";
}

if (isset($_GET['password_changed'])) {
    $message = "Password aggiornata con successo! Ora puoi effettuare il login.";
    $messageType = "success";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validazione base
    if (empty($email) || empty($password)) {
        $message = "Tutti i campi sono obbligatori.";
        $messageType = "warning";
    } else {
        $stmt = $conn->prepare("SELECT id, email, password, bu, password_last_changed, force_password_change, active, last_login FROM users WHERE email = ? AND active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verifica password
            if (password_verify($password, $row['password'])) {

                // Controllo scadenza password (45 giorni)
                $passwordLastChanged = new DateTime($row['password_last_changed']);
                $now = new DateTime();
                $daysSinceLastChange = $now->diff($passwordLastChanged)->days;

                if ($daysSinceLastChange >= 45) {
                    $_SESSION['reset_password_user'] = $email;
                    $_SESSION['password_expired'] = true;
                    header("Location: reset_password.php?expired=1");
                    exit;
                }

                // Controllo cambio password forzato
                if ($row['force_password_change'] == 1) {
                    $_SESSION['reset_password_user'] = $email;
                    $_SESSION['force_password_change'] = true;
                    header("Location: reset_password.php?force_change=1");
                    exit;
                }

                // Login riuscito - aggiorna last_login
                $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $row['id']);
                $updateStmt->execute();
                $updateStmt->close();

                // Imposta sessione
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['email'];
                $_SESSION['bu'] = $row['bu'];
                $_SESSION['login_time'] = time();

                header("Location: index.php");
                exit;
            } else {
                $message = "Email o password non corretti.";
                $messageType = "danger";
            }
        } else {
            $message = "Email o password non corretti.";
            $messageType = "danger";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MC5 Login - PayGlobe</title>

    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome 6.5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Design System - Modern Style -->
    <link rel="stylesheet" href="assets/css/design-modern.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            position: relative;
            overflow: hidden;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.03) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            margin: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-2xl);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: var(--space-8);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }

        .logo-container {
            position: relative;
            z-index: 1;
            margin-bottom: var(--space-4);
        }

        .logo {
            width: 180px;
            height: auto;
            filter: brightness(0) invert(1);
        }

        .login-header h1 {
            color: white;
            font-size: var(--text-2xl);
            font-weight: var(--font-extrabold);
            margin: 0 0 var(--space-2);
            position: relative;
            z-index: 1;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: var(--text-sm);
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .version-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: var(--font-bold);
            margin-top: var(--space-3);
            backdrop-filter: blur(10px);
        }

        .login-body {
            padding: var(--space-8);
        }

        .form-group {
            position: relative;
            margin-bottom: var(--space-6);
        }

        .form-label {
            display: block;
            color: var(--gray-700);
            font-size: var(--text-sm);
            font-weight: var(--font-semibold);
            margin-bottom: var(--space-2);
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: var(--space-4);
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: var(--text-lg);
            pointer-events: none;
            z-index: 2;
        }

        .form-control {
            width: 100%;
            padding: var(--space-4) var(--space-4) var(--space-4) var(--space-12);
            font-size: var(--text-base);
            font-family: inherit;
            color: var(--gray-900);
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            transition: all var(--transition-base);
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            background: white;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-control::placeholder {
            color: var(--gray-400);
        }

        .toggle-password {
            position: absolute;
            right: var(--space-4);
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: var(--space-2);
            font-size: var(--text-lg);
            transition: color var(--transition-base);
            z-index: 2;
        }

        .toggle-password:hover {
            color: var(--primary-500);
        }

        .btn-login {
            width: 100%;
            padding: var(--space-4);
            font-size: var(--text-base);
            font-weight: var(--font-bold);
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all var(--transition-base);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            font-family: inherit;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .forgot-link {
            display: block;
            text-align: center;
            margin-top: var(--space-5);
            color: var(--primary-600);
            text-decoration: none;
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
            transition: color var(--transition-base);
        }

        .forgot-link:hover {
            color: var(--primary-700);
            text-decoration: underline;
        }

        .alert {
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-5);
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-size: var(--text-sm);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .security-features {
            display: flex;
            justify-content: center;
            gap: var(--space-6);
            margin-top: var(--space-8);
            padding-top: var(--space-6);
            border-top: 1px solid var(--gray-200);
        }

        .security-feature {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--gray-600);
            font-size: var(--text-xs);
        }

        .security-feature i {
            color: var(--success-500);
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 10px;
            }

            .login-header,
            .login-body {
                padding: var(--space-6);
            }

            .security-features {
                flex-direction: column;
                gap: var(--space-3);
                align-items: center;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <svg class="logo" viewBox="0 0 761.13 177.1" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <style>
                                .cls-1 { fill: #ffffff; }
                                .cls-2 { fill: #ffffff; }
                                .cls-3 { fill: #ffffff; }
                            </style>
                        </defs>
                        <g>
                            <path class="cls-2" d="m431.25,46.38c20.22,0,30.1,10,30.1,10l-8.73,10.34s-7.7-6.78-21.03-6.78c-20.68,0-34.01,17.12-34.01,34.7,0,14.36,9.65,22.52,22.4,22.52s22.29-8.85,22.29-8.85l1.84-9.19h-12.64l2.53-12.75h25.85l-8.39,42.97h-12.64l.8-4.02c.34-1.72.81-3.45.81-3.45h-.23s-8.5,8.85-23.32,8.85c-18.73,0-34.58-13.44-34.58-35.16,0-26.66,21.83-49.18,48.94-49.18"/>
                            <polygon class="cls-2" points="484.11 47.76 498.93 47.76 485.49 116.59 520.76 116.59 518.11 129.34 468.25 129.34 484.11 47.76"/>
                            <path class="cls-2" d="m577.86,46.39c22.4,0,37.23,14.71,37.23,35.04,0,26.65-23.78,49.29-48.37,49.29-22.52,0-37.11-15.05-37.11-35.85,0-26.2,23.44-48.49,48.26-48.49m-11.03,70.77c16.31,0,32.97-15.51,32.97-34.81,0-13.33-9.08-22.4-22.06-22.4-16.66,0-32.97,15.28-32.97,34.12,0,13.67,9.08,23.09,22.06,23.09"/>
                            <path class="cls-2" d="m637.85,47.76h26.2c4.71,0,8.85.46,12.29,1.72,7.47,2.64,11.83,8.73,11.83,16.77,0,8.62-5.28,16.43-13.33,20.11v.23c6.55,2.41,9.88,8.62,9.88,15.85,0,12.52-7.81,21.26-18.27,24.93-3.91,1.38-8.16,1.95-12.41,1.95h-32.05l15.85-81.57Zm17.23,68.82c2.53,0,4.83-.46,6.78-1.5,4.59-2.41,7.7-7.35,7.7-12.98s-3.56-9.08-9.88-9.08h-15.85l-4.6,23.56h15.85Zm5.4-35.5c7.24,0,12.41-5.86,12.41-12.87,0-4.48-2.64-7.7-8.62-7.7h-14.13l-4.02,20.57h14.36Z"/>
                            <polygon class="cls-2" points="712.42 47.76 761.13 47.76 758.61 60.52 724.6 60.52 720.46 81.89 747.92 81.89 745.39 94.64 717.93 94.64 713.68 116.59 749.53 116.59 747.12 129.34 696.45 129.34 712.42 47.76"/>
                            <path class="cls-2" d="m189.47,48.68h29.64c14.82,0,25.51,10,25.51,25.39s-10.68,25.73-25.51,25.73h-18.27v29.99h-11.37V48.68Zm27.8,41.25c9.77,0,15.74-6.09,15.74-15.85s-5.97-15.51-15.63-15.51h-16.54v31.37h16.43Z"/>
                            <path class="cls-2" d="m297.02,106.47h-30.56l-8.04,23.32h-11.72l29.18-81.12h11.95l29.18,81.12h-11.83l-8.16-23.32Zm-15.28-46.65s-1.84,7.35-3.22,11.49l-9.08,25.73h24.59l-8.96-25.73c-1.38-4.14-3.1-11.49-3.1-11.49h-.23Z"/>
                            <path class="cls-2" d="m341.14,95.44l-27.23-46.76h12.87l15.05,26.66c2.53,4.48,4.94,10.23,4.94,10.23h.23s2.41-5.63,4.94-10.23l14.82-26.66h12.87l-27.11,46.76v34.35h-11.38v-34.35Z"/>
                            <path class="cls-1" d="m103.24,30.59c7.31-6.18,8.23-17.12,2.06-24.44-6.19-7.32-17.12-8.24-24.44-2.06-7.31,6.18-8.24,17.12-2.06,24.44,6.18,7.31,17.12,8.24,24.44,2.06Z"/>
                            <path class="cls-3" d="m139.67,142.35c28.74-41.92,13.35-99.92-20.92-105.49-41.38-6.95-66.36,65.65-58.42,65.09,0,0,0,0,0,0,4.6-.32,16.89-20,31.61-32.54,5.54-4.7,15.12-11.34,21.88-8.72,6.4,2.54,9.62,12.45,10.5,19.65,2.1,16.81-4.75,32.71-9.57,38.9-13.07,16.81-31.6,26.31-46.84,27.13-24.12,1.29-45.56-14.91-52.06-37.52-6.55-22.71,2.28-50.29,26.54-68.58,0,0,0,0,0,0,3.45-2.59,5.56-3.81,5.55-3.83,0,0-33.33,11.61-44.62,47.17-18.78,58.97,45.13,114.34,106.28,85.75,5.61-2.72,15.19-9.32,23.24-18.33-.03.04-.07.07-.1.11.15-.16.31-.33.45-.49,2.36-2.67,4.51-5.44,6.45-8.28"/>
                        </g>
                    </svg>
                </div>
                <h1>MC5 v3.0</h1>
                <p>Merchant Console PayGlobe</p>
                <span class="version-badge">MODERN DASHBOARD</span>
            </div>

            <div class="login-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <i class="fas fa-<?= $messageType === 'danger' ? 'exclamation-circle' : ($messageType === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label class="form-label" for="email">Indirizzo Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="tua@email.com"
                                required
                                autocomplete="email"
                                value="<?= htmlspecialchars($email ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Inserisci la password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Accedi a MC5
                    </button>

                    <a href="forgot_password.php" class="forgot-link">
                        <i class="fas fa-question-circle"></i> Password dimenticata?
                    </a>
                </form>

                <div class="security-features">
                    <div class="security-feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Connessione Sicura</span>
                    </div>
                    <div class="security-feature">
                        <i class="fas fa-lock"></i>
                        <span>Dati Crittografati</span>
                    </div>
                    <div class="security-feature">
                        <i class="fas fa-clock"></i>
                        <span>Sessione 24h</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }

        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Tutti i campi sono obbligatori');
                return false;
            }

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accesso in corso...';
            submitBtn.disabled = true;

            // Restore button after timeout (fallback)
            setTimeout(function() {
                submitBtn.innerHTML = originalHTML;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Auto-focus on email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>
