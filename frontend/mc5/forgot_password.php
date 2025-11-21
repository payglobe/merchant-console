<?php
session_start();
require_once '../conf.php'; // MC5 usa conf.php nella parent directory
// PHPMailer is loaded via composer in parent directory
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// DB connection for password reset
$servername = "10.10.10.12";
$username = "PGDBUSER";
$password = "PNeNkar{K1.%D~V";
$dbname = "payglobe";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Errore connessione database: " . $conn->connect_error);
}

$message = "";
$messageType = "info";
$showForm = true;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Validazione email
    if (empty($email)) {
        $message = "L'indirizzo email è obbligatorio";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Inserisci un indirizzo email valido";
        $messageType = "danger";
    } else {
        // Verifica esistenza email
        $stmt = $conn->prepare("SELECT id, email, active FROM users WHERE email = ?");
        if ($stmt === false) {
            die("Errore nella preparazione della query: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verifica se l'utente è attivo
            if (!$row['active']) {
                $message = "Account disabilitato. Contatta l'amministratore";
                $messageType = "danger";
            } else {
                // Genera token sicuro
                $token = bin2hex(random_bytes(32));
                $expiry = date("Y-m-d H:i:s", strtotime("+2 hours"));

                // Aggiorna database
                $updateStmt = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_token_expiry = ? WHERE email = ?");
                if ($updateStmt === false) {
                    die("Errore nella preparazione della query: " . $conn->error);
                }

                $updateStmt->bind_param("sss", $token, $expiry, $email);
                $updateStmt->execute();
                $updateStmt->close();

                // Invio email con PHPMailer
                $mail = new PHPMailer(true);

                try {
                    // Configurazione SMTP PayGlobe - SSL
                    $mail->isSMTP();
                    $mail->Host = 'email.payglobe.it';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'info';
                    $mail->Password = 'md-pu08ca80tOb6IJIEQGmLzg';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // SSL
                    $mail->Port = 465;
                    $mail->CharSet = 'UTF-8';
                    $mail->SMTPDebug = 0;

                    // Opzioni SSL per server
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );

                    // Mittente e destinatario
                    $mail->setFrom('info@payglobe.it', 'PayGlobe MC5');
                    $mail->addAddress($email);
                    $mail->addReplyTo('support@payglobe.it', 'PayGlobe Support');

                    // Contenuto email
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Password MC5 - PayGlobe';

                    $resetLink = "https://merchant-console.payglobe.it/payglobe/mc5/reset_password.php?token=" . $token;

                    // Template HTML con inline styles per compatibilità email
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                    </head>
                    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f5f5f5;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 0;">
                            <tr>
                                <td align="center">
                                    <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                                        <!-- Header -->
                                        <tr>
                                            <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); background-color: #667eea; padding: 40px 30px; text-align: center;">
                                                <h1 style="color: #ffffff; font-size: 28px; font-weight: 800; margin: 0 0 8px 0;">PayGlobe MC5</h1>
                                                <p style="color: #ffffff; font-size: 14px; margin: 0;">Merchant Console v3.0</p>
                                            </td>
                                        </tr>
                                        <!-- Content -->
                                        <tr>
                                            <td style="padding: 40px 30px;">
                                                <p style="font-size: 18px; font-weight: 600; color: #333333; margin: 0 0 20px 0;">Ciao,</p>
                                                <p style="font-size: 15px; color: #666666; line-height: 1.8; margin: 0 0 30px 0;">
                                                    Hai richiesto il reset della password per il tuo account <strong>MC5 PayGlobe Merchant Console</strong>.<br><br>
                                                    Clicca sul pulsante qui sotto per impostare una nuova password sicura:
                                                </p>

                                                <!-- Button -->
                                                <table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0;">
                                                    <tr>
                                                        <td align="center">
                                                            <a href="' . $resetLink . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); background-color: #667eea; color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 16px;">Reset Password MC5</a>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <!-- Security Box -->
                                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f4ff; border-left: 4px solid #667eea; margin: 30px 0;">
                                                    <tr>
                                                        <td style="padding: 20px;">
                                                            <p style="color: #667eea; font-weight: bold; margin: 0 0 12px 0; font-size: 15px;">🔒 Informazioni di Sicurezza</p>
                                                            <p style="color: #555555; font-size: 14px; margin: 6px 0;">✓ Questo link è valido per 2 ore</p>
                                                            <p style="color: #555555; font-size: 14px; margin: 6px 0;">✓ Se non hai richiesto questo reset, ignora questa email</p>
                                                            <p style="color: #555555; font-size: 14px; margin: 6px 0;">✓ Non condividere questo link con nessuno</p>
                                                            <p style="color: #555555; font-size: 14px; margin: 6px 0;">✓ Il link può essere usato una sola volta</p>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <!-- Link Box -->
                                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; margin: 20px 0;">
                                                    <tr>
                                                        <td style="padding: 20px;">
                                                            <p style="font-size: 13px; color: #666666; margin: 0 0 10px 0;"><strong>Il pulsante non funziona?</strong> Copia e incolla questo link nel tuo browser:</p>
                                                            <p style="word-break: break-all; color: #667eea; font-size: 12px; margin: 0;"><a href="' . $resetLink . '" style="color: #667eea;">' . $resetLink . '</a></p>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <p style="font-size: 15px; color: #666666; margin: 30px 0 0 0;">
                                                    Grazie,<br>
                                                    <strong style="color: #333333;">Team PayGlobe MC5</strong>
                                                </p>
                                            </td>
                                        </tr>
                                        <!-- Footer -->
                                        <tr>
                                            <td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                                                <p style="font-size: 13px; color: #6b7280; margin: 0 0 8px 0;"><strong>PayGlobe MC5 v3.0</strong> - Merchant Console</p>
                                                <p style="font-size: 13px; color: #6b7280; margin: 0 0 8px 0;">Per supporto: <a href="mailto:support@payglobe.it" style="color: #667eea; text-decoration: none;">support@payglobe.it</a></p>
                                                <p style="margin: 20px 0 0 0; font-size: 11px; color: #9ca3af;">
                                                    Questa è una email automatica. Per favore non rispondere direttamente.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </body>
                    </html>';

                    // Versione testo semplice
                    $mail->AltBody = "
PayGlobe MC5 - Reset Password

Ciao,

Hai richiesto il reset della password per il tuo account MC5 PayGlobe Merchant Console.

Clicca su questo link per impostare una nuova password:
$resetLink

IMPORTANTE:
- Questo link è valido per 2 ore
- Se non hai richiesto questo reset, ignora questa email
- Non condividere questo link con nessuno
- Il link può essere usato una sola volta

Grazie,
Team PayGlobe MC5

Per supporto: support@payglobe.it
                    ";

                    $mail->send();

                    $message = "Email di reset inviata con successo! Controlla la tua casella di posta e segui le istruzioni.";
                    $messageType = "success";
                    $showForm = false;

                } catch (Exception $e) {
                    error_log("Errore invio email reset password MC5: " . $mail->ErrorInfo);
                    // Show detailed error for debugging
                    $message = "Errore nell'invio dell'email: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage();
                    $messageType = "danger";
                }
            }
        } else {
            // Per sicurezza, mostra sempre lo stesso messaggio anche se l'email non esiste
            $message = "Se l'indirizzo email è registrato nel sistema, riceverai le istruzioni per il reset.";
            $messageType = "info";
            $showForm = false;
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
    <title>Password Dimenticata - MC5 PayGlobe</title>

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

        .forgot-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
            margin: 20px;
        }

        .forgot-card {
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

        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: var(--space-8);
            text-align: center;
            color: white;
        }

        .forgot-header i {
            font-size: 3rem;
            margin-bottom: var(--space-4);
            opacity: 0.9;
        }

        .forgot-header h1 {
            font-size: var(--text-2xl);
            font-weight: var(--font-extrabold);
            margin: 0 0 var(--space-2);
        }

        .forgot-header p {
            font-size: var(--text-sm);
            margin: 0;
            opacity: 0.9;
        }

        .forgot-body {
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

        .alert i {
            margin-top: 2px;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-info {
            background: #e0f2fe;
            color: #075985;
            border: 1px solid #bae6fd;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .info-box {
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            margin-bottom: var(--space-6);
        }

        .info-box h6 {
            color: var(--primary-600);
            font-size: var(--text-sm);
            font-weight: var(--font-bold);
            margin: 0 0 var(--space-3);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .info-box ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-box li {
            padding: var(--space-2) 0;
            padding-left: var(--space-6);
            position: relative;
            color: var(--gray-700);
            font-size: var(--text-sm);
        }

        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--primary-500);
            font-weight: bold;
        }

        .form-group {
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
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .success-icon {
            text-align: center;
            margin-bottom: var(--space-6);
        }

        .success-icon i {
            font-size: 4rem;
            color: var(--success-500);
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .success-message {
            text-align: center;
        }

        .success-message h5 {
            color: var(--gray-900);
            font-size: var(--text-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--space-3);
        }

        .success-message p {
            color: var(--gray-600);
            font-size: var(--text-sm);
        }

        .back-links {
            display: flex;
            justify-content: center;
            gap: var(--space-4);
            margin-top: var(--space-6);
            padding-top: var(--space-6);
            border-top: 1px solid var(--gray-200);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--primary-600);
            text-decoration: none;
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
            transition: color var(--transition-base);
        }

        .back-link:hover {
            color: var(--primary-700);
        }

        .security-note {
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            margin-top: var(--space-6);
        }

        .security-note h6 {
            color: var(--gray-900);
            font-size: var(--text-sm);
            font-weight: var(--font-bold);
            margin: 0 0 var(--space-2);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .security-note p {
            color: var(--gray-600);
            font-size: var(--text-xs);
            margin: 0;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .forgot-container {
                margin: 10px;
            }

            .forgot-header,
            .forgot-body {
                padding: var(--space-6);
            }
        }
    </style>
</head>

<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <i class="fas fa-key"></i>
                <h1>Password Dimenticata</h1>
                <p>Recupera l'accesso a MC5</p>
            </div>

            <div class="forgot-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <i class="fas fa-<?= $messageType === 'danger' ? 'exclamation-triangle' : ($messageType === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($showForm): ?>
                    <div class="info-box">
                        <h6><i class="fas fa-info-circle"></i> Come funziona</h6>
                        <ul>
                            <li>Inserisci l'email del tuo account MC5</li>
                            <li>Riceverai un link di reset sicuro</li>
                            <li>Il link è valido per 2 ore</li>
                            <li>Potrai impostare una nuova password</li>
                        </ul>
                    </div>

                    <form method="POST" id="forgotForm">
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
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Invia Link di Reset
                        </button>
                    </form>
                <?php else: ?>
                    <?php if ($messageType === 'success'): ?>
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="success-message">
                            <h5>Email Inviata!</h5>
                            <p>Controlla la tua casella di posta (e spam) per le istruzioni di reset.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="back-links">
                    <a href="login.php" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        Torna al Login
                    </a>
                    <?php if (!$showForm && $messageType === 'success'): ?>
                        <a href="#" onclick="location.reload()" class="back-link">
                            <i class="fas fa-redo"></i>
                            Invia di Nuovo
                        </a>
                    <?php endif; ?>
                </div>

                <div class="security-note">
                    <h6><i class="fas fa-shield-alt"></i> Sicurezza</h6>
                    <p>
                        I link di reset sono unici e sicuri. Se non hai richiesto questo reset,
                        puoi ignorare l'email. Il tuo account rimane protetto.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('forgotForm')?.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();

            if (!email) {
                e.preventDefault();
                alert('Inserisci il tuo indirizzo email');
                return false;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Inserisci un indirizzo email valido');
                return false;
            }

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Invio in corso...';
            submitBtn.disabled = true;

            // Restore button after timeout
            setTimeout(function() {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });

        // Focus on email field
        document.getElementById('email')?.focus();
    </script>
</body>
</html>
