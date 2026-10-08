<?php
require __DIR__ . '/cart-functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkToken();

    $id = filter_var(
        $_POST['product_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        http_response_code(400);
        exit('Ungültiges Produkt.');
    }

    $statement = $db->prepare(
        'SELECT id FROM products WHERE id = ?'
    );
    $statement->execute([$id]);

    if (!$statement->fetch()) {
        http_response_code(404);
        exit('Produkt nicht gefunden.');
    }

    $quantity = (int) ($_SESSION['cart'][$id] ?? 0);
    $_SESSION['cart'][$id] = min(99, $quantity + 1);

    header('Location: cart.php');
    exit;
}

$products = $db->query(
    'SELECT * FROM products ORDER BY name'
)->fetchAll();

$items = cartItems($db);
$cartCount = array_sum(array_column($items, 'quantity'));
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shop</title>
  <link rel="stylesheet" href="shop.css">
</head>
<body>
<main>
  <nav>
    <a href="index.php">Produktverwaltung</a>
    <a href="cart.php">
      Warenkorb (<?= $cartCount ?>)
    </a>
  </nav>

  <h1>Unser Shop</h1>

  <?php if (!$products): ?>
    <p>
      Noch keine Produkte vorhanden.
      Füge zuerst Produkte in der
      <a href="index.php">Produktverwaltung</a> hinzu.
    </p>
  <?php endif; ?>

  <div class="product-grid">
    <?php foreach ($products as $product): ?>
      <article class="card">
        <h2><?= escapeHtml($product['name']) ?></h2>
        <p><?= escapeHtml($product['category']) ?></p>
        <p class="price">
          <?= euro((int) $product['price_cents']) ?>
        </p>

        <form method="post" action="shop.php">
          <input type="hidden" name="csrf"
                 value="<?= escapeHtml($_SESSION['csrf']) ?>">
          <input type="hidden" name="product_id"
                 value="<?= (int) $product['id'] ?>">
          <button type="submit">In den Warenkorb</button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</main>
</body>
</html>