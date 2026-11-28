# MyMobileIndia — Word se WordPress (Draft) setup

Yeh guide **do writers** ke liye hai. Images WordPress mein baad mein add karenge.

---

## Part 1 — WordPress (admin / ek baar)

1. Plugin folder `mmi-word-publisher` ko `wp-content/plugins/` mein upload karein.
2. **Plugins → MMI Word Publisher → Activate**
3. **Settings → MMI Word Publisher** — manifest URL note karein.

### Har writer ke liye Application Password

1. **Users → All Users** → writer par click
2. Neeche **Application Passwords**
3. Name: `Word Article Transfer` → **Add New Application Password**
4. Jo password dikhe (jaise `abcd EFGH ijkl`) — writer ko ek baar bhejein (Word add-in mein spaces hata kar bhi chalega)

Writer role: **Author** ya **Editor** hona chahiye (`edit_posts` permission).

---

## Part 2 — Microsoft Word add-in (do tareeke)

### Option A — Centralized (recommended): dono writers ke PC par jaane ki zaroorat **nahi**

Agar aapke paas **Microsoft 365 Business** hai:

1. [Microsoft 365 admin center](https://admin.microsoft.com) → **Settings** → **Integrated apps** (ya **Deploy Office Add-ins**)
2. **Upload custom apps** → **Office Add-in manifest file** (URL se)
3. Manifest URL (site par plugin ke baad):

   `https://mymobileindia.com/wp-content/plugins/mmi-word-publisher/add-in/manifest.xml`

   > Agar domain alag hai, `manifest.xml` mein saari `https://mymobileindia.com` ko apni site se replace karein (Find & Replace).

4. Assignment: **Specific users/groups** → dono writers select karein
5. 15–60 minute baad Word restart → Home tab par **MyMobileIndia** group → **Send to MyMobileIndia**

Writers ko sirf task pane mein **ek baar** site URL + username + Application Password save karna hai.

### Option B — Har computer par manually (bina M365 admin)

Har writer apne Windows PC par:

1. Microsoft Word kholein
2. **Insert** → **Get Add-ins** (ya **Office Add-ins**)
3. **MY ADD-INS** tab → **Upload My Add-in** (ya **Add from URL** agar available ho)
4. File chunein: downloaded `manifest.xml`  
   **ya** URL: `https://mymobileindia.com/wp-content/plugins/mmi-word-publisher/add-in/manifest.xml`
5. Word band karke dubara kholein
6. **Home** → **Send to MyMobileIndia**

> Custom add-in Store mein search se **nahi** milega — upload / central deploy zaroori hai.

---

## Part 3 — Writer daily workflow

1. Word mein article likhein  
   - **Title**: pehla **Heading 1** ya **File → Info → Title**  
   - **Subheadings**: bold paragraph ya Heading 2/3  
   - **Links**: normal hyperlinks
2. **Home → Send to MyMobileIndia** (task pane khulega)
3. Pehli baar: Site URL, Username, Application Password → **Save settings** → **Test connection**
4. **Send to WordPress (Draft)**
5. WordPress **Posts → Drafts** — images yahan add karein, phir publish

---

## Troubleshooting

| Problem | Fix |
|--------|-----|
| 401 / not logged in | Application Password dubara banayein; username sahi ho |
| Add-in load nahi hota | Site **HTTPS** honi chahiye; manifest URLs sahi domain |
| Title galat | Heading 1 ya Document Title set karein |
| Button nahi dikhta | Word update; add-in **My Add-ins** mein enabled check |

REST test (browser nahi — Postman/curl):

```bash
curl -u "writer:APPLICATION_PASSWORD" \
  "https://mymobileindia.com/wp-json/mmi-word-publisher/v1/ping"
```

---

## Files in this plugin

| File | Kaam |
|------|------|
| `mmi-word-publisher.php` | Plugin bootstrap |
| `includes/class-rest.php` | Draft API |
| `add-in/manifest.xml` | Word ribbon + task pane |
| `add-in/taskpane.html/js` | Send button UI |

**Note:** `manifest.xml` mein icons `add-in/assets/icon-*.png` point karte hain. Simple 16/32/80 px PNG upload karein, ya manifest se IconUrl lines hata dein (Word default icon use karega).
