# Stock Price API Setup Guide

This application uses Alpha Vantage API to fetch real-time stock prices for companies with ticker symbols.

## Getting Started

### 1. Get Alpha Vantage API Key

1. Visit [Alpha Vantage](https://www.alphavantage.co/support/#api-key)
2. Sign up for a free account
3. Get your API key from the dashboard

### 2. Configure Environment Variables

Add your API key to your `.env` file:

```env
ALPHA_VANTAGE_API_KEY=your_api_key_here
```

### 3. Test the Setup

Run the test command to verify everything is working:

```bash
php artisan test:stock-price
```

### 4. Manual Stock Price Fetch

To manually fetch stock prices for all companies:

```bash
php artisan stocks:fetch
```

## Features

### Automatic Stock Price Fetching

- **Frequency**: Every 60 seconds (1 minute)
- **Companies**: All active companies with ticker symbols
- **Data Stored**: Price, open, high, low, close, volume, change, change percentage

### Dashboard Integration

The dashboard now shows:
- Current stock price
- Price change percentage (green for positive, red for negative)
- "No data" if no price data is available

### Historical Data

All stock prices are stored with timestamps, allowing for:
- Historical price analysis
- Price trend tracking
- Performance monitoring over time

## API Limits

**Free Tier Limits:**
- 5 API requests per minute
- 500 requests per day

**Rate Limiting:**
The application handles rate limiting gracefully and will log warnings when limits are reached.

## Database Schema

The `stock_prices` table stores:
- `company_id` - Foreign key to companies table
- `price` - Current stock price
- `open` - Opening price
- `high` - High price
- `low` - Low price
- `close` - Closing price
- `volume` - Trading volume
- `change` - Price change amount
- `change_percent` - Price change percentage
- `price_at` - Timestamp when price was recorded

## Troubleshooting

### Common Issues

1. **"API key not configured"**
   - Make sure `ALPHA_VANTAGE_API_KEY` is set in your `.env` file

2. **"Rate limit exceeded"**
   - Wait for the rate limit to reset (1 minute for free tier)
   - Consider upgrading to a paid plan for higher limits

3. **"No stock data found"**
   - Verify the ticker symbol is correct
   - Check if the stock is actively traded

4. **"No companies with tickers found"**
   - Make sure companies have ticker symbols set in the database
   - Run the seeder to add companies with tickers

### Logs

Check the Laravel logs for detailed error messages:
```bash
tail -f storage/logs/laravel.log
```

## Adding New Companies

To add a new company with stock tracking:

1. Add the company to the database with a ticker symbol
2. The system will automatically start fetching stock prices
3. Prices will appear on the dashboard within 1 minute

## Monitoring

The system logs all stock price fetching activities:
- Successful fetches
- API errors
- Rate limit warnings
- Data storage issues

Monitor the logs to ensure the system is working correctly.
