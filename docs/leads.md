# Leads management

The Leads navigation entry opens `/leads/dashboard`. The module uses the existing session authentication, Blade layout, Tailwind classes and users/employees tables. The Agropro pages are unchanged.

## Setup

```powershell
php C:\ProgramData\ComposerSetup\bin\composer.phar install --no-interaction
php artisan migrate
php artisan test --compact
```

On another machine, `composer install --no-interaction` is equivalent. No seeder is required. Use existing Admin, Owner and Employee accounts; no customer data or production accounts are seeded. The new migration creates leads, follow-ups, assignments, imports and audit tables. The project uses MySQL; feature tests default to a separate in-memory SQLite database.

To run the leads feature tests on MySQL, including simultaneous confirmation processes:

```powershell
php tests/Support/run-leads-mysql.php
```

This runner uses the configured MySQL connection to create a randomly named `abj_leads_test_*` database, runs the leads tests against it and drops only that database afterward. Run it with local/test credentials permitted to create and drop test databases. It does not run tests against the application's normal database. The default test suite also tests competing imports in an isolated temporary SQLite database.

## Permissions and assumptions for business review

These are explicit defaults in `config/leads.php`, not a new role system:

| Role | Default access |
| --- | --- |
| Admin | Company leads, creation, import, edit, assignment, status, follow-ups, full mobile, CSV export and import history |
| Owner | Company leads, details, full mobile, CSV export and import history; no mutations |
| Employee | Active employee profile's assigned leads, location/requirement edits and new follow-ups; mobile masked to last four digits |

1. **Company scope:** the existing database has no organization/tenant identifier. It is treated as a single ABJ Builders company. Multi-company use requires tenant columns, policies, duplicate keys and query scoping before deployment to multiple organizations.
2. **Duplicates:** one lead per normalized mobile across this company, including inactive leads. A unique database key enforces this during concurrent imports and manual entry. If repeat enquiries must become separate leads, change the customer/enquiry data model explicitly before relaxing this constraint.
3. **Phone rules:** Indian ten-digit mobiles beginning with 6–9. `+91`, bare `91`, a leading trunk `0`, spaces, dots, parentheses and hyphens are normalized. Validation pattern and country prefix are configured in `config/leads.php`.
4. **Employee identity:** reuse the existing application's exact email link between users and employees. Assignment requires an Active employee record with a matching employee-role login. Employee codes or emails are recommended during import. Names are accepted only when exactly one eligible employee matches. Employee record IDs in a spreadsheet are never trusted as assignments. Changes to employee emails and user roles must continue through the existing employee-management workflow so account links remain synchronized.
5. **Owner and employee permissions:** edit `config/leads.php` through a reviewed deployment to grant Owner mutations or Employee full-mobile access. A permission never bypasses employee record scoping. There is no client-supplied permission override. Owners' access to other existing modules is unchanged.
6. **Status and history:** only an explicitly authorized status operation changes Active/Inactive and requires a reason. Follow-up outcomes never change status automatically. Leads cannot be deleted through this module. Employees referenced by leads or assignment history must be made Inactive instead of deleted.
7. **Follow-ups:** history is append-only. The largest follow-up timestamp, then ID, determines current outcome/next callback. Backdated notes do not overwrite newer callbacks. Another Call requires a future callback; Others requires notes. Follow-ups cannot precede lead creation or be future dated. Forms and displays explicitly use the existing application timezone (`config('app.timezone')`, currently UTC).
8. **Exports:** Admin and Owner can export their filtered scope, at most 10,000 records per download. All exported cells are escaped against spreadsheet formula injection. Source workbook details are never included in CSV exports. Masked users cannot search mobile numbers, preventing enumeration through a search-result oracle.

## Import workflow and the supplied sales workbook

Upload an XLSX or UTF-8 comma-separated CSV, choose one worksheet, map the header row, validate the preview, then explicitly confirm. Use the workbook's first row for headers. The downloadable XLSX template has Lead Name, Mobile Number, Location, Requirement and optional Assigned Employee columns.

