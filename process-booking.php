<?php
session_start();

// HARUS LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// CEK ROLE JANGAN ADMIN MASUK USER PAGE
if ($_SESSION['role'] === "admin") {
    header("Location: admin/dashboard.php");
    exit;
}

// Include database connection
require_once 'includes/db.php';

// CEK APAKAH METHOD POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// AMBIL DATA DARI FORM
$user_id = $_SESSION['user_id'];
$schedule_train_id = isset($_POST['schedule_train_id']) ? (int)$_POST['schedule_train_id'] : 0;
$passengers = isset($_POST['passengers']) ? (int)$_POST['passengers'] : 0;
$date = isset($_POST['date']) ? $_POST['date'] : '';
$total_price = isset($_POST['total_price']) ? (float)$_POST['total_price'] : 0;

$contact_name = isset($_POST['contact_name']) ? trim($_POST['contact_name']) : '';
$contact_email = isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '';
$contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';

// VALIDASI DATA WAJIB
if ($schedule_train_id === 0 || $passengers < 1 || empty($date) || $total_price <= 0) {
    $_SESSION['error'] = 'Data pemesanan tidak lengkap!';
    header("Location: index.php");
    exit;
}

// VALIDASI JUMLAH PENUMPANG
if ($passengers > 8) {
    $_SESSION['error'] = 'Maksimal 8 penumpang per booking!';
    header("Location: index.php");
    exit;
}

// AMBIL DATA PENUMPANG
$passenger_names = isset($_POST['passenger_name']) ? $_POST['passenger_name'] : [];
$passenger_id_numbers = isset($_POST['passenger_id_number']) ? $_POST['passenger_id_number'] : [];
$passenger_birthdates = isset($_POST['passenger_birthdate']) ? $_POST['passenger_birthdate'] : [];
$passenger_genders = isset($_POST['passenger_gender']) ? $_POST['passenger_gender'] : [];

// VALIDASI JUMLAH DATA PENUMPANG
if (count($passenger_names) !== $passengers || 
    count($passenger_id_numbers) !== $passengers || 
    count($passenger_birthdates) !== $passengers || 
    count($passenger_genders) !== $passengers) {
    $_SESSION['error'] = 'Data penumpang tidak lengkap!';
    header("Location: booking.php?" . http_build_query([
        'schedule_train_id' => $schedule_train_id,
        'passengers' => $passengers,
        'date' => $date
    ]));
    exit;
}

// START TRANSACTION
$conn->begin_transaction();

try {
    // 1. CEK KETERSEDIAAN KURSI LAGI (DOUBLE CHECK)
    $stmt = $conn->prepare("
        SELECT 
            st.price,
            t.amount_seat,
            COALESCE(SUM(CASE 
                WHEN DATE(b.date_book) = ? AND b.payment_status IN ('pending', 'paid')
                THEN b.amount_ticket 
                ELSE 0 
            END), 0) as booked_seats
        FROM schedule_train st
        JOIN trains t ON st.train_id = t.id
        LEFT JOIN book b ON st.id = b.schedule_train_id
        WHERE st.id = ?
        GROUP BY st.id
    ");
    $stmt->bind_param("si", $date, $schedule_train_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule_check = $result->fetch_assoc();
    
    if (!$schedule_check) {
        throw new Exception('Jadwal tidak ditemukan!');
    }
    
    $available_seats = $schedule_check['amount_seat'] - $schedule_check['booked_seats'];
    
    if ($available_seats < $passengers) {
        throw new Exception('Kursi tidak mencukupi! Tersedia: ' . $available_seats . ' kursi');
    }
    
    // VALIDASI HARGA
    $expected_price = $schedule_check['price'] * $passengers;
    if (abs($total_price - $expected_price) > 0.01) {
        throw new Exception('Harga tidak sesuai!');
    }
    
    // 2. INSERT KE TABLE BOOK
    $stmt = $conn->prepare("
        INSERT INTO book (
            user_id, 
            schedule_train_id, 
            date_book, 
            amount_ticket, 
            amount_price, 
            payment_status
        ) VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iisid", $user_id, $schedule_train_id, $date, $passengers, $total_price);
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan data booking!');
    }
    
    $book_id = $conn->insert_id;
    
    // 3. INSERT KE TABLE PAYMENT
    $payment_gateway_ref = 'GOTIX-' . strtoupper(uniqid()) . '-' . time();
    $payment_date = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("
        INSERT INTO payment (
            book_id, 
            payment_status, 
            payment_date, 
            payment_gateway_ref
        ) VALUES (?, 'pending', ?, ?)
    ");
    $stmt->bind_param("iss", $book_id, $payment_date, $payment_gateway_ref);
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan data payment!');
    }
    
    // 4. INSERT KE TABLE TICKET UNTUK SETIAP PENUMPANG
    $stmt_ticket = $conn->prepare("
        INSERT INTO ticket (
            book_id, 
            ticket_code, 
            issued_at
        ) VALUES (?, ?, NOW())
    ");
    
    for ($i = 0; $i < $passengers; $i++) {
        // GENERATE TICKET CODE UNIK
        $ticket_code = 'TIX-' . strtoupper(substr(md5($book_id . $i . time()), 0, 10));
        
        $stmt_ticket->bind_param("is", $book_id, $ticket_code);
        
        if (!$stmt_ticket->execute()) {
            throw new Exception('Gagal generate tiket untuk penumpang ' . ($i + 1));
        }
    }
    
    // COMMIT TRANSACTION
    $conn->commit();
    
    // SET SUCCESS MESSAGE
    $_SESSION['success'] = 'Booking berhasil! Silakan lanjutkan pembayaran.';
    
    // REDIRECT KE PAYMENT PAGE
    header("Location: payment.php?book_id=" . $book_id);
    exit;
    
} catch (Exception $e) {
    // ROLLBACK JIKA ERROR
    $conn->rollback();
    
    $_SESSION['error'] = $e->getMessage();
    header("Location: booking.php?" . http_build_query([
        'schedule_train_id' => $schedule_train_id,
        'passengers' => $passengers,
        'date' => $date
    ]));
    exit;
}
?>