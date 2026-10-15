# Flowork Agency Software 🚀

Apni digital marketing agency ka complete management system — clients, projects, milestone payments, invoices (print + PDF), expenses, project-wise profit, team, reports aur settings. Sab kuch ek jagah.

## Features

1. **Dashboard** — Is mahine ki earning, total earning, total expenses, net profit (earning − expenses), pending payments, active projects, earning vs expenses chart, aanay wali payments, project profitability.
2. **Clients** — Client add karein (naam, company, phone, WhatsApp, email, address). Har client ka profile: total business, collected, remaining.
3. **Projects** — Project banayein: client, service, total amount, deadline, status. Har project ka apna detail page.
4. **Payment Plan (Milestones)** — Project ki payment 3-4 hisson mein: Advance / Mid / Final. Har milestone: amount, due date, paid/unpaid.
5. **Invoices** — Milestone se ya manual invoice. Invoice number auto-generate. Invoice par: logo, client detail, service, project total, pehle kitni li, is invoice ki amount, baaki kitni — aur "Thank you" note. Print + PDF download.
6. **Payments** — Har payment ka record (cash/bank/Easypaisa/JazzCash). Invoice aur milestone khud update ho jatay hain.
7. **Expenses** — Kharchay categories ke saath. Expense ko project se link karein taake **project ka asal profit** nikle (collected − project expenses).
8. **Team** — Team members ke login accounts (admin / staff). Project par team assign karein.
9. **Reports** — Monthly earning/expenses/profit, project profitability, pending payments, client-wise business.
10. **Settings** — Logo upload, agency naam/tagline, phone/WhatsApp/email/address, invoice prefix + next number, currency, payment details, thank-you note.

## Install (Hostinger) — 5 minute

1. **ZIP upload karein:** Hosting panel → File Manager → `public_html` kholein → `flowork-agency.zip` upload karein → right-click → **Extract** → phir ZIP file **delete** kar dein.
2. **Database banayein:** Hostinger panel → **Databases** → **MySQL Databases** → naya database + user banayein, user ko database se link kar dein. Naam, user aur password note kar lein.
3. **Installer kholein:** Browser mein `apki-site.com/install.php` kholein.
   - Step 1: database details dein → **Test Connection** → **Save & Continue**
   - Step 2: **Install Now** (tables khud ban jayengi)
   - Step 3: admin account banayein (naam, email, password)
   - Step 4: **Delete install.php** ka button dabayein
4. **Login karein:** `apki-site.com/login.php` → admin email + password.

Bas! Koi SQL file manually import karne ki zaroorat NAHI hai.

## Login mein masla aaye to

- Check karein `config/config.php` file bani hui hai (installer ne banayi hogi).
- Database user ka password theek hai? Step 1 par Test Connection dobara chalayein: `install.php?fresh=1` (⚠️ fresh install purana data delete kar dega — sirf tab jab abhi koi data nahi).
- Password bhool gaye? Mujh se (Muse) password-reset file banwa lein.
- **Hard refresh:** site check karte waqt `Ctrl + Shift + R` (ya phone par cache clear) zaroor karein taake purani files nazar na aayein.

## Zaroori notes

- `install.php` install ke baad **zaroor delete karein** (installer mein button hai).
- `uploads/` folder mein logo jayega — isay delete na karein.
- Pehle **Settings** page khol kar apna logo, phone, payment details aur thank-you note set kar lein — phir invoices par sab khud aayega.
- Staff ko **Team** page se account bana kar dein. Staff Settings aur Team pages nahi dekh sakta.

## Tech

PHP 7.4+ · MySQL/MariaDB · koi framework nahi, koi composer nahi — seedha shared hosting par chalta hai.
