# Social Media Management Plugin

A comprehensive Laravel plugin for managing and scheduling social media posts across multiple platforms with analytics, calendar views, and AI-powered caption generation.

## Features

### 📱 Multi-Platform Support
- **Facebook**: Schedule posts with media attachments
- **Instagram**: Post scheduling with image and video support  
- **LinkedIn**: Professional content scheduling
- **Pinterest**: Pin scheduling with media support
- **Multi-Platform Publishing**: Post to multiple platforms simultaneously

### 📅 Advanced Scheduling
- **Immediate Publishing**: Publish posts instantly across selected platforms
- **Scheduled Posts**: Schedule posts for future publishing with timezone support
- **Calendar View**: Visual calendar interface for managing scheduled content
- **Bulk Operations**: Delete multiple posts at once
- **Queue Management**: Automatic processing of scheduled posts via cron jobs

### 📊 Analytics & Insights  
- **Performance Tracking**: Monitor post success rates across platforms
- **Platform Analytics**: Compare performance between different social platforms
- **Daily Activity Charts**: Visualize posting activity over time
- **Status Distribution**: Track published, failed, and pending posts
- **Peak Hours Analysis**: Identify optimal posting times
- **Comprehensive Dashboard**: Overview of all social media activities

### 🎨 Content Management
- **Media Library**: Upload and manage images and videos with Spatie Media Library
- **AI Caption Generation**: Generate engaging captions using AI with custom prompts
- **Content Templates**: Create posts with rich text formatting
- **Draft Management**: Save and edit posts before publishing
- **Media Attachments**: Support for multiple media files per post

### 🤖 Automation Features
- **Auto-Publishing**: Automatically publish scheduled posts via command
- **Response Logging**: Detailed logging of platform publishing responses
- **Status Tracking**: Real-time status updates for all posts
- **Error Handling**: Robust error handling with retry mechanisms
- **Platform Validation**: Verify credentials before publishing

### 🔗 Integration Features
- **Settings Management**: Configure platform credentials and settings
- **User Permissions**: Role-based access control for different operations
- **Search & Filter**: Advanced filtering by status, platform, and date ranges
- **Export Capabilities**: View detailed post analytics and logs
- **Responsive Design**: Mobile-friendly interface

## Installation

1. Place the plugin in your `Plugins/SocialMediaManagement` directory
2. The plugin will auto-register and publish assets
3. Run migrations (handled automatically)
4. Configure social media credentials in settings

## Models

### SocialPost
- **Attributes**: caption, platforms (JSON), status, scheduled_at, published_at, response_logs, user_id
- **Relationships**: belongsTo User, media attachments via Spatie Media Library
- **Status Types**: pending, scheduled, published, failed, partially_published
- **Scopes**: Platform-specific filtering, date-based queries

### Settings Integration
- **Social Settings**: Platform credentials stored in settings table
- **Timezone Support**: User timezone handling for scheduling
- **Media Configuration**: File upload and storage settings

## Routes

### Post Management Routes
- `GET /social-media-scheduler/` - Main dashboard listing all posts
- `GET /social-media-scheduler/create` - Create new post form
- `POST /social-media-scheduler/post` - Save and publish/schedule post
- `GET /social-media-scheduler/edit/{id}` - Edit existing post
- `POST /social-media-scheduler/update/{id}` - Update post
- `GET /social-media-scheduler/posts/{id}` - View post details (JSON)
- `DELETE /social-media-scheduler/destroy/{id}` - Delete single post
- `POST /social-media-scheduler/destroy_multiple` - Delete multiple posts
- `GET /social-media-scheduler/list` - AJAX post listing with filters

### AI & Content Routes
- `POST /social-media-scheduler/ai/generate-caption` - AI caption generation
- **Features**: Custom prompts, platform optimization, HTML formatting

### Calendar Routes
- `GET /social-media-scheduler/calendar` - Calendar view interface
- `GET /social-media-scheduler/calendar-data` - Calendar data (month/week views)
- `GET /social-media-scheduler/calendar-stats` - Calendar statistics
- `GET /social-media-scheduler/posts-by-date` - Posts for specific date

### Analytics Routes
- `GET /social-media-scheduler/analytics` - Analytics dashboard
- `GET /social-media-scheduler/analytics/data` - Comprehensive analytics data
- `GET /social-media-scheduler/analytics/trends` - Posting trends analysis

### Settings Routes
- `GET /social-media-scheduler/social-settings` - Platform settings management
- `POST /social-media-scheduler/social-settings/update` - Update credentials

