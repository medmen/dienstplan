<?php
declare(strict_types=1);

namespace Dienstplan\Worker;
use Dienstplan\Worker\People;
use Odan\Session\SessionInterface;

class Wishes{
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
    function __construct(SessionInterface $session, People $people)
    {
        $this->session = $session;
        $this->flash = $this->session->getFlash();
        $this->people = $people;
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


    /**
     * @param $target_month \DateTimeImmutable
     *
     * @return array
     */
    public function get_wishes_for_month(\DateTimeImmutable $target_month, $add_empty_people = false, $allow_unknown_people = false): array
    {
        $this->set_target_month($target_month);

        $wishes = array();
        $people = $this->people->load_for_month($target_month);

        $wishes_file = $this->path_to_configfiles.'wishes_'.$this->month_string.'.php';
        $this->conffiles['wishes'] = $wishes_file;

        $urlaub_file = $this->path_to_configfiles.'urlaub_'.$this->month_string.'.php';
        $this->conffiles['urlaub'] = $wishes_file;

        if (file_exists($this->conffiles['wishes'])) {
           $wishes = require($this->conffiles['wishes']);
           // add people without wishes if requested
           if ($add_empty_people) {
               foreach (array_keys($people) as $personId) {
                   if (!array_key_exists($personId, $wishes)) {
                       $wishes[$personId] = [];
                   }
               }
           }
           // delete unknown people unless requested
           if ($allow_unknown_people == false) {
               foreach (array_keys($wishes) as $couldBeUnknown) {
                   if (!array_key_exists($couldBeUnknown, $people)) {
                        unset($wishes[$couldBeUnknown]);
                   }
               }
           }
        } else {
            $this->flash->add('warning', 'Bisher wurden keine Wünsche für diesen Monat gespeichert');
            if ($add_empty_people) {
                foreach (array_keys($people) as $personId) {
                    $wishes[$personId] = [];
                }
            }
        }
        return($wishes);
    }

    /**
     * wünsche_arr holds post data in shope of array(person=>day=wish)
     * @return bool
     * @throws \ErrorException
     */
    function save(array $wuensche_arr) :bool {
        $wuensche = array();
        // remove submit button value
        unset($wuensche_arr['submit_wishes'], $wuensche_arr['target_month']);
        // remove empty values recursively
        $wuensche_arr = $this->array_remove_empty_recursive($wuensche_arr);

        $allowed_dutytypes = array('D', 'F');

        foreach($wuensche_arr as $personname => $wishes_arr) {
            foreach($wishes_arr as $dutyDateType) {
                list($day, $dutytype) = explode('_', $dutyDateType);
                if(!in_array($dutytype, $allowed_dutytypes)) {
                    continue;
                }
                $wuensche[$personname][$day] = $dutytype;
            }
        }

        $file_name = $this->path_to_configfiles.'wishes_'.$this->year_int.'_'.$this->month_int.'.php';
        $file_content = "<?php\n return array(\n";

        foreach ($wuensche as $person => $dutytype) {
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
