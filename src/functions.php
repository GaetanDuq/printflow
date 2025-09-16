<?php

function formatJPY($amount) {
    return '¥' . number_format($amount);
}

function getMaterials(): array
{
  return [
    'PLA'=>1.0,
    'ABS'=>1.2,
    'Aluminum'=>2.0,
    'Titanium'=>3.0];
};

function getMultiplier(string $material): ?float
{
    $materials = getMaterials();
    return $materials[$material] ?? null; // return multiplier or null if not found
}

function estimatePrice(string $material, int $weightGrams): ?int {
    $base = 500;
    $perGram = 8;

    $multiplier = getMultiplier($material);
    if ($multiplier === null) {
        return null; // invalid material
    }

    return $base + ($perGram * $weightGrams * $multiplier);
}

function totalOrders(array $orders): int {
    // simply count how many orders are in the array
    return count($orders);
}

function totalRevenue(array $orders): int {
    $total = 0; // start accumulator
    foreach ($orders as $order) {
        $total += $order['price_jpy']; // add each order's price
    }
    return $total;
}

function countByStatus(array $orders, string $status): int {
    $count = 0;
    foreach ($orders as $order) {
        if ($order['status'] === $status) {
            $count++;
        }
    }
    return $count;
}

function nextStatus(string $current): string {
    $flow = ['requested','design','printing','qa','completed'];
    $i = array_search($current, $flow, true);
    if ($i === false) return 'requested';          // fallback
    if ($i >= count($flow) - 1) return 'completed'; // already at end
    return $flow[$i + 1];
}

function isValidMaterial(string $m): bool {
    return array_key_exists($m, getMaterials());
}

function isValidWeight(int $w): bool {
    return $w > 0; // must be a positive number
}

