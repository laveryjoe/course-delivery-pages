<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===================================
// COURSE CONFIGURATION
// ===================================
define('ADVANCED_COURSE_MODE', true); // true = 2-level collapsible navigation, false = simple flat list
define('USE_VIDEO_THUMBNAILS', true); // true = show thumbnails for video files, false = use browser default
define('AUTO_GENERATE_THUMBNAILS', true); // true = auto-extract first frame, false = use manual thumbnails only
define('DEFAULT_THEME_MODE', 'light'); // Default theme when no localStorage preference exists
define('SIDEBAR_POSITION', 'right'); // 'left' or 'right' - NEW V58 FEATURE

// ===================================
// ICON SYSTEM CONFIGURATION
// ===================================

/**
 * Simple function to render icons using text characters
 *
 * @param string $name Icon name
 * @return string Text character for the icon
 */
function render_icon($name) {
    // Simple map of icon names to text characters
    $icons = [
        'play' => '▶',
        'play-next' => '▶',
        'chevron-right' => '▶',
        'chevron-down' => '▼',
        'menu' => '☰',
        'chart' => '📊',
        'target' => '🎯',
        'book' => '📚',
        'moon' => '🌙',
        'sun' => '☀️',
    ];

    // Return the icon if it exists, otherwise return an empty string
    return isset($icons[$name]) ? $icons[$name] : '';
}

// ===================================
// SUPABASE CONFIGURATION
// ===================================
define('SUPABASE_URL', 'https://evfhfsjdzfippxmpvstz.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImV2Zmhmc2pkemZpcHB4bXB2c3R6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDkwMTMzNTksImV4cCI6MjA2NDU4OTM1OX0.lTMQNm1NQmUTcGgeK4u8q137utDYUn2DHSVaIxOxBTU');

// ===================================
// URL ROUTING & SLUG HANDLING
// ===================================
function get_lesson_from_url() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);

    // Match /lesson/SLUG or /lesson/ID
    if (preg_match('/\/lesson\/([^\/\?]+)/', $path, $matches)) {
        $identifier = $matches[1];

        // Check if it's numeric (ID) or slug
        if (is_numeric($identifier)) {
            return fetch_lesson_by_id($identifier);
        } else {
            return fetch_lesson_by_slug($identifier);
        }
    }

    return null;
}

function generate_slug($title) {
    // Convert title to SEO-friendly slug
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s_]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function get_canonical_url($lesson = null) {
    $base_url = "https://joe23.com/members/course-platform";

    if ($lesson && !empty($lesson['slug'])) {
        return $base_url . '/lesson/' . $lesson['slug'];
    } elseif ($lesson) {
        // Fallback to ID if no slug
        return $base_url . '/lesson/' . $lesson['id'];
    }

    return $base_url;
}

// ===================================
// CONTENT PROCESSING FUNCTIONS
// ===================================
function markdown_to_html($markdown) {
    if (empty($markdown)) return '';

    $html = $markdown;
    $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
    $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
    $html = preg_replace('/^- (.*$)/m', '<li>$1</li>', $html);
    $html = preg_replace('/^(\d+)\. (.*$)/m', '<li>$2</li>', $html);
    $html = preg_replace('/(<li>.*?<\/li>\s*)+/s', '<ul>$0</ul>', $html);
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
    $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $html);
    $html = preg_replace('/\n\n+/', '</p><p>', $html);
    $html = '<p>' . $html . '</p>';
    $html = preg_replace('/<p><\/p>/', '', $html);
    $html = preg_replace('/<p>(<h[1-6]>.*?<\/h[1-6]>)<\/p>/', '$1', $html);
    $html = preg_replace('/<p>(<ul>.*?<\/ul>)<\/p>/s', '$1', $html);

    return $html;
}

function format_transcript($transcript_text) {
    if (empty($transcript_text)) {
        return '<li class="transcript-item"><span class="transcript-timestamp">0:00</span><span class="transcript-text">Transcript will be available soon.</span></li>';
    }

    $lines = explode("\n", $transcript_text);
    $formatted = '';
    $time = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $minutes = floor($time / 60);
        $seconds = $time % 60;
        $timestamp = sprintf('%d:%02d', $minutes, $seconds);

        $formatted .= sprintf(
            '<li class="transcript-item" data-time="%s" data-original-text="%s">
                <span class="transcript-timestamp">%s</span>
                <span class="transcript-text">%s</span>
            </li>',
            $timestamp, htmlspecialchars(strtolower($line)), $timestamp, htmlspecialchars($line)
        );

        $time += 15;
    }

    return $formatted;
}

function render_lesson_content($lesson) {
    $content = '';

    if (!empty($lesson['episode_notes_markdown'])) {
        $content .= '<div class="episode-notes-content">' . markdown_to_html($lesson['episode_notes_markdown']) . '</div>';
    } else if (!empty($lesson['content_html'])) {
        $content .= '<div class="episode-notes-content">' . $lesson['content_html'] . '</div>';
    } else {
        $content .= '<div class="episode-notes-content"><p>Notes not available.</p></div>';
    }

    if (!empty($lesson['learning_objectives'])) {
        if (is_string($lesson['learning_objectives'])) {
            $objectives_str = trim($lesson['learning_objectives'], '{}');
            $objectives = array_map('trim', explode(',', $objectives_str));
            $objectives = array_map(function($obj) { return trim($obj, '"'); }, $objectives);
        } else {
            $objectives = $lesson['learning_objectives'];
        }

        if (!empty($objectives)) {
            $content .= '<div class="learning-objectives">';
            $content .= '<h4><span class="target-icon">' . render_icon('target') . '</span> Learning Objectives</h4>';
            $content .= '<ul>';
            foreach ($objectives as $objective) {
                $content .= '<li>' . htmlspecialchars($objective) . '</li>';
            }
            $content .= '</ul></div>';
        }
    }

    return $content;
}

// ===================================
// SUPABASE API FUNCTIONS
// ===================================
function supabase_request($endpoint, $method = 'GET', $data = null) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;

    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    // Try cURL first, fallback to file_get_contents if cURL is not available
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : [];
        }
    } else {
        // Fallback to file_get_contents for GET requests only
        if ($method === 'GET') {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers) . "\r\n"
                ]
            ]);

            $response = @file_get_contents($url, false, $context);
            if ($response !== false) {
                $decoded = json_decode($response, true);
                return is_array($decoded) ? $decoded : [];
            } else {
                // Debug: Log the error if needed
                error_log("Supabase request failed: Unable to fetch from $url");
            }
        } else {
            // Debug: Log unsupported method
            error_log("Supabase request failed: Method $method not supported without cURL");
        }
    }

    return [];
}

function fetch_course($course_id = 1) {
    return supabase_request("courses?id=eq.$course_id&select=*");
}

function fetch_modules($course_id = 1) {
    return supabase_request("modules?course_id=eq.$course_id&select=*&order=order_index");
}

function fetch_all_lessons($course_id = 1) {
    $all_lessons = supabase_request("lessons?select=*&order=module_id,order_index");

    if (empty($all_lessons)) {
        return [];
    }

    $modules = supabase_request("modules?course_id=eq.$course_id&select=id");
    $module_ids = array_column($modules ?: [], 'id');

    $filtered_lessons = [];
    foreach ($all_lessons as $lesson) {
        if (in_array($lesson['module_id'], $module_ids)) {
            $filtered_lessons[] = $lesson;
        }
    }

    return $filtered_lessons;
}

function fetch_lesson_by_id($lesson_id) {
    $result = supabase_request("lessons?id=eq.$lesson_id&select=*");
    return !empty($result) ? $result[0] : null;
}

function fetch_lesson_by_slug($slug) {
    $result = supabase_request("lessons?slug=eq.$slug&select=*");
    return !empty($result) ? $result[0] : null;
}

function fetch_lesson_resources($lesson_id) {
    return supabase_request("resources?lesson_id=eq.$lesson_id&select=*");
}

// NEW: Function to auto-generate and update slugs for existing lessons
function generate_missing_slugs() {
    $lessons = supabase_request("lessons?select=id,title,slug");

    foreach ($lessons as $lesson) {
        if (empty($lesson['slug']) && !empty($lesson['title'])) {
            $new_slug = generate_slug($lesson['title']);

            // Update lesson with new slug
            supabase_request("lessons?id=eq." . $lesson['id'], 'PATCH', [
                'slug' => $new_slug
            ]);

            echo "<!-- Generated slug '{$new_slug}' for lesson '{$lesson['title']}' -->\n";
        }
    }
}

// ===================================
// LOAD DATA WITH URL ROUTING
// ===================================
$course = fetch_course(1);
$modules = fetch_modules(1);
$all_lessons = fetch_all_lessons(1);

$course = is_array($course) ? $course : [];
$modules = is_array($modules) ? $modules : [];
$all_lessons = is_array($all_lessons) ? $all_lessons : [];

// Fallback data if Supabase is not working
if (empty($course)) {
    $course = [[
        'id' => 1,
        'title' => 'Modern Automation Workshop',
        'description' => 'Master n8n & Business Automation',
        'image_url' => 'https://joe23.com/images/modern-automation.png'
    ]];
}

if (empty($modules)) {
    $modules = [
        ['id' => 1, 'title' => 'Getting Started', 'order_index' => 1, 'course_id' => 1],
        ['id' => 2, 'title' => 'Core Fundamentals', 'order_index' => 2, 'course_id' => 1],
        ['id' => 3, 'title' => 'Advanced Workflows', 'order_index' => 3, 'course_id' => 1]
    ];
}

if (empty($all_lessons)) {
    $all_lessons = [
        [
            'id' => 1,
            'title' => 'Welcome & Setup',
            'description' => 'Get started with your automation journey',
            'module_id' => 1,
            'order_index' => 1,
            'duration_minutes' => 7,
            'slug' => 'welcome-setup',
            'video_url' => '',
            'episode_notes_markdown' => '# Welcome to the Course\n\nThis is your first lesson. Let\'s get started with automation!',
            'transcript_text' => 'Welcome to the modern automation workshop. In this course, you will learn how to build powerful automation workflows.'
        ],
        [
            'id' => 2,
            'title' => 'Platform Overview',
            'description' => 'Understanding the n8n platform',
            'module_id' => 1,
            'order_index' => 2,
            'duration_minutes' => 12,
            'slug' => 'platform-overview',
            'video_url' => '',
            'episode_notes_markdown' => '# Platform Overview\n\nGet familiar with the n8n interface and core concepts.',
            'transcript_text' => 'Now let\'s explore the n8n platform and understand its core interface and functionality.'
        ],
        [
            'id' => 3,
            'title' => 'Launch Fundamentals',
            'description' => 'Learn the basics of launching automation systems',
            'module_id' => 2,
            'order_index' => 1,
            'duration_minutes' => 10,
            'slug' => 'launch-fundamentals',
            'video_url' => '',
            'episode_notes_markdown' => '# Launch Fundamentals\n\nBefore the tech, let\'s get strategic. Learn the high-leverage philosophy behind building automation systems.',
            'transcript_text' => 'Before we dive into the technical aspects, it\'s important to understand the strategic foundation of automation systems.'
        ],
        [
            'id' => 4,
            'title' => 'Building Your First Workflow',
            'description' => 'Create your first automation workflow',
            'module_id' => 2,
            'order_index' => 2,
            'duration_minutes' => 15,
            'slug' => 'first-workflow',
            'video_url' => '',
            'episode_notes_markdown' => '# Building Your First Workflow\n\nStep-by-step guide to creating your first automation.',
            'transcript_text' => 'Let\'s build our first automation workflow together, starting with a simple example.'
        ],
        [
            'id' => 5,
            'title' => 'Complex Integrations',
            'description' => 'Advanced integration techniques',
            'module_id' => 3,
            'order_index' => 1,
            'duration_minutes' => 20,
            'slug' => 'complex-integrations',
            'video_url' => '',
            'episode_notes_markdown' => '# Complex Integrations\n\nLearn how to handle complex multi-step integrations.',
            'transcript_text' => 'In this advanced lesson, we\'ll explore complex integration patterns and best practices.'
        ],
        [
            'id' => 6,
            'title' => 'Error Handling & Monitoring',
            'description' => 'Robust error handling strategies',
            'module_id' => 3,
            'order_index' => 2,
            'duration_minutes' => 18,
            'slug' => 'error-handling',
            'video_url' => '',
            'episode_notes_markdown' => '# Error Handling & Monitoring\n\nImplement robust error handling and monitoring for your workflows.',
            'transcript_text' => 'Error handling is crucial for production automation systems. Let\'s explore the best practices.'
        ]
    ];
}

