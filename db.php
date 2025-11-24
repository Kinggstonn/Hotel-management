<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hotel_management";

$port = 3307;
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

/**
 * Ensure monetary columns can store large totals (e.g. long stays).
 */
function ensureFinancialPrecision(mysqli $conn, string $databaseName): void {
    $checks = [
        ['bookings', 'total_price', 15],
        ['payments', 'amount', 15],
    ];
    
    foreach ($checks as [$table, $column, $precision]) {
        $stmt = $conn->prepare("
            SELECT NUMERIC_PRECISION 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        
        if (!$stmt) {
            continue;
        }
        
        $stmt->bind_param("sss", $databaseName, $table, $column);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_precision = $result && $result->num_rows
            ? (int)$result->fetch_assoc()['NUMERIC_PRECISION']
            : null;
        $stmt->close();
        
        if ($current_precision === null) {
            continue;
        }
        
        if ($current_precision >= $precision) {
            continue;
        }
        
        $conn->query("ALTER TABLE `$table` MODIFY `$column` DECIMAL($precision,2) NOT NULL");
    }
}

ensureFinancialPrecision($conn, $db);
?>
