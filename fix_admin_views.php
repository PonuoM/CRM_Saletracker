<?php
/**
 * Script to fix admin view files - remove HTML structure and keep only content
 */

$adminViewFiles = [
    'app/views/admin/products/index.php',
    'app/views/admin/products/create.php',
    'app/views/admin/products/edit.php',
    'app/views/admin/companies/index.php',
    'app/views/admin/companies/create.php',
    'app/views/admin/companies/edit.php',
    'app/views/admin/users/create.php',
    'app/views/admin/users/edit.php',
    'app/views/admin/settings.php',
    'app/views/admin/workflow.php',
    'app/views/admin/customer_distribution.php'
];

foreach ($adminViewFiles as $file) {
    if (file_exists($file)) {
        echo "Processing: $file\n";
        
        $content = file_get_contents($file);
        
        // Remove HTML structure from beginning
        $content = preg_replace('/^.*?<main[^>]*>/s', '', $content);
        
        // Remove HTML structure from end
        $content = preg_replace('/<\/main>.*?<\/html>\s*$/s', '', $content);
        
        // Remove extra scripts and body/html tags
        $content = preg_replace('/<\/div>\s*<\/div>\s*<script.*?<\/html>\s*$/s', '', $content);
        
        // Clean up the beginning - remove DOCTYPE, html, head, body tags
        $content = preg_replace('/^.*?<!DOCTYPE.*?<body[^>]*>/s', '<?php
/**
 * ' . basename(dirname($file)) . ' - ' . basename($file, '.php') . '
 */
?>', $content);
        
        // Remove duplicate PHP opening tags
        $content = preg_replace('/\?>\s*<\?php/', '', $content);
        
        // Save the cleaned content
        file_put_contents($file, $content);
        
        echo "✅ Fixed: $file\n";
    } else {
        echo "❌ File not found: $file\n";
    }
}

echo "\n🎉 All admin view files have been processed!\n";
?>
