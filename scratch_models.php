<?php
$modelsDir = __DIR__ . '/app/Models';
$files = scandir($modelsDir);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $filePath = $modelsDir . '/' . $file;
        $content = file_get_contents($filePath);
        if ($file !== 'Admin.php' && $file !== 'User.php' && !str_contains($content, '$guarded')) {
            $content = preg_replace('/class (\w+) extends Model\n\{/', "class $1 extends Model\n{\n    protected \$guarded = [];", $content);
            file_put_contents($filePath, $content);
            echo "Updated model: $file\n";
        }
    }
}
