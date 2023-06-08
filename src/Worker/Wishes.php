<?php
namespace Dienstplan\Worker;

class Wishes{
    private array $config = array();
    private $month_string = null;
    private $month_name = null;
    private $month_int = null;
    private $year_int = null;
    private $path_to_configfiles = null;

    function __construct(\DateTimeImmutable $target_month)
    {
        // merge all config file for month in on big arrray
        $this->config = []; // start with pristine array
        $this->conffiles = [];

        $this->month_string = $target_month->format('Y_m');
        $this->month_name = $target_month->format('F');
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

        $this->days_in_target_month = cal_days_in_month(CAL_GREGORIAN, $target_month->format('m'), $target_month->format('Y'));
    }

    function load_wishes():array {
        $wishes = array();
        if (file_exists($this->conffiles['wishes'])) {
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

    // HELPER FUNCTIONS
    function getdebug() {
        global $config;
        if(true == $config['general']['debug']) {
            return $this->array_flatten($this->debug);
        } else {
            return false;
        }
    }

    function array_flatten($array = null) {
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

    function isInDateRange(DateTime $date, DateTime $startDate, DateTime $endDate) {
        return($date >= $startDate && $date <= $endDate);
    }

    function array_remove_empty_recursive($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->array_remove_empty_recursive($haystack[$key]);
            }

            $haystack = array_filter($haystack); // remove empty values
        }

        return $haystack;
    }

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
}
