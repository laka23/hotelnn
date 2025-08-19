<?php
declare(strict_types=1);

function cart_get(): array
{
	return $_SESSION['cart'] ?? [];
}

function cart_add(int $productId, string $name, float $price, int $quantity = 1): void
{
	$cart = cart_get();
	if (isset($cart[$productId])) {
		$cart[$productId]['quantity'] += $quantity;
	} else {
		$cart[$productId] = [
			'product_id' => $productId,
			'name' => $name,
			'price' => $price,
			'quantity' => $quantity,
		];
	}
	$_SESSION['cart'] = $cart;
}

function cart_update(int $productId, int $quantity): void
{
	$cart = cart_get();
	if (isset($cart[$productId])) {
		$cart[$productId]['quantity'] = max(0, $quantity);
		if ($cart[$productId]['quantity'] === 0) {
			unset($cart[$productId]);
		}
		$_SESSION['cart'] = $cart;
	}
}

function cart_clear(): void
{
	unset($_SESSION['cart']);
}

function cart_totals(float $taxRate, float $discount = 0.0): array
{
	$cart = cart_get();
	$subtotal = 0.0;
	foreach ($cart as $item) {
		$subtotal += $item['price'] * $item['quantity'];
	}
	$tax = round($subtotal * $taxRate, 2);
	$total = max(0.0, $subtotal + $tax - $discount);
	return compact('subtotal', 'tax', 'discount', 'total');
}