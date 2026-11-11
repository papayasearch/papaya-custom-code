Papaya Custom Code 2.6.1
Requires WordPress 5.8+ and PHP 7.4+.

FIRST UPGRADE FROM 2.1 / 2.2 / 2.3 / 2.4
1. Before replacing the installed plugin, copy all custom CSS and JavaScript
   into a separate backup. Back up PHP too. Older versions store CSS/JS in
   the plugin directory, which WordPress replaces during an update.
2. Install this ZIP, replacing the existing Papaya Custom Code plugin.
3. Open Tools > Papaya Custom Code. Check both CSS/JS editors, paste the
   backed-up code if necessary, and Save Changes. Reload to verify it saved.
4. Clear Perfmatters Used CSS (if enabled), hosting, and CDN caches.
The package cannot recover files overwritten before migration. Existing
PHP options are retained. Version 2.5 does not enable PHP automatically.
Do not run simultaneous updates through WordPress and a host update manager.

STORAGE AND EXTERNAL CSS
After the first successful CSS/JS save, code is stored in one per-site
WordPress option. Files are published under the site's uploads directory:
/papaya-custom-code/site-ID/custom-SHA256.css (or .js).
The filenames change with content. Complete files are staged before the
database record switches; a failed save does not replace that record.
Old generated files remain available for previously cached pages. They can
be cleaned up separately after cached references expire. CSS and JS files
are public by design: do not put passwords or private information in them.
Keep database AND uploads in site backups. Future plugin-folder updates do
not replace this stored code. Missing generated files are rebuilt on an
authorized visit to the dashboard. WordPress must be able to write uploads.

CSS loads as an external stylesheet in the head, using the same enqueue
priority (999) as version 2.3. JavaScript remains in the footer. This is not
version 2.4's inline CSS approach. Use absolute image/font URLs in CSS:
relative URLs now resolve against the generated file's uploads location.

PERFMATTERS
If Remove Unused CSS is OFF, leave it off; this plugin does not require it.
If it is ON, replace the old custom.css exclusion with:
/papaya-custom-code/site-
This matches the new generated assets' paths. Add it under Excluded
Stylesheets, not Excluded Selectors. Keep these styles out of any custom
async/delay rules. Save settings and clear Used CSS and page/CDN caches.

PERMISSIONS
All code editing requires manage_options, edit_plugins, and unfiltered_html.
Multisite requires a super administrator. DISALLOW_FILE_EDIT and
DISALLOW_FILE_MODS prevent code editing. Existing saved code still executes
when editing is locked; these settings are not execution kill switches.
CSS/JS share one form protected by a WordPress nonce; PHP has a separate
nonce-protected form. Unauthorized visitors cannot use pcc_safe_mode=1 to
skip the site's assets or PHP. Authorized editors can use that bypass.

PHP AND RECOVERY
PHP executes as trusted server-side plugin code, not in a sandbox. It can
modify files/data and is not made secure by syntax validation. Leave it
disabled unless required. Enter statements without opening/closing PHP tags.
Enabled PHP runs at plugins_loaded priority 20, including public/admin/cron
and CLI requests. The authorized editor page skips PHP for recovery.
Variables have local function scope; use global explicitly where required.
Errors thrown immediately during evaluation disable the snippet when the
database permits it. Fatal errors attributed to evaluated code trigger an
attempt to disable it. Errors in later callbacks, resource exhaustion,
exit calls, and errors in other files are not fully recoverable this way.
There is no rollback of side effects, and concurrent requests may already
be executing previously enabled code when it is disabled.
Emergency recovery: before WordPress loads, add to wp-config.php:
    define('PCC_DISABLE_PHP', true);
Then fix or disable the snippet. Remove the constant after correction.
The save process checks database results before replacing or enabling PHP.

EDITORS
Syntax colors, line numbers, bracket matching, four-space indentation, and
Reindent code remain available. Escape then Tab focuses Save; Escape then
Shift+Tab focuses Reindent. WordPress's syntax-highlighting profile preference
is respected. Reindent adjusts indentation, not complete code formatting.

VALIDATION
See the accompanying verification report for the checks performed. This is
security hardening, not a guarantee that arbitrary snippets are safe or an
independent penetration test of your live hosting environment.

GITHUB UPDATES (2.6.1)
Repository: https://github.com/papayasearch/papaya-custom-code (public).
No GitHub token is required on installed sites. Remove the PCC_GITHUB_TOKEN
line from wp-config.php if you previously added it solely for this plugin.
An existing configured token remains optional; unauthenticated checks use
GitHub's public API limits, which may be shared by sites on one hosting IP.
Updater: Plugin Update Checker v6.0, bundled under its MIT license.
Only published, non-prerelease GitHub Releases with an attached asset named
papaya-custom-code.zip are offered. No branch snapshots or bare tags.
WordPress checks periodically (normally about every 12 hours). Plugins >
Check for updates starts a manual check. WordPress/your host controls
whether available updates install automatically; this plugin does not
force auto-updates on.

INSTALL ONCE
Manually install the updater-enabled ZIP once on each site. Before upgrading
from 2.3/2.4, copy CSS/JS, restore and save them afterward, and clear caches.
Versions 2.5/2.6 already store CSS/JS in the database/uploads. Back up your
site as usual. The stricter editing permissions and external CSS loading
from 2.5 remain. If Remove Unused CSS is enabled in Perfmatters, use the
Excluded Stylesheets entry /papaya-custom-code/site- . If disabled, leave it
disabled. Installation is not proof an update check/download has succeeded.

RELEASES
Bump the Version header and create a matching v-prefixed tag (v2.6.1).
Alternatively, open Actions > Build plugin release > Run workflow and enter
the matching tag. The workflow packages the plugin and creates a draft
release with papaya-custom-code.zip and SHA256SUMS. Review/test and publish
the draft to offer it to installed sites. Drafts/prereleases are ignored.
The workflow uses GitHub's built-in GITHUB_TOKEN for publishing; website
administrators do not need to create or install personal tokens.
Protect repository write access: published updates contain executable code.