// URL-based lesson loading
$url_lesson = get_lesson_from_url();
$current_lesson = $url_lesson ?: (!empty($all_lessons) ? $all_lessons[0] : null);
$current_lesson_resources = $current_lesson ? fetch_lesson_resources($current_lesson['id']) : [];

// Group lessons by module for sidebar
$lessons_by_module = [];
if (!empty($all_lessons)) {
    foreach ($all_lessons as $lesson) {
        if (isset($lesson['module_id'])) {
            $lessons_by_module[$lesson['module_id']][] = $lesson;
        }
    }
}

// Generate missing slugs (run once to populate existing data)
// generate_missing_slugs(); // Uncomment this line once to generate slugs for existing lessons

// Meta tag data
$page_title = $current_lesson ?
    $current_lesson['title'] . ' - Modern Automation Workshop | Joe Lee' :
    'Modern Automation Workshop - Master n8n & Business Automation | Joe Lee';

$page_description = $current_lesson && !empty($current_lesson['description']) ?
    $current_lesson['description'] :
    'Learn to build powerful automation workflows with n8n. Master credential setup, workflow customization, and smart launch automation. Transform your business processes in 8 hands-on modules.';

$page_image = $current_lesson && !empty($current_lesson['thumbnail_url']) ?
    $current_lesson['thumbnail_url'] :
    (!empty($course[0]['image_url']) ? $course[0]['image_url'] : 'https://joe23.com/images/modern-automation.png');

$canonical_url = get_canonical_url($current_lesson);

function safe_count($array) {
    return is_array($array) ? count($array) : 0;
}

// NEW V58: Determine sidebar position class
$sidebar_position_class = (SIDEBAR_POSITION === 'left') ? 'sidebar-left' : 'sidebar-right';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Dynamic Meta Tags Based on Current Lesson -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="automation, n8n, workflow automation, business automation, no-code automation, process automation, integration, API automation, Joe Lee">
    <meta name="author" content="Joe Lee">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo $canonical_url; ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Joe Lee - Modern Automation">
    <meta property="og:url" content="<?php echo $canonical_url; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:image" content="<?php echo $page_image; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($current_lesson ? $current_lesson['title'] : 'Modern Automation Workshop'); ?> by Joe Lee">
    <meta property="og:locale" content="en_US">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $canonical_url; ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="twitter:image" content="<?php echo $page_image; ?>">
    <meta property="twitter:image:alt" content="<?php echo htmlspecialchars($current_lesson ? $current_lesson['title'] : 'Modern Automation Workshop'); ?> by Joe Lee">
    <meta property="twitter:creator" content="@joelee">
    <meta property="twitter:site" content="@joelee">

    <meta name="theme-color" content="#0046dd">

    <link href="https://fonts.googleapis.com/css2?family=Satoshi:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
/* ===================================
   CSS CUSTOM PROPERTIES (THEME SYSTEM)
   =================================== */
:root {
    --bg-primary: #fafafa;
    --bg-secondary: #ffffff;
    --bg-tertiary: #f8fafc;
    --bg-quaternary: #f1f5f9;
    --text-primary: #1f2937;
    --text-secondary: #4b5563;
    --text-tertiary: #6b7280;
    --text-quaternary: #9ca3af;
    --border-primary: #e5e5e5;
    --border-secondary: #e2e8f0;
    --border-tertiary: #cbd5e1;
    --blue-primary: #0046dd;
    --blue-secondary: #eff6ff;
    --blue-tertiary: #dbeafe;
    --blue-quaternary: #bfdbfe;
    --shadow-primary: rgba(0, 0, 0, 0.1);
    --shadow-secondary: rgba(0, 0, 0, 0.05);
}

.dark-theme {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --bg-quaternary: #475569;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-tertiary: #94a3b8;
    --text-quaternary: #64748b;
    --border-primary: #334155;
    --border-secondary: #475569;
    --border-tertiary: #64748b;
    --blue-primary: #0046dd;
    --blue-secondary: #1e293b;
    --blue-tertiary: #334155;
    --blue-quaternary: #475569;
    --shadow-primary: rgba(0, 0, 0, 0.3);
    --shadow-secondary: rgba(0, 0, 0, 0.2);
}

/* ===================================
   BASE STYLES
   =================================== */
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.65;
    color: var(--text-primary);
    background: var(--bg-primary);
    font-size: 16px;
    font-weight: 400;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Typography */
h1, h2, h3, h4, h5, h6 { font-family: 'Satoshi', sans-serif; color: var(--text-primary); margin: 0; }
h1 { font-size: 2.25rem; font-weight: 700; letter-spacing: -0.025em; line-height: 1.2; }
h2 { font-size: 1.875rem; font-weight: 600; letter-spacing: -0.02em; line-height: 1.25; }
h3 { font-size: 1.5rem; font-weight: 500; letter-spacing: -0.01em; line-height: 1.3; }
h4 { font-size: 1.125rem; font-weight: 600; letter-spacing: -0.005em; line-height: 1.4; }
p { font-size: 1rem; font-weight: 400; line-height: 1.7; color: var(--text-secondary); margin: 0 0 1rem 0; }

/* ===================================
   LAYOUT COMPONENTS
   =================================== */
.header {
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-primary);
    position: sticky;
    top: 0;
    z-index: 100;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.header-container {
    max-width: 90%;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 70px;
}

.logo {
    height: 48px;
    width: auto;
    transition: opacity 0.2s;
}
.logo:hover { opacity: 0.8; }
.logo.light { display: block; }
.logo.dark { display: none; }
.dark-theme .logo.light { display: none; }
.dark-theme .logo.dark { display: block; }

.nav {
    display: flex;
    gap: 2rem;
    align-items: center;
    height: 100%;
}
.nav a {
    color: var(--text-tertiary);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    letter-spacing: 0.005em;
    transition: color 0.2s;
}
.nav a:hover { color: var(--blue-primary); }

/* Main Layout */
.main {
    max-width: 90%;
    width: 100%;
    margin: 0 auto;
    padding: 2rem;
    display: grid;
    gap: 4rem;
    align-items: start;
}

.main.sidebar-right { grid-template-columns: 1fr 380px; }
.main.sidebar-left { grid-template-columns: 380px 1fr; }
.main.sidebar-left .content { order: 2; }
.main.sidebar-left .sidebar { order: 1; }

.content {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 1px 3px var(--shadow-primary);
    transition: background-color 0.3s ease;
}

.sidebar {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px var(--shadow-primary);
    height: fit-content;
    position: sticky;
    top: 100px;
    min-width: 320px;
    transition: background-color 0.3s ease;
    overflow-y: auto;
    max-height: calc(100vh - 4rem);
}

/* ===================================
   VIDEO & MEDIA
   =================================== */
.video-container {
    position: relative;
    width: 100%;
    height: 0;
    padding-bottom: 56.25%;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 2rem;
    background: var(--bg-tertiary);
}
.video-container img,
.video-container iframe,
.video-container video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
.video-container img { object-fit: cover; }
.video-container video { object-fit: contain; }

/* ===================================
   TABS
   =================================== */
.tabs { margin-bottom: 2rem; }
.tab-buttons {
    display: flex;
    border-bottom: 1px solid var(--border-primary);
    margin-bottom: 1.5rem;
}
.tab-button {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    font-weight: 500;
    font-size: 0.9rem;
    letter-spacing: 0.005em;
    color: var(--text-tertiary);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}
.tab-button.active {
    color: var(--blue-primary);
    border-bottom-color: var(--blue-primary);
}
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ===================================
   TRANSCRIPT
   =================================== */
.transcript-list { list-style: none; }
.transcript-item {
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background: var(--bg-tertiary);
    border-radius: 6px;
    border-left: 3px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
}
.transcript-item:hover {
    border-left-color: var(--blue-primary);
    background: var(--bg-quaternary);
}
.transcript-item.hidden { display: none; }
.highlight {
    background: #ffeb3b;
    color: #000;
    font-weight: 600;
    padding: 0.1rem 0.2rem;
    border-radius: 2px;
}
.transcript-timestamp {
    font-weight: 600;
    color: var(--blue-primary);
    font-size: 0.75rem;
    display: block;
    margin-bottom: 0.25rem;
}
.transcript-text {
    color: var(--text-secondary);
    line-height: 1.6;
}

/* ===================================
   LESSON NAVIGATION
   =================================== */
.up-next { margin-top: 3rem; }
.up-next h2 {
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    font-weight: 600;
}
.next-video {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--bg-tertiary);
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
    cursor: pointer;
}
.next-video:hover { background: var(--bg-quaternary); }
.next-video-thumb {
    width: 120px;
    height: 68px;
    background: linear-gradient(135deg, var(--blue-primary), #3b82f6);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.next-video-content h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.next-video-content p {
    color: var(--text-tertiary);
    font-size: 0.9rem;
    line-height: 1.5;
}

/* ===================================
   EPISODE/LESSON LISTS
   =================================== */
.episode-section {
    margin-bottom: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--border-primary);
    overflow: hidden;
}

.module-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: var(--bg-secondary);
    cursor: pointer;
    border-bottom: 1px solid var(--border-primary);
    transition: all 0.2s;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-primary);
}
.module-header:hover { background: var(--bg-tertiary); }
.module-header.active { background: var(--bg-tertiary); }

.module-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
}

.module-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: var(--blue-primary);
    color: white;
    border-radius: 50%;
    font-size: 0.8rem;
    font-weight: 600;
}

.module-arrow {
    transition: transform 0.2s ease-in-out;
    font-size: 0.8rem;
    color: var(--text-tertiary);
}
.module-header.active .module-arrow { transform: rotate(90deg); }

.module-lessons {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    background: var(--bg-secondary);
}
.module-lessons.expanded {
    max-height: 2000px;
    transition: max-height 0.5s ease-in;
}

.episode-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.episode-list li {
    margin: 0;
    padding: 0;
    list-style-type: none;
    display: block;
}

.episode-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    text-decoration: none;
    color: var(--text-secondary);
    border-radius: 6px;
    transition: all 0.2s;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid transparent;
}
.episode-link:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border-color: var(--border-secondary);
}
.episode-link.current {
    background: var(--blue-primary) !important;
    color: white !important;
    font-weight: 500;
    border-color: var(--blue-primary);
}

