<?php
session_start();
include 'config.php';

// VARIABILE GLOBALE PER ABILITARE/DISABILITARE CAPTCHA
$ENABLE_CAPTCHA = false; // Cambia a true per abilitare reCAPTCHA

// reCAPTCHA configuration (solo se abilitato)
$recaptchaSecretKey = '6LfgN_cqAAAAAMUhyVByEWjcv-hKzPDUf-CE1KZb';
$recaptchaSiteKey = '6LfgN_cqAAAAADShbUsjWMbainWNxaK2PXEQBZ25';

$message = "";
$messageType = "danger"; // Bootstrap alert type

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validazione base
    if (empty($email) || empty($password)) {
        $message = "Tutti i campi sono obbligatori.";
        $messageType = "warning";
    } else {

        // Verifica reCAPTCHA solo se abilitato
        $captchaValid = true;
        if ($ENABLE_CAPTCHA) {
            if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
                $recaptchaResponse = $_POST['g-recaptcha-response'];
                $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
                $recaptchaData = [
                    'secret' => $recaptchaSecretKey,
                    'response' => $recaptchaResponse,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ];

                $options = [
                    'http' => [
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        'content' => http_build_query($recaptchaData)
                    ]
                ];

                $context = stream_context_create($options);
                $result = file_get_contents($recaptchaUrl, false, $context);
                $response = json_decode($result, true);

                if (!$response['success']) {
                    $captchaValid = false;
                    $message = "Verifica CAPTCHA fallita. Riprova.";
                    $messageType = "danger";
                }
            } else {
                $captchaValid = false;
                $message = "Completa la verifica CAPTCHA.";
                $messageType = "warning";
            }
        }

        // Procedi con il login solo se CAPTCHA è valido (o disabilitato)
        if ($captchaValid) {
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
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayGlobe - Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #488dec 0%, #9a1bf1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #488dec 0%, #9a1bf1 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-body {
            padding: 2rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 5;
        }

        .form-control:focus {
            border-color: #488dec;
            box-shadow: 0 0 0 0.2rem rgba(72, 141, 236, 0.25);
            z-index: 15;
        }

        .btn-login {
            background: linear-gradient(135deg, #488dec 0%, #9a1bf1 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 141, 236, 0.4);
            color: white;
        }

        .logo {
            width: 200px;
            height: auto;
            margin-bottom: 1rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group .fas:not(.toggle-password) {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
            pointer-events: none;
        }

        .input-group .form-control {
            padding-left: 45px;
            padding-right: 50px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 20;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: auto;
            user-select: none;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            color: #488dec;
            background-color: rgba(72, 141, 236, 0.1);
        }

        .captcha-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 10px;
            margin: 15px 0;
            font-size: 14px;
        }

        .security-features {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 2rem;
            text-align: center;
        }

        .security-features .feature {
            display: inline-block;
            margin: 0 15px;
            color: #6c757d;
            font-size: 14px;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 20px;
                border-radius: 10px;
            }

            .login-header,
            .login-body {
                padding: 1.5rem;
            }
        }
    </style>

    <?php if ($ENABLE_CAPTCHA): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <div class="login-header">
                        <div class="mb-3">
                            <svg class="logo" preserveAspectRatio="xMidYMid meet" viewBox="0 0 761.13 177.1">
                                <!-- PayGlobe SVG content here -->
                                <g>
                                    <defs>
                                        <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color:#ffffff"/>
                                            <stop offset="100%" style="stop-color:#ffffff"/>
                                        </linearGradient>
                                    </defs>
                                    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="60" font-weight="bold">PayGlobe</text>
                                </g>
                            </svg>
                        </div>
                        <h4>Accesso Merchant Portal</h4>
                        <p class="mb-0">Gestisci le tue transazioni e negozi</p>
                    </div>

                    <div class="login-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?= $messageType === 'danger' ? 'exclamation-triangle' : ($messageType === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                            </div>
                        <?php endif; ?>

                        <?php if (!$ENABLE_CAPTCHA): ?>
                            <div class="captcha-info">
                                <i class="fas fa-shield-alt text-success"></i>
                                <strong>Modalità base:</strong> CAPTCHA disabilitato
                            </div>
                        <?php endif; ?>

                        <form method="post" id="loginForm">
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="Inserisci la tua email" required
                                       value="<?= htmlspecialchars($email ?? '') ?>">
                            </div>

                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Inserisci la tua password" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword()" title="Mostra/Nascondi password"></i>
                            </div>

                            <?php if ($ENABLE_CAPTCHA): ?>
                            <div class="text-center mb-3">
                                <div class="g-recaptcha" data-sitekey="<?= $recaptchaSiteKey ?>"></div>
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt"></i> Accedi
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="text-muted">
                                <i class="fas fa-question-circle"></i> Password dimenticata?
                            </a>
                        </div>

                        <div class="security-features">
                            <div class="feature">
                                <i class="fas fa-lock text-success"></i> Connessione sicura
                            </div>
                            <div class="feature">
                                <i class="fas fa-shield-alt text-primary"></i> Dati protetti
                            </div>
                            <div class="feature">
                                <i class="fas fa-clock text-info"></i> Sessione automatica
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                toggleIcon.title = 'Nascondi password';
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleIcon.title = 'Mostra password';
            }
        }

        // Migliora l'accessibilità dei campi
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const emailField = document.getElementById('email');

            // Assicura che i campi siano sempre accessibili
            [passwordField, emailField].forEach(field => {
                if (field) {
                    field.addEventListener('click', function() {
                        this.focus();
                    });
                    
                    // Previeni problemi di focus
                    field.addEventListener('mousedown', function(e) {
                        setTimeout(() => this.focus(), 10);
                    });
                }
            });

            // Migliora l'icona toggle con supporto tastiera
            const toggleIcon = document.querySelector('.toggle-password');
            if (toggleIcon) {
                toggleIcon.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        togglePassword();
                    }
                });
                
                // Rendi l'icona focusabile
                toggleIcon.setAttribute('tabindex', '0');
                toggleIcon.setAttribute('role', 'button');
                toggleIcon.setAttribute('aria-label', 'Mostra o nascondi password');
            }
        });

        // Form validation
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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accesso in corso...';
            submitBtn.disabled = true;
        });

        // Auto-dismiss alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    </script>
</body>
</html>
