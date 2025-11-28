<?php
// DO NOT EDIT / EXAMPLE ONLY
// ONLY EDIT V60 NOW
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

    <style>
        .module-arrow {
            transition: transform 0.2s ease-in-out;
            width: 1em;
            height: 1em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #e5e7eb; /* Tailwind gray-200 */
        }
        .module-header:hover .module-arrow {
            color: #d1d5db; /* Tailwind gray-300 */
        }
        .module-header.active .module-arrow {
            transform: rotate(90deg);
            color: #9ca3af; /* Tailwind gray-400 */
        }
    </style>
    <meta property="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="twitter:image" content="<?php echo $page_image; ?>">
    <meta property="twitter:image:alt" content="<?php echo htmlspecialchars($current_lesson ? $current_lesson['title'] : 'Modern Automation Workshop'); ?> by Joe Lee">
    <meta property="twitter:creator" content="@joelee">
    <meta property="twitter:site" content="@joelee">

    <meta name="theme-color" content="#0046dd">

    <link href="https://fonts.googleapis.com/css2?family=Satoshi:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* CSS Custom Properties for Theme System */
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

        /* Dark Theme - Applied via JavaScript with .dark-theme class */
        .logo.light { display: block; }
        .logo.dark { display: none; }

        .dark-theme .logo.light { display: none; }
        .dark-theme .logo.dark { display: block; }

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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.65; color: var(--text-primary); background: var(--bg-primary); font-size: 16px; font-weight: 400;
            -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        h1, h2, h3, h4, h5, h6 { font-family: 'Satoshi', sans-serif; color: var(--text-primary); margin: 0; }
        h1 { font-size: 2.25rem; font-weight: 700; letter-spacing: -0.025em; line-height: 1.2; }
        h2 { font-size: 1.875rem; font-weight: 600; letter-spacing: -0.02em; line-height: 1.25; }
        h3 { font-size: 1.5rem; font-weight: 500; letter-spacing: -0.01em; line-height: 1.3; }
        h4 { font-size: 1.125rem; font-weight: 600; letter-spacing: -0.005em; line-height: 1.4; }
        p { font-family: 'Inter', sans-serif; font-size: 1rem; font-weight: 400; line-height: 1.7; color: var(--text-secondary); margin: 0 0 1rem 0; }

        .text-large { font-size: 1.125rem; line-height: 1.65; font-weight: 400; }
        .font-bold { font-weight: 700; }

        .header { background: var(--bg-secondary); padding: 0; border-bottom: 1px solid var(--border-primary); position: sticky; top: 0; z-index: 100; transition: background-color 0.3s ease, border-color 0.3s ease; }
        .header-container { max-width: 90%; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; height: 70px; }
        .logo { height: 48px; width: auto; transition: opacity 0.2s; }
        .logo:hover { opacity: 0.8; }
        .logo.light { display: block; }
        .logo.dark { display: none; }
        .dark-theme .logo.light { display: none; }
        .dark-theme .logo.dark { display: block; }
        .nav { display: flex; gap: 2rem; align-items: center; height: 100%; }
        .nav a { font-family: 'Inter', sans-serif; color: var(--text-tertiary); text-decoration: none; font-weight: 500; font-size: 0.9rem; letter-spacing: 0.005em; transition: color 0.2s; }
        .nav a:hover { color: var(--blue-primary); }

        /* Mobile Lessons Dropdown - Hidden on Desktop */
        .mobile-lessons-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            color: var(--text-primary);
            font-weight: 600;
            font-family: 'Inter', sans-serif;
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
        .mobile-lessons-dropdown.active {
            display: block;
        }

        .mobile-menu { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
        .mobile-nav { display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-secondary); border-bottom: 1px solid var(--border-primary); box-shadow: 0 4px 6px var(--shadow-primary); z-index: 50; transition: background-color 0.3s ease; }
        .mobile-nav.active { display: block; }
        .mobile-nav a { display: block; padding: 1rem 2rem; color: var(--text-secondary); text-decoration: none; font-weight: 500; border-bottom: 1px solid var(--bg-tertiary); transition: background 0.2s; }
        .mobile-nav a:hover { background: var(--bg-tertiary); color: var(--blue-primary); }

        /* NEW V58: Dynamic main layout based on sidebar position */
        .main {
            max-width: 90%;
            width: 100%;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            gap: 4rem;
            align-items: start;
        }

        /* V58: Sidebar position configurations */
        .main.sidebar-right {
            grid-template-columns: 1fr 380px;
        }

        .main.sidebar-left {
            grid-template-columns: 380px 1fr;
        }

        .main.sidebar-left .content {
            order: 2;
        }

        .main.sidebar-left .sidebar {
            order: 1;
        }

        .content { background: var(--bg-secondary); border-radius: 12px; padding: 2rem; box-shadow: 0 1px 3px var(--shadow-primary); transition: background-color 0.3s ease; }

        .video-container { position: relative; width: 100%; height: 0; padding-bottom: 56.25%; border-radius: 8px; overflow: hidden; margin-bottom: 2rem; background: var(--bg-tertiary); }
        .video-container img, .video-container iframe, .video-container video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .video-container img { object-fit: cover; }
        .video-container video { object-fit: contain; }

        .tabs { margin-bottom: 2rem; }
        .tab-buttons { display: flex; border-bottom: 1px solid var(--border-primary); margin-bottom: 1.5rem; }
        .tab-button { font-family: 'Inter', sans-serif; background: none; border: none; padding: 1rem 1.5rem; font-weight: 500; font-size: 0.9rem; letter-spacing: 0.005em; color: var(--text-tertiary); cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
        .tab-button.active { color: var(--blue-primary); border-bottom-color: var(--blue-primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-content p { margin-bottom: 1rem; color: var(--text-secondary); }

        .transcript-list { list-style: none; }
        .transcript-item { margin-bottom: 0.75rem; padding: 0.75rem; background: var(--bg-tertiary); border-radius: 6px; border-left: 3px solid transparent; cursor: pointer; transition: all 0.2s; }
        .transcript-item:hover { border-left-color: var(--blue-primary); background: var(--bg-quaternary); }
        .transcript-item.hidden { display: none; }
        .highlight { background: #ffeb3b; color: #000; font-weight: 600; padding: 0.1rem 0.2rem; border-radius: 2px; }
        .transcript-timestamp { font-weight: 600; color: var(--blue-primary); font-size: 0.75rem; display: block; margin-bottom: 0.25rem; }
        .transcript-text { color: var(--text-secondary); line-height: 1.6; }

        .up-next { margin-top: 3rem; }
        .up-next h2 { margin-bottom: 1.5rem; font-size: 1.5rem; font-weight: 600; }
        .next-video { display: flex; gap: 1rem; padding: 1.5rem; background: var(--bg-tertiary); border-radius: 8px; text-decoration: none; color: inherit; transition: background 0.2s; cursor: pointer; }
        .next-video:hover { background: var(--bg-quaternary); }
        .next-video-thumb { width: 120px; height: 68px; background: linear-gradient(135deg, var(--blue-primary), #3b82f6); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; flex-shrink: 0; }
        .next-video-content h3 { font-family: 'Satoshi', sans-serif; font-size: 1.1rem; font-weight: 600; letter-spacing: -0.01em; margin-bottom: 0.5rem; }
        .next-video-content p { font-family: 'Inter', sans-serif; color: var(--text-tertiary); font-size: 0.9rem; font-weight: 400; line-height: 1.5; }

        .sidebar { background: var(--bg-secondary); border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px var(--shadow-primary); height: fit-content; position: sticky; top: 100px; min-width: 320px; transition: background-color 0.3s ease; }
        .sidebar h3 { font-family: 'Satoshi', sans-serif; font-size: 1.125rem; font-weight: 600; letter-spacing: -0.01em; margin-bottom: 1.5rem; color: var(--text-primary); }

        .episode-notes-content { line-height: 1.7; }
        .episode-notes-content h1 { font-size: 1.5rem; font-weight: 600; margin: 1.5rem 0 1rem 0; color: var(--text-primary); }
        .episode-notes-content h2 { font-size: 1.25rem; font-weight: 600; margin: 1.25rem 0 0.75rem 0; color: var(--text-primary); }
        .episode-notes-content h3 { font-size: 1.125rem; font-weight: 500; margin: 1rem 0 0.5rem 0; color: var(--text-secondary); }
        .episode-notes-content p { margin: 0 0 1rem 0; color: var(--text-secondary); }
        .episode-notes-content ul, .episode-notes-content ol { margin: 0 0 1rem 1.5rem; color: var(--text-secondary); }
        .episode-notes-content li { margin: 0.25rem 0; }
        .episode-notes-content strong { font-weight: 600; color: var(--text-primary); }
        .episode-notes-content code { background: var(--bg-tertiary); padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 0.875rem; color: #e11d48; }
        .episode-notes-content a { color: var(--blue-primary); text-decoration: none; }
        .episode-notes-content a:hover { text-decoration: underline; }

        .learning-objectives { background: var(--blue-secondary); border: 1px solid var(--blue-primary); border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .learning-objectives h4 { color: var(--blue-primary); margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; }
        .learning-objectives h4 .target-icon { margin-right: 0.5rem; }
        .learning-objectives ul { margin: 0; padding-left: 1.25rem; list-style: none; }
        .learning-objectives li { color: var(--text-primary); margin: 0.25rem 0; font-size: 0.9rem; position: relative; padding-left: 0.5rem; }
        .learning-objectives li:before { content: '•'; position: absolute; left: -0.75rem; color: var(--blue-primary); }

        /* Icon Styles */
        .play-icon {
            width: 16px;
            height: 16px;
            background: var(--blue-primary) !important;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white !important;
            font-size: 10px;
            flex-shrink: 0;
            margin-right: 0.5rem;
        }

        .module-arrow {
            transition: transform 0.2s;
            font-size: 0.8rem;
            color: var(--text-tertiary);
        }

        /* Module accordion styles */
        .module-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-secondary);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);

            .video-container { position: relative; width: 100%; height: 0; padding-bottom: 56.25%; border-radius: 8px; overflow: hidden; margin-bottom: 2rem; background: var(--bg-tertiary); }
            .video-container img, .video-container iframe, .video-container video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
            .video-container img { object-fit: cover; }
            .video-container video { object-fit: contain; }

        /* Mobile Module Items - Base Styling */
        .mobile-module-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            color: var(--text-primary);
            font-weight: 600;
            border-bottom: 1px solid var(--bg-tertiary);
            background: var(--bg-tertiary);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .mobile-module-item:hover { background: var(--bg-quaternary); color: var(--text-secondary); }
        .mobile-module-arrow { transition: transform 0.2s; font-size: 0.8rem; color: var(--text-tertiary); }
        .mobile-module-item.expanded .mobile-module-arrow { transform: rotate(90deg); }
        .mobile-module-lessons { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; background: var(--bg-secondary); }
        .mobile-module-lessons.expanded { max-height: 1000px; transition: max-height 0.3s ease-in; }
        .mobile-lesson-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 3rem; color: var(--text-secondary); font-weight: 500; border-bottom: 1px solid var(--bg-tertiary); font-size: 0.85rem; background: var(--bg-secondary); cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .mobile-lesson-item:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        .mobile-lesson-item.current { background: var(--blue-primary); color: white; }
        .mobile-lesson-item .play-icon { width: 14px; height: 14px; background: var(--blue-primary) !important; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white !important; font-size: 8px; flex-shrink: 0; }
        .mobile-lesson-item.current .play-icon { background: #ffffff !important; color: var(--blue-primary) !important; }
    </style>

    <?php if (ADVANCED_COURSE_MODE): ?>
    <!-- ADVANCED MODE STYLES - Only ~40 additional lines -->
    <style>
        /* Desktop Accordion Styles */
        .episode-section { margin-bottom: 0.5rem; }
        .module-header {
            display: flex; align-items: center; justify-content: space-between; padding: 1rem;
            background: var(--bg-tertiary); border: 1px solid var(--border-secondary); border-radius: 8px; cursor: pointer;
            transition: all 0.2s; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; color: var(--text-primary);

            .video-container { position: relative; width: 100%; height: 0; padding-bottom: 56.25%; border-radius: 8px; overflow: hidden; margin-bottom: 2rem; background: var(--bg-tertiary); }
            .video-container img, .video-container iframe, .video-container video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
            .video-container img { object-fit: cover; }
            .video-container video { object-fit: contain; }

        /* Mobile Module Items - Base Styling */
        .mobile-module-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            color: var(--text-primary);
            font-weight: 600;
            border-bottom: 1px solid var(--bg-tertiary);
            background: var(--bg-tertiary);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .mobile-module-item:hover { background: var(--bg-quaternary); color: var(--text-secondary); }
        .mobile-module-arrow { transition: transform 0.2s; font-size: 0.8rem; color: var(--text-tertiary); }
        .mobile-module-item.expanded .mobile-module-arrow { transform: rotate(90deg); }
        .mobile-module-lessons { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; background: var(--bg-secondary); }
        .mobile-module-lessons.expanded { max-height: 1000px; transition: max-height 0.3s ease-in; }
        .mobile-lesson-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 3rem; color: var(--text-secondary); font-weight: 500; border-bottom: 1px solid var(--bg-tertiary); font-size: 0.85rem; background: var(--bg-secondary); cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .mobile-lesson-item:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        .mobile-lesson-item.current { background: var(--blue-primary); color: white; }
        .mobile-lesson-item .play-icon { width: 14px; height: 14px; background: var(--blue-primary) !important; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white !important; font-size: 8px; flex-shrink: 0; }
        .mobile-lesson-item.current .play-icon { background: #ffffff !important; color: var(--blue-primary) !important; }
    </style>
    <?php else: ?>
    <!-- SIMPLE MODE STYLES - Original V56 structure -->
    <style>
        .episode-section { margin-bottom: 0.1rem; }
        .section-title { margin-top: 0.8rem; margin-bottom: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em; }
        .section-title:empty { display: none; }
        .episode-list { list-style: none; }
        .episode-item { margin-bottom: 0.4rem; }
        .mobile-lesson-item { display: block; padding: 1rem 2rem; color: #4b5563; text-decoration: none; font-weight: 500; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; font-size: 0.9rem; }
        .mobile-lesson-item:hover { background: #f9fafb; color: #1f2937; }
        .mobile-lesson-item.current { background: #0046dd; color: white; }
    </style>
    <?php endif; ?>

    <!-- SHARED STYLES FOR BOTH MODES -->
    <style>
        .episode-link { font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; text-decoration: none; color: var(--text-secondary); border-radius: 6px; transition: all 0.2s; font-size: 0.9rem; font-weight: 500; cursor: pointer; border: 1px solid transparent; }
        .episode-link:hover { background: var(--bg-tertiary); color: var(--text-primary); border-color: var(--border-secondary); }
        .episode-link.current { background: var(--blue-primary) !important; color: white !important; font-weight: 500; border-color: var(--blue-primary); }
    </style>

    <style>
        /* Sidebar Styles */
        .sidebar {
            background: var(--bg-secondary);
            border-radius: 8px;
            border: 1px solid var(--border-primary);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
            overflow-y: auto;
            max-height: calc(100vh - 4rem);
        }

        .sidebar h3 {
            font-family: 'Satoshi', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .episode-section {
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-primary);
            overflow: hidden;
        }

        .module-header {
            padding: 1rem;
            background: var(--bg-secondary);
            cursor: pointer;
            border-bottom: 1px solid var(--border-primary);
            transition: all 0.2s;
        }

        .module-header:hover {
            background: var(--bg-tertiary);
        }

        .module-header.active {
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-primary);
        }

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

        /* Fix for bullet points */
        .sidebar ul, .sidebar li {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        /* Theme Toggle Styles */
        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border-primary);
            background: var(--bg-secondary);
            cursor: pointer;
            padding: 0;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .theme-toggle:hover {
            background: var(--bg-tertiary);
        }

        .theme-toggle .moon-icon,
        .theme-toggle .sun-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .theme-toggle .moon-icon {
            opacity: 1;
        }

        .theme-toggle .sun-icon {
            opacity: 0;
            transform: translate(-50%, -50%) rotate(90deg);
        }

        .dark-theme .theme-toggle .moon-icon {
            opacity: 0;
            transform: translate(-50%, -50%) rotate(-90deg);
        }

        .dark-theme .theme-toggle .sun-icon {
            opacity: 1;
            transform: translate(-50%, -50%) rotate(0);
        }

        /* Mobile Theme Toggle */
        #mobile-theme-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            width: 100%;
            border-top: 1px solid var(--border-primary);
            margin-top: 0.5rem;
            position: relative;
        }

        #mobile-theme-toggle .moon-icon,
        #mobile-theme-toggle .sun-icon {
            position: relative;
            transition: opacity 0.3s ease;
        }

        #mobile-theme-toggle .moon-icon {
            opacity: 1;
        }

        #mobile-theme-toggle .sun-icon {
            opacity: 0;
            position: absolute;
        }

        .dark-theme #mobile-theme-toggle .moon-icon {
            opacity: 0;
        }

        .dark-theme #mobile-theme-toggle .sun-icon {
            opacity: 1;
        }

        /* Show/hide appropriate toggle based on screen size */
        @media (max-width: 768px) {
            .theme-toggle {
                display: none;
            }
        }

        @media (min-width: 769px) {
            #mobile-theme-toggle {
                display: none;
            }
        }
    </style>

    <style>
        /* NEW V58: Pixel-perfect icon styling */
        .play-icon {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .play-icon svg {
            width: 100%;
            height: 100%;
        }
        /* Default State: Blue circle, white triangle */
        .play-icon .play-svg-circle { fill: var(--blue-primary); transition: fill 0.2s; }
        .play-icon .play-svg-triangle { fill: white; transition: fill 0.2s; }

        /* Current Lesson State: White circle, blue triangle */
        .episode-link.current .play-icon .play-svg-circle { fill: white; }
        .episode-link.current .play-icon .play-svg-triangle { fill: var(--blue-primary); }

        .lesson-title { flex: 1; display: flex; justify-content: space-between; align-items: center; }
        .lesson-duration { font-size: 0.75rem; font-weight: 400; opacity: 0.8; margin-left: 0.5rem; }
        .episode-link.current .lesson-duration { color: rgba(255, 255, 255, 0.8) !important; opacity: 1; }
        .episode-link:not(.current) .lesson-duration { color: var(--text-tertiary); }
    </style>

    <style>
        /* Modern Clean Toggle Switch Styles */
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

        .toggle-slider:hover {
            background-color: #cbd5e1;
        }

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

        .dark-theme .toggle-slider:hover {
            background-color: #0037b8;
        }

        .dark-theme .toggle-slider:before {
            transform: translateX(24px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }


        /* Mobile Theme Toggle */
        #mobile-theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            width: 100%;
            border-top: 1px solid var(--border-primary);
            margin-top: 0.5rem;
        }

        .mobile-toggle-switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
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
        }

        .mobile-toggle-slider:before {
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

        .dark-theme .mobile-toggle-slider {
            background-color: var(--blue-primary);
            border-color: var(--blue-primary);
        }

        .dark-theme .mobile-toggle-slider:before {
            transform: translateX(24px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }


        /* Responsive Navigation and Toggle Styles */
        @media (max-width: 768px) {
            /* Hide desktop navigation */
            .nav {
                display: none;
            }

            /* Show mobile navigation elements */
            .mobile-menu {
                display: block;
            }

            .mobile-lessons-toggle {
                display: flex;
            }

            /* Hide desktop theme toggle */
            .theme-toggle {
                display: none;
            }

            /* Adjust main layout for mobile */
            .main {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 1rem 0.5rem;
                max-width: 95%;
            }

            .content {
                padding: 1rem;
            }

            .sidebar {
                display: none;
            }

            /* Mobile header layout adjustments */
            .header-container {
                padding: 0 0.5rem;
                display: grid;
                grid-template-columns: 1fr auto auto;
                align-items: center;
                gap: 1rem;
            }

            .logo {
                justify-self: start;
            }

            .mobile-lessons-toggle {
                justify-self: center;
                order: 2;
            }

            .mobile-menu {
                justify-self: end;
                order: 3;
            }
        }

        @media (min-width: 769px) {
            #mobile-theme-toggle {
                display: none;
            }
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
    </style>
   <!-- Add this CSS to your existing styles section, replacing the mobile-specific styles -->

<!-- Add this CSS to your existing styles section, replacing the mobile-specific styles -->

<style>
/* ===================================
   MOBILE FIXES - Core Responsive Styles
   =================================== */

/* Fix viewport and base mobile styles */
@media (max-width: 768px) {
    /* Fix body and html to prevent horizontal scroll */
    html, body {
        overflow-x: hidden;
        width: 100%;
    }

    /* Fix header container for mobile */
    .header-container {
        max-width: 100% !important;
        padding: 0 1rem !important;
        width: 100%;
        box-sizing: border-box;
    }

    /* Fix main container - full width on mobile */
    .main {
        max-width: 100% !important;
        width: 100% !important;
        padding: 1rem !important;
        margin: 0 !important;
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
        box-sizing: border-box;
    }

    /* Ensure content takes full width */
    .content {
        width: 100% !important;
        padding: 1rem !important;
        margin: 0 !important;
        box-sizing: border-box;
        border-radius: 8px !important;
    }

    /* Video container responsive */
    .video-container {
        margin-bottom: 1rem !important;
    }

    /* Fix mobile lessons dropdown styling */
    .mobile-lessons-dropdown {
        width: 100% !important;
        max-height: calc(100vh - 70px) !important;
        background: var(--bg-secondary) !important;
    }

    /* ADVANCED MODE - Mobile module items (clickable headers) */
    .mobile-module-item {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 1rem 1.5rem !important;
        color: var(--text-primary) !important;
        font-weight: 600 !important;
        border-bottom: 1px solid var(--border-primary) !important;
        background: var(--bg-tertiary) !important;
        font-size: 0.95rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
        text-decoration: none !important;
    }

    .mobile-module-item:hover {
        background: var(--bg-quaternary) !important;
        color: var(--text-primary) !important;
        text-decoration: none !important;
    }

    /* Module arrow rotation */
    .mobile-module-arrow {
        transition: transform 0.3s ease !important;
        font-size: 0.8rem !important;
        color: var(--text-tertiary) !important;
    }

    .mobile-module-item.expanded .mobile-module-arrow {
        transform: rotate(90deg) !important;
    }

    /* ADVANCED MODE - Hidden lessons container */
    .mobile-module-lessons {
        max-height: 0 !important;
        overflow: hidden !important;
        transition: max-height 0.3s ease-out !important;
        background: var(--bg-secondary) !important;
    }

    .mobile-module-lessons.expanded {
        max-height: 1500px !important;
        transition: max-height 0.3s ease-in !important;
    }

    /* Style mobile lesson items (both modes) */
    .mobile-lesson-item {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        padding: 0.875rem 1.5rem !important;
        color: var(--text-secondary) !important;
        font-weight: 500 !important;
        border-bottom: 1px solid var(--border-primary) !important;
        font-size: 0.9rem !important;
        background: var(--bg-secondary) !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
        text-decoration: none !important;
        position: relative !important;
    }

    /* ADVANCED MODE - Indent lessons under modules */
    .mobile-module-lessons .mobile-lesson-item {
        padding-left: 3rem !important;
        background: var(--bg-secondary) !important;
        border-bottom: 1px solid var(--border-primary) !important;
    }

    .mobile-lesson-item:hover {
        background: var(--bg-tertiary) !important;
        color: var(--text-primary) !important;
        text-decoration: none !important;
    }

    .mobile-lesson-item.current {
        background: var(--blue-primary) !important;
        color: white !important;
    }

    /* Ensure play icons display properly */
    .mobile-lesson-item .play-icon {
        width: 16px !important;
        height: 16px !important;
        background: var(--blue-primary) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: white !important;
        font-size: 10px !important;
        flex-shrink: 0 !important;
    }

    .mobile-lesson-item.current .play-icon {
        background: white !important;
        color: var(--blue-primary) !important;
    }

    /* Mobile navigation menu styling */
    .mobile-nav {
        width: 100% !important;
        background: var(--bg-secondary) !important;
    }

    .mobile-nav a {
        display: block !important;
        padding: 1rem 1.5rem !important;
        color: var(--text-secondary) !important;
        text-decoration: none !important;
        font-weight: 500 !important;
        border-bottom: 1px solid var(--border-primary) !important;
        transition: all 0.2s !important;
    }

    .mobile-nav a:hover {
        background: var(--bg-tertiary) !important;
        color: var(--blue-primary) !important;
        text-decoration: none !important;
    }

    /* Fix tab buttons on mobile */
    .tab-buttons {
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }

    .tab-button {
        flex-shrink: 0 !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.875rem !important;
    }

    /* Fix up-next section */
    .up-next {
        margin-top: 2rem !important;
    }

    .next-video {
        flex-direction: column !important;
        gap: 1rem !important;
        padding: 1rem !important;
    }

    .next-video-thumb {
        width: 100% !important;
        height: 180px !important;
    }

    /* Typography adjustments for mobile */
    h1 {
        font-size: 1.75rem !important;
        line-height: 1.3 !important;
    }

    h2 {
        font-size: 1.5rem !important;
    }

    h3 {
        font-size: 1.25rem !important;
    }

    p {
        font-size: 0.95rem !important;
    }

    /* Fix transcript search on mobile */
    #transcript-search-input {
        font-size: 16px !important; /* Prevents zoom on iOS */
    }

    /* Ensure proper spacing for mobile */
    .episode-notes-content {
        padding: 0 !important;
    }

    .learning-objectives {
        padding: 0.75rem !important;
        margin: 0.75rem 0 !important;
    }

    /* Fix resources section */
    #lesson-resources li {
        padding: 0.75rem !important;
        margin-bottom: 0.5rem !important;
    }
}

/* Additional mobile-specific dark theme fixes */
.dark-theme {
    /* Ensure dark theme variables work on mobile */
    --bg-primary: #0f172a !important;
    --bg-secondary: #1e293b !important;
    --bg-tertiary: #334155 !important;
    --bg-quaternary: #475569 !important;
    --text-primary: #f1f5f9 !important;
    --text-secondary: #cbd5e1 !important;
    --text-tertiary: #94a3b8 !important;
    --text-quaternary: #64748b !important;
    --border-primary: #334155 !important;
    --border-secondary: #475569 !important;
    --border-tertiary: #64748b !important;
}

/* Ensure dark theme applies to mobile dropdown */
.dark-theme .mobile-lessons-dropdown {
    background: var(--bg-secondary) !important;
    border-bottom: 1px solid var(--border-primary) !important;
}

.dark-theme .mobile-lesson-item {
    background: var(--bg-secondary) !important;
    color: var(--text-secondary) !important;
    border-bottom: 1px solid var(--border-primary) !important;
}

.dark-theme .mobile-lesson-item:hover {
    background: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
}

.dark-theme .mobile-module-item {
    background: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
    border-bottom: 1px solid var(--border-primary) !important;
}

.dark-theme .mobile-module-item:hover {
    background: var(--bg-quaternary) !important;
}

/* Fix for iOS Safari specific issues */
@supports (-webkit-touch-callout: none) {
    .mobile-lessons-dropdown {
        -webkit-overflow-scrolling: touch !important;
    }

    .main {
        -webkit-box-sizing: border-box !important;
    }
}

/* Ensure no horizontal scroll on very small screens */
@media (max-width: 375px) {
    .header-container {
        padding: 0 0.5rem !important;
    }

    .main {
        padding: 0.5rem !important;
    }

    .content {
        padding: 0.75rem !important;
    }

    h1 {
        font-size: 1.5rem !important;
    }
}

/* Fix mobile module lessons accordion */
@media (max-width: 768px) {
    /* Module item states for visual feedback */
    .mobile-module-item.expanded {
        background: var(--bg-quaternary) !important;
        border-bottom: 2px solid var(--blue-primary) !important;
    }

    /* Ensure smooth accordion animation */
    .mobile-module-lessons {
        background: var(--bg-secondary) !important;
        border-bottom: none !important;
    }

    .mobile-module-lessons.expanded {
        max-height: 2000px !important;
        border-bottom: 1px solid var(--border-primary) !important;
    }

    /* Visual hierarchy for lessons within modules */
    .mobile-module-lessons .mobile-lesson-item:last-child {
        border-bottom: none !important;
    }
}

/* Dark theme mobile accordion styles */
.dark-theme .mobile-module-item.expanded {
    background: var(--bg-quaternary) !important;
    border-bottom: 2px solid var(--blue-primary) !important;
}

.dark-theme .mobile-module-lessons {
    background: var(--bg-secondary) !important;
}

.dark-theme .mobile-module-lessons .mobile-lesson-item {
    background: var(--bg-secondary) !important;
    border-bottom: 1px solid var(--border-primary) !important;
}

.dark-theme .mobile-module-lessons .mobile-lesson-item:hover {
    background: var(--bg-tertiary) !important;
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

            <div style="margin-top: 2rem; padding: 1rem; background: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe;">
                <h4 style="font-size: 0.9rem; font-weight: 600; color: #0046dd; margin-bottom: 0.5rem;"><?php echo render_icon('chart', 'mr-2'); ?> Course Progress</h4>
                <div style="background: #dbeafe; height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="background: #0046dd; height: 100%; width: <?php echo $total_lessons > 0 ? (1 / $total_lessons * 100) : 12.5; ?>%; border-radius: 4px;"></div>
                </div>
                <p style="font-size: 0.8rem; color: #374151; margin: 0.5rem 0 0 0;">
                    1 of <?php echo $total_lessons > 0 ? $total_lessons : 8; ?> lessons complete
                </p>
            </div>
        </aside>
    </main>

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
                    e.preventDefault();
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
            const html = document.documentElement;

            // Check for stored theme preference, default to light mode
            const storedTheme = localStorage.getItem('darkMode');
            const isDarkMode = storedTheme === 'true' || (storedTheme === null && '<?php echo DEFAULT_THEME_MODE; ?>' === 'dark');

            // Apply initial theme based on localStorage
            if (isDarkMode) {
                html.classList.add('dark-theme');
                if (themeToggleCheckbox) themeToggleCheckbox.checked = true;
                if (mobileThemeToggleCheckbox) mobileThemeToggleCheckbox.checked = true;

                const toggleText = document.querySelector('#mobile-theme-toggle .toggle-text');
                if (toggleText) toggleText.textContent = 'Light Mode';
            } else {
                html.classList.remove('dark-theme');
                if (themeToggleCheckbox) themeToggleCheckbox.checked = false;
                if (mobileThemeToggleCheckbox) mobileThemeToggleCheckbox.checked = false;

                const toggleText = document.querySelector('#mobile-theme-toggle .toggle-text');
                if (toggleText) toggleText.textContent = 'Dark Mode';
            }

            function toggleDarkMode() {
                const isDark = html.classList.toggle('dark-theme');
                localStorage.setItem('darkMode', isDark.toString());

                // Sync the checkbox states
                if (themeToggleCheckbox) themeToggleCheckbox.checked = isDark;
                if (mobileThemeToggleCheckbox) mobileThemeToggleCheckbox.checked = isDark;

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

                // Set initial state for browser navigation
                if (currentLessonId) {
                    const currentLesson = courseData.currentLesson;
                    const identifier = currentLesson?.slug || currentLessonId;
                    history.replaceState({ lessonId: currentLessonId, identifier: identifier }, '', window.location.href);
                }

                // V58: Log sidebar position for debugging
                console.log('Sidebar position:', SIDEBAR_POSITION);
            } catch (error) {
                console.error('Error during initialization:', error);
            }
        });
    </script>
</body>
</html>