The supplied sales workbook contains multiple worksheets with differing layouts. Mapping suggestions recognize CUSTOMER NAME / CLIENT NAME, MOBILE NO, TELECALLER / EXECUTIVE and standard template labels. Suggestions are shown for review rather than silently applied. Import each worksheet separately; duplicates are checked against all already imported leads.

Location and Requirement remain required. If a sheet has no suitable column, enter an explicit default for that import. Defaults apply only to unmapped columns. The ambiguous FOLLOWUPS/LOCATION column is not automatically assumed to be a clean location. Fields such as DATE, SOURCE, QUALIFY, STATUS, REMARKS, FOLOW UP DATE, NEXT FOLLOW UP and booking details are preserved as encrypted original source details. They do not automatically create follow-up history, bookings, assignments or lead status transitions. Only users permitted to see full phone numbers can view source details, since those may contain unmasked phone data.

The module never imports the provided local workbook automatically. The actual workbook is not committed to the repository or changed by development/tests.

Each preview lists valid, duplicate and invalid rows with worksheet row numbers and validation errors. Only valid non-duplicates are inserted on confirmation. The server stores the preview; submitted row data cannot alter it. Assignment eligibility and uniqueness are checked again at confirmation. Confirmation locks the import record, is idempotent, and wraps lead, assignment, audit and summary writes in one transaction. Unexpected errors roll back the entire attempt and allow a retry. The final summary includes total/imported/duplicate/failed rows, uploader, filename and timestamps. A failed row includes a row rejected during validation or whose assignment became ineligible before confirmation.

## Upload security and retention

PhpSpreadsheet is pinned through `composer.lock`. The XLSX reader retrieves raw values without calculating formulas. See the [official reading-files documentation](https://phpspreadsheet.readthedocs.io/en/latest/topics/reading-files/) for raw-data reading and read filters.

Limits default to 5 MB compressed upload, 32 MB expanded XLSX archive, 2,000 archive entries, 20 worksheets, 5,000 data rows per selected worksheet and 64 columns. Unsupported legacy XLS, macros, embedded objects, external workbook/data connections, XML entities and unsafe archive paths are rejected. Formula-like data rows are invalid. No spreadsheet macros or embedded content are executed. Every rendered customer value uses escaped Blade output.

Uploads are stored under the local private filesystem (`storage/app/private/lead-imports`), outside the public web root with random filenames. Only an authorized uploader may read a pending preview. Application errors never reveal private storage paths or raw database errors in the import response. Preview payloads and lead source data use Laravel encrypted casts; protect `APP_KEY` and include it in the secure backup/rotation procedure.

Completed imports remove the uploaded source and preview rows immediately. Pending/failed uploads expire after 24 hours. Summary import records and audit trails are retained for historical reporting. Run the Laravel scheduler in production; the registered hourly prune command deletes expired source/preview data and old orphaned uploads:

```powershell
php artisan leads:prune-imports
php artisan schedule:run
```

Configure a system scheduler to execute `php artisan schedule:run` every minute. Keep `storage` writable only by the application account and authorized administrators, keep directory listing disabled and serve only Laravel's `public` directory. Customer mobile/name fields in the leads table require appropriate database/backups encryption and access controls supplied by the deployment.

## Production settings

Use HTTPS, a valid certificate and least-privilege MySQL runtime credentials. Set the following in the deployment environment, using the actual HTTPS origin for `APP_URL`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-company-domain.example
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true
```

The module sends private/no-store, nosniff, SAMEORIGIN and same-origin referrer headers, plus HSTS on HTTPS production responses. CSRF remains enabled on every state-changing web route. Upload/confirmation/export/follow-up routes are throttled. The existing login POST is limited to ten attempts per minute per rate-limit key. Do not put customer row data, phone search terms, request bodies, session cookies or credentials into proxy/access logs, analytics or exception telemetry.

After changing deployment configuration:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run migrations with a deployment account that can change schema; use a restricted runtime account afterward. Review the business assumptions above before granting broader permissions. Existing app authentication redirects and unrelated portal modules retain their behavior.
