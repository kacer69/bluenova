<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

$host = 'localhost';
$db   = 'moja_aplikacja';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Błąd połączenia: " . $conn->connect_error);
}

// Pobierz dane
$name     = $_POST['name']     ?? '';
$username = $_POST['username'] ?? '';
$email    = $_POST['email']    ?? '';
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm']  ?? '';
$code     = $_POST['code']     ?? '';

// 1) Sprawdź zgodność haseł
if ($password !== $confirm) {
    die("Hasła się nie zgadzają.");
}

// 2) Pobierz kod z bazy wraz z licznikami
$stmt = $conn->prepare(
    "SELECT id, letter, uses, max_uses 
     FROM codes 
     WHERE code = ?"
);
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Nieprawidłowy kod.");
}

$row = $result->fetch_assoc();

// 3) Sprawdź, czy nie przekroczono maksymalnej liczby użyć
if ($row['uses'] >= $row['max_uses']) {
    die("Ten kod został już wykorzystany maksymalnie {$row['max_uses']} razy.");
}

$code_id = (int)$row['id'];
$letter  = $row['letter'];

// 4) Hashowanie hasła
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 5) Wstaw użytkownika z przypisaną literą
$stmt = $conn->prepare(
    "INSERT INTO users (name, username, email, password, code_id, letter)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "ssssis",
    $name,
    $username,
    $email,
    $hashed_password,
    $code_id,
    $letter
);

if (! $stmt->execute()) {
    die("Błąd rejestracji: " . $stmt->error);
}

// 6) Zwiększ licznik użyć kodu o 1
$upd = $conn->prepare(
    "UPDATE codes 
     SET uses = uses + 1 
     WHERE id = ?"
);
$upd->bind_param("i", $code_id);
$upd->execute();

// 7) Przekieruj po sukcesie
header("Location: strona.html");
exit;
?>
