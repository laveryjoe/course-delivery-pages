# Course Delivery SPA - Self Host Revolution

A static, JSON-first course delivery page that works without any server-side code. Perfect for self-hosting your online courses.

## Quick Start

1. **Edit your course content** in `course-data.json`
2. **Upload both files** to any web server:
   - `index.html`
   - `course-data.json`
3. **Visit your URL** - it just works!

## Files

| File | Description |
|------|-------------|
| `index.html` | The complete SPA (Single Page Application) |
| `course-data.json` | Your course content (modules, lessons, resources) |
| `README.md` | This documentation |

## Configuration

Open `index.html` and find the `CONFIG` object near the top of the script section:

```javascript
const CONFIG = {
    // DATA SOURCE: 'json' or 'supabase'
    DATA_SOURCE: 'json',

    // JSON file path (used when DATA_SOURCE = 'json')
    JSON_FILE: 'course-data.json',

    // Supabase config (used when DATA_SOURCE = 'supabase')
    SUPABASE_URL: 'https://your-project.supabase.co',
    SUPABASE_KEY: 'your-anon-key',

    // Course configuration
    ADVANCED_COURSE_MODE: true,  // true = accordion navigation, false = flat list
    USE_VIDEO_THUMBNAILS: true,
    AUTO_GENERATE_THUMBNAILS: true,
    DEFAULT_THEME_MODE: 'light', // 'light' or 'dark'
    SIDEBAR_POSITION: 'right',   // 'left' or 'right'
};
```

### Configuration Options

| Option | Values | Description |
|--------|--------|-------------|
| `DATA_SOURCE` | `'json'` or `'supabase'` | Where to load course data from |
| `ADVANCED_COURSE_MODE` | `true` / `false` | Accordion modules vs flat list |
| `SIDEBAR_POSITION` | `'left'` / `'right'` | Which side to show the module list |
| `DEFAULT_THEME_MODE` | `'light'` / `'dark'` | Default color theme |
| `USE_VIDEO_THUMBNAILS` | `true` / `false` | Show video thumbnails |
| `AUTO_GENERATE_THUMBNAILS` | `true` / `false` | Auto-extract video first frame |

## Course Data Structure

Edit `course-data.json` to add your own content:

```json
{
  "course": {
    "title": "Your Course Title",
    "subtitle": "A compelling tagline",
    "instructor": "Your Name",
    "image": "url-to-course-thumbnail.jpg"
  },
  "modules": [
    {
      "id": 1,
      "order_index": 1,
      "title": "Module 1: Getting Started",
      "description": "What this module covers",
      "lessons": [
        {
          "id": 101,
          "order_index": 1,
          "slug": "welcome-lesson",
          "title": "Welcome",
          "description": "Introduction to the course",
          "duration_minutes": 10,
          "video_url": "https://youtube.com/watch?v=...",
          "thumbnail_url": "",
          "status": "available",
          "notes_markdown": "## Lesson Content\n\nYour notes in Markdown...",
          "transcript": "Full transcript text here...",
          "learning_objectives": [
            "Objective 1",
            "Objective 2"
          ],
          "resources": [
            {
              "title": "Resource Name",
              "url": "https://example.com",
              "description": "Why this resource helps"
            }
          ]
        }
      ]
    }
  ]
}
```

### Lesson Status Options

| Status | Description |
|--------|-------------|
| `available` | Lesson is ready to watch |
| `coming-soon` | Shows "Coming Soon" badge, grayed out |
| `locked` | Not accessible (for future use) |

### Supported Video Types

- **YouTube**: Any youtube.com or youtu.be URL
- **Vimeo**: Any vimeo.com URL
- **Bunny.net**: Stream URLs from bunny.net/b-cdn.net
- **Direct video**: .mp4, .webm, .ogg, etc.
- **Images**: .jpg, .png, .gif, etc. (as placeholder)

## Customization

### Changing Colors

Find the CSS variables in the `<style>` section:

