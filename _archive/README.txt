Files moved here during cleanup (2026-10-07) because nothing in the app referenced them.
They are kept, not deleted; move a file back to its original folder to restore it.

PHP/test.php      - "PHP is working" test page (should not be public)
modulesData.html  - was ../modulesData.js, but it is an HTML page, not JavaScript
JS/dashboard.js   - not loaded by any page
CSS/dashboard.css - not loaded by any page

Web access to this folder is blocked by .htaccess.

Unwired .php page variants (navigation and login use the .html versions):
HTML/AddModule.php, HTML/adminDashboard.php, HTML/module.php, HTML/sidebar.php

Legacy static module pages (not linked from anywhere; modules now come from the database):
HTML/basicModule.html (linked to moduleBasic-1..8.html, which no longer exist),
HTML/advancedModule.html, HTML/codeBlockModule.html, HTML/moduleCode-1..9.html,
JS/basic.js and PHP/saveQuizResult.php (the quiz script/endpoint those pages used)

Database backups (db/):
codefly_recovered_2026-06-24.sql - data recovered on 2026-10-07 from OneDrive/Desktop/backup_xampp/codefly (raw .ibd)
codefly_full_2026-10-07.sql      - full dump of the live codefly DB after recovery (schema + data + foreign keys)
Restore with: mysql -uroot < codefly_full_2026-10-07.sql
These files contain password hashes; web access to _archive/ is blocked by .htaccess.
- HTML/popup.html: toast demo page, not linked from anywhere (archived 2026-10-07)
