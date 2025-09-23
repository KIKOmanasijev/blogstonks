# Telegram Bot Setup for Blog Notifications

This application sends Telegram notifications when new blog posts are detected. Here's how to set it up:

## Step 1: Create a Telegram Bot

1. **Open Telegram** and search for `@BotFather`
2. **Start a chat** with BotFather
3. **Send the command**: `/newbot`
4. **Choose a name** for your bot (e.g., "Stock Blogs Monitor")
5. **Choose a username** for your bot (must end with 'bot', e.g., "stockblogs_monitor_bot")
6. **Copy the bot token** that BotFather gives you (looks like: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

## Step 2: Get Your Chat ID

### Method 1: Using @userinfobot (Recommended)
1. **Search for** `@userinfobot` in Telegram
2. **Start a chat** with the bot
3. **Send any message** (like "hello")
4. **Copy your chat ID** from the response (looks like: `123456789`)

### Method 2: Using @getidsbot
1. **Search for** `@getidsbot` in Telegram
2. **Start a chat** with the bot
3. **Send any message**
4. **Copy your chat ID** from the response

### Method 3: Using your bot
1. **Start a chat** with your newly created bot
2. **Send any message** to your bot
3. **Visit this URL** in your browser (replace `YOUR_BOT_TOKEN` with your actual token):
   ```
   https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates
   ```
4. **Look for** `"chat":{"id":123456789` in the response
5. **Copy the ID number**

## Step 3: Configure Your Application

Add these lines to your `.env` file:

```bash
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=123456789
```

Replace the values with your actual bot token and chat ID.

## Step 4: Test the Setup

Run the test command to verify everything works:

```bash
# Test single post notification
php artisan test:telegram-notification

# Test multiple posts notification
php artisan test:telegram-notification --type=multiple
```

## How It Works

- **Single New Post**: When exactly 1 new post is detected, a detailed notification is sent with the post title, content preview, and link
- **Multiple New Posts**: When 2+ new posts are detected, a summary notification is sent with the count and links

## Notification Features

- **Rich Formatting**: Uses HTML formatting for beautiful messages
- **Company Emojis**: Each company gets a unique emoji (⚛️ for IonQ, etc.)
- **Content Preview**: Shows a preview of the blog post content
- **Direct Links**: Clickable links to the blog post and company website
- **Timestamps**: Shows when the post was published
- **Error Handling**: Graceful fallback if Telegram is unavailable

## Troubleshooting

### No notifications received
1. Check that `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID` are set in your `.env` file
2. Verify the bot token is correct
3. Make sure you've started a chat with your bot
4. Check the application logs: `tail -f storage/logs/laravel.log`

### Bot token not working
1. Verify the token with BotFather: `/mybots` → select your bot → "API Token"
2. Make sure there are no extra spaces in your `.env` file
3. Test the token manually: `curl "https://api.telegram.org/botYOUR_TOKEN/getMe"`

### Chat ID not working
1. Make sure you've sent at least one message to your bot
2. Try getting your chat ID again using the methods above
3. For group chats, you need to add the bot to the group and use the group's chat ID

### Test notifications work but real ones don't
1. Ensure the scraper is running: `php artisan scrape:blogs`
2. Check if new posts are actually being detected
3. Verify the scheduler is running: `php artisan schedule:work`

## Example Notification

When a new post is detected, you'll receive a message like:

```
⚛️ New Blog Post from IonQ!

📝 IonQ's Accelerated Roadmap: Turning Quantum Ambition into Reality

📄 IonQ announces significant progress in quantum computing development...

🔗 Read full post
🏢 IonQ Website
📅 Published: June 13, 2025 at 2:30 PM
```
