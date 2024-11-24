<?php
// Lesen der Datenbank-Konfiguration aus der Python-Datei
function getDatabaseConfig() {
    $config = [];
    $pythonFile = '/var/www/backup-monitor2/config/database.py';
    if (file_exists($pythonFile)) {
        $content = file_get_contents($pythonFile);
        preg_match("/DB_HOST = '(.+)'/", $content, $matches);
        $config['host'] = $matches[1] ?? 'localhost';
        preg_match("/DB_USER = '(.+)'/", $content, $matches);
        $config['user'] = $matches[1] ?? '';
        preg_match("/DB_PASSWORD = '(.+)'/", $content, $matches);
        $config['password'] = $matches[1] ?? '';
        preg_match("/DB_NAME = '(.+)'/", $content, $matches);
        $config['database'] = $matches[1] ?? '';
    }
    return $config;
}

// Datenbankverbindung herstellen
$config = getDatabaseConfig();
$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

if ($conn->connect_error) {
    die("Verbindungsfehler: " . $conn->connect_error);
}

// Verarbeitung von POST-Anfragen
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $conn->real_escape_string($_POST['name']);
                $number = $conn->real_escape_string($_POST['number']);
                $note = $conn->real_escape_string($_POST['note']);
                $sql = "INSERT INTO customers (name, number, note) VALUES ('$name', '$number', '$note')";
                $conn->query($sql);
                break;

            case 'edit':
                $id = (int)$_POST['id'];
                $name = $conn->real_escape_string($_POST['name']);
                $number = $conn->real_escape_string($_POST['number']);
                $note = $conn->real_escape_string($_POST['note']);
                $sql = "UPDATE customers SET name='$name', number='$number', note='$note' WHERE id=$id";
                $conn->query($sql);
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                $sql = "DELETE FROM customers WHERE id=$id";
                $conn->query($sql);
                break;
        }
        // Weiterleitung zur gleichen Seite um Formular-Resubmission zu vermeiden
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// HTML-Ausgabe beginnt hier
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kundenverwaltung</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-group { margin: 10px 0; }
        .form-group label { display: inline-block; width: 100px; }
        button { margin: 5px; padding: 5px 10px; }
        .actions { display: flex; gap: 5px; }
    </style>
</head>
<body>
    <h1>Kundenverwaltung</h1>

    <!-- Formular für neue Kunden -->
    <h2>Neuen Kunden anlegen</h2>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="number">Nummer:</label>
            <input type="text" id="number" name="number" required>
        </div>
        <div class="form-group">
            <label for="note">Notiz:</label>
            <textarea id="note" name="note"></textarea>
        </div>
        <button type="submit">Kunde anlegen</button>
    </form>

    <!-- Kundenliste -->
    <h2>Kundenliste</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Nummer</th>
                <th>Notiz</th>
                <th>Erstellt am</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT * FROM customers ORDER BY id");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['name']}</td>";
                echo "<td>{$row['number']}</td>";
                echo "<td>{$row['note']}</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "<td class='actions'>";
                // Bearbeiten-Button und Form
                echo "<button onclick='editCustomer({$row['id']}, \"{$row['name']}\", \"{$row['number']}\", \"{$row['note']}\")'>Bearbeiten</button>";
                // Löschen-Form
                echo "<form method='post' style='display: inline;' onsubmit='return confirm(\"Wirklich löschen?\")'>";
                echo "<input type='hidden' name='action' value='delete'>";
                echo "<input type='hidden' name='id' value='{$row['id']}'>";
                echo "<button type='submit'>Löschen</button>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Modal für Bearbeiten -->
    <div id="editModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                              background: white; padding: 20px; border: 1px solid #ccc; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2>Kunde bearbeiten</h2>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label for="edit_name">Name:</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit_number">Nummer:</label>
                <input type="text" id="edit_number" name="number" required>
            </div>
            <div class="form-group">
                <label for="edit_note">Notiz:</label>
                <textarea id="edit_note" name="note"></textarea>
            </div>
            <button type="submit">Speichern</button>
            <button type="button" onclick="document.getElementById('editModal').style.display='none'">Abbrechen</button>
        </form>
    </div>

    <script>
        function editCustomer(id, name, number, note) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_number').value = number;
            document.getElementById('edit_note').value = note;
            document.getElementById('editModal').style.display = 'block';
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>