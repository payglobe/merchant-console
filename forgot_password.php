<?php
include 'config.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
                    // Configurazione SMTP (usa quella che ha funzionato nel debug)
                    $mail->isSMTP();
                    $mail->Host = 'email.payglobe.it';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'info';
                    $mail->Password = 'md-pu08ca80tOb6IJIEQGmLzg';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';
                    
                    // Opzioni per server problematici
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
                    
                    // Mittente e destinatario
                    $mail->setFrom('info@payglobe.it', 'PayGlobe Merchant Portal');
                    $mail->addAddress($email);
                    $mail->addReplyTo('support@payglobe.it', 'PayGlobe Support');
                    
                    // Contenuto email
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Password - PayGlobe Merchant Portal';
                    
                    $resetLink = "https://ricevute.payglobe.it/merchant/reset_password.php?token=" . $token;
                    
                    // Template HTML per l'email
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #488dec 0%, #9a1bf1 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                            .button { display: inline-block; background: linear-gradient(135deg, #488dec 0%, #9a1bf1 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; }
                            .footer { margin-top: 30px; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                            .security-notice { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0; border-radius: 5px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>PayGlobe</h1>
                                <h2>Reset Password</h2>
                            </div>
                            <div class="content">
                                <p>Ciao,</p>
                                <p>Hai richiesto il reset della password per il tuo account PayGlobe Merchant Portal.</p>
                                <p>Clicca sul pulsante qui sotto per impostare una nuova password:</p>
                                
                                <div style="text-align: center;">
                                    <a href="' . $resetLink . '" class="button">Reset Password</a>
                                </div>
                                
                                <div class="security-notice">
                                    <strong>Informazioni di sicurezza:</strong>
                                    <ul>
                                        <li>Questo link è valido per 2 ore</li>
                                        <li>Se non hai richiesto questo reset, ignora questa email</li>
                                        <li>Non condividere questo link con nessuno</li>
                                    </ul>
                                </div>
                                
                                <p>Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
                                <p style="word-break: break-all; color: #488dec;">' . $resetLink . '</p>
                                
                                <p>Grazie,<br><strong>Team PayGlobe</strong></p>
                            </div>
                            <div class="footer">
                                <p>PayGlobe Merchant Portal - Sistema di Gestione Transazioni</p>
                                <p>Per supporto: <a href="mailto:support@payglobe.it">support@payglobe.it</a></p>
                            </div>
                        </div>
                    </body>
                    </html>';
                    
                    // Versione testo semplice
                    $mail->AltBody = "
                    PayGlobe - Reset Password
                    
                    Ciao,
                    
                    Hai richiesto il reset della password per il tuo account PayGlobe Merchant Portal.
                    
                    Clicca su questo link per impostare una nuova password:
                    $resetLink
                    
                    IMPORTANTE:
                    - Questo link è valido per 2 ore
                    - Se non hai richiesto questo reset, ignora questa email
                    - Non condividere questo link con nessuno
                    
                    Grazie,
                    Team PayGlobe
                    
                    Per supporto: support@payglobe.it
                    ";
                    
                    $mail->send();
                    
                    $message = "Email di reset inviata con successo! Controlla la tua casella di posta e segui le istruzioni.";
                    $messageType = "success";
                    $showForm = false;
                    
                } catch (Exception $e) {
                    error_log("Errore invio email reset password: " . $mail->ErrorInfo);
                    $message = "Errore nell'invio dell'email. Riprova più tardi o contatta il supporto.";
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
    <title>Password Dimenticata - PayGlobe</title>
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
        
        .forgot-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
            max-width: 500px;
        }
        
        .forgot-header {
            background: linear-gradient(135deg, #488dec 0%, #9a1bf1 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .forgot-body {
            padding: 2rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #488dec;
            box-shadow: 0 0 0 0.2rem rgba(72, 141, 236, 0.25);
        }
        
        .btn-forgot {
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
        
        .btn-forgot:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 141, 236, 0.4);
            color: white;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group .fas {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }
        
        .input-group .form-control {
            padding-left: 45px;
        }
        
        .help-text {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .help-text h6 {
            color: #488dec;
            margin-bottom: 15px;
        }
        
        .help-text ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .help-text li {
            margin-bottom: 8px;
            color: #6c757d;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .back-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-links a {
            color: #488dec;
            text-decoration: none;
            margin: 0 15px;
            transition: color 0.3s ease;
        }
        
        .back-links a:hover {
            color: #9a1bf1;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .forgot-container {
                margin: 20px;
                border-radius: 10px;
            }
            
            .forgot-header,
            .forgot-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="forgot-container">
                    <div class="forgot-header">
                        <h4><i class="fas fa-key"></i> Password Dimenticata</h4>
                        <p class="mb-0">Recupera l'accesso al tuo account</p>
                    </div>
                    
                    <div class="forgot-body">
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
                            <div class="help-text">
                                <h6><i class="fas fa-info-circle"></i> Come funziona</h6>
                                <ul>
                                    <li>Inserisci l'email del tuo account</li>
                                    <li>Riceverai un link di reset sicuro</li>
                                    <li>Il link è valido per 2 ore</li>
                                    <li>Potrai impostare una nuova password</li>
                                </ul>
                            </div>

                            <form method="post" id="forgotForm">
                                <div class="input-group">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Inserisci il tuo indirizzo email" required
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                                
                                <button type="submit" class="btn btn-forgot">
                                    <i class="fas fa-paper-plane"></i> Invia Link di Reset
                                </button>
                            </form>
                        <?php else: ?>
                            <?php if ($messageType === 'success'): ?>
                                <div class="text-center">
                                    <i class="fas fa-check-circle success-icon"></i>
                                    <h5>Email inviata!</h5>
                                    <p>Controlla la tua casella di posta (e spam) per le istruzioni di reset.</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="back-links">
                            <a href="login.php">
                                <i class="fas fa-arrow-left"></i> Torna al Login
                            </a>
                            <?php if (!$showForm && $messageType === 'success'): ?>
                                <a href="#" onclick="location.reload()">
                                    <i class="fas fa-redo"></i> Invia di Nuovo
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="help-text mt-4">
                            <h6><i class="fas fa-shield-alt"></i> Sicurezza</h6>
                            <small class="text-muted">
                                I link di reset sono unici e sicuri. Se non hai richiesto questo reset, 
                                puoi ignorare l'email. Il tuo account rimane protetto.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation e UX improvements
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
            
            // Restore button after 10 seconds (fallback)
            setTimeout(function() {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });
        
        // Auto-dismiss alerts after 10 seconds
        setTimeout(function() {
            $('.alert:not(.alert-success)').fadeOut('slow');
        }, 10000);
        
        // Focus on email field on page load
        document.getElementById('email')?.focus();
    </script>
</body>
</html>
