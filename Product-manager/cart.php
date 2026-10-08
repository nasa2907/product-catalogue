<?php
require __DIR__ . '/cart-functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkToken();

    $id = filter_var(
        $_POST['product_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $action = $_POST['action'] ?? '';

    if ($id === false || !isset($_SESSION['cart'][$id])) {
        $error = 'Produkt nicht im Warenkorb gefunden.';
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$id]);
        header('Location: cart.php');
        exit;
    } elseif ($action === 'update') {
        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 99]]
        );

        if ($quantity === false) {
            $error = 'Bitte eine Menge zwischen 1 und 99 eingeben.';
        } else {
            $_SESSION['cart'][$id] = $quantity;
            header('Location: cart.php');
            exit;
        }
    } else {
        $error = 'Ungültige Aktion.';
    }
}

$items = cartItems($db);
$total = array_sum(array_column($items, 'subtotal'));
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Warenkorb</title>
  <link rel="stylesheet" href="shop.css">
</head>
<body>
<main>
  <nav>
    <a href="shop.php">Weiter einkaufen</a>
    <a href="index.php">Produktverwaltung</a>
  </nav>

  <h1>Warenkorb</h1>

  <?php if ($error !== ''): ?>
    <p class="error" role="alert">
      <?= escapeHtml($error) ?>
    </p>
  <?php endif; ?>

  <?php if (!$items): ?>
    <p>Dein Warenkorb ist leer.</p>
  <?php endif; ?>

  <?php foreach ($items as $item): ?>
    <article class="card">
      <h2><?= escapeHtml($item['name']) ?></h2>

      <p>
        Einzelpreis:
        <?= euro((int) $item['price_cents']) ?>
      </p>

      <p class="price">
        Zwischensumme: <?= euro($item['subtotal']) ?>
      </p>

      <form method="post" action="cart.php" class="cart-form">
        <input type="hidden" name="csrf"
               value="<?= escapeHtml($_SESSION['csrf']) ?>">
        <input type="hidden" name="product_id"
               value="<?= (int) $item['id'] ?>">

        <label for="quantity-<?= (int) $item['id'] ?>">Menge</label>
        <input
          id="quantity-<?= (int) $item['id'] ?>"
          type="number"
          name="quantity"
          min="1"
          max="99"
          required
          value="<?= $item['quantity'] ?>"
        >

        <button type="submit" name="action" value="update">
          Aktualisieren
        </button>

        <button type="submit" name="action" value="remove"
                class="remove" formnovalidate>
          Entfernen
        </button>
      </form>
    </article>
  <?php endforeach; ?>

  <h2>Gesamtsumme: <?= euro($total) ?></h2>
  <p>Demo ohne Zahlung oder Bestellabschluss.</p>
</main>
</body>
</html>