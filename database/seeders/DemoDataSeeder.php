<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fills the database with a realistic catalog so the admin panel and the
 * public endpoints have something to show during development and demos.
 *
 * Safe to re-run: it keys off slugs/emails that already exist.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * @var array<string, array<int, array{name: string, price: float, sale: bool}>>
     */
    protected array $catalog = [
        'Electronics' => [
            ['name' => 'Wireless Noise Cancelling Headphones', 'price' => 249.00, 'sale' => true],
            ['name' => 'Mechanical Keyboard 75 Percent', 'price' => 129.50, 'sale' => false],
            ['name' => 'USB C Docking Station', 'price' => 89.99, 'sale' => false],
            ['name' => '4K Webcam With Ring Light', 'price' => 74.00, 'sale' => true],
            ['name' => 'Portable SSD 1TB', 'price' => 112.00, 'sale' => false],
        ],
        'Home & Kitchen' => [
            ['name' => 'Pour Over Coffee Set', 'price' => 42.00, 'sale' => false],
            ['name' => 'Cast Iron Skillet 12 Inch', 'price' => 58.00, 'sale' => true],
            ['name' => 'Ceramic Dinnerware Set', 'price' => 96.00, 'sale' => false],
            ['name' => 'Electric Milk Frother', 'price' => 34.99, 'sale' => false],
        ],
        'Apparel' => [
            ['name' => 'Merino Wool Crew Sweater', 'price' => 118.00, 'sale' => true],
            ['name' => 'Selvedge Denim Jacket', 'price' => 165.00, 'sale' => false],
            ['name' => 'Organic Cotton Tee', 'price' => 28.00, 'sale' => false],
            ['name' => 'Waterproof Trail Runners', 'price' => 138.00, 'sale' => false],
        ],
        'Books' => [
            ['name' => 'The Pragmatic Programmer', 'price' => 44.00, 'sale' => false],
            ['name' => 'Designing Data Intensive Applications', 'price' => 52.00, 'sale' => true],
            ['name' => 'Refactoring Second Edition', 'price' => 48.00, 'sale' => false],
        ],
    ];

    public function run(): void
    {
        $products = $this->seedCatalog();
        $customers = $this->seedCustomers();

        $this->seedBanners();
        $this->seedContentBlocks();
        $this->seedOrders($customers, $products);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    protected function seedCatalog()
    {
        $products = collect();

        foreach ($this->catalog as $categoryName => $items) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'description' => "Everything in our {$categoryName} range.",
                    'is_active' => true,
                ]
            );

            foreach ($items as $index => $item) {
                $product = Product::firstOrCreate(
                    ['slug' => Str::slug($item['name'])],
                    [
                        'category_id' => $category->id,
                        'name' => $item['name'],
                        'sku' => Str::upper('SKU-' . Str::substr(Str::slug($item['name']), 0, 6) . '-' . random_int(100, 999)),
                        'short_description' => "A well made {$item['name']} from our {$categoryName} collection.",
                        'description' => "Full product description for the {$item['name']}. Replace this copy with real merchandising content.",
                        'price' => $item['price'],
                        'sale_price' => $item['sale'] ? round($item['price'] * 0.85, 2) : null,
                        'stock_quantity' => $index === 0 ? 3 : random_int(8, 90),
                        'is_active' => true,
                        'is_featured' => $index < 2,
                    ]
                );

                $products->push($product);
            }
        }

        return $products;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function seedCustomers()
    {
        $customers = collect();

        foreach ([
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Tom Hiddleston', 'email' => 'tom@example.com'],
            ['name' => 'Mark Johnson', 'email' => 'mark@example.com'],
        ] as $data) {
            $customer = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );

            if (!$customer->hasRole('customer')) {
                $customer->assignRole('customer');
            }

            $customers->push($customer);
        }

        return $customers;
    }

    protected function seedBanners(): void
    {
        if (Banner::query()->exists()) {
            return;
        }

        Banner::factory()->create([
            'title' => 'New season, new gear',
            'subtitle' => 'Up to 15% off selected electronics and apparel.',
            'button_text' => 'Shop the sale',
            'button_link' => '/products?featured=1',
            'image' => null,
            'sort_order' => 1,
        ]);

        Banner::factory()->create([
            'title' => 'Free shipping over $75',
            'subtitle' => 'On every order, no code needed.',
            'button_text' => 'Browse products',
            'button_link' => '/products',
            'image' => null,
            'sort_order' => 2,
        ]);
    }

    protected function seedContentBlocks(): void
    {
        foreach ([
            [
                'key' => 'home-hero',
                'title' => 'Thoughtfully made everyday goods',
                'content' => 'A small catalog of things we actually use, chosen for durability over novelty.',
            ],
            [
                'key' => 'shipping-policy',
                'title' => 'Shipping & returns',
                'content' => 'Orders ship within two business days. Returns accepted within 30 days of delivery.',
            ],
            [
                'key' => 'about-us',
                'title' => 'About this store',
                'content' => 'A demo storefront built on Laravel and Vue. Replace this copy with your own.',
            ],
        ] as $block) {
            ContentBlock::firstOrCreate(['key' => $block['key']], $block + ['is_active' => true]);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $customers
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    protected function seedOrders($customers, $products): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        foreach ($customers as $customerIndex => $customer) {
            foreach (range(1, 3) as $orderIndex) {
                $lineItems = $products->random(random_int(1, 3));

                $subtotal = 0;

                $order = Order::factory()->forUser($customer)->create([
                    'status' => $statuses[($customerIndex + $orderIndex) % count($statuses)],
                    'payment_status' => $orderIndex === 1 ? 'unpaid' : 'paid',
                    'subtotal' => 0,
                    'total' => 0,
                ]);

                foreach ($lineItems as $product) {
                    $quantity = random_int(1, 3);

                    OrderItem::factory()
                        ->forProduct($product, $quantity)
                        ->create(['order_id' => $order->id]);

                    $subtotal += $product->current_price * $quantity;
                }

                $order->update([
                    'subtotal' => round($subtotal, 2),
                    'total' => round($subtotal, 2),
                ]);
            }
        }
    }
}
