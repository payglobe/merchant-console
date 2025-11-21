<?php
session_start();
require_once '../../config.php';

$message = "";
$messageType = "info";
$showForm = false;
$email = "";
$isForceChange = false;
$isExpired = false;

// Gestione reset via token email
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE password_reset_token = ? AND password_reset_token_expiry > NOW()");
    if ($stmt === false) {
        die("Errore nella preparazione della query: " . $conn->error);
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $showForm = true;
        $row = $result->fetch_assoc();
        $email = $row['email'];

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            // Validazione password avanzata
            if (empty($newPassword) || empty($confirmPassword)) {
                $message = "Tutti i campi sono obbligatori";
                $messageType = "danger";
            } elseif (strlen($newPassword) < 8) {
                $message = "La password deve essere di almeno 8 caratteri";
                $messageType = "danger";
            } elseif (!preg_match('/[A-Z]/', $newPassword)) {
                $message = "La password deve contenere almeno una lettera maiuscola";
                $messageType = "danger";
            } elseif (!preg_match('/[0-9]/', $newPassword)) {
                $message = "La password deve contenere almeno un numero";
                $messageType = "danger";
            } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
                $message = "La password deve contenere almeno un carattere speciale";
                $messageType = "danger";
            } elseif ($newPassword !== $confirmPassword) {
                $message = "Le password non corrispondono";
                $messageType = "danger";
            } else {
                // Aggiorna password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_token_expiry = NULL, password_last_changed = NOW(), force_password_change = 0 WHERE email = ?");

                if ($stmt === false) {
                    die("Errore nella preparazione della query: " . $conn->error);
                }

                $stmt->bind_param("ss", $hashedPassword, $email);
                if ($stmt->execute()) {
                    $message = "Password aggiornata con successo! Ora puoi effettuare il login.";
                    $messageType = "success";
                    $showForm = false;
                } else {
                    $message = "Errore nell'aggiornamento della password";
                    $messageType = "danger";
                }
            }
        }
    } else {
        $message = "Il link per il reset della password non è valido o è scaduto";
        $messageType = "danger";
    }
}
// Gestione reset forzato o per scadenza
elseif (isset($_SESSION['reset_password_user'])) {
    $showForm = true;
    $email = $_SESSION['reset_password_user'];
    $isForceChange = isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'];
    $isExpired = isset($_SESSION['password_expired']) && $_SESSION['password_expired'];

    if ($isForceChange) {
        $message = "Per motivi di sicurezza, devi cambiare la password prima di continuare";
        $messageType = "warning";
    } elseif ($isExpired) {
        $message = "La tua password è scaduta (45 giorni). Devi impostarne una nuova";
        $messageType = "danger";
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validazione password avanzata
        if (empty($newPassword) || empty($confirmPassword)) {
            $message = "Tutti i campi sono obbligatori";
            $messageType = "danger";
        } elseif (strlen($newPassword) < 8) {
            $message = "La password deve essere di almeno 8 caratteri";
            $messageType = "danger";
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $message = "La password deve contenere almeno una lettera maiuscola";
            $messageType = "danger";
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $message = "La password deve contenere almeno un numero";
            $messageType = "danger";
        } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
            $message = "La password deve contenere almeno un carattere speciale";
            $messageType = "danger";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Le password non corrispondono";
            $messageType = "danger";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ?, password_last_changed = NOW(), force_password_change = 0, password_reset_token = NULL, password_reset_token_expiry = NULL WHERE email = ?");

            if ($stmt === false) {
                die("Errore nella preparazione della query: " . $conn->error);
            }

            $stmt->bind_param("ss", $hashedPassword, $email);

            if ($stmt->execute()) {
                if ($isForceChange || $isExpired) {
                    // Reimposta sessione e reindirizza
                    $stmt = $conn->prepare("SELECT id, bu FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();

                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $email;
                    $_SESSION['bu'] = $row['bu'];

                    // Pulisci variabili di sessione temporanee
                    unset($_SESSION['force_password_change']);
                    unset($_SESSION['reset_password_user']);
                    unset($_SESSION['password_expired']);

                    header("Location: index.php?password_changed=1");
                    exit;
                } else {
                    $message = "Password aggiornata con successo!";
                    $messageType = "success";
                    $showForm = false;
                    unset($_SESSION['reset_password_user']);
                }
            } else {
                $message = "Errore nell'aggiornamento della password";
                $messageType = "danger";
            }
        }
    }
} else {
    $message = "Nessuna richiesta di reset della password trovata";
    $messageType = "warning";
}

