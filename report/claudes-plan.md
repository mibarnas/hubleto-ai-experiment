# AlgoCorp Training Management — Implementation Plan

## Context

AlgoCorp, s.r.o. runs paid trainings (some accredited by RÚVZ) and needs the whole
lifecycle inside their freshly installed Hubleto ERP at `/var/www/html/algo-corp`:
catalogue of trainings → scheduled dates → orders (private or company, one or many
applicants) → attendance → satisfaction questionnaire → Word certificate generation →
retraining reminders years later.

None of it exists yet — [src/apps/](src/apps/) contains only `README.md`. The instance has
33 community apps enabled and 118 tables. The `Events` / `EventRegistrations` /
`EventFeedback` community apps are **not** installed; `Events` has a usable schema shape to
crib from (event / attendee / speaker / venue / agenda), but its `Attendee` has no link to
`Customer`, no completion status, no pricing and no certificate, and the other two are
empty single-model scaffolds. Building custom apps that *reference* Customers / Contacts /
Documents is cleaner than bending `Events`.

Outcome: three custom Hubleto apps providing the six requested sections, with the four
sections the spec says should come from Hubleto (Customers, Contacts, Companies,
Documents) genuinely coming from Hubleto.

### Decisions already taken
- **Three apps** — `Workers`, `Trainings`, `Certificates` (install order matters).
- **Certificates are DOCX only** — merge into the uploaded `.docx`; no PDF, no LibreOffice.
- **Questionnaire uses fixed columns** — 11 rating + 3 free-text columns on one row.
- **Custom `TrainingOrder` model** — the community `Orders` app is left untouched.

---

## Prerequisites

**1. The frontend build is broken.** Three independent problems:
- [package.json](package.json) declares `"@hubleto/react-ui": "file:../hubleto/src/react-ui"`,
  but the package is at `/var/www/html/hubleto/react-ui` — no `src/` segment. Fix to
  `file:../hubleto/react-ui`.
- [webpack.config.js](webpack.config.js) aliases `@hubleto/ui/core` and `@hubleto/ui/ext`
  to `vendor/hubleto/framework/src/Components/{Core,Ext}` — **neither directory exists**.
  Nothing imports those specifiers; drop both aliases.
- No `node_modules` anywhere and `assets/compiled/` is empty (`ConfigEnv.php` currently
  falls back to the prebuilt bundle in `vendor/hubleto/assets/compiled`). Run `npm install`,
  then `npm run build`.

Webpack auto-discovers any `src/apps/*/Loader.tsx`, so once this works the three new apps
are bundled with no config change.

**2. Email is not configured.** `smtpHost`/`smtpPort` are empty in
[ConfigEnv.php](ConfigEnv.php), so `Hubleto\Erp\EmailProvider::send()` throws
`'SMTP is not properly configured.'`. Separately, the **Mail app** (which is what we need,
see below) sends through a `mails_accounts` row with its own SMTP credentials — create one
for AlgoCorp before any send path is testable.

**3. Excel reading needs a library.** Vendor has `dompdf`, `phpmailer`, `endroid/qr-code`,
`defuse/php-encryption` — but **no PhpSpreadsheet and no PhpWord**.
`composer require phpoffice/phpspreadsheet` for the group-application `.xlsx`.
No PhpWord: the DOCX merge uses the built-in `ZipArchive` + `DOMDocument` (see below).

**4. `php hubleto app install` only runs install round 1.** It calls
`AppManager::installApp(1, …)` and stops — rounds 2 and 3 never execute, so **foreign keys
and default controller permissions are not applied**. After installing, run
`php hubleto migrate` (which does tables in round 1 and FKs in round 2) and grant the new
controller permissions to roles from `settings/role-permissions`.

**5. Two CLI commands are broken in this version** — don't plan around them:
`php hubleto debug router` fatals (references a nonexistent `Hubleto\Erp\Router`), and
`php hubleto create view` typos its mkdir as `Viewss`.

**6. If an app silently fails to appear**, check [log/](log/) —
`AppManager::init()` wraps each `$app->init()` in try/catch and only logs the exception.

---

## Architecture

### The three apps

