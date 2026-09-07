<?php

namespace Hubleto\App\Custom\Trainings;

/**
 * Discovers, validates and substitutes `<< value >>` parameters in a .docx
 * certificate template.
 *
 * Word splits a typed placeholder across multiple <w:t> runs whenever
 * formatting or spell-check state changes mid-word, so a template author's
 * "<< datum >>" can end up on disk as separate runs "<< da" + "tum >>".
 * Before scanning or substituting, every paragraph's runs are merged into one
 * so placeholders are found and replaced reliably regardless of how Word split
 * them. This collapses per-run formatting within a paragraph, which is an
 * acceptable trade-off for plain-text certificate parameters.
 *
 * Only ext-zip and DOMDocument are used (no PhpWord dependency): PhpWord's
 * TemplateProcessor cannot handle these delimiters -- in the document XML they
 * appear XML-escaped, and the regex it builds from `&lt;` contains `\l`, which
 * PCRE2 rejects.
 */
class DocxTemplate
{
  /**
   * The client authors placeholders as `<< value >>`. Single angle brackets are
   * deliberately NOT matched: certificate bodies contain ordinary `<` and `>`
   * characters, and matching them produced false positives.
   */
  private const PLACEHOLDER_PATTERN = '/<<\s*([\p{L}\p{N}_ .\'-]+?)\s*>>/u';

  public function __construct(private string $docxPath)
  {
    if (!is_file($this->docxPath)) {
      throw new \Exception("Template file not found: {$this->docxPath}");
    }
  }

  /**
   * Scans the template and reports what it asks for.
   *
   * @return array{
   *   found: string[],
   *   unknown: array<string, string>,
   *   missingRequired: string[],
   *   raw: array<string, string>
   * }
   *   found            canonical keys the template uses and we can supply
   *   unknown          normalized key => the text as written, for placeholders
   *                    that match no known value (typo or unsupported)
   *   missingRequired  canonical keys a certificate needs but the template
   *                    never uses
   *   raw              canonical key => the text as written in the template
   */
  public function analyse(): array
  {
    $found = [];
    $unknown = [];
    $raw = [];

    foreach ($this->scanRawPlaceholders() as $normalized => $asWritten) {
      $canonical = TemplateVariables::resolve($normalized);
      if ($canonical === null) {
        $unknown[$normalized] = $asWritten;
      } else {
        $found[$canonical] = true;
        $raw[$canonical] = $asWritten;
      }
    }

    return [
      'found' => array_keys($found),
      'unknown' => $unknown,
      'missingRequired' => array_values(array_diff(TemplateVariables::requiredKeys(), array_keys($found))),
      'raw' => $raw,
    ];
  }

  /**
   * Renders the template to $outPath, substituting each placeholder with the
   * value of its canonical key in $vars.
   *
   * A placeholder we have no value for is left untouched in the output rather
   * than blanked, so a missing parameter is visible on the document instead of
   * silently disappearing.
   *
   * @param array<string, string> $vars canonical key => value
   * @return array{unresolved: string[], unknown: string[]}
   */
  public function render(array $vars, string $outPath): array
  {
    if (!copy($this->docxPath, $outPath)) {
      throw new \Exception("Could not write certificate to: {$outPath}");
    }

    $unresolved = [];
    $unknown = [];

    $zip = new \ZipArchive();
    if ($zip->open($outPath) !== true) {
      throw new \Exception("Could not open generated certificate as a .docx archive: {$outPath}");
    }

    foreach ($this->getTemplatePartNames($zip) as $partName) {
      $xml = $zip->getFromName($partName);
      if ($xml === false) continue;

      $dom = $this->loadPart($xml);
      $this->mergeRunsPerParagraph($dom);

      foreach ($this->getTextNodes($dom) as $node) {
        $original = (string) $node->nodeValue;
        if (!str_contains($original, '<<')) continue;

        $replaced = preg_replace_callback(self::PLACEHOLDER_PATTERN, function ($m) use ($vars, &$unresolved, &$unknown) {
          $canonical = TemplateVariables::resolve($this->normalizeKey($m[1]));

          if ($canonical === null) {
            $unknown[$this->normalizeKey($m[1])] = true;
            return $m[0];
          }

          if (array_key_exists($canonical, $vars) && $vars[$canonical] !== null && $vars[$canonical] !== '') {
            return (string) $vars[$canonical];
          }

          $unresolved[$canonical] = true;
          return $m[0];
        }, $original);

        if ($replaced !== $original) $node->nodeValue = $replaced;
      }

      $zip->addFromString($partName, (string) $dom->saveXML());
    }

    $zip->close();

    return [
      'unresolved' => array_keys($unresolved),
      'unknown' => array_keys($unknown),
    ];
  }

