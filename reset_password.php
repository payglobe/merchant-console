<?php
include 'config.php';
session_start();

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

            // Validazione password
            if (empty($newPassword) || empty($confirmPassword)) {
                $message = "Tutti i campi sono obbligatori";
                $messageType = "danger";
            } elseif (strlen($newPassword) < 8) {
                $message = "La password deve essere di almeno 8 caratteri";
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
        $message = "La tua password è scaduta (45 giorni). Devi impostarla una nuova";
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
    <title><?= $pageTitle ?> - PayGlobe</title>
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
        
        .reset-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
            max-width: 500px;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #488dec 0%, #9a1bf1 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .reset-body {
            padding: 2rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 45px 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #488dec;
            box-shadow: 0 0 0 0.2rem rgba(72, 141, 236, 0.25);
        }
        
        .btn-reset {
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
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 141, 236, 0.4);
            color: white;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 5px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-fair { background: #ffc107; width: 50%; }
        .strength-good { background: #17a2b8; width: 75%; }
        .strength-strong { background: #28a745; width: 100%; }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .requirement {
            font-size: 14px;
            margin: 5px 0;
        }
        
        .requirement.valid {
            color: #28a745;
        }
        
        .requirement.invalid {
            color: #dc3545;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="reset-container">
                    <div class="reset-header">
                        <h4>
                            <?php if ($isForceChange): ?>
                                <i class="fas fa-shield-alt"></i> Cambio Password Obbligatorio
                            <?php elseif ($isExpired): ?>
                                <i class="fas fa-exclamation-triangle"></i> Password Scaduta
                            <?php else: ?>
                                <i class="fas fa-key"></i> Reset Password
                            <?php endif; ?>
                        </h4>
                        <?php if ($email): ?>
                            <p class="mb-0"><small><?= htmlspecialchars($email) ?></small></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="reset-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?= $messageType === 'danger' ? 'exclamation-triangle' : ($messageType === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
                                <?= htmlspecialchars($message) ?>
                                <?php if ($messageType !== 'success'): ?>
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($showForm): ?>
                            <form method="post" id="resetForm">
                                <div class="form-group">
                                    <label for="new_password">Nuova Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                                    
                                    <div class="password-strength mt-2">
                                        <div class="strength-bar" id="strengthBar"></div>
                                        <small id="strengthText" class="text-muted"></small>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Conferma Nuova Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                                </div>
                                
                                <div class="password-requirements">
                                    <h6>Requisiti password:</h6>
                                    <div class="requirement" id="req-length">
                                        <i class="fas fa-times"></i> Almeno 8 caratteri
                                    </div>
                                    <div class="requirement" id="req-uppercase">
                                        <i class="fas fa-times"></i> Una lettera maiuscola
                                    </div>
                                    <div class="requirement" id="req-number">
                                        <i class="fas fa-times"></i> Un numero
                                    </div>
                                    <div class="requirement" id="req-special">
                                        <i class="fas fa-times"></i> Un carattere speciale
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-reset mt-3">
                                    <i class="fas fa-save"></i> Salva Nuova Password
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if (!$showForm || $messageType === 'success'): ?>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt"></i> Torna al Login
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
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
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = [];
            
            // Length check
            if (password.length >= 8) {
                score += 25;
                updateRequirement('req-length', true);
            } else {
                updateRequirement('req-length', false);
                feedback.push('Almeno 8 caratteri');
            }
            
            // Uppercase check
            if (/[A-Z]/.test(password)) {
                score += 25;
                updateRequirement('req-uppercase', true);
            } else {
                updateRequirement('req-uppercase', false);
                feedback.push('Una maiuscola');
            }
            
            // Number check
            if (/[0-9]/.test(password)) {
                score += 25;
                updateRequirement('req-number', true);
            } else {
                updateRequirement('req-number', false);
                feedback.push('Un numero');
            }
            
            // Special character check
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                score += 25;
                updateRequirement('req-special', true);
            } else {
                updateRequirement('req-special', false);
                feedback.push('Un carattere speciale');
            }
            
            return { score, feedback };
        }
        
        function updateRequirement(id, valid) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');
            
            if (valid) {
                element.classList.add('valid');
                element.classList.remove('invalid');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-check');
            } else {
                element.classList.add('invalid');
                element.classList.remove('valid');
                icon.classList.remove('fa-check');
                icon.classList.add('fa-times');
            }
        }
        
        function updateStrengthBar(score) {
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');
            
            bar.className = 'strength-bar';
            
            if (score < 25) {
                bar.classList.add('strength-weak');
                text.textContent = 'Troppo debole';
                text.className = 'text-danger';
            } else if (score < 50) {
                bar.classList.add('strength-fair');
                text.textContent = 'Sufficiente';
                text.className = 'text-warning';
            } else if (score < 100) {
                bar.classList.add('strength-good');
                text.textContent = 'Buona';
                text.className = 'text-info';
            } else {
                bar.classList.add('strength-strong');
                text.textContent = 'Eccellente';
                text.className = 'text-success';
            }
        }
        
        // Event listeners
        document.getElementById('new_password')?.addEventListener('input', function() {
            const password = this.value;
            const { score } = checkPasswordStrength(password);
            updateStrengthBar(score);
        });
        
        // Form validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const { score } = checkPasswordStrength(newPassword);
            
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
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
