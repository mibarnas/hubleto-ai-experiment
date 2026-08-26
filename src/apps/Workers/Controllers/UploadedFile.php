<?php

namespace Hubleto\App\Custom\Workers\Controllers;

/**
 * Serves anything under upload/ to signed-in users.
 *
 * upload/.htaccess denies direct HTTP access ("Require all denied"), but the
 * framework builds browser URLs for every uploaded asset as
 * `uploadUrl + '/' + <path>` -- company logos, user photos, file and image
 * inputs, mail attachments, table cell renderers. With uploadUrl pointing at
 * the folder itself, all of those return 403.
 *
 * ConfigEnv.php therefore points uploadUrl at this controller instead, so the
 * same URLs keep working but are gated on an authenticated session -- which
 * matters here because generated certificates carry personal data.
 *
 * Lives in the Workers app because it is the base dependency of the other two
 * custom apps and is therefore always enabled.
 */
class UploadedFile extends \Hubleto\Erp\Controller
{
  public bool $hideDefaultDesktop = true;

  /** Rendered inline rather than pushed as a download. */
  private const INLINE_TYPES = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'image/bmp', 'image/x-icon', 'application/pdf',
  ];

  public function render(): string
  {
    $requestedPath = urldecode($this->router()->urlParamAsString('path'));

    $uploadFolder = realpath($this->env()->uploadFolder);
    $filePath = $requestedPath === '' ? false : realpath($this->env()->uploadFolder . '/' . $requestedPath);

    // Never let a crafted path escape the upload folder, and never serve the
    // .htaccess that protects it.
    if (
      $uploadFolder === false
      || $filePath === false
      || !str_starts_with($filePath, $uploadFolder . DIRECTORY_SEPARATOR)
      || !is_file($filePath)
      || str_starts_with(basename($filePath), '.')
    ) {
      http_response_code(404);
      return '';
    }

    $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
    $disposition = in_array($mimeType, self::INLINE_TYPES, true) ? 'inline' : 'attachment';

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($filePath) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=300');

    return file_get_contents($filePath);
  }
}
