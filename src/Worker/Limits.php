<?php

declare(strict_types=1);

namespace Dienstplan\Worker;

use Odan\Session\SessionInterface;
use Odan\Session\FlashInterface;

class Limits
{
    private string $path_to_configfiles;
    private array $limits;
    private SessionInterface $session;
    private FlashInterface $flash;
    function __construct(SessionInterface $session)
    {
        $this->path_to_configfiles = __DIR__ . '/../../data/limits.php';
        $this->session = $session;
        $this->flash = $this->session->getFlash();
    }
    public function load(): array
    {
        $this->limits = require($this->path_to_configfiles);
        /**
         * @TODO: add type and sanity checks
         */
        if (!is_array($this->limits)) {
            $this->limits = [
                'total' => 6,
                'we' => 2,
                'fr' => 1,
                'max_iterations' => 500
            ];

            $this->flash->add('warning', 'no limits configured, falling back to system default');
        } else {
            $this->flash->add('success', 'successfully loaded limits');
        }

        return ($this->limits);
    }

    public function save($new_limits): bool
    {
        /**
         * @TODO: add type and sanity checks
         */
        $file_content = "<?php\n return( array(\n\t";
        $file_content .= var_export($new_limits, true) . ";\n";

        // from https://stackoverflow.com/questions/272361/how-can-i-handle-the-warning-of-file-get-contents-function-in-php#272377
        if (@file_put_contents($this->path_to_configfiles, $file_content) === false) {
            $error = error_get_last();
            $this->flash->add('error', "Saving LIMITS failed with Error: " . $error['message']);
            return false;
        } else {
            $this->flash->add('success', 'successfully saved limits');
            return true;
        }
    }
}
