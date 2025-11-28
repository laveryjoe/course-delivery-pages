<?php
/**
 * Course Platform Router
 *
 * Just upload index.php and course-data.json - that's it!
 * Clean URLs work automatically: /lesson/your-lesson-slug
 */

// === ROUTING LOGIC ===
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath === '\\' || $basePath === '.') $basePath = '/';
$path = parse_url($requestUri, PHP_URL_PATH);
if ($basePath !== '/' && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
$path = trim($path, '/');

// Serve static files directly (JSON, images, JS, CSS, etc.)
if ($path !== '' && strpos($path, 'lesson') !== 0) {
    $filePath = __DIR__ . '/' . $path;
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $types = ['json'=>'application/json','js'=>'application/javascript','css'=>'text/css','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','svg'=>'image/svg+xml','mp4'=>'video/mp4','webm'=>'video/webm'];
        if (isset($types[$ext])) header('Content-Type: ' . $types[$ext]);
        readfile($filePath);
        exit;
    }
}

// Calculate base URL for assets (ensures config.js loads correctly from any path)
$baseUrl = rtrim($basePath, '/');
if ($baseUrl === '') $baseUrl = '.';

// For root or /lesson/* URLs, serve the course page below
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars($baseUrl); ?>/">
    <title>Self Host Revolution - Build Software Products with AI</title>
    <meta name="title" content="Self Host Revolution - Build Software Products with AI">
    <meta name="description" content="Learn to build software products using AI as your coding partner.">
    <meta name="author" content="Joe Lee">
    <meta name="theme-color" content="#0046dd">
    <link href="https://fonts.googleapis.com/css2?family=Satoshi:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Prevent FOUC - apply theme immediately
        (function() {
            try {
                if (localStorage.getItem('darkMode') === 'true') {
                    document.documentElement.classList.add('dark-theme');
                }
            } catch (e) {}
        })();
    </script>
<?php
// Include the rest of the HTML from course.html
$courseHtml = file_get_contents(__DIR__ . '/course.html');
// Extract everything after the opening <style> tag
$startPos = strpos($courseHtml, '<style>');
if ($startPos !== false) {
    echo substr($courseHtml, $startPos);
} else {
    echo "Error: course.html not found or invalid.";
}
?>
