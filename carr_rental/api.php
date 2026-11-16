<?php
header('Content-Type: application/json');

// Database credentials
$host = 'localhost';
$db   = 'system_db';
$user = 'root';
$pass = ''; // Change to your MySQL password if configured
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'get_all') {
    try {
        // 1. Vehicles from `cars`
        $stmt = $pdo->query("SELECT vehicle_id as id, vehicle_name as name, plate_number as plate, category as type, availability_status as status FROM cars");
        $vehicles = $stmt->fetchAll();

        // 2. Payments from `payments` joined with `bookings`
        $stmt = $pdo->query("
            SELECT p.payment_id as id, COALESCE(b.customer_name, 'Guest') as customer, p.amount, p.payment_method as method, p.payment_status as status 
            FROM payments p 
            LEFT JOIN bookings b ON p.booking_id = b.booking_id
        ");
        $payments = $stmt->fetchAll();

        // 3. Maintenance from `maintenance` joined with `cars`
        $stmt = $pdo->query("
            SELECT m.maintenance_id as id, COALESCE(c.vehicle_name, 'Unknown Vehicle') as vehicle, m.maintenance_type as issue, m.maintenance_date as date, m.maintenance_status as status 
            FROM maintenance m 
            LEFT JOIN cars c ON m.vehicle_id = c.vehicle_id
        ");
        $maintenance = $stmt->fetchAll();

        // 4. Rentals from `rental_returns` joined with `bookings` and `cars`
        $stmt = $pdo->query("
            SELECT r.return_id as id, COALESCE(b.customer_name, 'Guest') as customer, COALESCE(c.vehicle_name, 'Unknown Vehicle') as vehicle, r.actual_return_date as date, r.return_status as status 
            FROM rental_returns r 
            LEFT JOIN bookings b ON r.booking_id = b.booking_id
            LEFT JOIN cars c ON b.vehicle_id = c.vehicle_id
        ");
        $rentals = $stmt->fetchAll();

        // 5. Staff Requests from `staff`
        $stmt = $pdo->query("
            SELECT staff_id as id, CONCAT(first_name, ' ', last_name) as name, employee_id as email, position, registration_status as status 
            FROM staff
        ");
        $staffRequests = $stmt->fetchAll();

        // 6. Admin Requests from `users`
        $stmt = $pdo->query("
            SELECT user_id as id, username as name, email, role as position, account_status as status 
            FROM users 
            WHERE role IN ('admin', 'head_admin')
        ");
        $adminRequests = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'vehicles' => $vehicles,
            'payments' => $payments,
            'maintenance' => $maintenance,
            'rentals' => $rentals,
            'staffRequests' => $staffRequests,
            'adminRequests' => $adminRequests
        ]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action parameter.']);
}

