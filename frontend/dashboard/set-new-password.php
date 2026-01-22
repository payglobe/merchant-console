<?php
session_start();

// DB connection for password reset
$servername = "10.10.10.13";
$username = "PGDBUSER";
$password = "PNeNkar{K1.%D~V";
$dbname = "payglobe";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Errore connessione database: " . $conn->connect_error);
}

$message = "";
$messageType = "info";
$showForm = false;
$email = "";

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
} else {
    $message = "Token mancante. Utilizza il link ricevuto via email.";
    $messageType = "warning";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PayGlobe Merchant Console</title>
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
            <h1 class="text-2xl font-bold text-gray-900">Nuova Password</h1>
            <?php if ($email): ?>
                <div class="mt-2 inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                    <?php echo htmlspecialchars($email); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Message -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg flex items-start <?php
                echo $messageType === 'danger' ? 'bg-red-50 border border-red-200' :
                    ($messageType === 'success' ? 'bg-green-50 border border-green-200' :
                    ($messageType === 'warning' ? 'bg-amber-50 border border-amber-200' : 'bg-blue-50 border border-blue-200'));
            ?>">
                <i data-lucide="<?php echo $messageType === 'danger' ? 'alert-circle' : ($messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'alert-triangle' : 'info')); ?>"
                   class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0 <?php
                       echo $messageType === 'danger' ? 'text-red-600' :
                           ($messageType === 'success' ? 'text-green-600' :
                           ($messageType === 'warning' ? 'text-amber-600' : 'text-blue-600'));
                   ?>"></i>
                <span class="text-sm <?php
                    echo $messageType === 'danger' ? 'text-red-800' :
                        ($messageType === 'success' ? 'text-green-800' :
                        ($messageType === 'warning' ? 'text-amber-800' : 'text-blue-800'));
                ?>"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <!-- Form -->
            <form method="POST" id="resetForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nuova Password</label>
                    <div class="relative">
                        <input type="password" name="new_password" id="new_password" required minlength="8"
                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                               placeholder="Minimo 8 caratteri">
                        <button type="button" onclick="togglePassword('new_password')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i data-lucide="eye" class="w-5 h-5" id="eye_new_password"></i>
                        </button>
                    </div>
                    <!-- Password strength indicator -->
                    <div class="mt-2 h-1 bg-gray-200 rounded-full overflow-hidden">
                        <div id="strengthBar" class="h-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="strengthText" class="text-xs mt-1 text-gray-500"></p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Conferma Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                               placeholder="Ripeti la password">
                        <button type="button" onclick="togglePassword('confirm_password')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i data-lucide="eye" class="w-5 h-5" id="eye_confirm_password"></i>
                        </button>
                    </div>
                    <p id="matchText" class="text-xs mt-1"></p>
                </div>

                <!-- Requirements Box -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-2 text-sm">Requisiti Password</h3>
                    <div class="space-y-1 text-sm">
                        <div id="req-length" class="flex items-center text-gray-400">
                            <i data-lucide="x-circle" class="w-4 h-4 mr-2"></i>
                            <span>Almeno 8 caratteri</span>
                        </div>
                        <div id="req-match" class="flex items-center text-gray-400">
                            <i data-lucide="x-circle" class="w-4 h-4 mr-2"></i>
                            <span>Le password corrispondono</span>
                        </div>
                    </div>
                </div>

                <button type="submit" id="submitBtn" disabled
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                    <span id="btnText">Salva Nuova Password</span>
                </button>
            </form>

        <?php else: ?>
            <?php if ($messageType === 'success'): ?>
                <!-- Success State -->
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-scale-in">
                        <i data-lucide="check-circle" class="w-10 h-10 text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Password Aggiornata!</h3>
                    <p class="text-gray-600 text-sm">Ora puoi effettuare il login con la nuova password.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Back Link -->
        <div class="mt-6 pt-6 border-t border-gray-200 text-center">
            <a href="index.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300">
                <i data-lucide="log-in" class="w-5 h-5 mr-2"></i>
                Vai al Login
            </a>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-xs text-gray-500">
            <p>Powered by PayGlobe</p>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eyeIcon = document.getElementById('eye_' + fieldId);

            if (field.type === 'password') {
                field.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                field.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function checkPassword() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const submitBtn = document.getElementById('submitBtn');

            // Check length
            const hasLength = password.length >= 8;
            updateRequirement('req-length', hasLength);

            // Check match
            const passwordsMatch = password === confirmPassword && password.length > 0;
            updateRequirement('req-match', passwordsMatch);

            // Update match text
            const matchText = document.getElementById('matchText');
            if (confirmPassword.length > 0) {
                if (passwordsMatch) {
                    matchText.textContent = 'Le password corrispondono';
                    matchText.className = 'text-xs mt-1 text-green-600';
                } else {
                    matchText.textContent = 'Le password non corrispondono';
                    matchText.className = 'text-xs mt-1 text-red-600';
                }
            } else {
                matchText.textContent = '';
            }

            // Strength indicator
            let strength = 0;
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 15;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;

            strengthBar.style.width = strength + '%';

            if (strength < 30) {
                strengthBar.className = 'h-full transition-all duration-300 bg-red-500';
                strengthText.textContent = 'Debole';
                strengthText.className = 'text-xs mt-1 text-red-500';
            } else if (strength < 60) {
                strengthBar.className = 'h-full transition-all duration-300 bg-yellow-500';
                strengthText.textContent = 'Media';
                strengthText.className = 'text-xs mt-1 text-yellow-600';
            } else if (strength < 80) {
                strengthBar.className = 'h-full transition-all duration-300 bg-blue-500';
                strengthText.textContent = 'Buona';
                strengthText.className = 'text-xs mt-1 text-blue-500';
            } else {
                strengthBar.className = 'h-full transition-all duration-300 bg-green-500';
                strengthText.textContent = 'Forte';
                strengthText.className = 'text-xs mt-1 text-green-500';
            }

            // Enable/disable submit
            submitBtn.disabled = !(hasLength && passwordsMatch);
        }

        function updateRequirement(id, valid) {
            const element = document.getElementById(id);
            const icon = element.querySelector('[data-lucide]');

            if (valid) {
                element.className = 'flex items-center text-green-600';
                icon.setAttribute('data-lucide', 'check-circle');
            } else {
                element.className = 'flex items-center text-gray-400';
                icon.setAttribute('data-lucide', 'x-circle');
            }
            lucide.createIcons();
        }

        // Event listeners
        document.getElementById('new_password')?.addEventListener('input', checkPassword);
        document.getElementById('confirm_password')?.addEventListener('input', checkPassword);

        // Form submission
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password.length < 8) {
                e.preventDefault();
                alert('La password deve essere di almeno 8 caratteri');
                return false;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Le password non corrispondono');
                return false;
            }

            // Show loading
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            btnText.textContent = 'Salvando...';
            submitBtn.disabled = true;
        });

        // Focus on password field
        document.getElementById('new_password')?.focus();
    </script>
</body>
</html>
