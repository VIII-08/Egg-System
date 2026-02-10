# Python Setup Guide for Hostinger

## Problem
Your Hostinger server cannot find Python3, causing forecasting to fail with errors like:
- `sh: line 1: /usr/bin/python3: No such file or directory`
- `sh: line 1: /usr/local/bin/python3: No such file or directory`
- `exec: python3: not found`

## Solution Options

### Option 1: Find Python on Hostinger (Recommended First Step)

1. **Check if Python is installed** via Hostinger File Manager or SSH:
   - Log into Hostinger control panel
   - Open File Manager or use SSH
   - Run: `which python3` or `which python`
   - Run: `python3 --version` or `python --version`

2. **If Python is found**, add the path to your `.env` file:
   ```env
   PYTHON_PATH=/usr/bin/python3
   ```
   Or if it's just `python3`:
   ```env
   PYTHON_PATH=python3
   ```

### Option 2: Contact Hostinger Support

If Python is not installed, contact Hostinger support and ask:
- "Can you install Python 3.x on my shared hosting account?"
- "What is the path to Python3 on my server?"
- "Do you support Python for Laravel applications?"

**Note:** Some shared hosting plans don't include Python. You may need to:
- Upgrade to a VPS plan
- Use a different hosting provider
- Or disable forecasting feature

### Option 3: Disable Automatic Forecasting (Temporary Fix)

If Python is not available, you can disable automatic forecasting:

1. **Comment out the scheduler** in `routes/console.php`:
   ```php
   // Schedule::command('forecast:check-and-run')
   //     ->everyMinute()
   //     ->withoutOverlapping()
   //     ->runInBackground();
   ```

2. **The daily backup forecast** will still try to run at 2:00 AM, but it will fail gracefully and log the error.

### Option 4: Use Alternative Forecasting Method

If Python is not available, you could:
- Use PHP-based forecasting (simpler algorithms)
- Use JavaScript/Chart.js for client-side forecasting
- Use an external API for forecasting

## Updated Code Features

The code has been updated to:
1. ✅ **Auto-detect Python** - Tries common paths automatically
2. ✅ **Configurable via .env** - Set `PYTHON_PATH` in your `.env` file
3. ✅ **Better error logging** - Errors are logged to `storage/logs/laravel.log`
4. ✅ **Graceful failure** - System won't crash if Python is missing

## Testing

After setting up Python path, test it:

1. **Via SSH or Hostinger terminal:**
   ```bash
   php artisan forecast:run
   ```

2. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Expected output:**
   - ✅ Success: "Forecasting analysis completed successfully."
   - ❌ Failure: "Python executable not found" (with helpful message)

## Current Configuration

**Local (Windows):**
- Path: `C:\Users\VIII\AppData\Local\Programs\Python\Python310\python.exe`
- Auto-detected ✅

**Production (Hostinger):**
- Needs to be configured in `.env` file
- Add: `PYTHON_PATH=/path/to/python3` (or `python3` if in PATH)

## Next Steps

1. **Check Hostinger for Python** (Option 1 above)
2. **If found**, add `PYTHON_PATH` to your production `.env` file
3. **If not found**, contact Hostinger support (Option 2)
4. **Test** using `php artisan forecast:run`
5. **Monitor logs** to ensure it's working

---

**Last Updated:** Today
**Status:** Code updated, waiting for Python path configuration

