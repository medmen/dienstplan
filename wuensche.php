<?php
/**
 * Created by PhpStorm.
 * User: galak
 * Date: 02.05.17
 * Time: 21:25
 */
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

class wuensche{
    public $monthyear;

    function __construct($monthyear = false)
    {
        global $config;
        // load people
        require_once('./config/people.php');

        $now = getdate();
        $this->target_month = sprintf('%02d', $now['mon'] + 1);
        $this->target_year = $now['mon'] == 12 ? $now['year'] + 1 : $now['year'];

        // override defaults if value is passed and is of format mm_YYYY
        if($monthyear) {
            // do cruel check
            list($target_month, $target_year) = explode('_', $monthyear);
            if( is_int($target_month)
                and (0 < $target_month and $target_month < 13)
                and is_int($target_year)
                and ($target_year < $now['year'] + 2)
                and ($target_year > $now['year'] - 2)) {
                    $this->target_month = $target_month;
                    $this->target_year = $target_year;
            }
        }

        // see how many days the target month can have
        $d = new DateTime( $this->target_year.'-'.$this->target_month);
        $this->max_days_in_target_month = $d->format('t'); // see date_format
    }

    function load_wishes() {
        include_once('./config/wishes_'.$this->target_month.'_'.$this->target_year.'.php');
    }

    function get_duty($person) {
        global $config;
        $counter = 0;
        $first_of_month = new DateTime($this->target_year.'-'.$this->target_month.'-1');
        $last_of_month = new DateTime($this->target_year.'-'.$this->target_month.'-'.$this->max_days_in_target_month);

        $ret = '<input class="datepicker" type="text" size="15" name="duty_'.$person.'[] "id="duty_'.$person.'_'.$counter.'" placeholder="Dienstwunsch" ';

        if (array_key_exists($person, $config['wishes']['duty'])) {
            foreach($config['wishes']['duty'][$person] as $wid => $wish_date) {
                $counter++;
                echo "for $person wid is $wid and wishdate is "; var_dump($wish_date); echo "<br>";
                /**
                 * by convention a wish can be either a range of dates consisting
                 * of a start date and end date or a single date
                 **/
                // very crude check, TODO: make date storage and retrieval much safer
                $date_limits = explode('~', $wish_date);
                if(count($date_limits) == 2) {
                    $start_date = new DateTime(trim($date_limits[0]));
                    $end_date = new DateTime(trim($date_limits[1]));
                } else { // assume only a single date entry is given, see @TODO for this
                    $start_date = $end_date = new DateTime(trim($date_limits[0]));
                }

                // echo "StartDate is ".$start_date->format('d.m.Y')." AND EndDate is ".$end_date->format('d.m.Y')." <br>";
                // see if whole period is in target month, chop otherwise
                $start_in_range = $this->isInDateRange($start_date, $first_of_month, $last_of_month);
                $end_in_range = $this->isInDateRange($end_date, $first_of_month, $last_of_month);
                // echo "StartDate is in range: $start_in_range AND ";
                // echo "EndDate is in range: $end_in_range <br>";
                if($start_in_range && $end_in_range) {
                    if($start_date == $end_date) {
                        $ret.= ' value="'.$start_date->format('d.m.Y').'" ';
                    } else {
                        $ret.= ' value="'.$start_date->format('d.m.Y').' ~ '.$end_date->format('d.m.Y').'" ';
                    }
                    $ret.= '"><br><input class="datepicker" type="text" size="15" name="duty_'.$person.'[]" id="duty_'.$person.'_'.$counter.'" placeholder="Dienstwunsch" ';
                }
                elseif ($start_in_range) {
                    $ret.= ' value="'.$start_date->format('d.m.Y').' ~ '.$last_of_month->format('d.m.Y').'" ';
                    $ret.= '"><br><input class="datepicker" type="text" size="15" name="duty_'.$person.'[]" id="duty_'.$person.'_'.$counter.'" placeholder="Dienstwunsch" ';
                }
                elseif ($end_in_range) {
                    $ret.= ' value="'.$first_of_month->format('d.m.Y').' ~ '.$end_date->format('d.m.Y').'" ';
                    $ret.= '"><br><input class="datepicker" type="text" size="15" name="duty_'.$person.'[]" id="duty_'.$person.'_'.$counter.'" placeholder="Dienstwunsch" ';
                }
            }
        } else {
           print_r("$person is not in duty <br>");
        }
        $ret.= '><script id="sduty_'.$person.'_'.$counter.'"> $(function() { $("#duty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
        return $ret;
    }

