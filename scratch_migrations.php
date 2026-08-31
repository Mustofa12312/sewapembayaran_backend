<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$models = [
    'Admin', 'Product', 'Package', 'PackageFeature', 'LicenseKey',
    'Order', 'Payment', 'OrderLicenseKey', 'AuditLog', 'Customer',
    'Coupon', 'Subscription', 'AffiliateCommission'
];

foreach ($models as $modelName) {
    $class = "\\App\\Models\\$modelName";
    $model = new $class;
    $table = $model->getTable();
    $columns = Illuminate\Support\Facades\Schema::getColumnListing($table);
    
    // exclude id, created_at, updated_at
    $fillable = array_filter($columns, function($c) {
        return !in_array($c, ['id', 'created_at', 'updated_at']);
    });
    
    echo "$modelName \$fillable = ['" . implode("', '", $fillable) . "'];\n";
}
