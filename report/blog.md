# AlgoCorp

## Opening prompt for planning - Opus 5 (Extra high effort)

```md
You are given a freshly installed Hubleto instance. Plan the following features.

The project focuses on creating new modules within the Hubleto platform that will record and manage applications for various trainings provided by the company AlgoCorp, s.r.o.. The software records trainings, applicants, and the training history of applicants. The software will generate certificates for applicants who have completed the trainings.

## Summary of Sections

Based on the requirements of AlgoCorp, s.r.o., the following sections will be created in the Hubleto system to support the creation and management of trainings as well as the creation and management of certificates:
*   **Trainings** - management and creation of trainings, includes statistics
*   **Dates** - management and creation of training dates, includes information about applicants
*   **Workers** - management of workers, display of information regarding previous trainings
*   **Applicants** - overview of applicants for a specific date
*   **Orders** - list of all orders, order prices
*   **Certificates** - list of all created certificates

The Hubleto system will also utilize existing sections offered by the Hubleto platform, which are:
*   **Customers** - list of companies creating an order
*   **Contacts** - list of contacts for a company/customer
*   **Companies** - list of companies (AlgoCorp, etc.)
*   **Documents** - list of all documents created within the Hubleto system, including certificates

## Description of Requirements

### Organization of Trainings 

The software will contain a list of all trainings available on the website of AlgoCorp s.r.o.. The trainings will include the following data:
- training name
- price per person
- retraining interval in years
- training number
- certificate template
- company (AlgoCorp or freely selectable from the list of companies)

The training card will display a list of all dates for the given training and training statistics.

### Training Statistics
The training card will display graphs and data about the training, which were entered by applicants in the satisfaction questionnaire at the end of the training. This data will be exportable to a CSV file.

### Training Dates
Dates can also be defined for the trainings. For a training date, it will be possible to enter the following data:
- date and time
- link to the Teams meeting

In the date card, we can see all applicants who were registered for the given training date. Through the training date, it will be possible to bulk-send the meeting link and bulk-send the satisfaction questionnaire to the applicants.

### Applicants
The date card also contains a list of applicants who are registered for the training.

The list of applicants for a date specifies:
- date
- worker
- link to questionnaire details
- link to the questionnaire
- completion/fulfillment of the training
- link to the certificate
- file of the previous certificate

For certificate generation, a button will be added to the applicant's card that will generate and send a certificate to the applicant based on the template added in the training card.

### Satisfaction Questionnaire and Catalog Sheet
After completing the training, there will be an option to send participants a link to fill out a satisfaction questionnaire with several options, as well as a catalog sheet. The links are specific to the applicant and are for one-time use. By filling out the questionnaire, the answers are recorded in the system. The data from the questionnaire can be used for various statistics.

In the catalog sheet, it will be possible to fill in the highest education obtained by the applicant and how the participation in the training was financed. General worker data will not need to be entered again, as it already exists in the system.

In the satisfaction questionnaire, the following aspects of the training can be evaluated, where a rating from 1 to 5 must be assigned:

- The course content was understandable and well-structured
- The course met my expectations
- The acquired knowledge is useful to me
- The lecturer was an expert on the topic
- The lecturer communicated effectively and answered questions
- The lecturer created a stimulating and supportive environment
- The organization of the course was effective
- Technical support (online platform) was satisfactory
- Information about the course was available and understandable
- Overall satisfaction with the course
- I would recommend this course to others

The applicant will be able to freely write text for the following questions:
- What did you like most about the course?
- What would you suggest improving?
- Recommendations for future courses

A subpage will be created where this satisfaction questionnaire can be filled out.

## Order Processing
To solve the problem with applications that may contain one or multiple applicants, applications will be handled as orders. In the order, we can see the information of the person ordering (worker or company), the price per person, and the total price for the order. In the order, we can also see all applicants who were registered for the training. The order will also indicate when it was paid and what type of order it is (private individual, company).

An individual or groups of applicants will be archived in the system as workers based on the applicants' emails. This way, we can add the trainings a worker completes under their profile.

For an application with a group of applicants, an Excel file must be processed, which must be sent along with the application. The Excel file contains applicant information that will be added to the system and assigned to the order and the requested training date.

After an order is created, a notification will also be sent to administrative users.

## Workers
The workers section contains an archive of all workers who were registered for any training. A worker profile contains the following data:
- company
- title
- name
- address
- phone number
- email
- gender
- workplace name
- workplace address

The workers table also displays the next retraining date and which training the worker must attend again.

The worker's card displays all trainings and dates the worker attended, all documents (certificates) issued to the worker, and all orders the worker is associated with, whether as a private individual or through a company order.

### Worker Notifications
Applicants will be checked daily to see if their training validity is expiring. A notification that the training validity is ending will be sent via email based on the interval specified in the training card. For an individual, the notification is sent 6 months in advance before the certificate expires. If the person does not register for a training by this time, a notification will be sent once more a month before expiration. If the persons were ordered through a company, the notification is sent at the beginning of the year with a list of workers whose training validity ends in that given year. In case the email cannot be delivered, a notification will be sent to the administrative workers.

## Certificate Creation
In the training card, there will be an option to add a certificate template. The template will be in Word format. The template contains various parameters enclosed in brackets/arrows (e.g., date, applicant's name, etc.). These parameters will be replaced during the generation of certificates for each applicant who successfully completed the training. Templates must be replaceable in the event of changes to the templates.

All necessary parameters to be replaced will be extracted from the provided templates. These parameters will be processed to function with other and additional templates as well. If additional parameters need to be added for replacement in the template, they must be requested additionally.

The certificate is created in the applicant's card by clicking a button. Every generated certificate must be saved in the system for future retrieval. Certificates are saved in a folder designated by the current year, training, and date. The certificate is linked to the given applicant and worker.

## Certificate
The table contains all certificates that were generated for the applicants. The certificate card contains the following data:
- certificate document
- certificate number in the AlgoCorp records
- date of certificate creation by AlgoCorp
- date of certificate issuance by RÚVZ (Regional Public Health Authority)
- certificate number from RÚVZ
- name of RÚVZ
- certificate validity date

When generating a certificate, these details will be used for replacement in the certificate template.

## Companies
When creating an order through a company, the company's data is automatically recorded in the system. The following company information can be entered into the system:
- contact person
- name
- registered office
- IČO (Company Registration Number)
- DIČ (Tax Identification Number)
- IČ DPH (VAT Identification Number)

In addition to general information, the company card displays a list of all workers assigned under the company.

## Contact
The Contact section serves to store information regarding the person who created an order through a company. A contact contains the following information:
- name
- phone number
- email
```

## Plan execution - Sonnet 5 (High) 

```md
execute the plan
```

## Token Break - Opus 5 (High)

```md
Given this plan, continue where the last agent left off

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
```

## Verification and UI bug fix

```md
Verify, that the plan has been executed and implemented in full. Additionally, fix the UI bug, where the Tables aren't rendering nicely right now.
```

```md
the tables are still broken though?
```

```md
can you fill in some test data?
```

## Bug fixing 2

```md
Downloading certificates doesn't work, it throws 403 forbidden.
```

```md
Why are the tables that weird with black bars inbetween? They don't look like the default Hubleto tables.
```