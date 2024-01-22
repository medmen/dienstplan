<?php
declare(strict_types=1);

namespace Dienstplan\Worker;

use Dienstplan\Worker\Person;
use Odan\Session\FlashInterface;
use Odan\Session\SessionInterface;

class People {
    private string $path_to_configfiles;
    private SessionInterface $session;
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

    function load(\DateTimeImmutable $target_month) {
        $this->flash = $this->session->getFlash();
        $all_people = $this->load_all();
        $arrayOfPeople = [];
        foreach ($all_people as $id => $person) {
            $prs = new Person;
            $personObject = $prs->createFromNamedArray($person, $id);
            if($personObject === null or $personObject->isInactive($target_month)) {
                continue;
            }
            $arrayOfPeople[$personObject->getId()] = $personObject;
        }

        if(count($arrayOfPeople) == 0) {
            $this->flash->add('error', 'no people available');
        }

        if(count($arrayOfPeople) < 5) {
            $this->flash->add('warning', 'less than 5 people available');
        }

        $this->flash->add('info', 'loaded '.count($arrayOfPeople).' active People');
        return($arrayOfPeople);
    }

    private function id_exists($id) {
        if(in_array($id, array_keys($this->load_all()))) {
            return true;
        }
        return false;
    }
}
