<?php

namespace Hubleto\App\Custom\Trainings;

/**
 * Discovers and substitutes `<placeholder>` parameters in a .docx template.
 *
 * Word splits a typed placeholder across multiple <w:t> runs whenever
 * formatting or spell-check state changes mid-word, so a template author's
 * "<date>" can end up on disk as separate runs "<da" + "te>". Before
 * scanning or substituting, every paragraph's runs are merged into one so
 * placeholders are found and replaced reliably regardless of how Word split
 * them. This collapses per-run formatting within a paragraph, which is an
 * acceptable trade-off for plain-text certificate parameters.
 *
 * Only ext-zip and DOMDocument are used (no PhpWord dependency), since the
 * only thing needed is scalar text substitution, not full document editing.
 */
class DocxTemplate
{
  private const PLACEHOLDER_PATTERN = '/<\s*([\p{L}\p{N}_ .\'-]+?)\s*>/u';

  public function __construct(private string $docxPath)
  {
    if (!is_file($this->docxPath)) {
      throw new \Exception("Template file not found: {$this->docxPath}");
    }
  }

  /**
   * @return string[] normalized placeholder keys found in the template
   */
  public function scanPlaceholders(): array
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

      foreach ($this->extractText($dom) as $text) {
        if (preg_match_all(self::PLACEHOLDER_PATTERN, $text, $matches)) {
          foreach ($matches[1] as $raw) {
            $placeholders[$this->normalizeKey($raw)] = true;
          }
        }
      }
    }

    $zip->close();

    return array_keys($placeholders);
  }

  /**
   * Renders the template to $outPath with placeholders substituted from
   * $vars (keyed by normalized placeholder name). Returns the list of
   * placeholders found in the template that had no matching value in $vars
   * -- those are left untouched in the output rather than blanked, so a
   * missing parameter is visible instead of silently disappearing.
   *
   * @param array<string, string> $vars
   * @return string[] unresolved placeholder keys
   */
  public function render(array $vars, string $outPath): array
  {
    if (!copy($this->docxPath, $outPath)) {
      throw new \Exception("Could not write certificate to: {$outPath}");
    }

    $unresolved = [];

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
        $original = $node->nodeValue;
        if (!str_contains($original, '<')) continue;

        $replaced = preg_replace_callback(self::PLACEHOLDER_PATTERN, function ($m) use ($vars, &$unresolved) {
          $key = $this->normalizeKey($m[1]);
          if (array_key_exists($key, $vars)) return (string) $vars[$key];
          $unresolved[$key] = true;
          return $m[0];
        }, $original);

        if ($replaced !== $original) $node->nodeValue = $replaced;
      }

      $zip->addFromString($partName, $dom->saveXML());
    }

    $zip->close();

    return array_keys($unresolved);
  }

  /**
   * Writes a copy of the template with every paragraph's runs merged, so a
   * placeholder Word split across runs becomes a single contiguous string.
   *
   * PhpWord's own fixBrokenMacros() cannot do this for the client's `<name>`
   * syntax: the delimiters appear XML-escaped, and the regex it builds from
   * `&lt;` contains `\l`, which PCRE2 rejects. Normalising here first means
   * PhpWord only ever sees whole placeholders.
   */
  public function writeRunMergedCopy(string $outPath): void
  {
    if (!copy($this->docxPath, $outPath)) {
      throw new \Exception("Could not write normalised template to: {$outPath}");
    }

    $zip = new \ZipArchive();
    if ($zip->open($outPath) !== true) {
      throw new \Exception("Could not open template as a .docx archive: {$outPath}");
    }

    foreach ($this->getTemplatePartNames($zip) as $partName) {
      $xml = $zip->getFromName($partName);
      if ($xml === false) continue;

      $dom = $this->loadPart($xml);
      $this->mergeRunsPerParagraph($dom);
      $zip->addFromString($partName, $dom->saveXML());
    }

    $zip->close();
  }

  /**
   * @return string[] zip entry names for the document body, headers and footers
   */
  private function getTemplatePartNames(\ZipArchive $zip): array
  {
    $parts = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $name = $zip->getNameIndex($i);
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

      $first = true;
      foreach ($textNodes as $node) {
        $node->nodeValue = $first ? $combined : '';
        $first = false;
      }
    }
  }

  /**
   * @return string[]
   */
  private function extractText(\DOMDocument $dom): array
  {
    $texts = [];
    foreach ($this->getTextNodes($dom) as $node) $texts[] = $node->nodeValue;
    return $texts;
  }

  private function getTextNodes(\DOMDocument $dom): \DOMNodeList
  {
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    return $xpath->query('//w:t');
  }

  private function normalizeKey(string $raw): string
  {
    $key = strtolower(trim($raw));
    $key = preg_replace('/[^a-z0-9]+/', '_', $key);
    return trim($key, '_');
  }
}