## Commands

### Main Publishing Command
```bash
php artisan social:publish-scheduled
```
**Functionality**:
- Processes all scheduled posts due for publishing
- Handles multi-platform publishing
- Logs detailed responses from each platform
- Updates post status based on success/failure
- Runs automatically every minute via cron

### Command Features
- **Error Handling**: Graceful handling of platform API errors
- **Response Logging**: Detailed logging of all publishing attempts
- **Status Management**: Updates post status (published, failed, partially_published)
- **Platform Validation**: Verifies credentials before attempting to publish

## Platform Integration

### Facebook
- **Content Types**: Posts with text, images, videos
- **Features**: Multi-media support, HTML caption cleaning
- **Analytics**: Success/failure tracking

### Instagram  
- **Content Types**: Posts with images and videos
- **Features**: Caption optimization, media validation
- **Analytics**: Publishing success rates

### LinkedIn
- **Content Types**: Professional posts and updates
- **Features**: Business-focused content formatting
- **Analytics**: Platform-specific success tracking

### Pinterest
- **Content Types**: Pins with images
- **Features**: Board management, image optimization
- **Analytics**: Pin performance tracking

## Key Features in Detail

### Post Status Management
- **pending**: Ready for immediate publishing
- **scheduled**: Set for future publishing
- **published**: Successfully published to all platforms  
- **failed**: Failed to publish to any platform
- **partially_published**: Published to some but not all platforms

### AI Caption Generation
- **Custom Prompts**: User-defined prompts for specific content
- **Auto-Generation**: AI-powered caption improvement
- **Platform Optimization**: Content adapted for different platforms
- **HTML Formatting**: Rich text support with proper formatting
- **Character Limits**: Platform-specific character counting

### Calendar Management
- **Month View**: Monthly overview of all scheduled content
- **Week View**: Detailed weekly scheduling view  
- **Day View**: Individual day post management
- **Quick Actions**: Edit, delete, and publish from calendar
- **Status Indicators**: Visual status indicators for each post

### Analytics Dashboard
- **Overall Statistics**: Total posts, success rates, platform breakdown
- **Daily Activity**: 30-day posting activity charts
- **Platform Performance**: Success rates per platform
- **Peak Hours**: Optimal posting time analysis
- **Recent Activity**: Latest post activities and status updates

### Media Management
- **Multiple Uploads**: Support for multiple media files per post
- **File Types**: Images (jpg, jpeg, png, gif) and videos (mp4, mov, avi)
- **Size Limits**: Configurable file size limits (default 10MB)
- **Storage**: Integration with Spatie Media Library
- **Preview**: Media preview in post details

## Permissions & Access Control

- **Admin/All Data Access**: Full CRUD operations on all posts
- **Regular Users**: Can only manage their own posts
- **Role-Based Features**: Different access levels for different user roles

## Database Tables

- `social_posts` - Main post records with JSON platform data
- `media` - Media files managed by Spatie Media Library
- `settings` - Social media platform credentials and configuration

## Configuration

### Required Settings
```php
// Social media platform credentials stored in settings
'social_settings' => [
    'facebook' => ['access_token' => '...'],
    'instagram' => ['access_token' => '...'],
    'linkedin' => ['access_token' => '...'],
    'pinterest' => ['access_token' => '...']
]
```

### Scheduling Configuration
- **Cron Setup**: Required for automated publishing
- **Timezone Handling**: User timezone support via settings
- **Queue Configuration**: Laravel queue for background processing

## Requirements

- Laravel Framework 8.0+
- Spatie Media Library for file management
- Carbon for date/time handling
- Guzzle HTTP Client for API requests
- Cron jobs for scheduled publishing
- AI service integration for caption generation

## Error Handling & Logging

- **Comprehensive Logging**: All publishing attempts logged with details
- **Error Recovery**: Graceful handling of platform API failures
- **Response Storage**: Detailed API responses stored in `response_logs`
- **Status Updates**: Real-time status updates based on publishing results
- **Debug Support**: Detailed error information in debug mode

## Security Features

- **Credential Protection**: Encrypted storage of platform credentials
- **Input Validation**: Comprehensive validation of all user inputs
- **File Security**: Secure file upload handling
- **XSS Prevention**: HTML sanitization for user content
- **Permission Checks**: Role-based access controls throughout

## Version

Current version information is stored in `plugin.json`

---

*This plugin provides a complete social media management solution with intelligent scheduling, comprehensive analytics, AI-powered content creation, and reliable multi-platform publishing capabilities.*