```
src/apps/Workers/       Hubleto\App\Custom\Workers        install 1st
src/apps/Trainings/     Hubleto\App\Custom\Trainings      install 2nd
src/apps/Certificates/  Hubleto\App\Custom\Certificates   install 3rd
```

Scaffold with `php hubleto create app <Name>` — a bare name is auto-prefixed with
`Hubleto\App\Custom\` (`AppManager::sanitizeAppNamespace()`), and `Hubleto\App\Custom\*` is
autoloaded from `src/apps/` by a `spl_autoload_register` at the top of
`vendor/hubleto/erp/src/Loader.php`, so no composer entry is needed.

Declare ordering in each `manifest.yaml` so installs cascade correctly:
```yaml
# Trainings/manifest.yaml
requires:
  - Hubleto\App\Custom\Workers
  - Hubleto\App\Community\Customers
  - Hubleto\App\Community\Contacts
```

**One dependency cycle to design away:** `Applicant` ↔ `Certificate`. Put the link **only
on `Certificate`** (`id_applicant`, `id_worker`, `id_training`) and expose it on `Applicant`
as a reverse `HAS_ONE`. The graph is then acyclic: Workers → Trainings → Certificates.

Set `sidebarGroup: events` in each manifest — an existing group from
`Desktop\Loader::getSidebarGroups()`. Do **not** add a "Trainings" group by overriding
`sidebarGroups` in `ConfigEnv.php` unless you re-list all 19 defaults;
`config()->getAsArray('sidebarGroups', [...])` replaces the whole map.

### What is reused, not rebuilt

| Need | Reuse |
|---|---|
| Ordering companies with IČO / DIČ / IČ DPH | `Customers\Models\Customer` — `company_id` (IČO, unique+required), `tax_id` (DIČ), `vat_id` (IČ DPH), address, `id_country` |
| Contact person on a company order | `Contacts\Models\Contact` — **note it has no email/phone column**; those live in `Contacts\Models\Value` (`contact_values`, `type` ∈ `email\|number\|url\|other`) |
| AlgoCorp and other own companies | `Settings\Models\Company` (`companies`: name, `company_id`, `tax_id`, `vat_id`, logo, brand colors) |
| Certificate storage + the Documents section | `Documents\Models\Document` (polymorphic `model` + `record_id`), `DocumentVersion` (auto-increments `version`), `Folder` (self-nesting, root row `uid = '_ROOT_'`), `File` |
| Document bookkeeping pattern | `vendor/hubleto/erp/apps/Documents/Generator.php` — mirror `generatePdfDocumentFromTemplate()`'s Document+Version creation |
| **Email with attachments / bulk** | **Mail app** — `Mail\Models\Mail::create($mailData, $attachments)` to queue, `createAndSend(...)` to send now; attachments are `['name' => …, 'file' => <path under upload/>]`; the `SendMails` cron (`*/5 * * * *`) drains anything with `datetime_scheduled_to_send` set and `datetime_sent` null. `EmailProvider` has no attachment support, so certificates must go this way. |
| Simple transactional email | `Hubleto\Erp\EmailProvider::send($to, $subject, $rawBody, $template, $fromName)` |
| In-app admin notifications | `Notifications\Sender::send(...)` — **but it returns `false` when there is no session user**, so cron paths must create `Notifications\Models\Notification` rows directly |
| File upload | `Hubleto\Framework\Db\Column\File` + `setFolderPath()` / `setRenamePattern()`. The React input base64s the file into the normal record-save payload and `Column\File::normalize()` writes it — this is the only working upload path (`Api/FileUpload/Upload.php` has a mismatched namespace and no route; ignore it). |
| Charts | `@hubleto/react-ui/core/Chart` (`HubletoChart`, chart.js 4) — `async` + `endpoint` + `endpointParams`; types `bar\|doughnut\|pie\|line\|scatter\|goals`; data shape `{labels, values, colors}`. Also usable straight from Twig as `<hblreact-chart>`. |
| Row-level ACL | Free — add `id_owner` / `id_manager` / `shared_with` to `Training` and `TrainingOrder` and `Hubleto\Erp\Model::getPermissions()` enforces owner/manager/team/shared visibility |
| Public token pages | `requiresAuthenticatedUser = false` + `hideDefaultDesktop = true`, as in `EmailMarketing/Controllers/Unsubscribe.php` and `Documents/Controllers/DownloadFile.php` |

**Not reusable for our CSV:** the built-in table CSV export (`Hubleto\Erp\Api\TableExportCsv`)
runs `iconv("UTF-8", "windows-1250//TRANSLIT")`. Fine for Slovak spreadsheets opened in
Excel, but the aggregated statistics export is written by us — emit UTF-8 with a BOM.

---

## Data model

Each model needs three files: `Models/<X>.php`, `Models/RecordManagers/<X>.php`,
`Models/Migrations/<X>_0001.php`.

**Do not hand-write migration SQL.** Define `describeColumns()` first, then run
`php hubleto create migration <AppNamespace> <Model>`, which generates the raw SQL from the
column descriptions via `Db\ModelSQLCommandsGenerator`. Migrations are discovered by
filename prefix `<ModelShortName>_NNNN.php` and applied in `scandir` order, with the last
applied index stored in the `config` table.

Conventions the framework enforces:
- **Fix the scaffolded base classes.** `Model.php.twig` extends `\Hubleto\Framework\Model`
  and `ModelRecordManager.php.twig` extends `\Hubleto\Framework\RecordManager`; every real
  app extends `\Hubleto\Erp\Model` / `\Hubleto\Erp\RecordManager`, which add custom
  columns, row-level permissions and audit logging. Change them after generating.
- Relations are declared **twice**: as metadata in `Model::$relations`
  (`['NAME' => [self::BELONGS_TO, TargetModel::class, 'fk', 'id']]`, UPPERCASE, pointing at
  **Model** classes) and as Eloquent methods on the RecordManager (pointing at
  **RecordManager** classes).
- There is no `Column\Enum`. Enums are `Integer` + `->setEnumValues([...])`
  (+ optional `->setEnumCssClasses([...])`) with `const ENUM_*` constants.
- The per-column input hook is `describeInput(string $columnName)` — singular.
- `Column\Email` exists and validates + lowercases; use it for `Worker.email`.

### App 1 — `Workers`

**`Worker`** → `training_workers`

| Column | Type |
|---|---|
| `id_customer` | `Lookup` → `Customers\Models\Customer` (employer; null for private individuals) |
| `title_before`, `title_after` | `Varchar` |
| `first_name`, `last_name` | `Varchar`, required |
| `email` | `Email`, required, **unique** — the identity key for de-duplication |
| `phone` | `Varchar` |
| `gender` | `Integer` + enum |
| `street`, `city`, `zip`, `id_country` | address |
| `workplace_name`, `workplace_street`, `workplace_city`, `workplace_zip` | `Varchar` |
| `date_next_retraining` | `Date`, readonly — denormalised |
| `id_next_retraining_training` | `Lookup` → `Trainings\Models\Training`, readonly — denormalised |
| `note` | `Text` |

Unique index on `email` via `Model::indexes()` (`type: unique`) so the importer can upsert.

`date_next_retraining` / `id_next_retraining_training` are **denormalised rather than
virtual SQL** so the Workers table can sort and filter on them. Recomputed by
`Workers\RetrainingCalculator::recalculate(int $idWorker)`, invoked from
`Certificate::onAfterCreate` / `onAfterUpdate` and from the nightly cron.

> `id_next_retraining_training` points into the Trainings app, which installs *after*
> Workers. Declare it with `->setProperty('disableForeignKey', true)` so
> `ModelSQLCommandsGenerator` skips the FK and the install order stays clean.

**`ExpiryNotification`** → `training_expiry_notifications` — the ledger that keeps the cron
idempotent: `id_worker`, `id_certificate`, `id_customer`, `kind` (`Integer` enum:
1 = individual 6-month, 2 = individual 1-month, 3 = company yearly digest), `sent_on`,
`email_to`, `is_delivered`, `error`.

### App 2 — `Trainings`

**`Training`** → `trainings`
`name` (required, `->setCssClass('text-2xl text-primary')`), `training_number`,
`price_per_person` (`Decimal`, `->setDecimals(2)`), `id_currency` (`Lookup` →
`Settings\Models\Currency`), `retraining_interval_years` (`Integer`),
`id_company` (`Lookup` → `Settings\Models\Company`, defaults to AlgoCorp),
`certificate_template` (`File`, `->setFolderPath('certificate-templates')`,
`->setRenamePattern('{%FILENAME_ASCII%}-{%TS%}.{%EXT%}')`),
`certificate_template_params` (`Json`, readonly — placeholders discovered on upload),
`ruvz_name`, `ruvz_certificate_number`, `ruvz_date_issued` (defaults copied onto each
certificate), `is_active` (`Boolean`), `description` (`Text`), plus `id_owner` /
`id_manager` / `shared_with` for row-level ACL.
Relations: `COMPANY`, `CURRENCY`, `DATES` (HAS_MANY `TrainingDate`).

`onAfterCreate` / `onAfterUpdate` re-scan the uploaded template and refresh
`certificate_template_params` — this is what satisfies "all necessary parameters will be
extracted from the provided templates" and keeps templates replaceable.

**`TrainingDate`** → `training_dates`
`id_training` (required), `datetime_start` (required), `datetime_end`,
`teams_link` (`Varchar`, `->setReactComponent('InputHyperlink')`),
`id_lecturer` (`Lookup` → `Auth\Models\User`, `->setReactComponent('InputUserSelect')`),
`capacity` (`Integer`), `note` (`Text`).
Relations: `TRAINING`, `LECTURER`, `APPLICANTS` (HAS_MANY).

**`Applicant`** → `training_applicants` — one worker's participation in one date
`id_training_date` (required), `id_worker` (required), `id_order` (`Lookup` → `TrainingOrder`),
`date_registered`, `is_completed` (`Boolean`),
`previous_certificate_file` (`File`, `->setFolderPath('previous-certificates')`),
`questionnaire_token` (`Varchar`, unique, `->setDefaultHidden()`),
`questionnaire_sent_on`, `questionnaire_filled_on` (`DateTime`),
`catalog_token` (`Varchar`, unique, hidden), `catalog_filled_on` (`DateTime`),
`education_level` (`Integer` enum — catalog sheet),
`financing_type` (`Integer` enum — catalog sheet),
`meeting_link_sent_on` (`DateTime`).
Relations: `TRAINING_DATE`, `WORKER`, `ORDER`, `QUESTIONNAIRE` (HAS_ONE),
`CERTIFICATE` (HAS_ONE `Certificates\Models\Certificate` on `id_applicant`).
Unique index on each token, and a unique composite on
(`id_training_date`, `id_worker`) so nobody is registered twice for a date.

**`QuestionnaireAnswer`** → `training_questionnaire_answers`
`id_applicant` (unique), `filled_on`, eleven `Integer` 1–5 columns —
`q_content_clear`, `q_met_expectations`, `q_knowledge_useful`, `q_lecturer_expert`,
`q_lecturer_communication`, `q_lecturer_environment`, `q_organization`, `q_tech_support`,
`q_information`, `q_overall_satisfaction`, `q_would_recommend` — and three `Text` columns:
`txt_liked_most`, `txt_improve`, `txt_recommendations`.

Hold the eleven display labels in **one place** (`Trainings\Questionnaire::QUESTIONS`,
`code => translated label`) and drive the public form, the chart endpoint and the CSV header
off that array, so wording changes in a single edit.

**`TrainingOrder`** → `training_orders`
`identifier` (assigned in `onBeforeCreate`),
`order_type` (`Integer` enum: 1 = private individual, 2 = company),
`id_customer` (`Lookup` → `Customer`), `id_contact` (`Lookup` → `Contact`),
`id_worker` (`Lookup` → `Worker`, private orders),
`id_training_date`, `price_per_person` (`Decimal(2)`),
`number_of_applicants` (`Integer`, readonly), `total_price` (`Decimal(2)`, readonly),
`id_currency`, `date_ordered`, `date_paid`,
`applicants_xlsx` (`File`, `->setFolderPath('order-applicants')`), `note`,
plus `id_owner` / `id_manager` / `shared_with`.
Relations: `CUSTOMER`, `CONTACT`, `WORKER`, `TRAINING_DATE`, `APPLICANTS` (HAS_MANY).

`price_per_person` defaults from the selected date's training; `number_of_applicants` and
`total_price` recalculate whenever applicants change.

### App 3 — `Certificates`

**`Certificate`** → `training_certificates`
`id_applicant` (required, unique), `id_worker`, `id_training` (denormalised so the Worker
card and the statistics can join without walking the chain),
`certificate_number` (AlgoCorp's own register), `date_created`, `date_valid_until`,
`ruvz_name`, `ruvz_certificate_number`, `ruvz_date_issued`,
`id_document` (`Lookup` → `Documents\Models\Document`),
`file` (`Varchar`, readonly — path under `upload/`), `sent_to_applicant_on` (`DateTime`).

The three RÚVZ fields are **copied from the parent `Training` at generation time** and stay
editable per certificate. The spec lists them on the certificate card, but they describe
the training's accreditation — training-level defaults avoid re-typing them per applicant.

`date_valid_until` = training date + `Training.retraining_interval_years` years.

---

## Services

### `Certificates\DocxTemplate` — placeholder discovery + merge

Word stores document text in `word/document.xml` inside a zip and splits a typed
placeholder across several `<w:t>` runs whenever formatting or spell-check state changes.
The literal `<` and `>` of AlgoCorp's `<parameter>` syntax appear XML-escaped as
`&lt;` / `&gt;`, which conveniently disambiguates them from real markup.

```php
final class DocxTemplate
{
  public function __construct(string $docxPath) {}
  public function scanPlaceholders(): array;      // ['date', 'applicant_name', …]
  public function render(array $vars, string $outPath): array;  // returns unresolved keys
}
```

Per part (`word/document.xml`, `word/header*.xml`, `word/footer*.xml`):
1. Read the part from the `ZipArchive`.
2. **Merge split runs** — within each `<w:p>`, concatenate consecutive `<w:t>` bodies into
   the first run and blank the rest, so a broken `&lt;da` + `te&gt;` becomes whole. (This is
   the problem PhpWord solves with `fixBrokenMacros`; we do it ourselves rather than add
   PhpWord for scalar substitution only.)
3. `scanPlaceholders()` matches `/&lt;\s*([\p{L}\p{N}_ .-]+?)\s*&gt;/u` and normalises the keys.
4. `render()` substitutes the XML-escaped value for each match; **unknown placeholders are
   left in place and returned**, so a template using a parameter we do not yet supply is
   visible rather than silently blank.
5. Copy the zip to the destination and `ZipArchive::addFromString()` the rewritten parts.

Uses only `ext-zip` + `DOMDocument`.

### `Certificates\CertificateGenerator`

`generate(int $idApplicant): array` —
1. Load Applicant → TrainingDate → Training → Worker (+ Worker's Customer, Training's Company).
2. Refuse if `is_completed` is false or a certificate already exists — idempotent.
3. Create the `Certificate` row: number from a per-year counter, RÚVZ defaults copied from
   the Training, `date_valid_until` computed.
4. Build `$vars` — worker name/titles/address/workplace, company name + IČO/DIČ/IČ DPH,
   training name/number, date, lecturer, certificate number, RÚVZ block, validity, today.
5. Destination `upload/certificates/{Y}/{training-slug}/{Y-m-d}/{worker-slug}.docx` —
   the spec's "folder designated by the current year, training, and date".
6. `DocxTemplate::render()`; surface any unresolved placeholders in the API response.
7. Mirror `Documents\Generator`'s bookkeeping: create/find a `Documents\Models\Document`
   with `model = Applicant::class`, `record_id = idApplicant`, add a `DocumentVersion`, and
   create the matching `Folder` chain + `File` row so the certificate also appears in the
   Documents file browser.
8. Email it to the worker as an attachment via `Mail\Models\Mail::createAndSend()`; stamp
   `sent_to_applicant_on`.

### `Trainings\ApplicantsImporter`

`preview(int $idOrder, string $xlsxPath): array` and `import(int $idOrder, string $xlsxPath): array`.
Reads the sheet with PhpSpreadsheet, maps the header row onto `Worker` fields, **upserts
each worker by email** (the spec's "archived in the system as workers based on the
applicants' emails"), then creates `Applicant` rows against the order and its training date.
`preview()` runs the same mapping without writing, returning matched / new / invalid rows
plus any unmapped columns, so the user confirms before committing.

### `Trainings\Statistics`

`forTraining(int $idTraining, array $filters): array` — per-question `AVG()` and 1–5
distribution over `training_questionnaire_answers` joined through applicants → dates, plus
response count and a per-date trend series. Returns both the `{labels, values, colors}`
payload `HubletoChart` expects and a flat rows array the CSV controller streams.

The generic `api/get-chart-data` endpoint (`Hubleto\Erp\Api\GetTemplateChartData`) only
does one `groupBy` + one aggregate, so it cannot produce the eleven-question comparison —
hence a dedicated endpoint.

### `Workers\RetrainingCalculator`

`recalculate(int $idWorker)` — over the worker's certificates, find the earliest
`date_valid_until` still in the future (else the most recently expired) and write
`date_next_retraining` + `id_next_retraining_training` back onto the worker.

### `Workers\ExpiryNotifier`

`run(\DateTimeImmutable $today)`, invoked by the cron:
- **Private individuals** (no `id_customer`, or ordered via an `order_type = 1` order):
  email 6 months before `date_valid_until`, then again at 1 month — the second only if the
  worker has not since registered for a matching training date.
- **Company-ordered workers**: on the first run of the year, one digest per `Customer`
  listing every worker whose certificate expires that year, sent to the customer's primary
  contact email (join `contact_values` on `type = 'email'`).
- Every send writes an `ExpiryNotification` row first, so a re-run never double-sends.
- On failure, record `is_delivered = false` + the error and notify the admin users.

---

## Controllers, routes and views

Routes go in each `Loader::init()` via `$this->router()->get([...])`. **Matching loops over
every route and the last match wins**, so register specific patterns (`.../add`) after or
instead of the generic `(?<recordId>\d+)` one, or use the explicit
`['controller' => X::class, 'vars' => ['recordId' => -1]]` form.

Page controllers call `$this->setView('@Hubleto:App:Custom:<App>/<View>.twig')` (the Twig
namespace is the PHP namespace with `\` → `:`); the view embeds a registered React table as
an `<hblreact-*>` element.

For JSON endpoints extend `\Hubleto\Erp\Controllers\ApiController` and **override
`response(): array`, not `renderJson()`** — `renderJson()` is the base class's
exception→HTTP-400 wrapper, and the CLI template's habit of overriding it throws that away.

### Trainings
```
/^trainings\/add\/?$/                          → Controllers\Trainings, vars recordId = -1
/^trainings(\/(?<recordId>\d+))?\/?$/            Controllers\Trainings
/^trainings\/dates(\/(?<recordId>\d+))?\/?$/     Controllers\Dates
/^trainings\/applicants\/?$/                     Controllers\Applicants
/^trainings\/orders(\/(?<recordId>\d+))?\/?$/    Controllers\Orders
/^settings\/trainings\/?$/                       Controllers\Settings
```
API (`<rootUrlSlug>/api/<kebab>` convention):
```
trainings/api/statistics              chart payloads for one training
trainings/api/send-meeting-link       bulk, by idTrainingDate
trainings/api/send-questionnaire      bulk, by idTrainingDate — mints tokens
trainings/api/import-applicants       xlsx preview / commit, by idOrder
trainings/api/recalculate-order       refresh count + total on an order
```
The stats CSV needs a **plain** controller, not an `ApiController`, since that always emits
JSON — return the CSV from `render(): string` with `Content-Type` / `Content-Disposition`
headers, exactly as `Documents\Controllers\DownloadFile` streams a file:
```
trainings/statistics/export-csv
```

**Public, unauthenticated** (`requiresAuthenticatedUser = false`, `hideDefaultDesktop = true`)
— the "subpage where the questionnaire can be filled out":
```
/^training-questionnaire\/?$/          Controllers\Pub\Questionnaire   ?t=<token>
/^training-catalog-sheet\/?$/          Controllers\Pub\CatalogSheet    ?t=<token>
trainings/api/pub/submit-questionnaire   requiresAuthenticatedUser = false
trainings/api/pub/submit-catalog-sheet   requiresAuthenticatedUser = false
```
These are **plain Twig forms posting to a public endpoint, not React** — the React
table/form components call `api/table/describe`, `api/record/save` etc., all of which
require a session. The token is a 32-char random string on the `Applicant`; a page whose
`questionnaire_filled_on` is already set renders an "already submitted" state, which is what
"specific to the applicant and for one-time use" requires. The catalog sheet collects only
`education_level` and `financing_type` and shows the worker's existing data read-only,
since "general worker data will not need to be entered again".

### Certificates
```
/^certificates(\/(?<recordId>\d+))?\/?$/   Controllers\Certificates
certificates/api/generate                  idApplicant → generate + email
```

### Workers
```
/^workers(\/(?<recordId>\d+))?\/?$/        Controllers\Workers
```

### App menu and settings
In each `Loader::init()`:
```php
$appMenu = $this->getService(\Hubleto\App\Community\Desktop\AppMenuManager::class);
$appMenu->addItem($this, 'trainings', $this->translate('Trainings'), 'fas fa-chalkboard-user');
// …Dates / Applicants / Orders…

