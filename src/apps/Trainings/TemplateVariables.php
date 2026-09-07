<?php

namespace Hubleto\App\Custom\Trainings;

/**
 * Hard-coded catalogue of the values that can be substituted into a
 * certificate template.
 *
 * Templates are authored in Word by non-developers and use the client's
 * `<< value >>` syntax. The set of substitutable values is deliberately fixed
 * here (rather than derived from the model) so that:
 *
 *   - the template scanner can tell a *supported* placeholder from a typo, and
 *   - a new value is added in exactly one place when a template asks for it.
 *
 * Every entry lists Slovak aliases as well, because that is how the client
 * writes them in the documents: `<< meno a priezvisko >>` and
 * `<< applicant_name >>` resolve to the same value.
 */
class TemplateVariables
{
  const GROUP_ATTENDEE = 'attendee';
  const GROUP_TRAINING = 'training';
  const GROUP_CERTIFICATE = 'certificate';
  const GROUP_PROVIDER = 'provider';

  /**
   * key => [label, group, required, aliases]
   *
   * `required` marks a value a certificate is not valid without; the scanner
   * warns when a template does not use it.
   *
   * @var array<string, array{label: string, group: string, required: bool, aliases: string[]}>
   */
  const CATALOG = [
    // --- attendee -------------------------------------------------------
    'applicant_name' => [
      'label' => 'Full name of the attendee',
      'group' => self::GROUP_ATTENDEE,
      'required' => true,
      'aliases' => ['meno_a_priezvisko', 'meno_uchadzaca', 'meno_ucastnika', 'cele_meno', 'meno', 'uchadzac', 'ucastnik'],
    ],
    'first_name' => [
      'label' => 'First name',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['krstne_meno', 'worker_first_name'],
    ],
    'last_name' => [
      'label' => 'Last name',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['priezvisko', 'worker_last_name'],
    ],
    'title_before' => [
      'label' => 'Title before name',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['titul_pred'],
    ],
    'title_after' => [
      'label' => 'Title after name',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['titul_za'],
    ],
    'birth_number' => [
      'label' => 'Birth number',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['rodne_cislo', 'worker_birth_number'],
    ],
    'email' => [
      'label' => 'Email of the attendee',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['worker_email', 'e_mail'],
    ],
    'address' => [
      'label' => 'Address of the attendee',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['adresa', 'bydlisko', 'ulica'],
    ],
    'city' => [
      'label' => 'City of the attendee',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['mesto', 'obec'],
    ],
    'zip' => [
      'label' => 'ZIP of the attendee',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['psc'],
    ],
    'workplace' => [
      'label' => 'Workplace of the attendee',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['pracovisko', 'worker_workplace'],
    ],
    'employer' => [
      'label' => 'Employer of the attendee',
      'group' => self::GROUP_ATTENDEE,
      'required' => false,
      'aliases' => ['zamestnavatel', 'firma_uchadzaca'],
    ],

    // --- training -------------------------------------------------------
    'training_name' => [
      'label' => 'Training name',
      'group' => self::GROUP_TRAINING,
      'required' => true,
      'aliases' => ['nazov_skolenia', 'skolenie', 'kurz', 'nazov_kurzu'],
    ],
    'training_number' => [
      'label' => 'Training number',
      'group' => self::GROUP_TRAINING,
      'required' => false,
      'aliases' => ['cislo_skolenia', 'cislo_kurzu'],
    ],
    'training_date' => [
      'label' => 'Date the training took place',
      'group' => self::GROUP_TRAINING,
      'required' => true,
      'aliases' => ['datum_skolenia', 'datum_kurzu', 'datum_konania'],
    ],
    'training_date_end' => [
      'label' => 'Date the training ended',
      'group' => self::GROUP_TRAINING,
      'required' => false,
      'aliases' => ['datum_ukoncenia', 'datum_konca'],
    ],
    'training_interval' => [
      'label' => 'Retraining interval in years',
      'group' => self::GROUP_TRAINING,
      'required' => false,
      'aliases' => ['interval_preskolenia', 'perioda'],
    ],

    // --- certificate ----------------------------------------------------
    'certificate_number' => [
      'label' => 'Certificate number',
      'group' => self::GROUP_CERTIFICATE,
      'required' => true,
      'aliases' => ['cislo_osvedcenia', 'cislo_certifikatu', 'internal_number', 'evidencne_cislo'],
    ],
    'external_number' => [
      'label' => 'External (accredited) certificate number',
      'group' => self::GROUP_CERTIFICATE,
      'required' => false,
      'aliases' => ['externe_cislo', 'cislo_akreditacie', 'ruvz_certificate_number', 'ruvz_cislo'],
    ],
    'name_validator' => [
      'label' => 'Accreditation authority',
      'group' => self::GROUP_CERTIFICATE,
      'required' => false,
      'aliases' => ['akreditacna_entita', 'akreditacia', 'ruvz', 'ruvz_name'],
    ],
    'date_issued' => [
      'label' => 'Date the certificate was issued',
      'group' => self::GROUP_CERTIFICATE,
      'required' => true,
      'aliases' => ['datum_vydania', 'datum', 'date', 'vydane_dna'],
    ],
    'valid_until' => [
      'label' => 'Certificate valid until',
      'group' => self::GROUP_CERTIFICATE,
      'required' => false,
      'aliases' => ['platnost_do', 'datum_platnosti', 'date_expiration', 'plati_do'],
    ],

    // --- provider -------------------------------------------------------
    'company_name' => [
      'label' => 'Name of the training provider',
      'group' => self::GROUP_PROVIDER,
      'required' => false,
      'aliases' => ['nazov_spolocnosti', 'dodavatel', 'poskytovatel', 'organizator'],
    ],
    'company_id' => [
      'label' => 'Registration ID (ICO) of the provider',
      'group' => self::GROUP_PROVIDER,
      'required' => false,
      'aliases' => ['ico', 'company_ico'],
    ],
    'tax_id' => [
      'label' => 'Tax ID (DIC) of the provider',
      'group' => self::GROUP_PROVIDER,
      'required' => false,
      'aliases' => ['dic', 'company_dic'],
    ],
    'vat_id' => [
      'label' => 'VAT ID (IC DPH) of the provider',
      'group' => self::GROUP_PROVIDER,
      'required' => false,
      'aliases' => ['ic_dph', 'company_ic_dph'],
    ],
    'company_address' => [
      'label' => 'Address of the provider',
      'group' => self::GROUP_PROVIDER,
      'required' => false,
      'aliases' => ['adresa_spolocnosti', 'sidlo'],
    ],
  ];

  /** @return string[] */
  public static function keys(): array
  {
    return array_keys(self::CATALOG);
  }

  /** @return string[] keys a valid certificate template has to contain */
  public static function requiredKeys(): array
  {
    return array_keys(array_filter(self::CATALOG, fn($v) => $v['required']));
  }

  /**
   * Resolves a normalized placeholder (canonical key or alias) to its
   * canonical key, or null when the template asked for something unknown.
   */
  public static function resolve(string $normalizedKey): ?string
  {
    if (isset(self::CATALOG[$normalizedKey])) return $normalizedKey;

    foreach (self::CATALOG as $key => $definition) {
      if (in_array($normalizedKey, $definition['aliases'], true)) return $key;
    }

    return null;
  }

  public static function label(string $key): string
  {
    return self::CATALOG[$key]['label'] ?? $key;
  }
}
