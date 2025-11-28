# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a PHP-based course platform/learning management system that serves video-based courses with modules and lessons. The architecture uses a monolithic single-file approach where each version (V57-V60) contains PHP backend logic, HTML structure, CSS styles, and JavaScript in one file.

**Current Version**: `course-access-V60.php` (others are deprecated)

## Core Architecture

### Data Layer
- **Database**: Supabase (PostgreSQL) via REST API
- **Tables**: `courses`, `modules`, `lessons`, `resources`
- **API Function**: `supabase_request($endpoint, $method, $data)`

### Key Functions
```php
fetch_course($course_id)           // Get course details
fetch_modules($course_id)          // Get course modules
fetch_all_lessons($course_id)      // Get all lessons for course
fetch_lesson_by_slug($slug)        // Get lesson by URL slug
fetch_lesson_by_id($lesson_id)     // Get lesson by ID
fetch_lesson_resources($lesson_id) // Get lesson resources
```

### URL Routing
- **Pattern**: `/lesson/{slug-or-id}`
- **Entry Point**: `get_lesson_from_url()` in course-access-V60.php
- **SEO**: Auto-generated slugs via `generate_slug()`

## Configuration

Located at the top of course-access-V60.php:

```php
define('ADVANCED_COURSE_MODE', true);    // 2-level navigation vs flat
define('USE_VIDEO_THUMBNAILS', true);     // Show video thumbnails
define('AUTO_GENERATE_THUMBNAILS', true); // Auto-extract first frame
define('DEFAULT_THEME_MODE', 'light');    // Default theme
define('SIDEBAR_POSITION', 'right');      // Sidebar placement

// Database connection
define('SUPABASE_URL', 'https://evfhfsjdzfippxmpvstz.supabase.co');
define('SUPABASE_KEY', '[JWT_TOKEN]');
```

## Development Commands

### Running the Application
- **Local Server**: Standard PHP server (Apache/Nginx)
- **URL**: `https://joe23.com/members/course-platform/`
- **Entry Point**: `course-access-V60.php`

### Database Operations
```php
// Auto-generate missing slugs (run once after lesson updates)
generate_missing_slugs();

// Content processing
markdown_to_html($text)      // Convert markdown to HTML
format_transcript($text)     // Format timestamped transcripts
```

## Content Management

### Adding New Lessons
1. Insert lesson data into Supabase `lessons` table
2. Run `generate_missing_slugs()` if slug is missing
3. Update `video_url` field with MP4 file or YouTube URL
4. Add resources to `resources` table if needed

### Video Handling
- **Supported**: Direct MP4 files, YouTube URLs
- **Thumbnails**: Auto-generated from video first frame
- **Processing**: `get_video_thumbnail()` function

## Frontend Architecture

### JavaScript Components
- **Navigation**: Module collapsing, lesson switching (embedded ~line 2000+)
- **Responsive**: Mobile dropdown, sidebar toggle
- **Theme**: Light/dark mode with localStorage persistence
- **Media**: Video player with custom thumbnails

### CSS Structure
- **Location**: Embedded in PHP file (~lines 500-1500)
- **Theming**: CSS custom properties in `:root` and `.dark-theme`
- **Responsive**: Breakpoints at 768px and 1200px

## Common Modification Patterns

### Theme Updates
1. Modify CSS custom properties in `:root` selector
2. Update `.dark-theme` variables for dark mode
3. Adjust responsive breakpoints in media queries

### Adding Features
1. Add database fields to relevant fetch functions
2. Update HTML rendering logic in PHP
3. Add corresponding JavaScript for interactivity
4. Update CSS for styling

### Database Schema Changes
1. Update Supabase table structure
2. Modify fetch functions to include new fields
3. Update content processing functions as needed

## File Structure

- `course-access-V60.php` - Main application (current version)
- `course-access-V58.php` - Previous version with sidebar positioning
- `course-access-V59.php` - Previous version  
- `V57.php` - Older version

**Note**: Only work with V60 unless specifically requested to modify older versions.