$settingsApp = $this->appManager()->getApp(\Hubleto\App\Community\Settings\Loader::class);
$settingsApp->addSetting($this, ['title' => …, 'icon' => …, 'url' => 'settings/trainings']);
```

---

## React components

One `Table<Model>s.tsx` + one `Form<Model>.tsx` per model, extending
`@hubleto/react-ui/ext/TableExtended` and `@hubleto/react-ui/ext/FormExtended`. Register
them in each app's `Loader.tsx` using the modern pattern from `apps/Customers/Loader.tsx`:

```tsx
class TrainingsApp extends App {
  init() {
    super.init();
    globalThis.hubleto.registerReactComponent('TrainingsTableTrainings', TableTrainings);
    // …
  }
}
globalThis.hubleto.registerApp('Hubleto/App/Custom/Trainings', new TrainingsApp());
```
`<hblreact-trainings-table-trainings>` in Twig resolves to `TrainingsTableTrainings`.

Parent-child tables inside a form follow `FormEvent.tsx` / `FormCustomer.tsx`: render the
child table only once `this.state.id > 0`, pass `parentForm={this}` and
`customEndpointParams={{ idTraining: this.state.id }}`, and filter server-side by overriding
`prepareReadQuery()` in the child's RecordManager on that URL param.

- **`FormTraining`** — tabs: *Training* (fields, template upload, discovered-parameter
  list), *Dates* (`TableTrainingDates`), *Statistics* (`HubletoChart` with `async` +
  `endpoint='trainings/api/statistics'`, plus an Export CSV link).
- **`FormTrainingDate`** — fields, `TableApplicants` filtered by date, and two header
  buttons hitting `trainings/api/send-meeting-link` / `send-questionnaire`.
- **`FormApplicant`** — worker lookup, completion toggle, previous-certificate upload, links
  to the questionnaire detail and the public questionnaire URL, and a **Generate
  certificate** button posting to `certificates/api/generate`.
- **`FormWorker`** — profile fields plus tabs for trainings attended, certificates issued
  and orders. Reuse `TableDocuments` from `@hubleto/apps/Documents/Components/TableDocuments`
  for the documents tab.
- **`FormTrainingOrder`** — orderer block switching on `order_type`, price fields,
  applicants table, xlsx upload + import (preview, then commit).
- **`FormCertificate`** — certificate fields plus a download link.

`TableWorkers` shows `date_next_retraining` and `id_next_retraining_training` as ordinary
sortable columns — that is why they are denormalised.

---

## Cron

Apps register their own crons (as `Mail` and `Notifications` do), so this lives in the
Workers app rather than `src/crons/`:

`src/apps/Workers/Crons/RetrainingReminders.php`
```php
namespace Hubleto\App\Custom\Workers\Crons;

