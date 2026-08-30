<?php
$migrationsDir = __DIR__ . '/database/migrations';
$files = scandir($migrationsDir);

$schemaDefinitions = [
    'admins' => <<<PHP
            \$table->id();
            \$table->string('name');
            \$table->string('email')->unique();
            \$table->string('password');
            \$table->rememberToken();
            \$table->timestamps();
PHP,
    'products' => <<<PHP
            \$table->id();
            \$table->string('name');
            \$table->string('slug')->unique();
            \$table->text('description')->nullable();
            \$table->string('thumbnail')->nullable();
            \$table->string('category')->nullable();
            \$table->string('status')->default('ACTIVE'); // ACTIVE, INACTIVE, ARCHIVED
            \$table->timestamps();
PHP,
    'packages' => <<<PHP
            \$table->id();
            \$table->foreignId('product_id')->constrained()->cascadeOnDelete();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->decimal('price', 15, 2);
            \$table->integer('duration_value')->nullable();
            \$table->string('duration_unit')->nullable(); // MONTH, YEAR
            \$table->boolean('is_unlimited')->default(false);
            \$table->string('status')->default('ACTIVE'); // ACTIVE, INACTIVE, ARCHIVED
            \$table->timestamps();
PHP,
    'package_features' => <<<PHP
            \$table->id();
            \$table->foreignId('package_id')->constrained()->cascadeOnDelete();
            \$table->string('feature_name');
            \$table->timestamps();
PHP,
    'license_keys' => <<<PHP
            \$table->id();
            \$table->foreignId('product_id')->constrained()->cascadeOnDelete();
            \$table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            \$table->string('license_key')->unique();
            \$table->string('status')->default('AVAILABLE'); // AVAILABLE, ASSIGNED, ACTIVE, EXPIRED, DISABLED
            \$table->foreignId('assigned_order_id')->nullable();
            \$table->timestamp('assigned_at')->nullable();
            \$table->timestamp('expires_at')->nullable();
            \$table->timestamps();
PHP,
    'orders' => <<<PHP
            \$table->id();
            \$table->string('order_number')->unique();
            \$table->string('secure_token')->unique();
            \$table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            \$table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            \$table->string('customer_name');
            \$table->string('customer_email');
            \$table->string('customer_phone')->nullable();
            \$table->decimal('snapshot_price', 15, 2);
            \$table->dateTime('start_date')->nullable();
            \$table->dateTime('end_date')->nullable();
            \$table->string('status')->default('PENDING_PAYMENT'); // PENDING_PAYMENT, PAID, ACTIVE, EXPIRED
            \$table->timestamps();
PHP,
    'payments' => <<<PHP
            \$table->id();
            \$table->foreignId('order_id')->constrained()->cascadeOnDelete();
            \$table->string('midtrans_transaction_id')->nullable()->unique();
            \$table->string('gateway')->default('midtrans');
            \$table->decimal('amount', 15, 2);
            \$table->string('status')->default('PENDING'); // PENDING, PAID, FAILED, EXPIRED
            \$table->string('payment_method')->nullable();
            \$table->timestamp('paid_at')->nullable();
            \$table->json('raw_response')->nullable();
            \$table->timestamps();
PHP,
    'order_license_keys' => <<<PHP
            \$table->id();
            \$table->foreignId('order_id')->constrained()->cascadeOnDelete();
            \$table->foreignId('license_key_id')->constrained()->cascadeOnDelete();
            \$table->timestamps();
PHP,
    'audit_logs' => <<<PHP
            \$table->id();
            \$table->string('action');
            \$table->string('entity');
            \$table->unsignedBigInteger('entity_id');
            \$table->json('before_data')->nullable();
            \$table->json('after_data')->nullable();
            \$table->string('ip_address')->nullable();
            \$table->string('user_agent')->nullable();
            \$table->timestamps();
PHP
];

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        foreach ($schemaDefinitions as $table => $schema) {
            if (str_contains($file, "create_{$table}_table.php")) {
                $filePath = $migrationsDir . '/' . $file;
                $content = file_get_contents($filePath);
                
                // Replace up() function body
                $pattern = '/Schema::create\(\'' . $table . '\', function \(Blueprint \$table\) \{(.*?)\}\);/s';
                $replacement = "Schema::create('" . $table . "', function (Blueprint \$table) {\n" . $schema . "\n        });";
                $content = preg_replace($pattern, $replacement, $content);
                
                file_put_contents($filePath, $content);
                echo "Updated migration for $table\n";
            }
        }
    }
}
