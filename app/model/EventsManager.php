<?php

namespace App\Model;

use Nette;


/**
 * Users management.
 */
class EventsManager extends Nette\Object
{

	public $infoUsers;

	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

	public function nastavUdalostiPodleData(){
		$eventEditing = $this->database->table('udalosti');
			foreach ($eventEditing as $eventInTable) {
				$datumUdalosti = $eventInTable->kdy;

				if(date('Y-m-d')>$datumUdalosti->format('Y-m-d')){
					$idOfEvent = $eventInTable->id;
					$this->database->table('udalosti')->where('id', $idOfEvent)->update(Array('stav' => 'reg_no'));
				}else{

				}
			}
	}

}

