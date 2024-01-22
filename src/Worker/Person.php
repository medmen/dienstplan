<?php
declare(strict_types=1);

namespace Dienstplan\Worker;

class Person {
    private string $id;
    private string $firstname;
    private string $lastname;
    private string $fullname;
    private bool $is_admin;
    private bool|array $inactive;

/*    function __construct() {
        $this->id = (string)$person_data['id'];
        $this->firstname = (string)$person_data['firstname'];
        $this->lastname = (string)$person_data['lastname'];
        $this->fullname = (string)$person_data['fullname'];
        $this->is_admin = boolval($person_data['is_admin']) ?? false;
        $this->inactive = $person_data['inactive'];

    }*/

    function setId($id) {
        $this->id = $id;
    }

    function getId(){
        return $this->id;
    }

    function setFirstname($firstname) {
        $this->firstname = $firstname;
    }
    function getFirstname(){
        return $this->firstname;
    }

     function setLastname($lastname) {
        $this->lastname = $lastname;
    }
    function getLastname(){
        return $this->lastname;
    }

    function setFullname() {
        $this->fullname = $this->firstname.' '.$this->lastname;
    }
     function getFullname(){
        return $this->fullname;
    }

    function setAdminflag($is_admin) {
        $this->is_admin = filter_var($is_admin, FILTER_VALIDATE_BOOLEAN);
    }
    function getAdminflag(){
        return $this->is_admin;
    }

    function setInactive($inactive) {
        if (is_array($inactive) and
            array_key_exists('start', $inactive) and
            array_key_exists('end', $inactive) and
            $this->validateDate($inactive['start']) and
            $this->validateDate($inactive['end'])) {
            $this->inactive = $inactive;
        }

        if($this->isBoolish($inactive)) {
            $this->inactive = true;
        }
    }

    function getInactive():bool {
        if (isset($this->inactive)) {
            return $this->inactive;
        }
        return false;
    }

    function validateDate($date, $format = 'd.m.Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
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
            if (isset($id)) {$this->setId($id);} else {$this->generateId();}
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

    function isInactive(\DateTimeImmutable $date = new \DateTimeImmutable()) {

        $inactive_val = $this->getInactive();
        if(is_bool($inactive_val)) {
            return $inactive_val;
        }

        if(is_array($inactive_val)) {
            $startdate = \DateTime::createFromFormat('d.m.Y', $inactive_val['start']);
            $enddate = \DateTime::createFromFormat('d.m.Y', $inactive_val['end']);
            if($startdate instanceof \DateTime and $enddate instanceof \DateTime) {
                return $this->is_date_in_range($date, $startdate, $enddate);
            }
        }
        return false;
    }

    function __to_array() {
        return (array)$this;
    }

    function is_date_in_range(\DateTime $date, \DateTime $start, \DateTime $end) {
        return $date > $start && $date < $end;
    }

}
