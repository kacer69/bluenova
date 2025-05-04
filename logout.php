<?php
session_start(); // Rozpoczęcie sesji, jeśli nie jest jeszcze rozpoczęta

// Usunięcie wszystkich zmiennych sesyjnych
session_unset();

// Zniszczenie sesji
session_destroy();

// Przekierowanie na stronę logowania
header("Location: login.html");
exit;
?>
