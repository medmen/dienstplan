<?php
declare(strict_types=1);

namespace Dienstplan\Worker;

use Odan\Session\FlashInterface;
use Odan\Session\SessionInterface;

class People {
    private string $path_to_configfiles;
    private SessionInterface $session;
    private string $id;
    private string $firstname;
    private string $lastname;
    private string $fullname;
    private bool $is_admin;
    private bool|array $inactive;

    function __construct(SessionInterface $session) {

        $this->path_to_configfiles = __DIR__ . '/../../data/people.php';
        $this->session = $session;
    }

    function load_all() {
        $all_people = require($this->path_to_configfiles);
        /**
         * @TODO: add type and sanity checks
         */
        if(!is_array($all_people)) {
            $all_people = array();
        }
        return($all_people);
    }

    function load_for_month(\DateTimeImmutable $target_month) {
        $this->flash = $this->session->getFlash();
        $all_people = $this->load_all();
        foreach ($all_people as $id => $person) {
            if($this->isInactive($id, $target_month)) {
                unset($all_people[$id]);
            }
        }

        if(count($all_people) == 0) {
            $this->flash->add('error', 'no people available');
        }

        if(count($all_people) < 5) {
            $this->flash->add('warning', 'less than 5 people available');
        }

        $this->flash->add('info', 'loaded '.count($all_people).' active People');
        return($all_people);
    }

    private function id_exists($id) {
        if(in_array($id, array_keys($this->load_all()))) {
            return true;
        }
        return false;
    }

    function setId($id) {
        $this->id = $id;
    }

    function getbyId($id):array|null
    {
        return $this->load_all()[$id];
    }

    function setFullname($id) {
        $person = $this->getbyId($id);
        $person['fullname'] = $person['firstname'].' '.$person['lastname'];
    }

    function setAdminflag($id, $is_admin) {
        $person = $this->getbyId($id);
        $person['is_admin'] = filter_var($is_admin, FILTER_VALIDATE_BOOLEAN);
    }
    function isAdmin($id){
        return($this->getbyId($id)[$is_admin]);
    }

    function getInactive($id, \DateTimeImmutable $target_month) {
        $person = $this->getbyId($id);
        $inactive = $person['inactive'];
        if (is_array($inactive) and
            array_key_exists('start', $inactive) and
            array_key_exists('end', $inactive) and
            $this->validateDate($inactive['start']) and
            $this->validateDate($inactive['end'])) {

           return $inactive;
        }

        if($this->isBoolish($inactive)) {
            return($inactive);
        }
    }

    function validateDate($date, $format = 'd.m.Y')
    {
        $d = \DateTimeImmutable::createFromFormat($format, $date);
// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    /**
     * Check "Booleanic" Conditions :)
     *
     * @param  [mixed]  $variable  Can be anything (string, bol, integer, etc.)
     * @return [boolean]           Returns TRUE  for "1", "true", "on" and "yes"
     *                             Returns FALSE for "0", "false", "off" and "no"
     *                             Returns NULL otherwise.
     */
    function isBoolish($variable)
    {
        if (!isset($variable)) return null;
        return filter_var($variable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    function createFromNamedArray($person_data, $id=null) :Person|null {
        if (isset($id)) {$this->setId($id);} else {$this->generateId($person_data);}
        // ease transition from old cod
        if (isset($person_data['fullname'])) {
            $firstlastname = explode(' ', trim($person_data['fullname']));
            $person_data['firstname'] = trim($firstlastname[0]);
            $person_data['lastname'] = trim(end($firstlastname));
        }
        if (isset($person_data['firstname'])) {$this->setFirstname($person_data['firstname']);} else {return null;}
        if (isset($person_data['lastname'])) {$this->setLastname($person_data['lastname']);} else {return null;}
        $this->setFullname();
        if (isset($person_data['is_admin'])) $this->setAdminflag($person_data['is_admin']);
        if (isset($person_data['inactive'])) $this->setInactive($person_data['inactive']);
        return $this;
    }

    private function generateId($person_data) :string {
        if (isset($person_data['fullname'])) {
            $firstlastname = explode(' ', trim($person_data['fullname']));
            $person_data['firstname'] = trim($firstlastname[0]);
            $person_data['lastname'] = trim(end($firstlastname));
        }

        $prefix = $person_data['lastname'].substr($person_data['firstname'], 0,1); // lastname + first letter of firstname

        return (uniqid($prefix));
    }

    function isInactive(string $persoId, \DateTimeImmutable $date = new \DateTimeImmutable()) {
        $person = $this->getbyId($id);
        $inactive = $person['inactive'];
        if (is_array($inactive) and
            array_key_exists('start', $inactive) and
            array_key_exists('end', $inactive) and
            $this->validateDate($inactive['start']) and
            $this->validateDate($inactive['end'])) {
                $startdate = \DateTimeImmutable::createFromFormat('d.m.Y', $inactive['start']);
                $enddate = \DateTimeImmutable::createFromFormat('d.m.Y', $inactive['end']);
                if($startdate instanceof \DateTimeImmutable and $enddate instanceof \DateTimeImmutable) {
                    return $this->is_date_in_range($date, $startdate, $enddate);
                }
        }

        if ($this->isBoolish($inactive)) {
            return($inactive);
        }

        return false;
    }

    function __to_array() {
        return (array)$this;
    }

    function is_date_in_range(\DateTimeImmutable $date, \DateTimeImmutable $start, \DateTimeImmutable $end) {
        return $date > $start && $date < $end;
    }

}