/* Play Icon */
.play-icon {
    width: 18px;
    height: 18px;
    background: var(--blue-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
    flex-shrink: 0;
}
.episode-link.current .play-icon {
    background: white;
    color: var(--blue-primary);
}

.lesson-title {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.lesson-duration {
    font-size: 0.75rem;
    font-weight: 400;
    opacity: 0.8;
    margin-left: 0.5rem;
    color: var(--text-tertiary);
}
.episode-link.current .lesson-duration {
    color: rgba(255, 255, 255, 0.8) !important;
    opacity: 1;
}

/* ===================================
   CONTENT STYLES
   =================================== */
.episode-notes-content { line-height: 1.7; }
.episode-notes-content h1 { font-size: 1.5rem; font-weight: 600; margin: 1.5rem 0 1rem 0; }
.episode-notes-content h2 { font-size: 1.25rem; font-weight: 600; margin: 1.25rem 0 0.75rem 0; }
.episode-notes-content h3 { font-size: 1.125rem; font-weight: 500; margin: 1rem 0 0.5rem 0; }
.episode-notes-content p { margin: 0 0 1rem 0; }
.episode-notes-content ul,
.episode-notes-content ol { margin: 0 0 1rem 1.5rem; color: var(--text-secondary); }
.episode-notes-content li { margin: 0.25rem 0; }
.episode-notes-content strong { font-weight: 600; color: var(--text-primary); }
.episode-notes-content code {
    background: var(--bg-tertiary);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #e11d48;
}
.episode-notes-content a { color: var(--blue-primary); text-decoration: none; }
.episode-notes-content a:hover { text-decoration: underline; }

.learning-objectives {
    background: var(--blue-secondary);
    border: 1px solid var(--blue-primary);
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
}
.learning-objectives h4 {
    color: var(--blue-primary);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
}
.learning-objectives h4 .target-icon { margin-right: 0.5rem; }
.learning-objectives ul {
    margin: 0;
    padding-left: 1.25rem;
    list-style: none;
}
.learning-objectives li {
    color: var(--text-primary);
    margin: 0.25rem 0;
    font-size: 0.9rem;
    position: relative;
    padding-left: 0.5rem;
}
.learning-objectives li:before {
    content: '•';
    position: absolute;
    left: -0.75rem;
    color: var(--blue-primary);
}

/* ===================================
   LESSON COMPLETION FEATURE
   =================================== */
.lesson-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    margin-bottom: 2rem;
}

.lesson-header > div:first-child {
    flex: 1;
}

.lesson-completion-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.completion-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-secondary);
    user-select: none;
    transition: color 0.2s;
}

.completion-checkbox {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
}

.completion-checkbox input {
    opacity: 0;
    width: 0;
    height: 0;
}

.checkbox-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: white;
    transition: all 0.3s ease;
    border-radius: 13px;
    border: 1px solid var(--border-secondary);
}

.checkbox-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 2px;
    top: 2px;
    background-color: white;
    transition: all 0.3s ease;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.completion-checkbox input:checked + .checkbox-slider {
    background-color: #10b981;
    border-color: #10b981;
}

.completion-checkbox input:checked + .checkbox-slider:before {
    transform: translateX(22px);
}

.completion-checkbox input:checked ~ .completion-label {
    color: #10b981;
}

/* Progress indicator update */
.progress-stats {
    margin-top: 2rem;
    padding: 1rem;
    background: var(--bg-tertiary);
    border-radius: 8px;
    border: 1px solid var(--border-primary);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.progress-header h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
}

.progress-percentage {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--blue-primary);
}

.progress-bar {
    background: var(--bg-quaternary);
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    background: linear-gradient(90deg, var(--blue-primary) 0%, #10b981 100%);
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-text {
    font-size: 0.8rem;
    color: var(--text-tertiary);
}

/* Completed lesson indicators */
.episode-link.completed .play-icon,
.mobile-lesson-item.completed .play-icon {
    background: #10b981 !important;
    position: relative;
    font-size: 0 !important; /* Hide any text content */
    line-height: 0 !important;
}

/* Aggressively hide all inner content */
.episode-link.completed .play-icon *,
.mobile-lesson-item.completed .play-icon * {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}

/* Clear contents and only show checkmark */
.episode-link.completed .play-icon:before,
.mobile-lesson-item.completed .play-icon:before {
    content: '' !important;
    display: block !important;
    width: 100% !important;
    height: 100% !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    background-color: #10b981 !important;
    border-radius: 50% !important;
}

.episode-link.completed .play-icon:after {
    content: '✓' !important; /* Checkmark character */
    color: white !important;
    font-size: 11px !important;
    font-weight: bold !important;
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    display: block !important;
    z-index: 10 !important;
}

.mobile-lesson-item.completed .play-icon:after {
    content: '✓' !important; /* Checkmark character */
    color: white !important;
    font-size: 9px !important;
    font-weight: bold !important;
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    display: block !important;
    z-index: 10 !important;
}

/* Dark theme support */
.dark-theme .completion-checkbox input:checked + .checkbox-slider {
    background-color: #10b981;
}

.dark-theme .progress-stats {
    background: var(--bg-tertiary);
    border-color: var(--border-primary);
}

/* ===================================
   THEME TOGGLE
   =================================== */
.theme-toggle {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
    margin-left: 15px;
    vertical-align: middle;
}
.theme-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e2e8f0;
    transition: all 0.3s ease;
    border-radius: 14px;
    border: 1px solid var(--border-secondary);
}
.toggle-slider:hover { background-color: #cbd5e1; }
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 2px;
    top: 2px;
    background-color: white;
    transition: all 0.3s ease;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.dark-theme .toggle-slider {
    background-color: var(--blue-primary);
    border-color: var(--blue-primary);
}
.dark-theme .toggle-slider:hover { background-color: #0037b8; }
.dark-theme .toggle-slider:before {
    transform: translateX(24px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* ===================================
   MOBILE STYLES
   =================================== */
/* Mobile toggle slider styles */
.mobile-toggle-slider {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
    background-color: white !important;
    border-radius: 13px;
    border: 1px solid var(--border-secondary);
    transition: all 0.3s ease;
    cursor: pointer;
    vertical-align: middle;
    margin-right: 8px;
}

.mobile-toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    top: 3px;
    background-color: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.mobile-toggle-slider.active {
    background-color: #10b981 !important;
    border-color: #10b981 !important;
}

.mobile-toggle-slider.active:before {
    transform: translateX(24px);
}

.mobile-menu {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.mobile-nav {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-primary);
    box-shadow: 0 4px 6px var(--shadow-primary);
    z-index: 50;
    transition: background-color 0.3s ease;
}
.mobile-nav.active { display: block; }
.mobile-nav a {
    display: block;
    padding: 1rem 2rem;
    color: var(--text-secondary);
    text-decoration: none;
    font-weight: 500;
    border-bottom: 1px solid var(--bg-tertiary);
    transition: background 0.2s;
}
.mobile-nav a:hover {
    background: var(--bg-tertiary);
    color: var(--blue-primary);
}

.mobile-lessons-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1rem;
    cursor: pointer;
    color: var(--text-primary);
    font-weight: 600;
    align-items: center;
    gap: 0.5rem;
}
.mobile-lessons-toggle .arrow {
    transition: transform 0.2s;
    font-size: 0.8rem;
}
.mobile-lessons-toggle.active .arrow {
    transform: rotate(-180deg);
}

.mobile-lessons-dropdown {
    display: none;
    position: fixed;
    top: 70px;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-primary);
    box-shadow: 0 4px 6px var(--shadow-primary);
    z-index: 50;
    overflow-y: auto;
    padding-bottom: 2rem;
    transition: background-color 0.3s ease;
}
.mobile-lessons-dropdown.active { display: block; }

/* Mobile Module Items (Advanced Mode) */
.mobile-module-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    color: var(--text-primary);
    font-weight: 600;
    border-bottom: 1px solid var(--border-primary);
    background: var(--bg-tertiary);
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.mobile-module-item:hover {
    background: var(--bg-quaternary);
    color: var(--text-primary);
}
.mobile-module-item.expanded {
    background: var(--bg-quaternary);
    border-bottom: 2px solid var(--blue-primary);
}

.mobile-module-arrow {
    transition: transform 0.3s ease;
    font-size: 0.8rem;
    color: var(--text-tertiary);
}
.mobile-module-item.expanded .mobile-module-arrow {
    transform: rotate(90deg);
}

.mobile-module-lessons {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    background: var(--bg-secondary);
}
.mobile-module-lessons.expanded {
    max-height: 1500px;
    transition: max-height 0.3s ease-in;
}

/* Mobile Lesson Items (Both Modes) */
.mobile-lesson-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    color: var(--text-secondary);
    font-weight: 500;
    border-bottom: 1px solid var(--border-primary);
    font-size: 0.9rem;
    background: var(--bg-secondary);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    position: relative;
}
.mobile-module-lessons .mobile-lesson-item {
    padding-left: 3rem;
}
.mobile-lesson-item:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    text-decoration: none;
}
.mobile-lesson-item.current {
    background: var(--blue-primary);
    color: white;
}
.mobile-lesson-item .play-icon {
    width: 14px;
    height: 14px;
    background: var(--blue-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 8px;
    flex-shrink: 0;
}
.mobile-lesson-item.current .play-icon {
    background: white;
    color: var(--blue-primary);
}

/* Mobile Theme Toggle */
#mobile-theme-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    width: 100%;
    border-top: 1px solid var(--border-primary);
    margin-top: 0.5rem;
}
.mobile-toggle-switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
    margin: 0;
}
.mobile-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.mobile-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e2e8f0;
    transition: all 0.3s ease;
    border-radius: 14px;
    border: 1px solid var(--border-secondary);
    overflow: hidden;
}
.mobile-toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 2px;
    top: 2px;
    background-color: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Styles for mobile theme toggle slider */
.mobile-toggle-slider.active {
    background-color: var(--blue-primary) !important;
    border-color: var(--blue-primary) !important;
}

.mobile-toggle-slider.active:before {
    transform: translateX(24px);
}

.mobile-toggle-slider:hover {
    background-color: #cbd5e1;
}

/* Make mobile slider behave exactly like desktop toggle */
.dark-theme #mobile-theme-toggle .mobile-toggle-slider {
    background-color: var(--blue-primary);
    border-color: var(--blue-primary);
}

.dark-theme #mobile-theme-toggle .mobile-toggle-slider:hover {
    background-color: #0037b8;
}

.dark-theme #mobile-theme-toggle .mobile-toggle-slider:before {
    transform: translateX(24px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Make sure it works correctly when the toggle is actually off in dark mode */
.dark-theme #mobile-theme-toggle input:not(:checked) ~ .mobile-toggle-slider {
    background-color: #e2e8f0;
    border-color: var(--border-secondary);
}

.dark-theme #mobile-theme-toggle input:not(:checked) ~ .mobile-toggle-slider:before {
    transform: none;
}

/* ===================================
   RESPONSIVE BREAKPOINTS
   =================================== */