    function get_noduty($person) {
        return '<input  class="datepicker" type="text" size="15" name="noduty_'.$person.'" id="noduty_'.$person.'_0" placeholder="Dienstfrei-Wunsch">'."<br>";
    }

    function save($wuensche_arr) {
        unset($wuensche_arr['dpupdate']);
        $wuensche_arr = $this->array_remove_empty_recursive($wuensche_arr); // remove empty values recursively
        foreach($wuensche_arr as $name => $value) {
            list($dutytype, $personname) = explode('_', $name);

            // cruel check
            $allowed_dutytypes = array('duty', 'noduty');
            if(!in_array($dutytype, $allowed_dutytypes)) {
                continue;
            }

            foreach($value as $vid => $daterange) {
                ${$dutytype}[$personname][$vid] = $daterange;
            }

        }

        $file_name = 'config/wishes_'.$this->target_month.'_'.$this->target_year.'.php';
        $file_content = "<?php\nglobal \$config;\n";
        foreach ($allowed_dutytypes as $dutytype) {
            if(is_array(${$dutytype})) {
                $file_content.= '$config[\'wishes\'][\''.$dutytype.'\'] = '.var_export(${$dutytype}, true).";\n";
            }
        }

        $success = file_put_contents($file_name, $file_content, LOCK_EX);
        // $success can not simple be returned because it holds the number of bytes written, no bool
        if($success) {
            return true;
        }

    }



    // HELPER FUNCTIONS
    function isInDateRange(DateTime $date, DateTime $startDate, DateTime $endDate) {
        return($date >= $startDate && $date <= $endDate);
    }

    function full_date($target_day) {
        return(strtotime($this->target_year.'-'.$this->target_month.'-'.$target_day));
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
}

// load people
$wuensche = new wuensche();
$monat = new DateTime($wuensche->target_year.'-'.$wuensche->target_month);
setlocale(LC_TIME, 'de_DE.UTF-8');
$monat_formatiert = strftime("%B %Y", $monat->getTimestamp());

if($_POST['dpupdate']) {
    var_export($_POST);
    $success = $wuensche->save($_POST);
    if($success) {
        $message = "Wünsche wurden erfolgreich gespeichert";
    }
}

$wuensche->load_wishes();

$output = '<form id="frm_duty" method= "post" action="./wuensche.php"><div class="gridcontainer">';
foreach($config['people'] as $person) {
    $output.= '<div id="person_'.$person.'" class="box">'.$person.'</div>'."\n";
    $output.= '<div id="duty_'.$person.'" class="box autoinput">'.$wuensche->get_duty($person).'</div>'."\n";
    $output.= '<div id="noduty_'.$person.'" class="box autoinput">'.$wuensche->get_noduty($person).'</div>'."\n";
}
$output.= '</div><input type="submit" name="dpupdate"></form>';
?>

<html lang="de">
<head>
<?php include 'header.html';?>
    <link rel="stylesheet" href="css/daterangepicker.css">
    <script src="js/moment.min.js"></script>
    <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/jq_new-inputfield.js"></script>
    <script>
        $.datepicker_settings =
            {
                singleMonth: true,
                showShortcuts: false,
                showTopbar: false,
                startOfWeek: 'monday',
                separator : ' ~ ',
                format: 'DD.MM.YYYY',
                startDate: '<?php echo '01.'.$wuensche->target_month.'.'.$wuensche->target_year;?>',
                endDate: '<?php echo $wuensche->max_days_in_target_month.'.'.$wuensche->target_month.'.'.$wuensche->target_year;?>'
            };
    </script>
    <script src="js/jquery.daterangepicker.js"></script>
</head>
<body>
<div class="container">
    <?php include 'navigation.html';?>
    <h1>WÜNSCHE für <?php echo $monat_formatiert; ?></h1>
    <section class="row">
        <aside><?php echo $message; ?></aside>
    </section>

    <section class="row">
        <article id="main" class="nine columns">
            <?php   echo $output; ?>
        </article>
        <div class="two columns">
            <aside>
                <pre>here be dragons</pre>
            </aside>
            <aside id="debug">
                <pre>here too</pre>
            </aside>
        </div>
    </section>
</div>
</body>
</html>

