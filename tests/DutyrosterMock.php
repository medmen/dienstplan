<?php
declare(strict_types=1);

namespace DienstplanTest;

use Dienstplan\Worker\Dutyroster;

/**
 * Setup: extend Dutyroster class to add a setter for config data
 */
class DutyrosterMock extends Dutyroster
{
    public function set_config_data(array $data): void
    {
        $this->config = $data;
    }

    public function get_config_data(string $key): array
    {
        if (is_array($this->config) and array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        return [];
    }

    public function set_dienstplan(array $dp): void
    {
        $this->dienstplan = $dp;
    }
}
