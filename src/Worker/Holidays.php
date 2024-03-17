<?php
declare(strict_types=1);

namespace Dienstplan\Worker;
use Dienstplan\Worker\People;
use Odan\Session\SessionInterface;

class Holidays{
    /**
     * config can hold complex arrays, but key is always a name
     * @var array<string, array<string,mixed>>
     */
    public array $config;
    /**
     * conffiles holds array(name => path_to_file)
     * @var array<string, string>
     */
    public array $conffiles;
    private \DateTimeImmutable $target_month;
    private string $month_string;
    private string $month_int;
    private string $year_int;
    private array $people_available;
    private string $path_to_configfiles;
    private SessionInterface $session;
    function __construct(SessionInterface $session)
    {
        $this->session = $session;
        $this->flash = $this->session->getFlash();
        // merge all config file for month in on big arrray
        $this->config = []; // start with pristine array
        $this->path_to_configfiles = __DIR__.'/../../data/';
    }

    private function set_target_month(\DateTimeImmutable $target_month) :void
    {
        $this->target_month = $target_month;
        $this->month_string = $target_month->format('Y_m');
        $this->month_int = $target_month->format('m');
        $this->year_int = $target_month->format('Y');
    }

    private function get_people_for_month(): array
    {
        $people = new People($this->session);
        return($people->load_for_month($this->target_month));
    }

    /**
     * @param $target_month \DateTimeImmutable
     *
     * @return array
     * @TODO finalize, then reduce load_wishes to wrapper.
     */
    public function get_holidays_for_month(\DateTimeImmutable $target_month, $add_empty_people = false, $allow_unknown_people = false): array
    {
        $this->set_target_month($target_month);

        $holidays = array();
        $people = $this->get_people_for_month();

        $holidays_file = $this->path_to_configfiles.'holidays_'.$this->month_string.'.php';
        $this->conffiles['urlaub'] = $holidays_file;

        if (file_exists($this->conffiles['urlaub'])) {
           $holidays = require($this->conffiles['urlaub']);
           // add people without wishes if requested
           if ($add_empty_people) {
               foreach (array_keys($people) as $personId) {
                   if (!array_key_exists($personId, $holidays)) {
                       $holidays[$personId] = [];
                   }
               }
           }
           // delete unknown people unless requested
           if ($allow_unknown_people == false) {
               foreach (array_keys($holidays) as $couldBeUnknown) {
                   if (!array_key_exists($couldBeUnknown, $people)) {
                        unset($holidays[$couldBeUnknown]);
                   }
               }
           }
        } else {
            $this->flash->add('warning', 'Bisher wurde kein Urlaub für diesen Monat gespeichert');
            if ($add_empty_people) {
                foreach (array_keys($people) as $personId) {
                    $holidays[$personId] = [];
                }
            }
        }
        ksort($holidays);
        return($holidays);
    }

    /**
     * wünsche_arr holds post data in shope of array(person=>day=wish)
     * @return bool
     * @throws \ErrorException
     */
    function save(\DateTimeImmutable $target_month, array $holidays_arr) :bool {
        $holidays = array();
        $this->set_target_month($target_month);

        // remove submit button value
        unset($holidays_arr['submit_holidays'], $holidays_arr['target_month']);
        // remove empty values recursively
        $holidays_arr = $this->array_remove_empty_recursive($holidays_arr);

        $allowed_dutytypes = array('U');

        foreach($holidays_arr as $personname => $holidays_arr) {
            foreach($holidays_arr as $dutyDateType) {
                list($day, $dutytype) = explode('_', $dutyDateType);
                if(!in_array($dutytype, $allowed_dutytypes)) {
                    continue;
                }
                $holidays[$personname][$day] = $dutytype;
            }
        }

        $file_name = $this->path_to_configfiles.'holidays_'.$this->year_int.'_'.$this->month_int.'.php';
        $file_content = "<?php\n return array(\n";

        foreach ($holidays as $person => $dutytype) {
            $file_content.= "\t\"".$person.'" => '."\n\t\t".var_export($dutytype, true).',';
        }
        // we should trim last komma to avoid extra parsing
        $file_content = rtrim($file_content, ',')."\n); \n";

        $success = file_put_contents($file_name, $file_content, LOCK_EX);
        // $success can not simple be returned because it holds the number of bytes written, no bool
        if(!$success) {
            throw new \ErrorException('Speichern der Dienstwünsche ist fehlgeschlagen');
        }
        return true;
    }

    /**
     * @param array<array<int|string>|int|string> $haystack
     * @return array
     */
    function array_remove_empty_recursive(array $haystack):array
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->array_remove_empty_recursive($haystack[$key]);
            }

            $haystack = array_filter($haystack); // remove empty values
        }

        return $haystack;
    }
}
