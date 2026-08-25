Status summary
Fully built, installed, and verified against the live database:

Frontend build pipeline fixed (package.json/webpack path bugs, missing babel option, missing npm deps in the sibling react-ui repo, a wrong @babel/runtime major version, and missing primereact/fullcalendar packages) — npm run build now compiles cleanly end to end.
All three apps (Workers, Trainings, Certificates) scaffolded, installed, and enabled.
All 8 models (Worker, ExpiryNotification, Training, TrainingDate, Applicant, QuestionnaireAnswer, TrainingOrder, Certificate) with RecordManagers and migrations — php hubleto migrate applied every table and foreign key with no cycles or errors (verified directly against information_schema).
Backend services: DocxTemplate (docx placeholder scan/merge/substitute), CertificateGenerator, ApplicantsImporter (xlsx via PhpSpreadsheet), Statistics, RetrainingCalculator, ExpiryNotifier, shared Mailer, AdminUsers.
Page/API/public controllers and Twig views for all six sections, including the public one-time-token questionnaire and catalog-sheet pages.
Cron (RetrainingReminders) registered.
All PHP lints clean; app boot and model describeColumns() verified with no runtime errors.
Not yet done (ran out of budget before reaching it):

React components (Table*.tsx/Form*.tsx) for the six new models and their Loader.tsx registration — the backend API/routes are ready for them, but the UI won't render yet.
No live browser walkthrough of the golden path (create training → date → order → applicant → certificate → questionnaire).
One operational prerequisite still needed before certificates/emails work: a mails_accounts row for AlgoCorp under Mail → Accounts (SMTP creds).
The plan file at report/claudes-plan.md (and the original plan doc) has the full spec for the remaining React layer if you pick this back up later.