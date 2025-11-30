<?php

declare(strict_types=1);

namespace PhilTenno\FilesyncGo\Dca;

use Contao\DataContainer;
use Contao\Database;

/**
 * DCA save_callback for token field.
 */
class TokenDca
{
    /**
     * Hash the provided token before saving.
     *
     * @param string       $varValue Plaintext token from the backend password field (may be empty)
     * @param DataContainer $dc      Contao data container
     *
     * @return string The hash to store in the database
     *
     * @throws \Exception on invalid input (will be shown in backend)
     */
    public function hashToken($varValue, DataContainer $dc): string
    {
        $value = (string) $varValue;

        // If the backend field is left empty during edit, keep the existing hash.
        if (trim($value) === '') {
            if (!empty($dc->id)) {
                $row = Database::getInstance()
                    ->prepare('SELECT token_hash FROM tl_filesync_tokens WHERE id = ?')
                    ->limit(1)
                    ->execute((int) $dc->id);

                return (string) $row->token_hash;
            }

            // New record with empty value -> invalid (DCA's mandatory flag should normally prevent this).
            throw new \Exception('Token darf nicht leer sein.');
        }

        // Validate max length according to spec (<= 32)
        if (mb_strlen($value) > 32) {
            throw new \Exception('Token darf maximal 32 Zeichen lang sein.');
        }

        // Optional: you can add extra validation for allowed characters here (URL-safe base64 etc.)
        // Example (uncomment to enforce): if (!preg_match('/^[A-Za-z0-9_\-]+$/', $value)) { throw new \Exception('Ungültige Zeichen im Token.'); }

        // Hash using the PHP password API (time-constant verification later with password_verify)
        $hash = password_hash($value, PASSWORD_DEFAULT);

        if ($hash === false) {
            throw new \Exception('Fehler beim Erzeugen des Token‑Hashes.');
        }

        return $hash;
    }
}