class RetrainingReminders extends \Hubleto\Erp\Cron
{
  public string $schedulingPattern = '0 6 * * *';

  public function run(): void
  {
    $this->getService(\Hubleto\App\Custom\Workers\ExpiryNotifier::class)
      ->run(new \DateTimeImmutable());
  }
}
```
Registered from `Workers\Loader::init()` with
`$this->cronManager()->addCron(Crons\RetrainingReminders::class);`.

`CronManager::run()` implements its own matcher supporting only `*`, `*/N` and exact
integers — no ranges, no comma lists. A system crontab entry running `php cron.php` (every
5 minutes) is required, and it also drives the Mail app's `SendMails` queue drainer.

---

## Order-created notification

`TrainingOrder::onAfterCreate()` notifies administrative users: an in-app notification via
`Notifications\Sender::send()` plus an email. "Administrative users" = users whose role
grants the Trainings app permission, resolved through `Settings\PermissionsManager`.
Guard the `Sender::send()` call — it no-ops when there is no session user.

---

## Build & install sequence

```bash
# 1. fix package.json + webpack.config.js (see Prerequisites)
npm install
composer require phpoffice/phpspreadsheet

# 2. scaffold
php hubleto create app Workers
php hubleto create app Trainings
php hubleto create app Certificates
# then: change generated models/record managers to extend \Hubleto\Erp\*,
#       fill in manifest.yaml (sidebarGroup: events, requires: …)