@media (max-width: 768px) {
    /* Layout */
    html, body { overflow-x: hidden; width: 100%; }
    .header-container {
        max-width: 100%;
        padding: 0 1rem;
        display: grid;
        grid-template-columns: 1fr auto auto;
        align-items: center;
        gap: 1rem;
    }
    .logo { justify-self: start; }
    .mobile-lessons-toggle { justify-self: center; order: 2; display: flex; }
    .mobile-menu { justify-self: end; order: 3; display: block; }

    .nav { display: none; }
    .theme-toggle { display: none; }
    .sidebar { display: none; }

    .main {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        grid-template-columns: 1fr !important;
        gap: 0 !important;
        box-sizing: border-box;
    }

    .content {
        width: 100% !important;
        max-width: 100% !important;
        padding: 1.5rem 1rem !important;
        margin: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        box-sizing: border-box;
    }

    /* Mobile Typography */
    h1 { font-size: 1.75rem; line-height: 1.3; }
    h2 { font-size: 1.5rem; }
    h3 { font-size: 1.25rem; }
    p { font-size: 0.95rem; }

    /* Components */
    .video-container { margin-bottom: 1rem; }

    .tab-buttons {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .tab-button {
        flex-shrink: 0;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }

    .up-next { margin-top: 2rem; }
    .next-video {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
    }
    .next-video-thumb {
        width: 100%;
        height: 180px;
    }

    /* Forms */
    #transcript-search-input { font-size: 16px; }

    /* Content Spacing */
    .episode-notes-content { padding: 0; }
    .learning-objectives { padding: 0.75rem; margin: 0.75rem 0; }
    #lesson-resources li { padding: 0.75rem; margin-bottom: 0.5rem; }

    /* Lesson header mobile */
    .lesson-header {
        flex-direction: column;
        gap: 1rem;
    }

    .lesson-completion-toggle {
        align-self: flex-start;
    }
}

@media (min-width: 769px) {
    #mobile-theme-toggle { display: none; }
}

@media (max-width: 375px) {
    .header-container { padding: 0 0.75rem; }
    .content { padding: 1.25rem 0.75rem !important; }
    h1 { font-size: 1.5rem; }
}

@media (min-width: 1400px) {
    .header-container { max-width: 85%; }
    .main { max-width: 85%; }
    .main.sidebar-right { grid-template-columns: 1fr 420px; }
    .main.sidebar-left { grid-template-columns: 420px 1fr; }
}

@media (min-width: 1600px) {
    .header-container { max-width: 80%; }
    .main { max-width: 80%; }
    .main.sidebar-right { grid-template-columns: 1fr 450px; }
    .main.sidebar-left { grid-template-columns: 450px 1fr; }
}

/* ===================================
   iOS SAFARI FIXES
   =================================== */
@supports (-webkit-touch-callout: none) {
    .mobile-lessons-dropdown { -webkit-overflow-scrolling: touch; }
    .main { -webkit-box-sizing: border-box; }
}

/* ===================================
   SIMPLE MODE OVERRIDES
   =================================== */
/* When ADVANCED_COURSE_MODE is false, these styles apply */
.episode-section.simple-mode {
    margin-bottom: 0.1rem;
    border: none;
}
.section-title {
    margin-top: 0.8rem;
    margin-bottom: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.section-title:empty { display: none; }

/* Simple mode mobile styles */
@media (max-width: 768px) {
    .mobile-lesson-item.simple-mode {
        display: block;
        padding: 1rem 2rem;
        color: #4b5563;
        text-decoration: none;
        font-weight: 500;
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s;
        font-size: 0.9rem;
    }
    .mobile-lesson-item.simple-mode:hover {
        background: #f9fafb;
        color: #1f2937;
    }
}

/* Mobile Progress Display */
@media (max-width: 768px) {
    .mobile-progress-display {
        display: block;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: var(--blue-primary);
        color: white;
        text-align: center;
        padding: 8px;
        font-weight: 600;
        font-size: 14px;
        z-index: 100;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    }

    .dark-theme .mobile-progress-display {
        background-color: var(--bg-primary);
        border-top: 1px solid var(--dark-border);
    }
}

    </style>

    <!-- Theme Script - Load immediately to prevent FOUC -->
    <script>
        (function() {
            try {
                const storedTheme = localStorage.getItem('darkMode');
                const isDark = storedTheme === 'true' || (storedTheme === null && '<?php echo DEFAULT_THEME_MODE; ?>' === 'dark');
                if (isDark) {
                    document.documentElement.classList.add('dark-theme');
                }
            } catch (error) {
                // localStorage not available, use default
                if ('<?php echo DEFAULT_THEME_MODE; ?>' === 'dark') {
                    document.documentElement.classList.add('dark-theme');
                }
            }
        })();
    </script>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>" class="logo">
                <img src="https://joe23.com/images/Joe-Lee-Lavery-Logo-black-modern-automation.png" alt="Joe Lee - Modern Automation" class="logo light">
                <img src="https://joe23.com/images/Joe-Lee-Lavery-Logo-white-modern-automation.png" alt="Joe Lee - Modern Automation" class="logo dark">
            </a>
            <nav class="nav">
                <a href="#overview">Course Overview</a>
                <a href="#modules">All Modules</a>
                <a href="#resources">Resources</a>
                <a href="#community">Community</a>
                <a href="#support">Support</a>
                <label class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
                    <input type="checkbox" id="theme-toggle-checkbox">
                    <span class="toggle-slider"></span>
                </label>
            </nav>
            <button class="mobile-lessons-toggle" id="mobile-lessons-toggle">
                Lessons <span class="arrow"><?php echo render_icon('chevron-down'); ?></span>
            </button>
            <button class="mobile-menu" id="mobile-menu-btn"><?php echo render_icon('menu'); ?></button>
        </div>
        <nav class="mobile-nav" id="mobile-nav">
            <a href="#overview">Course Overview</a>
            <a href="#modules">All Modules</a>
            <a href="#resources">Resources</a>
            <a href="#community">Community</a>
            <a href="#support">Support</a>
            <div id="mobile-theme-toggle">
                <span class="toggle-text">Dark Mode</span>
                <label class="mobile-toggle-switch">
                    <input type="checkbox" id="mobile-theme-toggle-checkbox">
                    <span class="mobile-toggle-slider"></span>
                </label>
            </div>
        </nav>
        <div class="mobile-lessons-dropdown" id="mobile-lessons-dropdown">
            <?php if (ADVANCED_COURSE_MODE): ?>
                <!-- ADVANCED MODE - Two-level mobile dropdown -->
                <?php
                if (!empty($modules)):
                    foreach ($modules as $module):
                        $module_lessons = isset($lessons_by_module[$module['id']]) ? $lessons_by_module[$module['id']] : [];
                        if (!empty($module_lessons)): ?>
                            <div class="mobile-module-item" data-module-id="<?php echo $module['id']; ?>">
                                <span><?php echo htmlspecialchars($module['title'] ?? 'Module ' . $module['id']); ?></span>
                                <span class="mobile-module-arrow"><?php echo render_icon('chevron-right'); ?></span>
                            </div>
                            <div class="mobile-module-lessons" data-module-id="<?php echo $module['id']; ?>">
                                <?php foreach ($module_lessons as $lesson): ?>
                                    <a href="/members/course-platform/lesson/<?php echo !empty($lesson['slug']) ? $lesson['slug'] : $lesson['id']; ?>"
                                       class="mobile-lesson-item <?php echo ($current_lesson && $current_lesson['id'] == $lesson['id']) ? 'current' : ''; ?>"
                                       data-lesson-id="<?php echo $lesson['id']; ?>">
                                        <div class="play-icon"><?php echo render_icon('play'); ?></div>
                                        <?php echo htmlspecialchars($lesson['title']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif;
                    endforeach;
                else: ?>
                    <div class="mobile-module-item" data-module-id="1">
                        <span>Getting Started</span>
                        <span class="mobile-module-arrow"><?php echo render_icon('chevron-right'); ?></span>
                    </div>
                    <div class="mobile-module-lessons" data-module-id="1">
                        <a href="/members/course-platform/lesson/1" class="mobile-lesson-item current" data-lesson-id="1">
                            <div class="play-icon"><?php echo render_icon('play'); ?></div>
                            Welcome & Setup
                        </a>
                        <a href="/members/course-platform/lesson/2" class="mobile-lesson-item" data-lesson-id="2">
                            <div class="play-icon"><?php echo render_icon('play'); ?></div>
                            Launch Fundamentals
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- SIMPLE MODE - Original flat mobile dropdown -->
                <?php
                if (!empty($modules)):
                    foreach ($modules as $module):
                        $module_lessons = isset($lessons_by_module[$module['id']]) ? $lessons_by_module[$module['id']] : [];
                        if (!empty($module_lessons)):
                            foreach ($module_lessons as $lesson): ?>
                                <a href="/members/course-platform/lesson/<?php echo !empty($lesson['slug']) ? $lesson['slug'] : $lesson['id']; ?>"
                                   class="mobile-lesson-item <?php echo ($current_lesson && $current_lesson['id'] == $lesson['id']) ? 'current' : ''; ?>"
                                   data-lesson-id="<?php echo $lesson['id']; ?>">
                                    <?php echo htmlspecialchars($lesson['title']); ?>
                                </a>
                            <?php endforeach;
                        endif;
                    endforeach;
                else: ?>
                    <a href="/members/course-platform/lesson/1" class="mobile-lesson-item current">Welcome & Setup</a>
                    <a href="/members/course-platform/lesson/2" class="mobile-lesson-item">Launch Fundamentals</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>

    <!-- V58: Apply dynamic sidebar position class -->
    <main class="main <?php echo $sidebar_position_class; ?>">
        <div class="content">
            <div class="video-container" id="video-container">
                        <?php if ($current_lesson && !empty($current_lesson['video_url'])): ?>
                            <!-- Smart video detection in PHP -->
                            <?php
                            $video_url = $current_lesson['video_url'];
                            $is_youtube = preg_match('/(?:youtube\.com|youtu\.be)/', $video_url);
                            $is_video = preg_match('/\.(mp4|webm|ogg|avi|mov|wmv|flv|mkv|m4v)/', $video_url);
                            $is_image = preg_match('/\.(jpg|jpeg|png|gif|bmp|svg|webp)/', $video_url);

                            if ($is_youtube) {
                                // Extract YouTube ID and convert to embed
                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches);
                                $embed_url = isset($matches[1]) ? 'https://www.youtube.com/embed/' . $matches[1] : $video_url;
                                echo '<iframe src="' . htmlspecialchars($embed_url) . '" title="' . htmlspecialchars($current_lesson['title']) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                            } elseif ($is_video) {
                                // Video file with smart thumbnail handling
                                $video_id = 'video-' . $current_lesson['id'];
                                $has_custom_thumbnail = !empty($current_lesson['thumbnail_url']);

                                // Force auto-generation if enabled, regardless of thumbnail_url
                                if (AUTO_GENERATE_THUMBNAILS) {
                                    echo '<video id="' . $video_id . '" controls preload="metadata"><source src="' . htmlspecialchars($video_url) . '">Your browser does not support video.</video>';
                                    echo '<script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        generateVideoThumbnail("' . $video_id . '", "' . htmlspecialchars($video_url) . '");
                                    });
                                    </script>';
                                } elseif (USE_VIDEO_THUMBNAILS && $has_custom_thumbnail) {
                                    // Only use manual thumbnail if auto-generation is disabled
                                    echo '<video id="' . $video_id . '" controls preload="metadata" poster="' . htmlspecialchars($current_lesson['thumbnail_url']) . '"><source src="' . htmlspecialchars($video_url) . '">Your browser does not support video.</video>';
                                } else {
                                    // Default browser behavior
                                    echo '<video id="' . $video_id . '" controls preload="metadata"><source src="' . htmlspecialchars($video_url) . '">Your browser does not support video.</video>';
                                }
                            } else {
                                // Treat as image
                                echo '<img src="' . htmlspecialchars($video_url) . '" alt="' . htmlspecialchars($current_lesson['title']) . '" />';
                            }
                            ?>
                        <?php else: ?>
                            <img src="<?php echo !empty($course[0]['image_url']) ? $course[0]['image_url'] : 'https://joe23.com/images/modern-automation.png'; ?>" alt="Course Preview" />
                        <?php endif; ?>
            </div>

            <div class="lesson-header">
                <div>
                    <h1 class="font-bold" style="margin-bottom: 1rem;" id="lesson-title">
                        <?php echo htmlspecialchars($current_lesson ? $current_lesson['title'] : 'Welcome to the Course'); ?>
                    </h1>
                    <p class="text-large" style="color: #6b7280; margin-bottom: 2rem; font-weight: 400;" id="lesson-description">
                        <?php
                        if ($current_lesson && !empty($current_lesson['description'])) {
                            echo htmlspecialchars($current_lesson['description']);
                        } else {
                            echo 'Select a lesson from the dropdown to get started with your automation journey.';
                        }
                        ?>
                    </p>
                </div>
                <div class="lesson-completion-toggle">
                    <label class="completion-checkbox">
                        <input type="checkbox" id="lesson-complete-checkbox" <?php echo $current_lesson ? 'data-lesson-id="' . $current_lesson['id'] . '"' : ''; ?>>
                        <span class="checkbox-slider"></span>
                    </label>
                    <span class="completion-label">Mark Complete</span>
                </div>
            </div>

            <div class="tabs">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="notes">Notes</button>
                    <button class="tab-button" data-tab="transcript">Transcript</button>
                </div>

                <div class="tab-content active" id="notes">
                    <div id="lesson-content">
                        <?php if ($current_lesson): ?>
                            <?php echo render_lesson_content($current_lesson); ?>
                        <?php else: ?>
                            <p>Welcome to the Modern Automation Workshop! Select a lesson from the dropdown to get started.</p>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e5e5;">
                        <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; color: #1f2937;">Resources & Tools Mentioned</h4>
                        <ul style="list-style: none; padding: 0;" id="lesson-resources">
                            <?php if (!empty($current_lesson_resources)): ?>
                                <?php foreach ($current_lesson_resources as $resource): ?>
                                    <li style="margin-bottom: 0.75rem; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 6px; border-left: 3px solid #0046dd;">
                                        <a href="<?php echo htmlspecialchars($resource['url']); ?>" style="text-decoration: none; color: #0046dd; font-weight: 500;" target="_blank">
                                            <?php echo htmlspecialchars($resource['title']); ?>
                                        </a>
                                        <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($resource['description']); ?>
                                        </p>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li style="margin-bottom: 0.75rem; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 6px; border-left: 3px solid #0046dd;">
                                    <a href="https://n8n.io" style="text-decoration: none; color: #0046dd; font-weight: 500;" target="_blank">n8n Official Website</a>
                                    <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.9rem;">Download and documentation for the automation platform</p>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="tab-content" id="transcript">
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                            <input type="text" id="transcript-search-input" placeholder="Search transcript..." style="flex: 1; padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; background: white; color: #4b5563;">
                            <button id="clear-search" style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; color: #4b5563; border-radius: 6px; font-size: 0.9rem; cursor: pointer;">Clear</button>
                        </div>
                    </div>

                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e5e5; border-radius: 8px; padding: 1rem;">
                        <ul class="transcript-list" id="transcript-content">
                            <?php
                            if ($current_lesson && !empty($current_lesson['transcript_text'])) {
                                echo format_transcript($current_lesson['transcript_text']);
                            } else {
                                echo format_transcript('');
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="up-next">
                <h2>Up next</h2>
                <div class="next-video" id="next-lesson">
                    <div class="next-video-thumb"><?php echo render_icon('play-next'); ?></div>
                    <div class="next-video-content">
                        <h3>Launch Fundamentals <span style="color: #6b7280; font-weight: 400; font-size: 0.9rem;">&bull; 10 min</span></h3>
                        <p>Before the tech, let's get strategic. Learn the high-leverage philosophy behind building automation systems that actually move the needle for your business.</p>
                    </div>
                </div>
            </div>
        </div>

        <aside class="sidebar">
            <h3>Course Modules</h3>

            <?php if (ADVANCED_COURSE_MODE): ?>
                <!-- ADVANCED MODE - Accordion-style sidebar -->
                <?php
                $lesson_count = 0;
                $total_lessons = safe_count($all_lessons);

                if (!empty($modules)):
                    foreach ($modules as $module):
                        $module_lessons = isset($lessons_by_module[$module['id']]) ? $lessons_by_module[$module['id']] : [];
                        $is_current_module = false;

                        // Check if current lesson is in this module
                        if ($current_lesson) {
                            foreach ($module_lessons as $lesson) {
                                if ($lesson['id'] == $current_lesson['id']) {
                                    $is_current_module = true;
                                    break;
                                }
                            }
                        }
                        // Default to expand the first module if no current lesson
                        if (!$current_lesson && $module['id'] == 1) {
                            $is_current_module = true;
                        }
                ?>
                    <div class="episode-section">
                        <div class="module-header <?php echo $is_current_module ? 'active expanded' : ''; ?>" data-module-id="<?php echo $module['id']; ?>">
                            <div class="module-title">
                                <div class="module-icon"><?php echo $module['id']; ?></div>
                                <span><?php echo htmlspecialchars($module['title'] ?? 'Module ' . $module['id']); ?></span>
                            </div>
                            <span class="module-arrow"><?php echo render_icon('chevron-right'); ?></span>
                        </div>

                        <div class="module-lessons <?php echo $is_current_module ? 'expanded' : ''; ?>" data-module-id="<?php echo $module['id']; ?>">
                            <?php if (!empty($module_lessons)): ?>
                                <ul class="episode-list">
                                    <?php foreach ($module_lessons as $lesson): ?>
                                        <li class="episode-item">
                                            <a href="/members/course-platform/lesson/<?php echo !empty($lesson['slug']) ? $lesson['slug'] : $lesson['id']; ?>"
                                               class="episode-link <?php echo ($current_lesson && $current_lesson['id'] == $lesson['id']) ? 'current' : 'available'; ?>"
                                               data-lesson-id="<?php echo $lesson['id']; ?>"
                                               data-lesson-slug="<?php echo !empty($lesson['slug']) ? $lesson['slug'] : ''; ?>">
                                                <div class="play-icon"><?php echo render_icon('play'); ?></div>
                                                <span class="lesson-title">
                                                    <?php echo htmlspecialchars($lesson['title']); ?>
                                                    <span class="lesson-duration"><?php echo $lesson['duration_minutes'] ?? '10'; ?> min</span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php $lesson_count++; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div style="padding: 1rem; color: #9ca3af; font-size: 0.9rem; text-align: center;">
                                    <?php echo htmlspecialchars($module['title'] ?? 'Coming Soon'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <div class="episode-section">
                        <div class="module-header active" data-module-id="1">
                            <div class="module-title">
                                <div class="module-icon">1</div>
                                <span>Getting Started</span>
                            </div>
                            <span class="module-arrow"><?php echo render_icon('chevron-right'); ?></span>
                        </div>

                        <div class="module-lessons expanded" data-module-id="1">
                            <ul class="episode-list">
                                <li class="episode-item">
                                    <a href="/members/course-platform/lesson/1" class="episode-link current" data-lesson-id="1">
                                        <div class="play-icon"><?php echo render_icon('play'); ?></div>
                                        <span class="lesson-title">
                                            Welcome & Setup
                                            <span class="lesson-duration">7 min</span>
                                        </span>
                                    </a>
                                </li>
                                <li class="episode-item">
                                    <a href="/members/course-platform/lesson/2" class="episode-link available" data-lesson-id="2">
                                        <div class="play-icon"><?php echo render_icon('play'); ?></div>
                                        <span class="lesson-title">
                                            Launch Fundamentals
                                            <span class="lesson-duration">10 min</span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- SIMPLE MODE - Original flat structure -->
                <?php
                $lesson_count = 0;
                $total_lessons = safe_count($all_lessons);

                if (!empty($modules)):
                    foreach ($modules as $module):
                        $module_lessons = isset($lessons_by_module[$module['id']]) ? $lessons_by_module[$module['id']] : [];
                ?>
                    <div class="episode-section">
                        <div class="section-title"><?php echo strtoupper(htmlspecialchars($module['section_title'] ?? 'MODULE')); ?></div>
                        <ul class="episode-list">
                            <?php if (!empty($module_lessons)): ?>
                                <?php foreach ($module_lessons as $lesson): ?>
                                    <li class="episode-item">
                                        <a href="/members/course-platform/lesson/<?php echo !empty($lesson['slug']) ? $lesson['slug'] : $lesson['id']; ?>"
                                           class="episode-link <?php echo ($current_lesson && $current_lesson['id'] == $lesson['id']) ? 'current' : 'available'; ?>"
                                           data-lesson-id="<?php echo $lesson['id']; ?>"
                                           data-lesson-slug="<?php echo !empty($lesson['slug']) ? $lesson['slug'] : ''; ?>">
                                            <div class="play-icon"><?php echo render_icon('play'); ?></div>
                                            <span class="lesson-title">
                                                <?php echo htmlspecialchars($lesson['title']); ?>
                                                <span class="lesson-duration"><?php echo $lesson['duration_minutes'] ?? '10'; ?> min</span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php $lesson_count++; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="episode-item">
                                    <span style="color: #9ca3af; font-size: 0.9rem; padding: 0.75rem;">
                                        <?php echo htmlspecialchars($module['title'] ?? 'Coming Soon'); ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <div class="episode-section">
                        <div class="section-title">GETTING STARTED</div>
                        <ul class="episode-list">
                            <li class="episode-item">
                                <a href="/members/course-platform/lesson/1" class="episode-link current" data-lesson-id="1">
                                    <div class="play-icon"><?php echo render_icon('play'); ?></div>
                                    <span class="lesson-title">
                                        Welcome & Setup
                                        <span class="lesson-duration">7 min</span>
                                    </span>
                                </a>
                            </li>
                            <li class="episode-item">
                                <a href="/members/course-platform/lesson/2" class="episode-link available" data-lesson-id="2">
                                    <div class="play-icon"><?php echo render_icon('play'); ?></div>
                                    <span class="lesson-title">
                                        Launch Fundamentals
                                        <span class="lesson-duration">10 min</span>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="progress-stats">
                <div class="progress-header">
                    <h4><?php echo render_icon('chart', 'mr-2'); ?> Course Progress</h4>
                    <span class="progress-percentage" id="progress-percentage">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
                </div>
                <p class="progress-text" id="progress-text">
                    <span id="completed-count">0</span> of <?php echo $total_lessons > 0 ? $total_lessons : 8; ?> lessons complete
                </p>
            </div>
        </aside>
    </main>

    <!-- Mobile Progress Display -->
    <div id="mobile-progress-display" class="mobile-progress-display"></div>

<script>
        const courseData = {
            lessons: <?php echo json_encode($all_lessons, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            modules: <?php echo json_encode($modules, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            course: <?php echo json_encode(!empty($course[0]) ? $course[0] : [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            currentLesson: <?php echo json_encode($current_lesson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
        };

        const SUPABASE_URL = '<?php echo addslashes(SUPABASE_URL); ?>';
        const SUPABASE_KEY = '<?php echo addslashes(SUPABASE_KEY); ?>';
        const BASE_URL = '<?php echo $_SERVER["SCRIPT_NAME"]; ?>';
        const ADVANCED_MODE = <?php echo ADVANCED_COURSE_MODE ? 'true' : 'false'; ?>;
        const USE_THUMBNAILS = <?php echo USE_VIDEO_THUMBNAILS ? 'true' : 'false'; ?>;
        const AUTO_THUMBNAILS = <?php echo AUTO_GENERATE_THUMBNAILS ? 'true' : 'false'; ?>;
        const SIDEBAR_POSITION = '<?php echo SIDEBAR_POSITION; ?>'; // NEW V58

        let currentLessonId = courseData.currentLesson ? courseData.currentLesson.id : null;

        // Shared Functions (Both Modes)
        function detectUrlType(url) {
            if (!url || url.trim() === '') return { type: 'none', url: null };
            url = url.trim();
            const youtubeRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
            const youtubeMatch = url.match(youtubeRegex);
            if (youtubeMatch) {
                const videoId = youtubeMatch[1];
                return { type: 'youtube', url: `https://www.youtube.com/embed/${videoId}` };
            }
            const videoExtensions = ['.mp4', '.webm', '.ogg', '.avi', '.mov', '.wmv', '.flv', '.mkv', '.m4v'];
            if (videoExtensions.some(ext => url.toLowerCase().includes(ext))) {
                return { type: 'video', url: url };
            }
            const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.svg', '.webp'];
            if (imageExtensions.some(ext => url.toLowerCase().includes(ext))) {
                return { type: 'image', url: url };
            }
            return { type: 'image', url: url };
        }

        // Auto-generate video thumbnail from first frame
        function generateVideoThumbnail(videoId, videoUrl) {
            const video = document.getElementById(videoId);
            if (!video || !AUTO_THUMBNAILS) return;

            // Create hidden canvas for thumbnail extraction
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Wait for video metadata to load
            video.addEventListener('loadedmetadata', function() {
                // Set canvas size to match video
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                // Seek to 1 second (or 10% of video, whichever is smaller)
                const seekTime = Math.min(1, video.duration * 0.1);
                video.currentTime = seekTime;
            });

            // When seek is complete, capture frame
            video.addEventListener('seeked', function() {
                // Draw current frame to canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convert to data URL
                const thumbnailDataUrl = canvas.toDataURL('image/jpeg', 0.8);

                // Set as poster
                video.poster = thumbnailDataUrl;

                // Reset video to beginning
                video.currentTime = 0;

                // Clean up
                canvas.remove();
            }, { once: true });

            // Handle errors gracefully
            video.addEventListener('error', function() {
                console.log('Video thumbnail generation failed for:', videoUrl);
            });
        }

        function renderVideoContainer(urlData, title = 'Video', thumbnailUrl = null, lessonId = null) {
            if (urlData.type === 'none') {
                const defaultImage = (courseData.course?.image_url) || 'https://joe23.com/images/modern-automation.png';
                return `<img src="${defaultImage}" alt="Course Preview" />`;
            }
            if (urlData.type === 'youtube') {
                return `<iframe src="${urlData.url}" title="${title}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
            }
            if (urlData.type === 'video') {
                const videoId = `video-${lessonId || 'dynamic'}`;

                // Force auto-generation if enabled, regardless of thumbnailUrl
                if (AUTO_THUMBNAILS) {
                    const videoHtml = `<video id="${videoId}" controls preload="metadata"><source src="${urlData.url}">Your browser does not support video.</video>`;

                    // Generate thumbnail after video element is created
                    setTimeout(() => generateVideoThumbnail(videoId, urlData.url), 100);

                    return videoHtml;
                } else if (USE_THUMBNAILS && thumbnailUrl) {
                    // Only use manual thumbnail if auto-generation is disabled
                    return `<video id="${videoId}" controls preload="metadata" poster="${thumbnailUrl}"><source src="${urlData.url}">Your browser does not support video.</video>`;
                } else {
                    // Default browser behavior
                    return `<video id="${videoId}" controls preload="metadata"><source src="${urlData.url}">Your browser does not support video.</video>`;
                }
            }
            if (urlData.type === 'image') {
                return `<img src="${urlData.url}" alt="${title}" />`;
            }
            return `<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #6b7280;">Content not available</div>`;
        }

        async function supabaseRequest(endpoint) {
            try {
                const response = await fetch(SUPABASE_URL + '/rest/v1/' + endpoint, {
                    headers: {
                        'apikey': SUPABASE_KEY,
                        'Authorization': 'Bearer ' + SUPABASE_KEY,
                        'Content-Type': 'application/json'
                    }
                });
                return await response.json();
            } catch (error) {
                console.error('Supabase request failed:', error);
                return null;
            }
        }

        function convertMarkdownToHTML(markdown) {
            if (!markdown) return '<p>No content available.</p>';
            let html = markdown;
            html = html.replace(/^### (.*$)/gm, '<h3>$1</h3>');
            html = html.replace(/^## (.*$)/gm, '<h2>$1</h2>');
            html = html.replace(/^# (.*$)/gm, '<h1>$1</h1>');
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
            html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
            html = html.replace(/^(\d+)\.\s+(.+$)/gm, '<oli>$2</oli>');
            html = html.replace(/^-\s+(.+$)/gm, '<uli>$1</uli>');
            html = html.replace(/(<oli>.*?<\/oli>\s*)+/gs, function(match) {
                return '<ol>' + match.replace(/<oli>/g, '<li>').replace(/<\/oli>/g, '</li>') + '</ol>';
            });
            html = html.replace(/(<uli>.*?<\/uli>\s*)+/gs, function(match) {
                return '<ul>' + match.replace(/<uli>/g, '<li>').replace(/<\/uli>/g, '</li>') + '</ul>';
            });
            html = html.replace(/\n\s*\n/g, '</p><p>');
            html = '<p>' + html + '</p>';
            html = html.replace(/<p>\s*<\/p>/g, '');
            html = html.replace(/<p>(<h[1-6]>.*?<\/h[1-6]>)<\/p>/g, '$1');
            html = html.replace(/<p>(<ol>.*?<\/ol>)<\/p>/gs, '$1');
            html = html.replace(/<p>(<ul>.*?<\/ul>)<\/p>/gs, '$1');
            return html;
        }

        function getNextLesson(currentLessonId) {
            if (!courseData.lessons || courseData.lessons.length === 0) return null;
            const sortedLessons = [...courseData.lessons].sort((a, b) => {
                if (a.module_id !== b.module_id) return a.module_id - b.module_id;
                return (a.order_index || 0) - (b.order_index || 0);
            });
            const currentIndex = sortedLessons.findIndex(lesson => lesson.id == currentLessonId);
            if (currentIndex === -1 || currentIndex === sortedLessons.length - 1) return null;
            return sortedLessons[currentIndex + 1];
        }

        function updateUpNext(currentLessonId) {
            const upNextElement = document.getElementById('next-lesson');
            if (!upNextElement) return;
            const nextLesson = getNextLesson(currentLessonId);
            if (!nextLesson) {
                upNextElement.innerHTML = `
                    <div class="next-video-thumb">🎉</div>
                    <div class="next-video-content">
                        <h3>Course Complete! <span style="color: #6b7280; font-weight: 400; font-size: 0.9rem;">• Well done!</span></h3>
                        <p>Congratulations! You've completed all available lessons. Keep building amazing automations!</p>
                    </div>
                `;
                upNextElement.style.cursor = 'default';
                upNextElement.onclick = null;
                return;
            }
            const duration = nextLesson.duration_minutes || 10;
            upNextElement.innerHTML = `
                <div class="next-video-thumb">▶</div>
                <div class="next-video-content">
                    <h3>${nextLesson.title} <span style="color: #6b7280; font-weight: 400; font-size: 0.9rem;">• ${duration} min</span></h3>
                    <p>${nextLesson.description || 'Continue your automation journey with the next lesson.'}</p>
                </div>
            `;
            upNextElement.style.cursor = 'pointer';
            upNextElement.onclick = function() {
                navigateToLesson(nextLesson.id, nextLesson.slug || nextLesson.id);
            };
        }

        function updateMetaTags(lessonData) {
            if (!lessonData) return;
            const title = `${lessonData.title} - Modern Automation Workshop | Joe Lee`;
            const description = lessonData.description || 'Learn to build powerful automation workflows with n8n.';
            const url = lessonData.slug ? `${BASE_URL}/lesson/${lessonData.slug}` : `${BASE_URL}/lesson/${lessonData.id}`;
            const image = lessonData.thumbnail_url || courseData.course?.image_url || 'https://joe23.com/images/modern-automation.png';
            document.title = title;
            updateMetaTag('name', 'title', title);
            updateMetaTag('name', 'description', description);
            updateMetaTag('property', 'og:title', title);
            updateMetaTag('property', 'og:description', description);
            updateMetaTag('property', 'og:url', url);
            updateMetaTag('property', 'og:image', image);
            updateMetaTag('property', 'twitter:title', title);
            updateMetaTag('property', 'twitter:description', description);
            updateMetaTag('property', 'twitter:url', url);
            updateMetaTag('property', 'twitter:image', image);
            let canonical = document.querySelector('link[rel="canonical"]');
            if (canonical) {
                canonical.href = url;
            }
        }

        function updateMetaTag(attribute, name, content) {
            let meta = document.querySelector(`meta[${attribute}="${name}"]`);
            if (meta) {
                meta.content = content;
            }
        }

        // Mode-Specific Functions
        <?php if (ADVANCED_COURSE_MODE): ?>
        // ADVANCED MODE - Compact JS
        function initModuleAccordion() {
            const moduleHeaders = document.querySelectorAll('.module-header');

            moduleHeaders.forEach(function(header) {
                header.addEventListener('click', function() {
                    const moduleId = this.getAttribute('data-module-id');
                    const moduleLessons = document.querySelector('.module-lessons[data-module-id="' + moduleId + '"]');
                    const wasActive = this.classList.contains('active');

                    // First, close all modules
                    moduleHeaders.forEach(h => {
                        h.classList.remove('active');
                        h.classList.remove('expanded');
                    });
                    document.querySelectorAll('.module-lessons').forEach(ml => ml.classList.remove('expanded'));

                    // If the clicked module was not already active, open it.
                    if (!wasActive) {
                        this.classList.add('active');
                        this.classList.add('expanded');
                        if (moduleLessons) {
                            moduleLessons.classList.add('expanded');
                        }
                    }
                    // If it was active, it is now closed because of the loop above.
                });
            });
        }

        function initMobileModuleAccordion() {
            const mobileModuleItems = document.querySelectorAll('.mobile-module-item');
            mobileModuleItems.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const moduleId = this.getAttribute('data-module-id');
                    const moduleLessons = document.querySelector('.mobile-module-lessons[data-module-id="' + moduleId + '"]');
                    const isExpanded = this.classList.contains('expanded');
                    // Close all modules first
                    mobileModuleItems.forEach(function(mi) { mi.classList.remove('expanded'); });
                    document.querySelectorAll('.mobile-module-lessons').forEach(function(ml) { ml.classList.remove('expanded'); });
                    // Open clicked module if it wasn't already open
                    if (!isExpanded) {
                        this.classList.add('expanded');
                        if (moduleLessons) { moduleLessons.classList.add('expanded'); }
                    }
                });
            });
        }

        function initMobileLessonsDropdown() {
            const toggle = document.getElementById('mobile-lessons-toggle');
            const dropdown = document.getElementById('mobile-lessons-dropdown');
            if (toggle && dropdown) {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggle.classList.toggle('active');
                    dropdown.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                        toggle.classList.remove('active');
                        dropdown.classList.remove('active');
                        // Reset all module expansions
                        const mobileModuleItems = document.querySelectorAll('.mobile-module-item');
                        const mobileModuleLessons = document.querySelectorAll('.mobile-module-lessons');
                        mobileModuleItems.forEach(function(item) { item.classList.remove('expanded'); });
                        mobileModuleLessons.forEach(function(lessons) { lessons.classList.remove('expanded'); });
                    }
                });
                const mobileLinks = dropdown.querySelectorAll('.mobile-lesson-item');
                mobileLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const lessonId = this.getAttribute('data-lesson-id');
                        if (lessonId) {
                            const href = this.getAttribute('href');
                            const identifier = href.split('/').pop();
                            navigateToLesson(lessonId, identifier);
                            toggle.classList.remove('active');
                            dropdown.classList.remove('active');
                        }
                    });
                });
            }
        }

        function updateMobileDropdown(lessonId) {
            const mobileLinks = document.querySelectorAll('.mobile-lesson-item');
            mobileLinks.forEach(function(link) {
                link.classList.remove('current');
                if (link.getAttribute('data-lesson-id') == lessonId) {
                    link.classList.add('current');
                }
            });
        }
        <?php else: ?>
        // SIMPLE MODE - Original behavior
        function initMobileLessonsDropdown() {
            const toggle = document.getElementById('mobile-lessons-toggle');
            const dropdown = document.getElementById('mobile-lessons-dropdown');
            if (toggle && dropdown) {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggle.classList.toggle('active');
                    dropdown.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                        toggle.classList.remove('active');
                        dropdown.classList.remove('active');
                    }
                });
                const mobileLinks = dropdown.querySelectorAll('.mobile-lesson-item');
                mobileLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const lessonId = this.getAttribute('data-lesson-id');
                        if (lessonId) {
                            const href = this.getAttribute('href');
                            const identifier = href.split('/').pop();
                            navigateToLesson(lessonId, identifier);
                            toggle.classList.remove('active');
                            dropdown.classList.remove('active');
                        }
                    });
                });
            }
        }

        function updateMobileDropdown(lessonId) {
            const mobileLinks = document.querySelectorAll('.mobile-lesson-item');
            mobileLinks.forEach(function(link) {
                link.classList.remove('current');
                if (link.getAttribute('data-lesson-id') == lessonId) {
                    link.classList.add('current');
                }
            });
        }
        <?php endif; ?>

        function navigateToLesson(lessonId, identifier) {
            const url = `${BASE_URL}/${identifier}`;
            history.pushState({ lessonId: lessonId, identifier: identifier }, '', url);
            const episodeLinks = document.querySelectorAll('.episode-link');
            episodeLinks.forEach(function(link) {
                link.classList.remove('current');
                if (link.getAttribute('data-lesson-id') == lessonId) {
                    link.classList.add('current');
                }
            });
            updateMobileDropdown(lessonId);
            loadLesson(lessonId);
        }

        async function loadLesson(lessonId) {
            currentLessonId = lessonId;
            try {
                const lesson = await supabaseRequest('lessons?id=eq.' + lessonId + '&select=*');
                if (lesson && lesson.length > 0) {
                    const lessonData = lesson[0];
                    updateMetaTags(lessonData);
                    const titleEl = document.getElementById('lesson-title');
                    if (titleEl) titleEl.textContent = lessonData.title || 'Lesson';
                    const descEl = document.getElementById('lesson-description');
                    if (descEl) descEl.textContent = lessonData.description || 'No description available';
                    const contentEl = document.getElementById('lesson-content');
                    if (contentEl) {
                        let content = '';
                        if (lessonData.episode_notes_markdown) {
                            content = '<div class="episode-notes-content">' + convertMarkdownToHTML(lessonData.episode_notes_markdown) + '</div>';
                        } else if (lessonData.content_html) {
                            content = '<div class="episode-notes-content">' + lessonData.content_html + '</div>';
                        } else {
                            content = '<div class="episode-notes-content"><p>No content available.</p></div>';
                        }
                        if (lessonData.learning_objectives) {
                            let objectives = [];
                            if (typeof lessonData.learning_objectives === 'string') {
                                const objStr = lessonData.learning_objectives.replace(/[{}]/g, '');
                                objectives = objStr.split(',').map(function(obj) {
                                    return obj.trim().replace(/"/g, '');
                                });
                            } else if (Array.isArray(lessonData.learning_objectives)) {
                                objectives = lessonData.learning_objectives;
                            }
                            if (objectives.length > 0 && objectives[0].trim() !== '') {
                                content += '<div class="learning-objectives">';
                                content += '<h4><span class="target-icon">🎯</span> Learning Objectives</h4>';
                                content += '<ul>';
                                objectives.forEach(function(objective) {
                                    content += '<li>' + objective.trim() + '</li>';
                                });
                                content += '</ul></div>';
                            }
                        }
                        contentEl.innerHTML = content;
                    }
                    const videoContainer = document.getElementById('video-container');
                    if (videoContainer) {
                        const urlData = detectUrlType(lessonData.video_url);
                        const videoContent = renderVideoContainer(urlData, lessonData.title || 'Video', lessonData.thumbnail_url, lessonData.id);
                        videoContainer.innerHTML = videoContent;
                    }
                    const transcriptEl = document.getElementById('transcript-content');
                    if (transcriptEl && lessonData.transcript_text) {
                        const lines = lessonData.transcript_text.split('\n');
                        let transcriptHTML = '';
                        let time = 0;
                        lines.forEach(function(line) {
                            if (line.trim()) {
                                const minutes = Math.floor(time / 60);
                                const seconds = time % 60;
                                const timeStr = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                                transcriptHTML += '<li class="transcript-item" data-original-text="' + line.trim().toLowerCase() + '">';
                                transcriptHTML += '<span class="transcript-timestamp">' + timeStr + '</span>';
                                transcriptHTML += '<span class="transcript-text">' + line.trim() + '</span>';
                                transcriptHTML += '</li>';
                                time += 15;
                            }
                        });
                        transcriptEl.innerHTML = transcriptHTML;
                        initTranscriptSearch();
                    }
                    updateUpNext(lessonId);

                    // Advanced mode: Update module accordion state
                    if (ADVANCED_MODE) {
                        const lesson = courseData.lessons.find(l => l.id == lessonId);
                        if (lesson && lesson.module_id) {
                            const moduleHeaders = document.querySelectorAll('.module-header');
                            const moduleLessons = document.querySelectorAll('.module-lessons');
                            moduleHeaders.forEach(function(header) { header.classList.remove('active'); });
                            moduleLessons.forEach(function(lessons) { lessons.classList.remove('expanded'); });
                            const currentModuleHeader = document.querySelector('.module-header[data-module-id="' + lesson.module_id + '"]');
                            const currentModuleLessons = document.querySelector('.module-lessons[data-module-id="' + lesson.module_id + '"]');
                            if (currentModuleHeader) { currentModuleHeader.classList.add('active'); }
                            if (currentModuleLessons) { currentModuleLessons.classList.add('expanded'); }
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading lesson:', error);
            }
        }

        function initTabs() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            tabButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    tabButtons.forEach(function(b) { b.classList.remove('active'); });
                    tabContents.forEach(function(c) { c.classList.remove('active'); });
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    const targetTab = document.getElementById(tabId);
                    if (targetTab) targetTab.classList.add('active');
                });
            });
        }

        function initSidebar() {
            const episodeLinks = document.querySelectorAll('.episode-link');
            episodeLinks.forEach(function(link) {
                const lessonId = link.getAttribute('data-lesson-id');
                const lessonSlug = link.getAttribute('data-lesson-slug');
                if (lessonId) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const identifier = lessonSlug || lessonId;
                        navigateToLesson(lessonId, identifier);
                    });
                }
            });
        }

        function initMobile() {
            const mobileButton = document.getElementById('mobile-menu-btn');
            const mobileNav = document.getElementById('mobile-nav');
            if (mobileButton && mobileNav) {
                mobileButton.addEventListener('click', function() {
                    mobileNav.classList.toggle('active');
                });
            }
        }

        function initUpNext() {
            if (currentLessonId) {
                updateUpNext(currentLessonId);
            }
        }

        // Handle browser back/forward
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.lessonId) {
                loadLesson(event.state.lessonId);
                const episodeLinks = document.querySelectorAll('.episode-link');
                episodeLinks.forEach(function(link) {
                    link.classList.remove('current');
                    if (link.getAttribute('data-lesson-id') == event.state.lessonId) {
                        link.classList.add('current');
                    }
                });
                updateMobileDropdown(event.state.lessonId);
            }
        });

        function initTranscriptSearch() {
            const searchInput = document.getElementById('transcript-search-input');
            const clearButton = document.getElementById('clear-search');
            const transcriptItems = document.querySelectorAll('.transcript-item');

            if (!searchInput || !clearButton) return;

            function performSearch(searchTerm) {
                searchTerm = searchTerm.toLowerCase().trim();

                transcriptItems.forEach(function(item) {
                    const originalText = item.getAttribute('data-original-text');
                    const textSpan = item.querySelector('.transcript-text');

                    if (!searchTerm) {
                        // Show all items and remove highlighting
                        item.classList.remove('hidden');
                        if (textSpan) {
                            textSpan.innerHTML = textSpan.textContent;
                        }
                    } else if (originalText && originalText.includes(searchTerm)) {
                        // Show item and highlight matching text
                        item.classList.remove('hidden');
                        if (textSpan) {
                            const regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                            const highlightedText = textSpan.textContent.replace(regex, '<span class="highlight">$1</span>');
                            textSpan.innerHTML = highlightedText;
                        }
                    } else {
                        // Hide item
                        item.classList.add('hidden');
                    }
                });
            }

            searchInput.addEventListener('input', function(e) {
                performSearch(e.target.value);
            });

            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                performSearch('');
            });
        }

        function initThemeToggle() {
            const themeToggleCheckbox = document.getElementById('theme-toggle-checkbox');
            const mobileThemeToggleCheckbox = document.getElementById('mobile-theme-toggle-checkbox');
            const mobileToggleSlider = document.querySelector('#mobile-theme-toggle .mobile-toggle-slider');
            const html = document.documentElement;

            // Check for stored theme preference, default to light mode
            const storedTheme = localStorage.getItem('darkMode');
            const isDarkMode = storedTheme === 'true' || (storedTheme === null && '<?php echo DEFAULT_THEME_MODE; ?>' === 'dark');

            // Apply initial theme based on localStorage
            if (isDarkMode) {
                html.classList.add('dark-theme');
                if (themeToggleCheckbox) themeToggleCheckbox.checked = true;
                if (mobileThemeToggleCheckbox) mobileThemeToggleCheckbox.checked = true;
                if (mobileToggleSlider) mobileToggleSlider.classList.add('active');

                const toggleText = document.querySelector('#mobile-theme-toggle .toggle-text');
                if (toggleText) toggleText.textContent = 'Light Mode';
            } else {
                html.classList.remove('dark-theme');
                if (themeToggleCheckbox) themeToggleCheckbox.checked = false;
                if (mobileThemeToggleCheckbox) mobileThemeToggleCheckbox.checked = false;
                if (mobileToggleSlider) mobileToggleSlider.classList.remove('active');

                const toggleText = document.querySelector('#mobile-theme-toggle .toggle-text');
                if (toggleText) toggleText.textContent = 'Dark Mode';
            }

            function toggleDarkMode() {
                const isDark = html.classList.toggle('dark-theme');
                localStorage.setItem('darkMode', isDark.toString());

                // Sync the checkbox states
                if (themeToggleCheckbox) themeToggleCheckbox.checked = isDark;
                if (mobileThemeToggleCheckbox) mobileThemeToggleCheckbox.checked = isDark;

                // Update the active state on mobile toggle slider
                if (mobileToggleSlider) {
                    if (isDark) {
                        mobileToggleSlider.classList.add('active');
                    } else {
                        mobileToggleSlider.classList.remove('active');
                    }
                }

                // Update toggle text in mobile menu
                const toggleText = document.querySelector('#mobile-theme-toggle .toggle-text');
                if (toggleText) {
                    toggleText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
                }

                console.log('Theme toggled:', isDark ? 'dark' : 'light');
            }

            // Desktop toggle
            if (themeToggleCheckbox) {
                themeToggleCheckbox.addEventListener('change', function() {
                    toggleDarkMode();
                });
            } else {
                console.error('Desktop theme toggle checkbox not found');
            }

            // Mobile toggle
            if (mobileThemeToggleCheckbox) {
                mobileThemeToggleCheckbox.addEventListener('change', function() {
                    toggleDarkMode();

                    // Close mobile menu after toggle
                    const mobileNav = document.getElementById('mobile-nav');
                    if (mobileNav) {
                        mobileNav.classList.remove('active');
                    }
                });
            } else {
                console.error('Mobile theme toggle checkbox not found');
            }

            console.log('Theme toggle initialized, current mode:', isDarkMode ? 'dark' : 'light');
        }

        function initModuleAccordion() {
            const moduleHeaders = document.querySelectorAll('.module-header');
            moduleHeaders.forEach(function(header) {
                header.addEventListener('click', function() {
                    const moduleId = this.getAttribute('data-module-id');
                    const moduleLessons = document.querySelector('.module-lessons[data-module-id="' + moduleId + '"]');
                    const wasActive = this.classList.contains('active');

                    // Close all other modules
                    moduleHeaders.forEach(h => {
                        h.classList.remove('active');
                        h.classList.remove('expanded');
                    });
                    document.querySelectorAll('.module-lessons').forEach(ml => ml.classList.remove('expanded'));

                    // Open this module if it wasn't active
                    if (!wasActive) {
                        this.classList.add('active');
                        this.classList.add('expanded');
                        if (moduleLessons) {
                            moduleLessons.classList.add('expanded');
                        }
                    }
                    // If it was active, it is now closed because of the loop above.
                });
            });
        }

        function initMobileModuleAccordion() {
            const mobileModuleItems = document.querySelectorAll('.mobile-module-item');
            mobileModuleItems.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const moduleId = this.getAttribute('data-module-id');
                    const moduleLessons = document.querySelector('.mobile-module-lessons[data-module-id="' + moduleId + '"]');
                    const wasExpanded = this.classList.contains('expanded');

                    // Close all other modules
                    mobileModuleItems.forEach(mi => mi.classList.remove('expanded'));
                    document.querySelectorAll('.mobile-module-lessons').forEach(ml => ml.classList.remove('expanded'));

                    // Open this module if it wasn't expanded
                    if (!wasExpanded) {
                        this.classList.add('expanded');
                        if (moduleLessons) {
                            moduleLessons.classList.add('expanded');
                        }
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                initTabs();
                initSidebar();
                initMobile();
                initUpNext();
                initMobileLessonsDropdown();
                initTranscriptSearch();
                initThemeToggle();

                // Initialize mode-specific functionality
                if (ADVANCED_MODE) {
                    initModuleAccordion();
                    initMobileModuleAccordion();
                }

                // Parse the URL to determine which lesson to load based on slug/ID
                function getLessonFromUrl() {
                    // Get the slug from the URL
                    const urlPath = window.location.pathname;
                    const urlSlug = urlPath.split('/').pop();

                    // If we have lessons data and a slug
                    if (courseData && courseData.lessons && courseData.lessons.length > 0 && urlSlug) {
                        // First check if the slug directly matches a lesson slug
                        const lessonBySlug = courseData.lessons.find(lesson =>
                            lesson.slug && lesson.slug.toLowerCase() === urlSlug.toLowerCase());

                        if (lessonBySlug) {
                            return lessonBySlug;
                        }

                        // If not, check if it's a numeric ID
                        if (!isNaN(urlSlug) && Number.isInteger(parseFloat(urlSlug))) {
                            const lessonById = courseData.lessons.find(lesson =>
                                lesson.id === parseInt(urlSlug));

                            if (lessonById) {
                                return lessonById;
                            }
                        }
                    }

                    // Default to the current lesson or first lesson if no match
                    return courseData.currentLesson || courseData.lessons[0] || null;
                }

                // Find and load the appropriate lesson based on the URL
                const lessonToLoad = getLessonFromUrl();
                if (lessonToLoad) {
                    // Set the current lesson ID
                    currentLessonId = lessonToLoad.id;

                    // Update the UI/sidebar to highlight this lesson
                    const episodeLinks = document.querySelectorAll('.episode-link');
                    episodeLinks.forEach(function(link) {
                        link.classList.remove('current');
                        if (link.getAttribute('data-lesson-id') == lessonToLoad.id) {
                            link.classList.add('current');
                        }
                    });
                    updateMobileDropdown(lessonToLoad.id);

                    // Load the lesson content
                    loadLesson(lessonToLoad.id);

                    // Update browser history state
                    const identifier = lessonToLoad.slug || lessonToLoad.id;
                    history.replaceState({ lessonId: lessonToLoad.id, identifier: identifier }, '', window.location.href);
                } else {
                    // Fallback to current lesson ID if already set
                    if (currentLessonId) {
                        const currentLesson = courseData.currentLesson;
                        const identifier = currentLesson?.slug || currentLessonId;
                        history.replaceState({ lessonId: currentLessonId, identifier: identifier }, '', window.location.href);
                    }
                }

                // V58: Log sidebar position for debugging
                console.log('Sidebar position:', SIDEBAR_POSITION);
            } catch (error) {
                console.error('Error during initialization:', error);
            }
        });

		// Lesson Completion System