```css
:root {
    --blue-primary: #0046dd;    /* Main accent color */
    --bg-primary: #fafafa;      /* Page background */
    --text-primary: #1f2937;    /* Main text color */
    /* ... more variables */
}
```

### Changing the Logo

Find the logo images in the header and update the URLs:

```html
<img src="your-logo-light.png" class="logo light">
<img src="your-logo-dark.png" class="logo dark">
```

### Changing Fonts

1. Get a font link from [Google Fonts](https://fonts.google.com)
2. Add it to the `<head>` section
3. Update the `font-family` in the CSS

## Upgrading to Supabase

When you're ready for a database backend:

1. Create a Supabase project at [supabase.com](https://supabase.com)
2. Create tables matching the JSON structure
3. Update `CONFIG`:

```javascript
const CONFIG = {
    DATA_SOURCE: 'supabase',
    SUPABASE_URL: 'https://your-project.supabase.co',
    SUPABASE_KEY: 'your-anon-key',
    // ...
};
```

### Supabase Table Schema

```sql
-- Courses table
CREATE TABLE courses (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    subtitle TEXT,
    instructor TEXT,
    image_url TEXT
);

-- Modules table
CREATE TABLE modules (
    id SERIAL PRIMARY KEY,
    course_id INTEGER REFERENCES courses(id),
    order_index INTEGER,
    title TEXT NOT NULL,
    description TEXT
);

-- Lessons table
CREATE TABLE lessons (
    id SERIAL PRIMARY KEY,
    module_id INTEGER REFERENCES modules(id),
    order_index INTEGER,
    slug TEXT UNIQUE,
    title TEXT NOT NULL,
    description TEXT,
    duration_minutes INTEGER,
    video_url TEXT,
    thumbnail_url TEXT,
    status TEXT DEFAULT 'available',
    episode_notes_markdown TEXT,
    transcript_text TEXT,
    learning_objectives TEXT[]
);

-- Resources table
CREATE TABLE resources (
    id SERIAL PRIMARY KEY,
    lesson_id INTEGER REFERENCES lessons(id),
    title TEXT NOT NULL,
    url TEXT NOT NULL,
    description TEXT
);
```

## Features

- **No server-side code required** - Pure HTML/CSS/JS
- **Responsive design** - Works on all devices
- **Dark/light mode** - User preference saved
- **Progress tracking** - LocalStorage-based completion
- **Transcript search** - Find content quickly
- **URL routing** - Shareable lesson links
- **Coming soon lessons** - Drip content support
- **Markdown notes** - Easy content formatting

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Hosting Options

This works on ANY web server:

- **Contabo VPS** ($6/month) - Full control
- **Netlify** (free) - Easy drag & drop
- **Vercel** (free) - Git-based deploys
- **GitHub Pages** (free) - For public courses
- **Any shared hosting** - Just upload files

## AI Course Generator Prompt

Use this prompt with ChatGPT or Claude to generate course content:

```
You are a course curriculum designer. Create a complete course outline for:

**Course Topic:** [YOUR TOPIC]
**Target Audience:** [WHO IS THIS FOR]
**Number of Modules:** [4-8 recommended]
**Lessons per Module:** [3-6 recommended]

For each lesson provide:
- Title and SEO-friendly slug
- Description (2-3 sentences)
- Duration estimate in minutes
- Notes in Markdown (200-400 words)
- Learning objectives (3-5 points)
- Resources (relevant links)

Output as JSON matching this structure:
[paste the course-data.json structure]
```

## Troubleshooting

### Course data not loading

- Check browser console for errors
- Verify `course-data.json` is valid JSON (use jsonlint.com)
- Ensure files are in the same directory

### Videos not playing

- Check the video URL is accessible
- YouTube/Vimeo URLs auto-convert to embeds
- Direct video files need correct MIME types on server

### Styles look broken

- Clear browser cache
- Check for CSS syntax errors
- Verify font URLs are loading

## License

Free for personal and commercial use. Built with Claude Code.

---

*Part of the Self Host Revolution course by Joe Lee*
