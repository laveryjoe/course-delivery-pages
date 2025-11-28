/**
 * Course Platform Configuration
 *
 * Edit these settings to customize your course.
 * This is the only file most people need to touch.
 */

const CONFIG = {
    // =============================================
    // DATA SOURCE
    // =============================================
    // 'json' = Load from course-data.json file (simple, no database)
    // 'supabase' = Load from Supabase database (advanced, web-based editing)
    DATA_SOURCE: 'json',

    // JSON file name (used when DATA_SOURCE = 'json')
    JSON_FILE: 'course-data.json',

    // Supabase settings (used when DATA_SOURCE = 'supabase')
    SUPABASE_URL: 'https://your-project.supabase.co',
    SUPABASE_KEY: 'your-anon-key',

    // =============================================
    // URL ROUTING
    // =============================================
    // 'path' = Clean URLs like /lesson/my-lesson (requires index.php router)
    // 'hash' = Hash URLs like #lesson/my-lesson (works without any server config)
    ROUTING_MODE: 'path',

    // =============================================
    // COURSE LAYOUT
    // =============================================
    // true = Accordion-style modules that expand/collapse
    // false = Flat list showing all lessons
    ADVANCED_COURSE_MODE: true,

    // 'left' or 'right' - which side to show the lesson sidebar
    SIDEBAR_POSITION: 'left',

    // =============================================
    // THEME
    // =============================================
    // 'light' or 'dark' - default theme for new visitors
    DEFAULT_THEME_MODE: 'light',

    // =============================================
    // VIDEO
    // =============================================
    // Show thumbnail images for videos
    USE_VIDEO_THUMBNAILS: true,

    // Auto-generate thumbnails from video first frame
    AUTO_GENERATE_THUMBNAILS: true,

    // =============================================
    // ADVANCED (usually don't need to change)
    // =============================================
    // Base URL - auto-detected, but you can set manually if needed
    // Example: '/members' or '/courses/my-course'
    BASE_URL: null  // null = auto-detect
};