  /**
   * @return array<string, string> normalized key => placeholder text as written
   */
  private function scanRawPlaceholders(): array
  {
    $placeholders = [];

    $zip = new \ZipArchive();
    if ($zip->open($this->docxPath) !== true) {
      throw new \Exception("Could not open template as a .docx archive: {$this->docxPath}");
    }

    foreach ($this->getTemplatePartNames($zip) as $partName) {
      $xml = $zip->getFromName($partName);
      if ($xml === false) continue;

      $dom = $this->loadPart($xml);
      $this->mergeRunsPerParagraph($dom);

      foreach ($this->getTextNodes($dom) as $node) {
        $text = (string) $node->nodeValue;
        if (!str_contains($text, '<<')) continue;

        if (preg_match_all(self::PLACEHOLDER_PATTERN, $text, $matches)) {
          foreach ($matches[1] as $asWritten) {
            $placeholders[$this->normalizeKey($asWritten)] = trim($asWritten);
          }
        }
      }
    }

    $zip->close();

    return $placeholders;
  }

  /**
   * @return string[] zip entry names for the document body, headers and footers
   */
  private function getTemplatePartNames(\ZipArchive $zip): array
  {
    $parts = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $name = (string) $zip->getNameIndex($i);
      if ($name === 'word/document.xml' || preg_match('#^word/(header|footer)\d*\.xml$#', $name)) {
        $parts[] = $name;
      }
    }
    return $parts;
  }

  private function loadPart(string $xml): \DOMDocument
  {
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput = false;
    if (!$dom->loadXML($xml, LIBXML_NOENT)) {
      throw new \Exception('Could not parse .docx XML part.');
    }
    return $dom;
  }

  /**
   * Merges every <w:t> run within each <w:p> paragraph into the first run,
   * blanking the rest, so a placeholder split across runs becomes whole.
   */
  private function mergeRunsPerParagraph(\DOMDocument $dom): void
  {
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    foreach ($xpath->query('//w:p') as $paragraph) {
      $textNodes = $xpath->query('.//w:r/w:t', $paragraph);
      if ($textNodes->length < 2) continue;

      $combined = '';
      foreach ($textNodes as $node) $combined .= $node->nodeValue;

      // Nothing to stitch together if the paragraph has no placeholder at all;
      // leaving it alone preserves its per-run formatting.
      if (!str_contains($combined, '<<')) continue;

      $first = true;
      foreach ($textNodes as $node) {
        $node->nodeValue = $first ? $combined : '';
        $first = false;
      }
    }
  }

  private function getTextNodes(\DOMDocument $dom): \DOMNodeList
  {
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    return $xpath->query('//w:t');
  }

  /** `<< Meno a priezvisko >>` and `<<meno_a_priezvisko>>` are the same key. */
  private function normalizeKey(string $raw): string
  {
    $key = trim($raw);
    // Strip Slovak diacritics so `<< dátum >>` and `<< datum >>` both resolve.
    $key = (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
    $key = strtolower($key);
    $key = (string) preg_replace('/[^a-z0-9]+/', '_', $key);
    return trim($key, '_');
  }
}
