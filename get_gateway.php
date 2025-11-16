<?php
session_start();
header('Content-Type: application/json');

// Verifica autenticazione
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit();
}

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminal_id'])) {
    $terminalId = trim($_POST['terminal_id']);
    
    if (!$terminalId) {
        echo json_encode(['success' => false, 'error' => 'Terminal ID richiesto']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("SELECT host_primary, port_primary FROM terminal_gateways WHERE terminal_id = ?");
        $stmt->bind_param("s", $terminalId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'host' => $result['host_primary'],
                'port' => $result['port_primary']
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'Gateway non trovato'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'error' => 'Errore database: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Richiesta non valida']);
}
?>
