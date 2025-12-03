<?php
session_start();

// PHPMailer - caricato dalla directory merchant/vendor
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
                    $mail->setFrom('info@payglobe.it', 'PayGlobe Merchant Console');
                    $mail->addAddress($email);
                    $mail->addReplyTo('support@payglobe.it', 'PayGlobe Support');

                    // Contenuto email
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Password - PayGlobe Merchant Console';

                    // Link reset - punta a set-new-password.php nella stessa directory
                    $resetLink = "https://ricevute.payglobe.it/merchant/frontend/dashboard/set-new-password.php?token=" . $token;

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
                                                <h1 style="color: #ffffff; font-size: 28px; font-weight: 800; margin: 0 0 8px 0;">PayGlobe</h1>
                                                <p style="color: #ffffff; font-size: 14px; margin: 0;">Merchant Console</p>
                                            </td>
                                        </tr>
                                        <!-- Content -->
                                        <tr>
                                            <td style="padding: 40px 30px;">
                                                <p style="font-size: 18px; font-weight: 600; color: #333333; margin: 0 0 20px 0;">Ciao,</p>
                                                <p style="font-size: 15px; color: #666666; line-height: 1.8; margin: 0 0 30px 0;">
                                                    Hai richiesto il reset della password per il tuo account <strong>PayGlobe Merchant Console</strong>.<br><br>
                                                    Clicca sul pulsante qui sotto per impostare una nuova password sicura:
                                                </p>

                                                <!-- Button -->
                                                <table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0;">
                                                    <tr>
                                                        <td align="center">
                                                            <a href="' . $resetLink . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); background-color: #667eea; color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 16px;">Reset Password</a>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <!-- Security Box -->
                                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f4ff; border-left: 4px solid #667eea; margin: 30px 0;">
                                                    <tr>
                                                        <td style="padding: 20px;">
                                                            <p style="color: #667eea; font-weight: bold; margin: 0 0 12px 0; font-size: 15px;">Informazioni di Sicurezza</p>
                                                            <p style="color: #555555; font-size: 14px; margin: 6px 0;">&#10003; Questo link è valido per 2 ore</p>
                                                            <p style="color: #555555; font-size: 14px; margin: 6px 0;">&#10003; Se non hai richiesto questo reset, ignora questa email</p>
                                                            <p style="color: #555555; font-size: 14px; margin: 6px 0;">&#10003; Non condividere questo link con nessuno</p>
                                                            <p style="color: #555555; font-size: 14px; margin: 6px 0;">&#10003; Il link può essere usato una sola volta</p>
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
                                                    <strong style="color: #333333;">Team PayGlobe</strong>
                                                </p>
                                            </td>
                                        </tr>
                                        <!-- Footer -->
                                        <tr>
                                            <td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                                                <p style="font-size: 13px; color: #6b7280; margin: 0 0 8px 0;"><strong>PayGlobe</strong> - Merchant Console</p>
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
PayGlobe Merchant Console - Reset Password

Ciao,

Hai richiesto il reset della password per il tuo account PayGlobe Merchant Console.

Clicca su questo link per impostare una nuova password:
$resetLink

IMPORTANTE:
- Questo link è valido per 2 ore
- Se non hai richiesto questo reset, ignora questa email
- Non condividere questo link con nessuno
- Il link può essere usato una sola volta

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
                    $message = "Errore nell'invio dell'email. Riprova più tardi o contatta l'assistenza.";
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
    <title>Password Dimenticata - PayGlobe Merchant Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up {
            animation: slideUp 0.5s ease-out;
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        .animate-scale-in {
            animation: scaleIn 0.5s ease-out;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">

    <div class="glass-effect rounded-2xl shadow-2xl p-8 max-w-md w-full animate-slide-up">
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i data-lucide="key" class="w-10 h-10 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Password Dimenticata</h1>
            <p class="text-gray-600 mt-2">Recupera l'accesso al tuo account</p>
        </div>

        <!-- Message -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg flex items-start <?php
                echo $messageType === 'danger' ? 'bg-red-50 border border-red-200' :
                    ($messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-blue-50 border border-blue-200');
            ?>">
                <i data-lucide="<?php echo $messageType === 'danger' ? 'alert-circle' : ($messageType === 'success' ? 'check-circle' : 'info'); ?>"
                   class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0 <?php
                       echo $messageType === 'danger' ? 'text-red-600' :
                           ($messageType === 'success' ? 'text-green-600' : 'text-blue-600');
                   ?>"></i>
                <span class="text-sm <?php
                    echo $messageType === 'danger' ? 'text-red-800' :
                        ($messageType === 'success' ? 'text-green-800' : 'text-blue-800');
                ?>"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <!-- Info Box -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                    <i data-lucide="info" class="w-4 h-4 mr-2 text-blue-600"></i>
                    Come funziona
                </h3>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Inserisci l'email del tuo account</li>
                    <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Riceverai un link di reset sicuro</li>
                    <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Il link è valido per 2 ore</li>
                    <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Potrai impostare una nuova password</li>
                </ul>
            </div>

            <!-- Form -->
            <form method="POST" id="forgotForm">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Indirizzo Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2"></i>
                        <input type="email" name="email" id="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                               placeholder="tua@email.com">
                    </div>
                </div>

                <button type="submit" id="submitBtn"
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 transform hover:scale-105 flex items-center justify-center">
                    <i data-lucide="send" class="w-5 h-5 mr-2"></i>
                    <span id="btnText">Invia Link di Reset</span>
                </button>
            </form>

        <?php else: ?>
            <?php if ($messageType === 'success'): ?>
                <!-- Success State -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-scale-in">
                        <i data-lucide="check-circle" class="w-10 h-10 text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Email Inviata!</h3>
                    <p class="text-gray-600 text-sm">Controlla la tua casella di posta (e spam) per le istruzioni di reset.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Back Links -->
        <div class="mt-6 pt-6 border-t border-gray-200 flex justify-center gap-6">
            <a href="index.php" class="text-sm text-blue-600 hover:text-blue-800 hover:underline flex items-center">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>
                Torna al Login
            </a>
            <?php if (!$showForm && $messageType === 'success'): ?>
                <a href="reset-password.php" class="text-sm text-blue-600 hover:text-blue-800 hover:underline flex items-center">
                    <i data-lucide="refresh-cw" class="w-4 h-4 mr-1"></i>
                    Invia di Nuovo
                </a>
            <?php endif; ?>
        </div>

        <!-- Security Note -->
        <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-start">
                <i data-lucide="shield" class="w-5 h-5 text-amber-600 mr-3 mt-0.5 flex-shrink-0"></i>
                <div>
                    <h3 class="font-semibold text-amber-900 text-sm">Sicurezza</h3>
                    <p class="text-xs text-amber-800 mt-1">
                        I link di reset sono unici e sicuri. Se non hai richiesto questo reset,
                        puoi ignorare l'email. Il tuo account rimane protetto.
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-xs text-gray-500">
            <p>Powered by PayGlobe</p>
        </div>
    </div>

    <script>
        lucide.createIcons();

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
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            btnText.textContent = 'Invio in corso...';
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        });

        // Focus on email field
        document.getElementById('email')?.focus();
    </script>
</body>
</html>