# 3. write Models/*.php with describeColumns(), then generate migrations
php hubleto create migration Workers Worker
php hubleto create migration Trainings Training
# …one per model…

# 4. install in dependency order
php hubleto app install Hubleto\\App\\Custom\\Workers
php hubleto app install Hubleto\\App\\Custom\\Trainings
php hubleto app install Hubleto\\App\\Custom\\Certificates
php hubleto migrate            # round 1 = tables, round 2 = foreign keys

# 5. grant the new controller permissions to roles via settings/role-permissions
#    (install round 3 does not run from the CLI)

# 6. frontend
npm run build
```

---

## Verification

1. **Schema** — after `php hubleto migrate`, confirm the nine tables exist and the FKs
   landed: `show tables like 'training%'`, then `show create table training_applicants` to
   check the `id_worker` / `id_training_date` constraints. (`php hubleto app install`
   alone does *not* create them.)
2. **Sidebar & routes** — sign in; Trainings / Workers / Certificates should appear under
   the Events sidebar group with their app-menu items. If one is missing, check
   [log/](log/) — `AppManager::init()` swallows Loader exceptions. Do not use
   `php hubleto debug router`; it fatals in this version.
3. **Catalogue → date → order → applicant** — create a Training priced per person with a
   2-year retraining interval, add a date with a Teams link, create a company order with an
   `.xlsx` of three applicants, run the import, and confirm three `Worker` rows were created
   (or matched by email) and three `Applicant` rows attached to the order and date.
   Re-import the same file and confirm no duplicate workers.
4. **Questionnaire** — from the date card, bulk-send the questionnaire; open the emitted
   `?t=<token>` URL **in a logged-out browser**; submit; confirm the answers land in
   `training_questionnaire_answers`, reloading the link shows the already-submitted state,
   and the Training card's charts and CSV export now include the response. Check the CSV
   opens with correct Slovak diacritics.
5. **Certificate** — upload a `.docx` template containing a placeholder deliberately split
   mid-word by Word (type `<date>`, then bold half of it) and confirm
   `certificate_template_params` still discovers it. Mark an applicant completed, click
   Generate, then check: the file exists under
   `upload/certificates/<year>/<training>/<date>/`, placeholders are substituted,
   `documents` + `documents_versions` + `files` rows were created, and the certificate shows
   on the Worker card and in the Documents section. Deliberately include an unknown
   placeholder and confirm it is reported rather than silently blanked.
6. **Retraining** — set a certificate's `date_valid_until` to exactly 6 months out, run
   `php cron.php`, and confirm one queued mail plus one `training_expiry_notifications` row.
   Run again the same day and confirm nothing re-sends. Repeat with a company-owned worker
   to exercise the yearly digest path.
7. **Regression** — the community Orders, Customers, Contacts and Documents sections are
   unchanged, and nothing under `vendor/` has been modified.

---

## Assumptions worth confirming during implementation

- RÚVZ fields default from the Training and are copied (still editable) onto each certificate.
- The catalog sheet is a second one-time-token page against the same applicant record,
  not a separate document.
- The group-application `.xlsx` column layout is unknown; the importer maps by header name
  through a configurable alias map, and `preview()` surfaces unmapped columns rather than
  guessing.
- "Administrative users" for notifications = users whose role grants the Trainings app.
- Bulk and certificate emails go through the Mail app, which needs one `mails_accounts`
  row for AlgoCorp with working SMTP credentials.
