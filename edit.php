<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Dati</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Modifica Dati</h2>
        <?php
        include 'config.php';

        if (isset($_GET['uuid'])) {
            $uuid = $_GET['uuid'];
            $stmt = $conn->prepare("SELECT * FROM  scontrini_digitali WHERE uuid = ?");
            $stmt->bind_param("s", $uuid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                ?>
                <form action="update.php" method="post">
                    <input type="hidden" name="uuid" value="<?php echo $row['uuid']; ?>">
                    <div class="form-group">
                        <label for="type">Type:</label>
                        <input type="text" class="form-control" id="type" name="type" value="<?php echo $row['type']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <input type="text" class="form-control" id="status" name="status" value="<?php echo $row['status']; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </form>
                <?php
            } else {
                echo "<p>Dato non trovato</p>";
            }
            $stmt->close();
        } else {
            echo "<p>UUID non specificato</p>";
        }
        $conn->close();
        ?>
    </div>
</body>
</html>
