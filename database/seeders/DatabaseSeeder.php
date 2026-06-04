<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Location;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\SupplierBankAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::count() > 0) {
            return;
        }

        // 1. Create Default Users for Roles
        User::factory()->create([
            'name' => 'POS Administrator (المدير)',
            'email' => 'admin@pos.ly',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'POS Cashier (الكاشير)',
            'email' => 'cashier@pos.ly',
            'password' => bcrypt('cashier123'),
            'role' => 'cashier',
        ]);

        User::factory()->create([
            'name' => 'POS Kitchen Chef (الطاهي)',
            'email' => 'chef@pos.ly',
            'password' => bcrypt('chef123'),
            'role' => 'chef',
        ]);

        // 2. Create Locations
        $tripoli = Location::create([
            'id' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Tripoli Central (Al-Nofleen)',
        ]);

        $benghazi = Location::create([
            'id' => '22222222-2222-2222-2222-222222222222',
            'name' => 'Benghazi Branch (Dubai Street)',
        ]);

        // 3. Create Products (Menu Items)
        $pizza = Product::create([
            'name' => 'Pizza Margherita',
            'base_price' => 25.00,
            'category' => 'Pizza',
            'image_url' => 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=500&auto=format&fit=crop',
        ]);

        $shawarma = Product::create([
            'name' => 'Chicken Shawarma',
            'base_price' => 15.00,
            'category' => 'Shawarma',
            'image_url' => 'https://images.unsplash.com/photo-1619535860434-ba1d8fa12536?w=500&auto=format&fit=crop',
        ]);

        $burger = Product::create([
            'name' => 'Beef Burger Classic',
            'base_price' => 18.00,
            'category' => 'Burgers',
            'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&auto=format&fit=crop',
        ]);

        $espresso = Product::create([
            'name' => 'Espresso',
            'base_price' => 5.00,
            'category' => 'Coffee',
            'image_url' => 'https://images.unsplash.com/photo-151097252790b-a49ef2a24316?w=500&auto=format&fit=crop',
        ]);

        $orangeJuice = Product::create([
            'name' => 'Fresh Orange Juice',
            'base_price' => 8.00,
            'category' => 'Cold Drinks',
            'image_url' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=500&auto=format&fit=crop',
        ]);

        // 4. Create Ingredients
        $flour = Ingredient::create([
            'name' => 'Flour',
            'unit' => 'kg',
            'alert_threshold' => 10.00,
        ]);

        $cheese = Ingredient::create([
            'name' => 'Mozzarella Cheese',
            'unit' => 'kg',
            'alert_threshold' => 5.00,
        ]);

        $tomato = Ingredient::create([
            'name' => 'Tomato Sauce',
            'unit' => 'liter',
            'alert_threshold' => 8.00,
        ]);

        $chicken = Ingredient::create([
            'name' => 'Chicken Breast',
            'unit' => 'kg',
            'alert_threshold' => 15.00,
        ]);

        $beef = Ingredient::create([
            'name' => 'Burger Beef',
            'unit' => 'kg',
            'alert_threshold' => 12.00,
        ]);

        $coffeeBeans = Ingredient::create([
            'name' => 'Espresso Beans',
            'unit' => 'kg',
            'alert_threshold' => 3.00,
        ]);

        $oranges = Ingredient::create([
            'name' => 'Fresh Oranges',
            'unit' => 'kg',
            'alert_threshold' => 20.00,
        ]);

        // 5. Connect Products to Ingredients (Recipes)
        $pizza->ingredients()->attach($flour->id, ['quantity_needed' => 0.2500]);
        $pizza->ingredients()->attach($cheese->id, ['quantity_needed' => 0.1500]);
        $pizza->ingredients()->attach($tomato->id, ['quantity_needed' => 0.1000]);

        $shawarma->ingredients()->attach($chicken->id, ['quantity_needed' => 0.2000]);

        $burger->ingredients()->attach($beef->id, ['quantity_needed' => 0.1800]);

        $espresso->ingredients()->attach($coffeeBeans->id, ['quantity_needed' => 0.0180]);

        $orangeJuice->ingredients()->attach($oranges->id, ['quantity_needed' => 0.5000]);

        // 6. Create Initial Inventory Transactions (Restocks)
        $products = [$pizza, $shawarma, $burger, $espresso, $orangeJuice];
        $locations = [$tripoli, $benghazi];

        foreach ($locations as $loc) {
            foreach ($products as $prod) {
                // Initial restock
                InventoryTransaction::create([
                    'product_id' => $prod->id,
                    'location_id' => $loc->id,
                    'quantity' => 100.0000, // restock 100 units
                    'unit_cost' => $prod->base_price * 0.4, // unit cost is 40% of menu price
                    'source_id' => null,
                ]);
            }
        }

        // 7. Create Supplier Bank Accounts
        SupplierBankAccount::create([
            'supplier_name' => 'Al-Madina Food Supplies',
            'bank_name' => 'Wahda Bank',
            'account_no' => '10203040506070',
            'swift_code' => 'WAHDAHLYXXX',
        ]);

        SupplierBankAccount::create([
            'supplier_name' => 'Libya Beverage Distributors',
            'bank_name' => 'Sahary Bank',
            'account_no' => '98765432109876',
            'swift_code' => 'SAHARYLYXXX',
        ]);

        SupplierBankAccount::create([
            'supplier_name' => 'Italian Coffee Importers Co.',
            'bank_name' => 'Jumhouria Bank',
            'account_no' => '54321098765432',
            'swift_code' => 'JUMHLYXXX',
        ]);
    }
}
