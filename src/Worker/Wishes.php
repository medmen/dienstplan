<?php
declare(strict_types=1);

namespace Dienstplan\Worker;

class Wishes{
    /**
     * config can hold complex arrays, but key is always a name
     * @var array<string, array<string,mixed>>
     */
    private array $config;
    /**
     * conffiles holds array(name => path_to_file)
     * @var array<string, string>
     */
    private array $conffiles;
    private string $month_string;
    private string $month_int;
    private string $year_int;
    private string $path_to_configfiles;
    function __construct(\DateTime $target_month)
    {
        // merge all config file for month in on big arrray
        $this->config = []; // start with pristine array
        $this->month_string = $target_month->format('Y_m');
        $this->month_int = $target_month->format('m');
        $this->year_int = $target_month->format('Y');
        $this->path_to_configfiles = __DIR__.'/../../data/';

        $conffiles['people'] = $this->path_to_configfiles.'people.php';

        foreach(['wishes', 'urlaub'] as $subconf) {
            $conffiles[$subconf] = $this->path_to_configfiles.$subconf.'_'.$this->month_string.'.php';
        }

        $this->conffiles = $conffiles;

        // load people
        if (file_exists($conffiles['people'])) {
                $this->config = array_replace($this->config, require($conffiles['people']));
        }
    }

    /**
    * @return array<string,array<int,string>> returns array like person_name=>array(integer day-in-month, wish-as-letter)
    */
    function load_wishes():array {
        $wishes = array();
        if (array_key_exists('wishes',$this->conffiles) and file_exists($this->conffiles['wishes'])) {
            $wishes = require($this->conffiles['wishes']);
            foreach ($this->config['people'] as $person => $persondata) {
                if (!in_array($person, array_keys($wishes))) {
                $wishes[$person] = [];
                }
            }
        } else {
             foreach (array_keys($this->config['people']) as $person) {
                 $wishes[$person] = [];
             }
        }
        return($wishes);
    }

    /**
     * wünsche_arr holds post data in shope of array(person=>day=wish)
     * @param array<string,array<int, string>> $wuensche_arr
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
    function array_flatten($array = null):array {
        $result = array();

        if (!is_array($array)) {
            $array = func_get_args();
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result = array_merge($result, array($key => $value));
            }
        }

        return $result;
    }
*/

/**
    function isInDateRange(DateTime $date, DateTime $startDate, DateTime $endDate) {
        return($date >= $startDate && $date <= $endDate);
    }
*/

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

    /**
    function get_people()
    {
        global $config;
        if (!is_array($config['people'])) {
            return array();
        }

        $people_available = array();
        $user_logged_in = $_SESSION['username'];

        // only Admins can change wishes for everyone
        if (isset($config['people'][$user_logged_in]['is_admin']) and
            true === $config['people'][$user_logged_in]['is_admin']) {
            foreach ($config['people'] as $i => $ppl) {
                if (is_array($ppl)) {
                    $people_available[] = $i;
                }

                if (is_string($ppl)) {
                    $people_available[] = $ppl;
                }
            }
        } else {
            # logged in user can change own wishes
            $people_available[] = $user_logged_in;
        }

        return $people_available;
    }
     */
}
