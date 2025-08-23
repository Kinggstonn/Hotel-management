<?php
// AJAX endpoint to get booking details for calendar modal
header('Content-Type: application/json');
include "db.php";
include "auth/auth_functions.php";

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit();
}

try {
    $query = $conn->prepare("
        SELECT b.*, r.room_number, r.type, r.floor, r.price,
               g.name as guest_name, g.phone, g.email, g.id_card
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN guests g ON b.guest_id = g.id
        WHERE b.id = ?
    ");
    
    $query->bind_param("i", $booking_id);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit();
    }
    
    $booking = $result->fetch_assoc();
    
    // Check permissions
    $user_role = getUserRole();
    if ($user_role === 'customer') {
        // Customer can only see their own bookings
        $user_id = $_SESSION['user_id'];
        $guest_check = $conn->query("SELECT id FROM guests WHERE user_id = $user_id AND id = " . $booking['guest_id']);
        if ($guest_check->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit();
        }
    }
    
    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
