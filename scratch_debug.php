<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$locationStocks = \DB::table('inventory_transactions')
    ->select('product_id', 'location_id', \DB::raw('SUM(quantity) as stock'))
    ->groupBy('product_id', 'location_id')
    ->get()
    ->groupBy('location_id')
    ->map(function ($items) {
        return $items->pluck('stock', 'product_id');
    });

echo "--- locationStocks ---\n";
print_r($locationStocks->toArray());

$stockLevels = \DB::table('inventory_transactions')
    ->select('product_id', \DB::raw('SUM(quantity) as stock'))
    ->groupBy('product_id')
    ->pluck('stock', 'product_id');

echo "--- stockLevels ---\n";
print_r($stockLevels->toArray());
