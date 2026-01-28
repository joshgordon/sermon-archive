# Sermon Archive Web Interface

A dynamic web interface for browsing sermon archives (or any directory structure of MP3 files). Automatically reads ID3 tags and renders markdown readme files.

## Features

- Reads ID3 tags (title, artist, comment) from MP3 files
- Two-column layout for directories containing only subdirectories
- Markdown rendering for `readme.md` files in any directory
- Featured content section on the home page via `featured.csv`
- Responsive Bootstrap-based design
- Pure PHP - no external dependencies required

## Requirements

- PHP 8.0 or higher
- Web server (Apache, Nginx, etc.)

## Quick Start with Docker

The easiest way to run the archive locally for development:

```bash
# Clone the repository
git clone https://github.com/joshgordon/sermon-archive.git
cd sermon-archive

# Start the development server
docker compose up

# Visit http://localhost:8080
```

To use your own sermon files, edit `docker-compose.yml` and update the volume mount:

```yaml
volumes:
  - /path/to/your/sermons:/data/spep/spepmedia.com
```

## Manual Installation

1. Copy all PHP files to your web server document root
2. Configure your web server to route all requests through `index.php`
3. Update the `$sdir` variable in `index.php` to point to your sermon directory:
   ```php
   $sdir = '/path/to/your/sermons/';
   ```

### Nginx Configuration

See `nginx-config` for a sample Nginx configuration. Key points:

- Route all non-file requests to `index.php`
- Optionally expose the raw files at `/sermons/` with autoindex

### Apache Configuration

Create a `.htaccess` file in your document root:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

## Directory Structure

Organize your sermon files in a hierarchical structure:

```
/sermons/
  readme.md              # Optional - rendered on home page
  featured.csv           # Optional - featured items on home page
  2024/
    readme.md            # Optional - rendered when viewing 2024/
    Spring Series/
      sermon1.mp3
      sermon2.mp3
    Fall Series/
      sermon1.mp3
  2023/
    ...
```

## Special Files

### readme.md

Place a `readme.md` file in any directory to display formatted content above the file listing. Useful for series descriptions, speaker bios, etc.

### featured.csv

Place a `featured.csv` in the root directory to display featured content on the home page:

```csv
title,link,image,pastor
"Summer Series 2024",/2024/Summer,/images/summer.jpg,"Pastor Smith"
"Christmas Messages",/2023/Christmas,/images/christmas.jpg,"Pastor Jones"
```

## MP3 ID3 Tags

The archive reads the following ID3 tags from MP3 files:

| Tag | Display |
|-----|---------|
| Title | Sermon title (falls back to filename) |
| Artist | Pastor/Artist column |
| Comment | Comments column |

Both ID3v1 and ID3v2 tags are supported, with v2 taking precedence.

## Project Structure

```
sermon-archive/
  index.php           # Main application entry point
  functions.php       # Utility functions
  Parsedown.php       # Markdown parser library
  style.css           # Custom styles
  getid3/             # ID3 tag reading library
  nginx-config        # Sample Nginx configuration
  Dockerfile          # Docker image for development
  docker-compose.yml  # Docker Compose configuration
```

## Development

### Running Tests Locally

```bash
# Build and run with Docker
docker compose up --build

# View at http://localhost:8080
```

The Docker setup mounts source files as volumes, so changes to PHP files are reflected immediately without rebuilding.

### Code Style

The codebase uses modern PHP 8+ features:
- Strict types (`declare(strict_types=1)`)
- Type declarations for parameters and return types
- Null coalescing operator (`??`)
- Arrow functions
- Match expressions

## License

This project includes:
- [getID3](https://www.getid3.org/) - for reading MP3 metadata
- [Parsedown](https://parsedown.org/) - for Markdown rendering

## Live Demo

See a live demo at http://archive.spepmedia.com