const LessonProgress = {
    storageKey: 'course_completed_lessons',

    // Get completed lessons from localStorage
    getCompleted: function() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            console.error('Error reading progress:', e);
            return [];
        }
    },

    // Save completed lessons to localStorage
    setCompleted: function(lessonIds) {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(lessonIds));
        } catch (e) {
            console.error('Error saving progress:', e);
        }
    },

    // Toggle lesson completion
    toggleLesson: function(lessonId) {
        const completed = this.getCompleted();
        const index = completed.indexOf(lessonId);
        const isCompleting = (index === -1);

        if (isCompleting) {
            completed.push(lessonId);
        } else {
            completed.splice(index, 1);
        }

        // Save to localStorage
        this.setCompleted(completed);

        // Immediately update the specific mobile toggle that was clicked
        const mobileItems = document.querySelectorAll('.mobile-lesson-item[data-lesson-id="' + lessonId + '"]');
        mobileItems.forEach(item => {
            const mobileToggle = item.querySelector('.mobile-toggle-slider');
            if (mobileToggle) {
                if (isCompleting) {
                    mobileToggle.classList.add('active');
                    item.classList.add('completed');
                } else {
                    mobileToggle.classList.remove('active');
                    item.classList.remove('completed');
                }
            }
        });

        // Update the checkbox state if this is for current lesson
        const checkbox = document.getElementById('lesson-complete-checkbox');
        if (checkbox && parseInt(checkbox.getAttribute('data-lesson-id')) === lessonId) {
            checkbox.checked = this.isCompleted(lessonId);
        }

        // Update all UI elements
        this.updateUI();
    },

    // Check if lesson is completed
    isCompleted: function(lessonId) {
        return this.getCompleted().includes(lessonId);
    },

    // Calculate progress percentage
    calculateProgress: function() {
        const totalLessons = courseData.lessons ? courseData.lessons.length : 0;
        const completedCount = this.getCompleted().length;

        if (totalLessons === 0) return { percentage: 0, completed: 0, total: 0 };

        const percentage = Math.round((completedCount / totalLessons) * 100);
        return { percentage, completed: completedCount, total: totalLessons };
    },

    // Update all UI elements
    updateUI: function() {
        const progress = this.calculateProgress();

        // Update progress bar
        const progressFill = document.getElementById('progress-fill');
        const progressPercentage = document.getElementById('progress-percentage');
        const progressText = document.getElementById('progress-text');
        const completedCount = document.getElementById('completed-count');

        if (progressFill) progressFill.style.width = progress.percentage + '%';
        if (progressPercentage) progressPercentage.textContent = progress.percentage + '%';
        if (completedCount) completedCount.textContent = progress.completed;

        // Always update mobile progress display
        this.updateMobileProgress();

        // Update lesson indicators
        const completed = this.getCompleted();

        // Desktop sidebar
        document.querySelectorAll('.episode-link').forEach(link => {
            const lessonId = parseInt(link.getAttribute('data-lesson-id'));
            if (completed.includes(lessonId)) {
                link.classList.add('completed');
            } else {
                link.classList.remove('completed');
            }
        });

        // Mobile dropdown - update both item class and toggle slider
        document.querySelectorAll('.mobile-lesson-item').forEach(item => {
            const lessonId = parseInt(item.getAttribute('data-lesson-id'));
            const mobileToggle = item.querySelector('.mobile-toggle-slider');

            if (completed.includes(lessonId)) {
                item.classList.add('completed');
                if (mobileToggle) {
                    mobileToggle.classList.add('active');
                }
            } else {
                item.classList.remove('completed');
                if (mobileToggle) {
                    mobileToggle.classList.remove('active');
                }
            }
        });

        // Update any checkbox inputs
        document.querySelectorAll('input[type="checkbox"].lesson-complete-checkbox').forEach(checkbox => {
            const lessonId = parseInt(checkbox.getAttribute('data-lesson-id'));
            if (lessonId) {
                checkbox.checked = completed.includes(lessonId);
            }
        });

        // Update current lesson checkbox
        const checkbox = document.getElementById('lesson-complete-checkbox');
        const currentLessonId = checkbox ? parseInt(checkbox.getAttribute('data-lesson-id')) : null;

        if (checkbox && currentLessonId) {
            const isCompleted = this.isCompleted(currentLessonId);
            checkbox.checked = isCompleted;

            // Also update the visual state of the checkbox slider
            const checkboxSlider = checkbox.nextElementSibling;
            if (checkboxSlider && checkboxSlider.classList.contains('checkbox-slider')) {
                if (isCompleted) {
                    checkboxSlider.style.backgroundColor = '#10b981';
                    checkboxSlider.style.borderColor = '#10b981';
                } else {
                    checkboxSlider.style.backgroundColor = 'white';
                    checkboxSlider.style.borderColor = 'var(--border-secondary)';
                }
            }
        }
    },

    // Handle mobile progress display
    updateMobileProgress: function() {
        const progress = this.calculateProgress();

        // Update mobile progress display if it exists or create it if it doesn't
        let mobileProgressDisplay = document.getElementById('mobile-progress-display');
        if (!mobileProgressDisplay) {
            mobileProgressDisplay = document.createElement('div');
            mobileProgressDisplay.id = 'mobile-progress-display';
            mobileProgressDisplay.className = 'mobile-progress-display';
            document.body.appendChild(mobileProgressDisplay);
        }

        mobileProgressDisplay.innerHTML = `
            <div class="progress-header">
                <h4>Your Progress</h4>
                <span class="progress-percentage">${progress.percentage}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${progress.percentage}%"></div>
            </div>
            <div class="progress-text">${progress.completed} of ${progress.total} lessons completed</div>
        `;
        mobileProgressDisplay.style.display = window.innerWidth < 768 ? 'block' : 'none';

        // Ensure the mobile progress display is properly styled
        if (!document.getElementById('mobile-progress-styles')) {
            const styleElement = document.createElement('style');
            styleElement.id = 'mobile-progress-styles';
            styleElement.textContent = `
                .mobile-progress-display {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: var(--bg-tertiary);
                    border-top: 1px solid var(--border-primary);
                    color: var(--text-primary);
                    text-align: center;
                    padding: 10px 15px;
                    font-weight: 600;
                    z-index: 1000;
                    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }
                @media (min-width: 768px) {
                    .mobile-progress-display {
                        display: none;
                    }
                }
            `;
            document.head.appendChild(styleElement);
        }
    },

    // Initialize the system
    init: function() {
        // Add window resize listener for mobile progress display
        window.addEventListener('resize', () => {
            const mobileProgressDisplay = document.getElementById('mobile-progress-display');
            if (mobileProgressDisplay) {
                mobileProgressDisplay.style.display = window.innerWidth < 768 ? 'block' : 'none';
            }
        });

        // Set up checkbox listener
        const checkbox = document.getElementById('lesson-complete-checkbox');
        if (checkbox) {
            // Remove any existing event listeners by cloning
            const newCheckbox = checkbox.cloneNode(true);
            checkbox.parentNode.replaceChild(newCheckbox, checkbox);

            newCheckbox.addEventListener('change', (e) => {
                const lessonId = parseInt(e.target.getAttribute('data-lesson-id'));
                if (lessonId) {
                    this.toggleLesson(lessonId);
                }
            });
        }

        // Set up mobile toggle listeners - important for dynamic navigation
        this.initMobileToggleListeners();

        // Initial UI update
        this.updateUI();
        this.updateMobileProgress();
    },

    // Separate function to initialize mobile toggle listeners
    initMobileToggleListeners: function() {
        document.querySelectorAll('.mobile-lesson-item').forEach(item => {
            const toggle = item.querySelector('.mobile-toggle-slider');
            const lessonId = parseInt(item.getAttribute('data-lesson-id'));

            if (toggle && lessonId) {
                // Remove existing listeners first to avoid duplicates
                const newToggle = toggle.cloneNode(true);
                toggle.parentNode.replaceChild(newToggle, toggle);

                newToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    // Toggle lesson completion state
                    this.toggleLesson(lessonId);
                });

                // Apply initial state - make sure the active class matches completion state
                if (this.isCompleted(lessonId)) {
                    newToggle.classList.add('active');
                } else {
                    newToggle.classList.remove('active');
                }
            }
        });
    }
};

// Initialize everything on DOM ready - single event listener for better performance
document.addEventListener('DOMContentLoaded', function() {
    // First initialize the LessonProgress system
    LessonProgress.init();

    // Now that everything is initialized, enhance the loadLesson function
    const originalLoadLesson = window.loadLesson;

    window.loadLesson = async function(lessonId) {
        // Call the original function first
        await originalLoadLesson(lessonId);

        // Update checkbox for new lesson
        const checkbox = document.getElementById('lesson-complete-checkbox');
        if (checkbox) {
            checkbox.setAttribute('data-lesson-id', lessonId);
            checkbox.checked = LessonProgress.isCompleted(lessonId);
        }

        // Update UI before initializing listeners to ensure correct initial state
        LessonProgress.updateUI();

        // Reinitialize mobile toggle listeners after navigation
        LessonProgress.initMobileToggleListeners();
    };
});
    </script>
</body>
</html>