# Eden Ridge — using your dashboard

Everything on the public website can be changed from
**https://app.edenridgegh.com** — every word, number, photograph, phone number
and link. You never need a developer to change a sentence, and you can do it
from your phone.

---

## 1. Signing in

1. Go to **https://app.edenridgegh.com**.
2. Enter your email and password.
3. Tick *Keep me signed in* on your own phone or laptop; leave it unticked on a
   shared computer.

Forgotten your password? Click **Forgotten your password?** and we email you a
link that works once and expires after an hour.

You are signed out automatically after two hours of inactivity.

---

## 2. The screens

| Screen | What it is for |
|---|---|
| **Dashboard** | New enquiries at a glance, recent changes, quick links |
| **Home page** | The sixteen sections of the main website |
| **Privacy / Terms** | The two legal pages |
| **Media library** | Every photograph and render |
| **Gallery** | Which images appear in the filtered gallery, and under which filter |
| **Video tour** | Where the walkthrough is hosted, and who has requested it |
| **Enquiries** | Everyone who has contacted you |
| **Appearance** | Logo, favicon, site name |
| **Site settings** | Phone, WhatsApp, email, social accounts, email delivery |
| **SEO** | How the site appears in Google and when links are shared |
| **Users** | Who can sign in (administrators only) |
| **Activity log** | Who changed what, and when (administrators only) |
| **Tools** | Backups and cache (administrators only) |

---

## 3. Editing a section

1. Click **Home page** in the sidebar.
2. Pick a section from the list on the left — they are listed in the order they
   appear on the site.
3. Change whatever you need.
4. Choose one of the two buttons at the bottom:
   - **Save draft** — keeps your work privately. The public site does not change.
   - **Publish** — puts it live. Refresh the website and it is there.

A section with an unpublished draft shows a small amber dot in the list and a
**Draft** badge at the top.

### Writing headlines

Headlines use the site's two type styles. To set a word in the italic accent
face — the way *residences* is styled in the main headline — wrap it in
asterisks:

```
Bold, contemporary
*residences* for a
new generation
```

Each new line becomes a line break, exactly as you type it.

### Lists of things (repeaters)

Anything that appears more than once — navigation links, statistics, feature
bullets, amenities, FAQ questions, payment stages, footer links — is edited as a
list of rows:

- **Add** a row with the button at the bottom of the list.
- **Click a row's title bar** to open or close it.
- **Drag the handle** on the left to reorder, or use the ↑ ↓ buttons.
- **Duplicate** copies a row and everything in it.
- **Remove** deletes it (you are asked to confirm).

Do not forget to **Publish** afterwards.

### Swapping an image

Every image field has three buttons:

- **Choose image** — pick something already in the media library.
- **Upload** — take a new file straight from your computer or phone.
- **Remove** — clear the image.

Uploads are converted automatically into six sizes plus a WebP version, so the
site stays fast on mobile data. You do not need to resize anything first.

### Undoing a bad edit

Every publish is kept. Scroll to **Revision history** at the bottom of the
section, find the version you want, and click **Restore**. It is published
immediately. The last thirty versions of each section are kept.

### Hiding or reordering sections

- **Hide**: open the section and click *Hide this section* under **Section options**.
- **Reorder**: drag the handle beside a section in the left-hand list. The order
  saves as soon as you let go.

The header, hero, enquiry form and footer cannot be hidden.

---

## 4. Reading enquiries

Every form submission lands in **Enquiries** and is emailed to whoever is listed
under Site settings → *Send new enquiries to*.

- Click a name to open the full message.
- Set a **status** as you work the lead: New → Contacted → Qualified → Won / Lost.
  (Mark junk as Spam.)
- Use **Reply by email**, **Call** or **WhatsApp** — WhatsApp opens a chat with a
  greeting already written.
- Add **internal notes**. They are timestamped, attributed, and never sent to
  the enquirer.
- **Export CSV** downloads exactly the rows currently on screen, filters and all.
- **Move to trash** hides a record; the trash is emptied automatically after 30
  days, and anything in it can be restored before then.

The number beside *Enquiries* in the sidebar counts unread ones.

---

## 5. The video walkthrough

The walkthrough is far too large to live on the website's hosting, so it is
hosted on YouTube, Vimeo or Bunny and the site plays it from there.

1. Upload the video to YouTube as **Unlisted** (or to Vimeo/Bunny).
2. Copy its link.
3. In **Video tour**, choose the provider, paste the link, and save.

Leave **Ask for a name and email before playing** switched on. A visitor who
clicks *Request the Video Tour* fills in a short form; the video then plays
immediately *and* the link is emailed to them. Every request becomes an enquiry,
which is the whole point of having the walkthrough.

---

## 6. Contact details, socials and email

**Site settings** holds the phone number, WhatsApp number, email address and
postal address used by the WhatsApp button, the email footers and the
information Google reads about the business.

The contact block *inside* the enquiry section on the home page is edited
separately, under Home page → Enquiry form, so you can word it however you like.

Social handles go in Site settings. To show the icon row in the footer, add the
full URL there and switch on *Show the social icon row* under Home page → Footer.

**Email delivery** also lives here. Use **Send a test email** at the bottom of
the page after any change: if the test arrives, enquiry notifications will too.
If an email ever fails, it is retried automatically and a warning appears at the
top of the dashboard.

---

## 7. Backups

**Tools → Create & download** produces a zip of everything: the database and all
uploaded images. Keep a copy somewhere other than the server — your own laptop
or Google Drive is fine.

A database-only backup is taken automatically each day and seven days are kept
on the server.

To go back to a backup, use **Restore from a backup** on the same screen. It
replaces everything currently on the site, so it takes a typed confirmation —
and a safety backup is taken first, just in case.

---

## 8. Two things worth changing before launch

Both are inherited from the original build and are already editable:

1. **The phone number is a US number** (+1 404 931 7171). A Ghanaian number, or
   a WhatsApp Business number, reads as more local to buyers in Accra and Tema.
2. **The email address is a personal Outlook address.** A `sales@edenridgegh.com`
   address looks considerably more established, and improves the chance that
   your notification emails avoid the spam folder.

Both live in Site settings, and in the Enquiry form and Footer sections of the
home page.
