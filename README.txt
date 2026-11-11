Papaya Custom Code 2.6
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

GITHUB UPDATES
Repository: https://github.com/papayasearch/papaya-custom-code (private).
Updater: Plugin Update Checker v6.0, bundled under its MIT license.
Updates use published, non-prerelease GitHub Releases with an attached
asset named exactly papaya-custom-code.zip. Branch commits and bare tags
are never offered as updates. WordPress checks periodically (normally
about every 12 hours); use Plugins > Check for updates for a manual check.
WordPress or your host controls whether an available update installs
automatically. This plugin does not force auto-updates on.

ONE-TIME SETUP PER SITE
Before upgrading from 2.3/2.4, back up CSS/JS and restore them in the new
editors as described above. New storage and stricter permissions from 2.5
are included; external CSS, JavaScript in the footer, and editors remain.
Create a GitHub fine-grained personal access token restricted to this
repository, with Contents: Read-only (Metadata read is automatic). Your
organization may require approval or SSO authorization. Store the token
only in server configuration, never in the repository or a release ZIP.
Before WordPress loads in wp-config.php, set:
    define('PCC_GITHUB_TOKEN', 'YOUR_READ_ONLY_TOKEN');
Do not add a duplicate definition. Rotate expiring/revoked tokens as needed.
No token is bundled, displayed, or accepted through this plugin's forms.
Never send a token in chat. The installed updater uses it to authenticate
GitHub metadata and private release-asset downloads.
Install the updater-enabled ZIP manually once on each site. Restore and
save CSS/JS, then verify the external files and clear caches. Future updates
retain the database/uploads storage. If updates do not appear, check token
permissions/expiry, organization approval, WP cron and outbound API access.

PUBLISHING
Change the Version header, commit, and create a matching tag such as v2.7.
The release workflow builds the ZIP and SHA256SUMS, then creates a draft
GitHub Release. Review/download/test the artifact before publishing it.
A draft or prerelease is not offered to installed sites. Protect repository
write access: publishing a release distributes executable code to sites.
