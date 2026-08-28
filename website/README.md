# Yogesh Web Developer - yogeshwebdeveloper.com

Professional React.js website for **yogeshwebdeveloper.com**

## Features

- Pure React + Vite (no WordPress)
- Brand: **Yogesh Web Developer** (yogeshwebdeveloper.com)
- Full SEO: meta tags, Open Graph, Schema.org, sitemap.xml, robots.txt
- 30 location SEO pages
- Secure contact form with email notifications
- Fully responsive

## Contact Details

- **Phone:** +91 83779 56442
- **Email:** contact@yogeshwebdeveloper.com
- **Owner notifications:** ygupta13@gmail.com

## Setup

```bash
cd website
npm install
npm run dev
```

## Build & Deploy

```bash
npm run build
```

Upload everything inside `dist/` folder to your hosting (cPanel / Apache).

**Important:** The contact form uses `api/contact.php` — make sure PHP mail is enabled on your hosting and `contact@yogeshwebdeveloper.com` is set as sender in cPanel.

### Contact Form Features

- Sends query to **ygupta13@gmail.com**
- Auto-reply confirmation email to customer
- Email & phone validation
- Honeypot anti-spam
- Rate limiting (1 submission per minute per IP)
- XSS / injection protection

### Social Media Links

Update links in `src/data/company.js` → `SOCIAL_LINKS` array (currently set to `#` placeholders).

## Google Indexing

Submit sitemap: `https://yogeshwebdeveloper.com/sitemap.xml` in Google Search Console.
