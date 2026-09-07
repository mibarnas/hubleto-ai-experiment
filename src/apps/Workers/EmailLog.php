<?php

namespace Hubleto\App\Custom\Workers;

/**
 * Append-only log of the bulk emails this project sends.
 *
 * The framework logger is gated behind the `debugLevel` config value, which is
 * not set in this installation, so nothing it is given ever reaches disk. Mail
 * delivery is exactly the thing an operator needs a trail of, so it is written
 * here unconditionally: one line per event under
 * `log/emails/<YYYY-MM>.log`.
 */
class EmailLog extends \Hubleto\Erp\Core
{
  /**
   * @param array<string, mixed> $context Extra key=value pairs, e.g. the
   *   schedule the mail belongs to or how many recipients it reached.
   */
  public function log(string $event, array $context = []): void
  {
    $line = date('Y-m-d H:i:s')
      . ' [' . $event . ']'
      . ' user=' . ($this->authProvider()->getUserId() ?: '-')
    ;

    foreach ($context as $key => $value) {
      if (is_array($value)) $value = implode('|', $value);
      if (is_bool($value)) $value = $value ? 'yes' : 'no';
      $line .= ' ' . $key . '=' . str_replace(["\r", "\n"], ' ', (string) $value);
    }

    $this->write($line);
  }

  private function write(string $line): void
  {
    $folder = $this->config()->getAsString('logFolder');
    if ($folder === '' || !is_dir($folder)) return;

    $folder .= '/emails';
    if (!is_dir($folder) && !@mkdir($folder, 0775, true)) return;

    // Logging must never be the reason a send fails, so a write error is
    // swallowed rather than propagated.
    @file_put_contents($folder . '/' . date('Y-m') . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
  }
}
