<?php
require __DIR__ . '/database.php';

session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete') {

    $token = $_POST['csrf'] ?? '';

    if (!is_string($token)
        || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Ungültige Anfrage. Bitte die Seite neu laden.');
    }

    $id = filter_var(
        $_POST['product_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        http_response_code(400);
        exit('Ungültiges Produkt.');
    }

    $statement = $db->prepare('DELETE FROM products WHERE id = ?');
    $statement->execute([$id]);

    // Remove the product from this browser's cart too.
    unset($_SESSION['cart'][$id]);

    header('Location: index.php');
    exit;
}

$categories = ['Kopfhörer', 'Tastaturen', 'Mäuse'];
$error = '';
$name = '';
$category = '';
$price = '';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $category = (string) ($_POST['category'] ?? '');
    $price = str_replace(',', '.', trim((string) ($_POST['price'] ?? '')));

    if ($name === '' || strlen($name) > 120) {
        $error = 'Bitte einen Produktnamen mit maximal 120 Bytes eingeben.';
    } elseif (!in_array($category, $categories, true)) {
        $error = 'Bitte eine gültige Kategorie auswählen.';
    } elseif (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $price)) {
        $error = 'Bitte einen Preis zwischen 0 und 999999,99 eingeben.';
    } else {
        // Convert the price to cents without floating-point calculations.
        $parts = explode('.', $price);
        $priceCents = (int) $parts[0] * 100
            + (int) str_pad($parts[1] ?? '', 2, '0');

        $statement = $db->prepare(
            'INSERT INTO products (name, category, price_cents)
             VALUES (:name, :category, :price)'
        );

        $statement->execute([
            'name' => $name,
            'category' => $category,
            'price' => $priceCents
        ]);

        // Prevent another insertion when refreshing the page.
        header('Location: index.php');
        exit;
    }
}

$products = $db->query(
    'SELECT * FROM products ORDER BY id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Produktverwaltung</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f8;
      color: #182230;
      margin: 0;
    }
    main { max-width: 850px; margin: auto; padding: 30px 20px; }
    form, article {
      background: white;
      border: 1px solid #d8dee5;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 16px;
    }
    label { display: block; font-weight: bold; margin-bottom: 8px; }
    input, select {
      width: 100%;
      padding: 12px;
      margin-bottom: 18px;
      font: inherit;
    }
    button {
      background: #1859b7;
      color: white;
      border: 0;
      border-radius: 6px;
      padding: 12px 20px;
      font: inherit;
      cursor: pointer;
    }
    .error { color: #a00000; }
  </style>
</head>
<body>
<main>
  <h1>Produktverwaltung</h1>
<nav style="display: flex; gap: 24px; margin-bottom: 24px;">
  <a href="shop.php">Zum Shop</a>
  <a href="cart.php">Zum Warenkorb</a>
</nav>
  <h2>Produkt hinzufügen</h2>

  <?php if ($error !== ''): ?>
    <p class="error" role="alert"><?= escape($error) ?></p>
  <?php endif; ?>

  <form method="post" action="index.php">
    <label for="name">Produktname</label>
    <input id="name" name="name" required
           value="<?= escape($name) ?>">

    <label for="category">Kategorie</label>
    <select id="category" name="category" required>
      <option value="">Bitte auswählen</option>
      <?php foreach ($categories as $item): ?>
        <option value="<?= escape($item) ?>"
          <?= $category === $item ? 'selected' : '' ?>>
          <?= escape($item) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="price">Preis in Euro</label>
    <input id="price" name="price" type="text"
           inputmode="decimal" placeholder="24,99" required
           value="<?= escape($price) ?>">

    <button type="submit">Produkt speichern</button>
  </form>

  <h2>Gespeicherte Produkte</h2>

  <?php if (count($products) === 0): ?>
    <p>Noch keine Produkte vorhanden.</p>
  <?php endif; ?>

  <?php foreach ($products as $product): ?>
    <article>
      <h3><?= escape($product['name']) ?></h3>
      <p><?= escape($product['category']) ?></p>
      <strong>
        <?= number_format($product['price_cents'] / 100, 2, ',', '.') ?> €
      </strong>
            <form method="post" action="index.php"
            onsubmit="return confirm('Dieses Produkt wirklich löschen?');">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="product_id"
               value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="csrf"
               value="<?= escape($_SESSION['csrf']) ?>">

        <button type="submit" style="background: #a32323;">
          Produkt löschen
        </button>
      </form>
    </article>
  <?php endforeach; ?>
</main>
</body>
</html>