// Determine page title and context
$pageTitle = "Reset Password";
if ($isForceChange) {
    $pageTitle = "Cambio Password Obbligatorio";
} elseif ($isExpired) {
    $pageTitle = "Password Scaduta";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - MC5 PayGlobe</title>

    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome 6.5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Design System -->
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

        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, -30px) rotate(180deg); }
        }

        .reset-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 540px;
            margin: 20px;
        }

        .reset-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-2xl);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: var(--space-8);
            text-align: center;
            color: white;
        }

        .reset-header i {
            font-size: 3rem;
            margin-bottom: var(--space-3);
            opacity: 0.9;
        }

        .reset-header h1 {
            font-size: var(--text-2xl);
            font-weight: var(--font-extrabold);
            margin: 0 0 var(--space-2);
        }

        .reset-header .email-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: var(--space-2) var(--space-4);
            border-radius: var(--radius-full);
            font-size: var(--text-sm);
            display: inline-block;
            margin-top: var(--space-2);
            backdrop-filter: blur(10px);
        }

        .reset-body {
            padding: var(--space-8);
        }

        .alert {
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-5);
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            font-size: var(--text-sm);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        .form-group {
            margin-bottom: var(--space-5);
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

        .form-control {
            width: 100%;
            padding: var(--space-4) var(--space-12) var(--space-4) var(--space-4);
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
        }

        .toggle-password:hover {
            color: var(--primary-500);
        }

        .password-strength {
            margin-top: var(--space-3);
        }

        .strength-bar {
            height: 4px;
            border-radius: var(--radius-full);
            transition: all var(--transition-base);
            background: var(--gray-200);
        }

        .strength-bar.active {
            animation: progress 0.3s ease-out;
        }

        @keyframes progress {
            from { width: 0; }
        }

        .strength-weak { background: var(--danger-500); width: 25%; }
        .strength-fair { background: var(--warning-500); width: 50%; }
        .strength-good { background: #3b82f6; width: 75%; }
        .strength-strong { background: var(--success-500); width: 100%; }

        .strength-text {
            font-size: var(--text-xs);
            margin-top: var(--space-2);
            font-weight: var(--font-medium);
        }

        .requirements-box {
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            margin-top: var(--space-5);
        }

        .requirements-box h6 {
            color: var(--gray-900);
            font-size: var(--text-sm);
            font-weight: var(--font-bold);
            margin: 0 0 var(--space-3);
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2) 0;
            font-size: var(--text-sm);
            color: var(--gray-600);
            transition: color var(--transition-base);
        }

        .requirement i {
            width: 18px;
            transition: all var(--transition-base);
        }

        .requirement.valid {
            color: var(--success-600);
        }

        .requirement.valid i {
            color: var(--success-500);
        }

        .requirement.invalid {
            color: var(--gray-400);
        }

        .requirement.invalid i {
            color: var(--gray-300);
        }

        .btn-submit {
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
            margin-top: var(--space-5);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .success-message {
            text-align: center;
            padding: var(--space-6) 0;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-500);
            margin-bottom: var(--space-4);
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .success-message h5 {
            color: var(--gray-900);
            font-size: var(--text-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--space-2);
        }

        .success-message p {
            color: var(--gray-600);
            font-size: var(--text-sm);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: var(--space-3) var(--space-6);
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-size: var(--text-sm);
            font-weight: var(--font-semibold);
            transition: all var(--transition-base);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            margin-top: var(--space-5);
        }

        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        @media (max-width: 768px) {
            .reset-container {
                margin: 10px;
            }

            .reset-header,
            .reset-body {
                padding: var(--space-6);
            }
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <?php if ($isForceChange): ?>
                    <i class="fas fa-shield-alt"></i>
                    <h1>Cambio Password Obbligatorio</h1>
                <?php elseif ($isExpired): ?>
                    <i class="fas fa-exclamation-triangle"></i>
                    <h1>Password Scaduta</h1>
                <?php else: ?>
                    <i class="fas fa-key"></i>
                    <h1>Reset Password</h1>
                <?php endif; ?>

                <?php if ($email): ?>
                    <div class="email-badge">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($email) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="reset-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <i class="fas fa-<?= $messageType === 'danger' ? 'exclamation-triangle' : ($messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-circle' : 'info-circle')) ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($showForm): ?>
                    <form method="POST" id="resetForm">
                        <div class="form-group">
                            <label class="form-label" for="new_password">Nuova Password</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="new_password"
                                    name="new_password"
                                    placeholder="Inserisci nuova password"
                                    required
                                >
                                <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <div class="password-strength">
                                <div class="strength-bar" id="strengthBar"></div>
                                <div class="strength-text" id="strengthText"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Conferma Password</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Conferma nuova password"
                                    required
                                >
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="requirements-box">
                            <h6>Requisiti Password</h6>
                            <div class="requirement invalid" id="req-length">
                                <i class="fas fa-times-circle"></i>
                                <span>Almeno 8 caratteri</span>
                            </div>
                            <div class="requirement invalid" id="req-uppercase">
                                <i class="fas fa-times-circle"></i>
                                <span>Una lettera maiuscola</span>
                            </div>
                            <div class="requirement invalid" id="req-number">
                                <i class="fas fa-times-circle"></i>
                                <span>Un numero</span>
                            </div>
                            <div class="requirement invalid" id="req-special">
                                <i class="fas fa-times-circle"></i>
                                <span>Un carattere speciale (!@#$%^&*)</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-save"></i> Salva Nuova Password
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (!$showForm || $messageType === 'success'): ?>
                    <?php if ($messageType === 'success'): ?>
                        <div class="success-message">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5>Password Aggiornata!</h5>
                            <p>Ora puoi effettuare il login con la nuova password.</p>
                        </div>
                    <?php endif; ?>

                    <div style="text-align: center;">
                        <a href="login.php" class="back-link">
                            <i class="fas fa-sign-in-alt"></i> Vai al Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.parentElement.querySelector('.toggle-password i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            let score = 0;

            // Length
            const hasLength = password.length >= 8;
            updateRequirement('req-length', hasLength);
            if (hasLength) score += 25;

            // Uppercase
            const hasUppercase = /[A-Z]/.test(password);
            updateRequirement('req-uppercase', hasUppercase);
            if (hasUppercase) score += 25;

            // Number
            const hasNumber = /[0-9]/.test(password);
            updateRequirement('req-number', hasNumber);
            if (hasNumber) score += 25;

            // Special character
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            updateRequirement('req-special', hasSpecial);
            if (hasSpecial) score += 25;

            return score;
        }

        function updateRequirement(id, valid) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');

            if (valid) {
                element.classList.add('valid');
                element.classList.remove('invalid');
                icon.classList.remove('fa-times-circle');
                icon.classList.add('fa-check-circle');
            } else {
                element.classList.add('invalid');
                element.classList.remove('valid');
                icon.classList.remove('fa-check-circle');
                icon.classList.add('fa-times-circle');
            }
        }

        function updateStrengthBar(score) {
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');

            bar.className = 'strength-bar active';

            if (score < 25) {
                bar.classList.add('strength-weak');
                text.textContent = 'Troppo debole';
                text.style.color = 'var(--danger-500)';
            } else if (score < 50) {
                bar.classList.add('strength-fair');
                text.textContent = 'Debole';
                text.style.color = 'var(--warning-500)';
            } else if (score < 100) {
                bar.classList.add('strength-good');
                text.textContent = 'Buona';
                text.style.color = '#3b82f6';
            } else {
                bar.classList.add('strength-strong');
                text.textContent = 'Eccellente';
                text.style.color = 'var(--success-500)';
            }

            // Enable/disable submit button
            const submitBtn = document.getElementById('submitBtn');
            if (score === 100) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Event listeners
        document.getElementById('new_password')?.addEventListener('input', function() {
            const password = this.value;
            const score = checkPasswordStrength(password);
            updateStrengthBar(score);
        });

        // Form validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const score = checkPasswordStrength(newPassword);

            if (score < 100) {
                e.preventDefault();
                alert('La password non soddisfa tutti i requisiti di sicurezza');
                return false;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Le password non corrispondono');
                return false;
            }

            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            submitBtn.disabled = true;
        });

        // Focus on password field
        document.getElementById('new_password')?.focus();
    </script>
</body>
</html>
