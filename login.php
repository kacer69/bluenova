<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

// Dane bazy
$host = 'localhost';
$db   = 'moja_aplikacja';
$user = 'root';
$pass = '';

// Połączenie z bazą
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Błąd połączenia: ' . $conn->connect_error);
}

// Pobranie danych z formularza
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$code     = trim($_POST['code'] ?? '');

// Podstawowa walidacja
if ($username === '' || $email === '' || $password === '' || $code === '') {
    die('Wszystkie pola są wymagane.');
}

// Pobierz użytkownika i powiązany kod
$stmt = $conn->prepare("
    SELECT u.id, u.password, c.code
    FROM users AS u
    JOIN codes AS c ON u.code_id = c.id
    WHERE u.username = ? AND u.email = ?
");
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Nie znaleziono użytkownika o podanych danych.');
}

$row = $result->fetch_assoc();

// Weryfikacja hasła
if (!password_verify($password, $row['password'])) {
    die('Nieprawidłowe hasło.');
}

// Weryfikacja kodu dostępu
if ($code !== $row['code']) {
    die('Nieprawidłowy kod dostępu.');
}

// Zapisz dane w sesji
$_SESSION['user_id']  = $row['id'];
$_SESSION['username'] = $username;

// Sprawdzenie, czy sesja została zapisana i przekierowanie
if (isset($_SESSION['user_id'])) {
    header('Location: strona.html');
    exit;
} else {
    die('Błąd sesji. Spróbuj ponownie.');
}
