<?php

namespace App\Presenters;

use Nette;
use App\Model;
use Nette\Utils\Finder,
    App\Model\EventsManager,
    Nette\Utils\Random;

class HomepagePresenter extends BasePresenter
{

    private $database;
    private $eventsManager;

    public function __construct(Nette\Database\Context $database,  \App\Model\EventsManager $eventsManager)
    {
        $this->database = $database;
        $this->eventsManager = $eventsManager;
    }

    /*
    public function nactiFotkyAUlozJeDoPole($index){

        $fotogalerie = $this->database->table('fotogalerie')->get($index);

        $dir = WWW_DIR/$fotogalerie->linkSlozka+"";
        $fotografie = array();
        $i = 0;

        foreach (Finder::findFiles('*.jpg')->in($dir) as $key => $file) {
            echo $key; // $key je řetězec s názvem souboru včetně cesty
         //   echo $file; // $file je objektem SplFileInfo
            $fotografie[$i] = $key;
            $i++;
        }

        $this->getHttpRequest()->getUrl()->getBasePath()

        return $fotografie;
    }
*/

    public function handleSledovatUdalost($idUdalosti)
    {
        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $retezec = $thisUserRow['sledovane_udalosti'];
        $retezec = $retezec.";".$idUdalosti;

        $this->database->table('users')->where('id', $thisUserID)->update(Array('sledovane_udalosti' => $retezec));
        $this->flashMessage('Událost sledujete.');
    }

    public function renderDefault()
    {
        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $sledovaneUdalostiRetezec = $thisUserRow['sledovane_udalosti'];
        $sledovaneUdalostiPole = explode(';',$sledovaneUdalostiRetezec);
        $this->template->sledovaneUdalosti = $sledovaneUdalostiPole;

        $this->eventsManager->nastavUdalostiPodleData();

        $this->template->udalosti = $this->database->table('udalosti')->order('kdy DESC')->limit(5);

        $this->template->clanky = $this->database->table('clanky')->order('date DESC')->limit(4);

        $WODs = $this->database->table('wod');
        $poleIdcek = array();
        $i=0;
        foreach ($WODs as $WOD) {
            if($WOD->status != "Neschváleno"){
                $poleIdcek[$i] =$WOD->id;
                $i++;
            }
        }

        shuffle($poleIdcek);
        $hodnota = $poleIdcek[0];

        $this->template->wod = $this->database->table('wod')->where('id = ?',$hodnota)->fetch();
  /*      $this->template->fotogalerie = $this->database->table('fotogalerie')->get(1);
        $this->template->fotogarafie = nactiFotkyAUlozJeDoPole(1);*/
	}
}
