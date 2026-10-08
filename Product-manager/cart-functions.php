<?php
session_start();
require __DIR__ . '/database.php';

$_SESSION['cart'] ??= [];
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function euro(int $cents): string
{
    return number_format($cents / 100, 2, ',', '.') . ' €';
}

function checkToken(): void
{
    $token = $_POST['csrf'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Ungültige Anfrage. Bitte die Seite neu laden.');
    }
}

// Read current products and prices from the database.
function cartItems(PDO $db): array
{
    $items = [];
    $statement = $db->prepare(
        'SELECT * FROM products WHERE id = ?'
    );

    foreach ($_SESSION['cart'] as $id => $quantity) {
        $statement->execute([(int) $id]);
        $product = $statement->fetch();

        if (!$product) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        $product['quantity'] = (int) $quantity;
        $product['subtotal'] =
            (int) $product['price_cents'] * (int) $quantity;

        $items[] = $product;
    }

    return $items;
}