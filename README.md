# C.Console

An online PlayStation 5 game store built with PHP and MySQL/MariaDB. Customers browse games, place and track orders and write reviews with photos. Administrators manage games, stock, orders and users.

## Requirements

- PHP 8.2 or newer (needs the `mysqli`, `mbstring`, `gd` and `fileinfo` extensions; `mysqli_execute_query` requires PHP 8.2+)
- MySQL 8.0.16+ or MariaDB 10.5+ (the database uses CHECK constraints)
- Apache (for example through XAMPP) with the project served from `/C.Console`

## Setup

1. Clone or download this repository into your web root so the folder is named `C.Console` (for XAMPP: `htdocs/C.Console`).
2. Start Apache and MySQL/MariaDB.
3. Import the single database file `C.Console.sql`. It is the only SQL file in the project and creates the `C.Console` database, all tables, relationships, constraints, views and the starting data.
   - phpMyAdmin: open the **Import** tab, choose `C.Console.sql` and run it.
   - Command line: `mysql -u root -p < C.Console.sql`
   - Warning: the script begins with `DROP DATABASE IF EXISTS` and rebuilds the `C.Console` database from scratch. Do not import it over a database you want to keep.
4. Configure the database connection if yours differs from the defaults (`localhost`, user `root`, empty password, database `C.Console`):
   - copy `includes/db.env.example` to `includes/db.env` and edit the values. Quote values that contain special characters.
   - `includes/db.env` is ignored by Git, so your credentials are never committed.
5. Optional, for emails (registration notice and order receipts): place your own `mail.env` file in the `includes` folder (`includes/mail.env`). The site reads these keys from it: `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`. `includes/mail.env.example` shows the format only. Quote values that contain special characters. Without the file the site still works and simply skips the emails.
   - `includes/mail.env` is ignored by Git, so your credentials are never committed.
6. Open `http://localhost/C.Console/`.

If the project is served from a different folder name, change `BASE_URL` at the top of `includes/functions.php`.

## Sample accounts

| Role | Username | Email |
| --- | --- | --- |
| Administrator | `cconsole` | `cconsole@cconsole.test` |
| Customer | `jdelacruz` | `juan.delacruz@gmail.com` |

All other sample customers use the pattern `first.last@gmail.com` (see `C.Console.sql`). The sample passwords are in the SQL file and are converted to secure hashes the first time each account signs in. These are demo accounts: change or remove them before using the site for real.

## Site pages

Home (`index.php`), Shop (`shop.php`), About (`about.php`), Contact (`contact.php`), FAQ (`faq.php`), Privacy Policy (`privacy.php`), Terms of Service (`terms.php`) and Disclaimer (`disclaimer.php`), plus the account, cart, checkout, review and admin areas.

Store contact details (email, phone, location, hours) and the legal "last updated" date are constants near the top of `includes/functions.php`. Replace them with the shop's real details. The legal pages are a project draft and should be reviewed before real use.

## Inventory and admin features

- **Automatic stock deduction:** placing an order (online or at the New Sale screen) deducts the purchased quantity inside one database transaction. An order that asks for more than the stock on hand is rejected and nothing is deducted. Cancelling a pending order or voiding a confirmed order restores the stock once.
- **Low stock report** (`item/lowstock.php`): lists active games with stock above zero and at or below their low stock alert level, read from the `low_stock_report` view. Games with zero stock appear under out of stock.
- **Stock-in history** (`item/stockin.php`): every stock addition with the game, quantity added, date, reason and responsible administrator, with search and filters by date range, reason and administrator.
- **Delete or archive:** a game with order or stock history cannot be deleted; it is archived instead (hidden from the shop, records kept) and can be restored from the games list under Visibility, Archived.
- **Access control:** every admin page and action checks the signed-in user's role on the server. Customers and visitors are redirected away.

## Reports and backup (administrators only)

- **Sales summary** (`admin/sales.php`): daily, weekly (Monday start) or monthly totals for a chosen date range. Shows orders, units, gross sales, discounts, net sales, cost and profit. Pending and confirmed orders count as sales, matching the dashboard; cancelled and voided orders are excluded and reported separately. Export to CSV or print.
- **Transaction history** (`admin/transactions.php`): every order with customer, channel, games, items, discount, total, cash payment and status. Search by order number, customer, email or game, and filter by date range, customer and status. Export to CSV or print.
- **Backup and recovery** (`admin/backup.php`): download a `.sql` backup of all tables and views, or restore one. A restore only accepts backups downloaded from this page (format header, checksum and statement checks), requires typing `RESTORE`, saves a safety copy of the current data in `backups/` first, and puts the previous data back if the restore fails. Everyone is signed out after a restore. Sign-in sessions and uploaded image files are not part of the backup.

## Notes

- Contact form messages are saved in the `contact_messages` table. There is no admin screen for reading them yet; view them in phpMyAdmin.
- Uploaded profile photos and review photos are stored in `user/images/` and `review/images/` and are not committed to Git.
