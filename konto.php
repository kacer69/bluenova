<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$conn = new mysqli('localhost','root','','moja_aplikacja');
if ($conn->connect_error) {
    die("Błąd połączenia: ".$conn->connect_error);
}

$userId = intval($_SESSION['user_id']);

// Obsługa POST (aktualizacja danych)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
      'first_name','last_name','birth_date','phone_number',
      'address','city','postcode','country',
      'bio','gender','status','role','social_media_links'
    ];
    $sets = [];
    $vals = [];
    foreach ($fields as $f) {
        $sets[] = "$f = ?";
        $vals[] = ($_POST[$f] ?? '') !== "" ? $_POST[$f] : null;
    }
    $sql = "UPDATE users SET " . implode(", ", $sets) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);

    // scal wartości i userId
    $params = array_merge($vals, [$userId]);
    $types  = str_repeat('s', count($fields)) . 'i';

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    header("Location: konto.php");
    exit;
}

// Pobranie danych
$stmt = $conn->prepare("
  SELECT username,email,created_at,
         first_name,last_name,birth_date,phone_number,
         address,city,postcode,country,
         bio,gender,last_login,status,role,social_media_links
  FROM users WHERE id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute(); // Zmienione na poprawną metodę
$stmt->bind_result(
  $username,$email,$createdAt,
  $firstName,$lastName,$birthDate,$phoneNumber,
  $address,$city,$postcode,$country,
  $bio,$gender,$lastLogin,$status,$role,$socialMediaLinks
);
$stmt->fetch();
$stmt->close();
$conn->close();

// Czy pokazujemy formularz edycji?
$editing = isset($_GET['edit']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Moje konto</title>
  <link rel="stylesheet" href="konto.css">
</head>
<body>
  <div class="container">
    <h2>Moje konto</h2>

<?php if (!$editing): ?>
    <div class="info">
      <p><span class="label">Nazwa użytkownika:</span> <?=htmlspecialchars($username)?></p>
      <p><span class="label">E-mail:</span> <?=htmlspecialchars($email)?></p>
      <p><span class="label">Data rejestracji:</span> <?=$createdAt?></p>
      <p><span class="label">Imię:</span> <?=htmlspecialchars($firstName)?></p>
      <p><span class="label">Nazwisko:</span> <?=htmlspecialchars($lastName)?></p>
      <p><span class="label">Data urodzenia:</span> <?=$birthDate?></p>
      <p><span class="label">Telefon:</span> <?=htmlspecialchars($phoneNumber)?></p>
      <p><span class="label">Adres:</span> <?=nl2br(htmlspecialchars($address))?></p>
      <p><span class="label">Miasto:</span> <?=htmlspecialchars($city)?></p>
      <p><span class="label">Kod pocztowy:</span> <?=htmlspecialchars($postcode)?></p>
      <p><span class="label">Kraj:</span> <?=htmlspecialchars($country)?></p>
      <p><span class="label">Biografia:</span> <?=nl2br(htmlspecialchars($bio))?></p>
      <p><span class="label">Płeć:</span> <?=htmlspecialchars($gender)?></p>
      <p><span class="label">Ostatnie logowanie:</span> <?=$lastLogin?></p>
      <p><span class="label">Status:</span> <?=htmlspecialchars($status)?></p>
      <p><span class="label">Rola:</span> <?=htmlspecialchars($role)?></p>
      <p><span class="label">Social media:</span> <?=nl2br(htmlspecialchars($socialMediaLinks))?></p>
    </div>
    <div class="actions">
      <a href="konto.php?edit=1" class="btn">Edytuj dane</a>
    </div>

<?php else: ?>
    <form method="POST" action="konto.php">
        <div class="info1">
      <p><label class="label">Imię:<br><input type="text" name="first_name" value="<?=htmlspecialchars($firstName)?>"></label></p>
      <p><label class="label">Nazwisko:<br><input type="text" name="last_name" value="<?=htmlspecialchars($lastName)?>"></label></p>
      <p><label class="label">Data urodzenia:<br><input type="date" name="birth_date" value="<?=$birthDate?>"></label></p>
      <p><label class="label">Telefon:<br><input type="text" name="phone_number" value="<?=htmlspecialchars($phoneNumber)?>"></label></p>
      <p><label class="label">Adres:<br><textarea name="address"><?=htmlspecialchars($address)?></textarea></label></p>
      <p><label class="label">Miasto:<br><input type="text" name="city" value="<?=htmlspecialchars($city)?>"></label></p>
      <p><label class="label">Kod pocztowy:<br><input type="text" name="postcode" value="<?=htmlspecialchars($postcode)?>"></label></p>
      <p><label class="label">Kraj:<br><input type="text" name="country" value="<?=htmlspecialchars($country)?>"></label></p>
      <p><label class="label">Biografia:<br><textarea name="bio"><?=htmlspecialchars($bio)?></textarea></label></p>
      <p><label class="label">Płeć:<br>
        <select name="gender">
          <option value="">— wybierz —</option>
          <?php foreach (['Kobieta','Mężczyzna','Inna'] as $g): ?>
            <option <?= $gender === $g ? 'selected' : '' ?> value="<?=$g?>"><?=htmlspecialchars($g)?></option>
          <?php endforeach; ?>
        </select>
      </label></p>
      <p><label class="label">Status:<br><input type="text" name="status" value="<?=htmlspecialchars($status)?>"></label></p>
      <p><label class="label">Social media (URL):<br><textarea name="social_media_links"><?=htmlspecialchars($socialMediaLinks)?></textarea></label></p>
          </div> <div class="actions">
        <button type="submit" class="save">Zapisz</button>
        <a href="konto.php" class="cancel">Anuluj</a>
      </div>
    </form>
<?php endif; ?>
  </div>
</body>
</html>
