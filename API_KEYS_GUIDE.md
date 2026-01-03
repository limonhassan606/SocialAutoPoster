# 🔑 Social Media API Configuration Guide

This guide provides step-by-step instructions to obtain the necessary API keys and access tokens for all supported platforms.

---

## 📋 Table of Contents

1. [Facebook & Instagram](#1-facebook--instagram)
2. [Twitter / X](#2-twitter--x)
3. [LinkedIn](#3-linkedin)
4. [YouTube](#4-youtube)
5. [Telegram](#5-telegram)
6. [TikTok](#6-tiktok)
7. [Pinterest](#7-pinterest)

---

## 1. Facebook & Instagram

**Required .env variables:**
- `FACEBOOK_ACCESS_TOKEN`
- `FACEBOOK_PAGE_ID`
- `INSTAGRAM_ACCESS_TOKEN`
- `INSTAGRAM_ACCOUNT_ID`

### Steps:
1. Go to [Meta for Developers](https://developers.facebook.com/).
2. Click **My Apps** > **Create App**.
3. Select **Business** or **Other** as the app type.
4. Add **Facebook Login for Business** to your app.
5. In the left (or settings) menu, link your **Facebook Page** and **Instagram Business Account**.
6. **Get Access Token (Development):**
   - Go to **Tools** > **Graph API Explorer**.
   - Select your App.
   - Under "User or Page", select **Get Page Access Token**.
   - Add Permissions: `pages_manage_posts`, `pages_read_engagement`, `instagram_basic`, `instagram_content_publish`.
   - Click **Generate Access Token**.
   - **Crucial:** Once generated, click the Info (i) icon next to the token -> "Open in Access Token Tool" -> "Extend Access Token" to get a long-lived token (lasts 60 days).
7. **Get Page ID:**
   - Go to your Facebook Page > **About** > **Page Transparency** (or look at the URL for the number).
   - Or uses Graph Explorer: `GET /me/accounts` to see ID.
8. **Get Instagram Account ID:**
   - In Graph Explorer, use `GET /me/accounts?fields=instagram_business_account` to find the ID linked to your page.

---

## 2. Twitter / X

**Required .env variables:**
- `TWITTER_API_KEY`
- `TWITTER_API_SECRET`
- `TWITTER_ACCESS_TOKEN`
- `TWITTER_ACCESS_TOKEN_SECRET`
- `TWITTER_BEARER_TOKEN`

### Steps:
1. Go to the [X Developer Portal](https://developer.twitter.com/en/portal/dashboard).
2. Sign up for a **Free** or **Basic** (paid) account. *Note: Free tier has very limited posting capabilities (write-only).*
3. Create a **Project** and an **App**.
4. Go to **App Settings** > **User authentication settings**.
   - Enable **OAuth 1.0a**.
   - App permissions: Select **Read and Write**.
   - Type of App: **Web App, Automated App or Bot**.
   - Callback URI / Website URL: You can use `https://example.com` if testing locally.
5. Go to **Keys and Tokens** tab.
   - **Consumer Keys** -> API Key & Secret.
   - **Authentication Tokens** -> Generate Access Token & Secret (Make sure you set permissions to "Read and Write" *before* generating this).

---

## 3. LinkedIn

**Required .env variables:**
- `LINKEDIN_ACCESS_TOKEN`
- `LINKEDIN_PERSON_URN` (for personal profile)
- `LINKEDIN_ORGANIZATION_URN` (for company page)

### Steps:
1. Go to [LinkedIn Developers](https://www.linkedin.com/developers/).
2. Click **Create App**.
3. Link your LinkedIn Company Page (required for creating an app).
4. In **Products** tab, Request Access for:
   - **Share on LinkedIn** (or Marketing API).
   - **Sign In with LinkedIn**.
5. Go to **Auth** tab.
   - Note your Client ID and Client Secret.
   - Use the **OAuth 2.0 Tools** link (developer tool) to generate a temporary `LINKEDIN_ACCESS_TOKEN` for testing.
6. **Get URNs:**
   - **Person URN:** Go to your profile. The ID is in the URL (not always reliable) or make a request to `GET https://api.linkedin.com/v2/me`.
   - **Organization URN:** Go to your Company Page admin view. The URL will look like `linkedin.com/company/123456/admin`. The `123456` is your URN ID.

---

## 4. YouTube

**Required .env variables:**
- `YOUTUBE_API_KEY`
- `YOUTUBE_ACCESS_TOKEN`
- `YOUTUBE_CHANNEL_ID`

### Steps:
1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new Project.
3. Search for **"YouTube Data API v3"** and **Enable** it.
4. Go to **Credentials** -> **Create Credentials** -> **API Key**. Copy this as `YOUTUBE_API_KEY`.
5. **Get Access Token (OAuth):**
   - Create **OAuth 2.0 Client ID** credentials.
   - Use [Google OAuth 2.0 Playground](https://developers.google.com/oauthplayground/).
   - Select "YouTube Data API v3" -> `https://www.googleapis.com/auth/youtube.upload`.
   - Exchange authorization code for tokens. Copy `Access Token`.
6. **Get Channel ID:**
   - Go to your YouTube Channel -> **Settings** -> **Advanced Settings**.
   - Copy **Channel ID**.

---

## 5. Telegram

**Required .env variables:**
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`

### Steps:
1. Open Telegram app and search for **@BotFather**.
2. Send command `/newbot`.
3. Follow instructions to name your bot.
4. BotFather will give you the **HTTP API Token**. Copy this as `TELEGRAM_BOT_TOKEN`.
5. **Get Chat ID (Channel/Group):**
   - Add your bot to the Channel or Group as an **Admin**.
   - Send a test message to the channel.
   - Visit `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates` in your browser.
   - Look for `"chat": { "id": -100123456789 ... }`. The number starting with `-100` is your `TELEGRAM_CHAT_ID`.

---

## 6. TikTok

**Required .env variables:**
- `TIKTOK_ACCESS_TOKEN`
- `TIKTOK_CLIENT_KEY`
- `TIKTOK_CLIENT_SECRET`

### Steps:
1. Register at [TikTok for Developers](https://developers.tiktok.com/).
2. Create an App. Select **"Content Posting API"** or **"Share to TikTok"** capability.
3. Submit for review (TikTok requires approval for posting APIs).
4. Once approved, get **Client Key** and **Client Secret**.
5. Use their OAuth flow to generate an `ACCESS_TOKEN`. (Note: TikTok tokens expire quickly and require refresh logic, handled by the package's underlying logic or manual refresh).

---

## 7. Pinterest

**Required .env variables:**
- `PINTEREST_ACCESS_TOKEN`
- `PINTEREST_BOARD_ID`

### Steps:
1. Go to [Pinterest Developers](https://developers.pinterest.com/).
2. Create an App.
3. Go to **Tools** -> **API Explorer**.
4. Select scopes: `boards:read`, `pins:read`, `pins:write`.
5. Generate `Access Token`.
6. **Get Board ID:**
   - Go to your board URL: `pinterest.com/username/board-name/`.
   - Or use API Explorer `GET /v5/boards` to list boards and copy the ID.
