<?php

namespace HubletoProject\Dependency;

/**
 * Translator that also reads dictionaries from `PROJECT_FOLDER/lang/<language>`.
 *
 * The stock translator resolves every dictionary to `vendor/hubleto/erp/lang`,
 * which is not part of this repository and is wiped by `composer install`.
 * Custom apps (`Hubleto\App\Custom\...`) therefore keep their dictionaries in
 * the project, and anything found there is merged over the packaged one so a
 * community string can be overridden without touching vendor/.
 *
 * Registered in boot.php.
 */
class Translator extends \Hubleto\Framework\Translator
{
  /** Contexts of custom apps always resolve to the project's lang folder. */
  private const PROJECT_CONTEXT_PREFIX = 'hubleto-app-custom-';

  public function getDictionaryFilename(\Hubleto\Framework\Interfaces\CoreInterface $core, string $language, string $context): string
  {
    $projectFile = $this->getProjectDictionaryFilename($core, $language, $context);

    if ($projectFile !== '' && str_starts_with(basename($projectFile), self::PROJECT_CONTEXT_PREFIX)) {
      return $projectFile;
    }

    return parent::getDictionaryFilename($core, $language, $context);
  }

  public function loadDictionary(\Hubleto\Framework\Interfaces\CoreInterface $core, string $language, string $context): void
  {
    if ($language == 'en') return;
    if (!empty($this->dictionary[$language][$context])) return;

    parent::loadDictionary($core, $language, $context);

    $projectFile = $this->getProjectDictionaryFilename($core, $language, $context);
    if ($projectFile === '' || !is_file($projectFile)) return;

    $this->dictionary[$language][$context] = array_replace_recursive(
      (array) ($this->dictionary[$language][$context] ?? []),
      (array) @json_decode((string) file_get_contents($projectFile), true)
    );
  }

  /**
   * Feeds the browser-side dictionary (see Desktop controller), so the React
   * components of custom apps are translated too.
   */
  public function loadFullDictionary(\Hubleto\Framework\Interfaces\CoreInterface $core, string $language): array
  {
    $dictionary = parent::loadFullDictionary($core, $language);

    if (strlen($language) !== 2) return $dictionary;

    $folder = $core->env()->projectFolder . "/lang/{$language}";
    if (!is_dir($folder)) return $dictionary;

    foreach ((array) scandir($folder) as $file) {
      if (substr($file, -5) !== '.json') continue;
      try {
        $context = substr($file, 0, -5);
        $dictionary[$context] = array_replace_recursive(
          (array) ($dictionary[$context] ?? []),
          (array) json_decode((string) file_get_contents($folder . '/' . $file), true)
        );
      } catch (\Throwable $e) {
        // A broken project dictionary must never take the whole desktop down.
      }
    }

    return $dictionary;
  }

  private function getProjectDictionaryFilename(\Hubleto\Framework\Interfaces\CoreInterface $core, string $language, string $context): string
  {
    if (strlen($language) !== 2) return '';
    return $core->env()->projectFolder . '/lang/' . $language . '/' . strtolower(strtr($context, '\\/', '--')) . '.json';
  }
}
