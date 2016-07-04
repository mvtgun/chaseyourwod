<?php

namespace App\Presenters;

use App\Model;
use Nette;
use Nette\Forms\Form,
    App\Model\ImageDirectories,
    Nette\Forms\Controls,
    Nette\Utils\Random,
    Mesour\DataGrid\NetteDbDataSource,
    Mesour\DataGrid\Grid,
    App\Model\EventsManager,
    Mesour\DataGrid\Components\Link,
    Nette\Application\UI;

class EventsPresenter extends BasePresenter
{
    private $database;
    private $uploadImage;
    private $editEventId;
    private $valuesFilterForm;
    private $eventsManager;
    private $number;

    public function __construct(Nette\Database\Context $database, ImageDirectories $uploadImage,\App\Model\EventsManager $eventsManager)
    {
        $this->database = $database;
        $this->uploadImage = $uploadImage;
        $this->editEventId = 1;
        $this->valuesFilterForm = 0;
        $this->eventsManager = $eventsManager;
        $this->number = 1;
    }

    protected function createComponentEditEventForm() {
        $form = new \Nette\Application\UI\Form();

        $eventEditing = $this->database->table('udalosti')->get($this->editEventId);

        $form->addGroup();
        $form->addText('id', 'ID:')
            ->setAttribute('value', $eventEditing['id']);
        $form->addText('nazev', 'Název akce:')
            ->setAttribute('value', $eventEditing['nazev']);
        $form->addText('misto', 'Místo konání (hala, tělocvična):')
            ->setAttribute('value', $eventEditing['misto']);

        $form->addTextArea('popis', 'Popis akce:')
            ->setAttribute('class', 'mceEditor')
            ->setValue($eventEditing['popis']);
;
        $form->addText('ulice', 'Ulice:')
        ->setAttribute('value', $eventEditing['ulice']);
        $form->addText('mesto', 'Město:')->setAttribute('value', $eventEditing['mesto']);

        $kraje = array(
            'A' => 'Hlavní město Praha',
            'S' => 'Středočeský kraj',
            'C' =>'Jihočeský kraj',
            'P' => 'Plzeňský kraj',
            'K' => 'Karlovarský kraj',
            'U' => 'Ústecký kraj',
            'L' => 'Liberecký kraj',
            'H' => 'Královéhradecký kraj',
            'E' => 'Pardubický kraj',
            'J' => 'Kraj Vysočina',
            'B' => 'Jihomoravský kraj',
            'M' => 'Olomoucký kraj',
            'Z' => 'Zlínský kraj',
            'T' => 'Moravskoslezský kraj'
        );

        $form->addSelect('kraj', 'Kraj:', $kraje)
            ->setValue($eventEditing['kraj']);


        $form->addText('mapa_link', 'Odkaz na mapu:')
        ->setAttribute('value', $eventEditing['mapa_link']);
        $form->addText('lokace_embed', 'Embed mapy:')->setAttribute('value', $eventEditing['lokace_embed']);
        $form->addText('poradatel', 'Pořadatel:')->setAttribute('value', $eventEditing['poradatel']);
        $form->addText('poradatel_link', 'Odkaz na pořadatele:')->setAttribute('value', $eventEditing['poradatel_link']);
        $form->addText('poradatel_mail', 'E-mail pořadatele pro registraci', 35)
            ->setAttribute('value', $eventEditing['poradatel_mail']);

        // $form->addText('stav', 'Stav:'); // vždy z počátku stejný - registrace povolena

        $pro = array(
            'OPEN' => 'OPEN',
            'ELITE' => 'ELITE',
            'MASTERS' => 'MASTERS',
            'OPEN i ELITE' => 'OPEN i ELITE',
            'OPEN i ELITE i MASTERS'  => 'OPEN i ELITE i MASTERS',
        );

        $form->addSelect('pro', 'Pro:', $pro)
            ->setValue($eventEditing['pro']);

        $kvalifikace = array(
            'Ne' => 'Ne',
            'Ano' => 'Ano',
        );

        $form->addSelect('kvalifikace', 'Kvalifikace:', $kvalifikace)
            ->setValue($eventEditing['kvalifikace']);

        $soutezi = array(
            'Jednotlivci' => 'Jednotlivci',
            'Týmy' => 'Týmy',
            'OPEN i ELITE i MASTERS'  => 'Jednotlivci i týmy',
        );

        $form->addSelect('soutezijo', 'Soutěží:', $soutezi)
            ->setValue($eventEditing['soutezijo']);

        $form->addText('startovne', 'Startovné:')
        ->setValue($eventEditing['startovne']);
        $form->addText('pocet_ucastniku', 'Počet účastníků:')->setValue($eventEditing['pocet_ucastniku']);


        $form->addText('kdy', 'Od kdy:')
            //   ->setFormat('Y/m/d/')
            ->setAttribute('class', 'form-control datepicker');

        $form->addText('kdy_do', 'Do kdy:')
            //   ->setFormat('Y/m/d/')
            ->setAttribute('class', 'form-control datepicker');

        $form->addText('cas_od', 'Od kolika:')
            //   ->setFormat('H:i')
            ->setAttribute('class', 'form-control datetimepicker');

      /*  $form->addText('cas_do', 'Do kolika (přibližně):')
            //    ->setFormat('H:i')
            ->setAttribute('class', 'form-control datetimepicker');*/

        $form->addGroup();
        $form->addUpload('foto_udalosti', 'Fotografie události:', FALSE)
           ->setAttribute('class', 'file-loading')->setAttribute('id', 'avatar');

        $form->addText('seo_description', 'SEO Description:')
            ->setAttribute('title', 'Popis události, cca 155 znaků kvůli SEO.')
            ->setValue($eventEditing['seo_description']);

        $form->addText('seo_kw', 'SEO Klíčová slova:')
            ->setAttribute('title', 'Klíčová slova oddělená čárkou pro vyhledávače.')
            ->setValue($eventEditing['seo_kw']);

        $form->addText('site_name', 'SEO Site name:')
            ->setAttribute('title', 'Název stránky pro vyhledávače a soc. sítě.')
            ->setValue($eventEditing['site_name']);

        $form->addSubmit('send', 'Upravit událost');
        $form->onSuccess[] = $this->EditEventSubmitted;

        // setup form rendering
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-9';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-2 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
// make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-horizontal');

        return $form;
    }

    public function EditEventSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();

        $file = $values['foto_udalosti'];

        if($values['kdy']!=""){
            $dateKdy = $values['kdy'];
            $dateKdyPole = explode('.',$dateKdy);
            $retezecFinal = "";
            foreach (array_reverse($dateKdyPole) as &$date) {
                if($retezecFinal==""){
                    $retezecFinal = $retezecFinal.$date;
                    continue;
                }
                $retezecFinal=$retezecFinal."-".$date;
            }

            $values['kdy'] = $retezecFinal;
            $this->database->table('udalosti')->where('id', $values['id'])->update(Array('kdy' => $values['kdy']));
        }

        if($values['kdy_do']!=""){
            $dateKdyDo = $values['kdy_do'];
            $dateKdyPole = explode('.',$dateKdyDo);
            $retezecFinal = "";
            foreach (array_reverse($dateKdyPole) as &$date) {
                if($retezecFinal==""){
                    $retezecFinal = $retezecFinal.$date;
                    continue;
                }
                $retezecFinal=$retezecFinal."-".$date;
            }

            $values['kdy_do'] = $retezecFinal;
            $this->database->table('udalosti')->where('id', $values['id'])->update(Array('kdy_do' => $values['kdy_do']));
        }

        if($values['cas_od']!=""){
            $Row =  $this->database->table('udalosti')->get($values['id']);
            $dateKdy = $Row['kdy'];
            $dateKdyPoleBig = explode(' ',$dateKdy);
            $dateKdyPole = explode('.',$dateKdyPoleBig[0]);
            $retezecFinal = "";
            foreach (array_reverse($dateKdyPole) as &$date) {
                if($retezecFinal==""){
                    $retezecFinal = $retezecFinal.$date;
                    continue;
                }
                $retezecFinal=$retezecFinal."-".$date;
            }

            $timeOdFinal = $retezecFinal." ".$values['cas_od'].":00";
            $values['cas_od'] = $timeOdFinal;

            $this->database->table('udalosti')->where('id', $values['id'])->update(Array('cas_od' => $values['cas_od']));
        }
/*
        if($values['cas_do']!=""){
            $Row =  $this->database->table('udalosti')->get($values['id']);
            $dateKdy = $Row['kdy'];
            $dateKdyPoleBig = explode(' ',$dateKdy);
            $dateKdyPole = explode('.',$dateKdyPoleBig[0]);
            $retezecFinal = "";
            foreach (array_reverse($dateKdyPole) as &$date) {
                if($retezecFinal==""){
                    $retezecFinal = $retezecFinal.$date;
                    continue;
                }
                $retezecFinal=$retezecFinal."-".$date;
            }

            $timeOdFinal = $retezecFinal." ".$values['cas_do'].":00";
            $values['cas_do'] = $timeOdFinal;

            $this->database->table('udalosti')->where('id', $values['id'])->update(Array('cas_do' => $values['cas_do']));
        }*/

       // dump($values);


        if($file->name==NULL){
            //update dotaz
            $this->database->table('udalosti')->where('id', $values['id'])->update(
                Array(
                    'nazev' => $values['nazev'],
                    'misto' => $values['misto'],
                    'ulice' => $values['ulice'],
                    'mesto' => $values['mesto'],
                    'kraj' => $values['kraj'],
                    'mapa_link' => $values['mapa_link'],
                    'lokace_embed' => $values['lokace_embed'],
                    'poradatel' => $values['poradatel'],
                    'popis' => $values['popis'],
                    'poradatel_link' => $values['poradatel_link'],
                    'pro' => $values['pro'],
                    'poradatel_mail' => $values['poradatel_mail'],
                    'soutezijo' => $values['soutezijo'],
                    'startovne' => $values['startovne'],
                    'pocet_ucastniku' => $values['pocet_ucastniku'],
                    'seo_kw' => $values['seo_kw'],
                    'seo_description' => $values['seo_description'],
                    'site_name' => $values['site_name']
                )
            );
            $this->flashMessage('Aktualizace údajů se zdařila.');
        }else{


            /** Tady smažu původní fotografii */

            $eventEditing = $this->database->table('udalosti')->get($values['id']);
            $this->uploadImage->removeEventPhoto($eventEditing['foto_udalosti']); // smaže fotku

            /** Tady menim nazev souboru na ojedinely hash **/

            $nazevComplete =  $this->uploadImage->getPhotoName($file);
            $priponaObrazku = pathinfo($nazevComplete, PATHINFO_EXTENSION);

            $arrayOfEvents = $this->database->table('udalosti');
            $pocetRadku = $arrayOfEvents->count("*");

            $nazevSedi = true;
            $nazevHash = "tady_nic";

            while($nazevSedi){
                $countOfOK = 0;
                $nazevHash = Random::generate(6, '0-9a-z');
                foreach ($arrayOfEvents as $eventInTable) {
                    $nazevExistujiciPhoto = $eventInTable->foto_udalosti;
                    $nazevExistujiciPhotoWithoutExtension = preg_replace('/\\.[^.\\s]{3,4}$/', '', $nazevExistujiciPhoto);

                    if ($nazevExistujiciPhotoWithoutExtension != $nazevHash) {
                        $countOfOK++;
                    }
                }
                if($countOfOK==$pocetRadku){
                    $nazevSedi = false;
                }
            }
            /** jestli dojdeme až sem, můžeme použít hash jako název fotky*/
            $hashNameOfPhoto = $nazevHash.".".$priponaObrazku; //vytvori nazev souboru - hash + puvodni pripona
            $this->uploadImage->saveEvent($file,$hashNameOfPhoto); //zapise obrazek
            //nahrání názvu nové fotky do pole údajů
            $values["foto_udalosti"] = $hashNameOfPhoto;

            $this->database->table('udalosti')->where('id', $values['id'])->update(
                Array(
                    'nazev' => $values['nazev'],
                    'misto' => $values['misto'],
                    'ulice' => $values['ulice'],
                    'mesto' => $values['mesto'],
                    'kraj' => $values['kraj'],
                    'mapa_link' => $values['mapa_link'],
                    'lokace_embed' => $values['lokace_embed'],
                    'poradatel' => $values['poradatel'],
                    'popis' => $values['popis'],
                    'poradatel_link' => $values['poradatel_link'],
                    'pro' => $values['pro'],
                    'poradatel_mail' => $values['poradatel_mail'],
                    'soutezijo' => $values['soutezijo'],
                    'startovne' => $values['startovne'],
                    'pocet_ucastniku' => $values['pocet_ucastniku'],
                    'foto_udalosti' => $values['foto_udalosti'],
                    'kvalifikace' => $values['kvalifikace'],
                    'seo_kw' => $values['seo_kw'],
                    'seo_description' => $values['seo_description'],
                    'site_name' => $values['site_name']
                )
            );
            $this->flashMessage('Aktualizace údajů se zdařila.');
        }
    }

    protected function createComponentAddForm() {
        $form = new \Nette\Application\UI\Form();

        $form->addGroup();
        $form->addText('nazev', 'Název akce:')
        ->setRequired("Vyplňte pole Název akce");
        $form->addText('misto', 'Místo konání (hala, tělocvična):')
            ->setRequired("Vyplňte místo konání");

        $form->addTextArea('popis', 'Popis akce:')
            ->setAttribute('class', 'mceEditor')
            ->setRequired("Vyplňte náplň Wodu.");

        $form->addText('ulice', 'Ulice:');
        $form->addText('mesto', 'Město:');

        $kraje = array(
            'A' => 'Hlavní město Praha',
            'S' => 'Středočeský kraj',
            'C' =>'Jihočeský kraj',
            'P' => 'Plzeňský kraj',
            'K' => 'Karlovarský kraj',
            'U' => 'Ústecký kraj',
            'L' => 'Liberecký kraj',
            'H' => 'Královéhradecký kraj',
            'E' => 'Pardubický kraj',
            'J' => 'Kraj Vysočina',
            'B' => 'Jihomoravský kraj',
            'M' => 'Olomoucký kraj',
            'Z' => 'Zlínský kraj',
            'T' => 'Moravskoslezský kraj'
        );

        $form->addSelect('kraj', 'Kraj:', $kraje)
            ->setPrompt('Vyber kraj');

        $form->addText('mapa_link', 'Odkaz na mapu:');
        $form->addText('lokace_embed', 'Embed mapy:');
        $form->addText('poradatel', 'Pořadatel:');
        $form->addText('poradatel_link', 'Odkaz na pořadatele:');
        $form->addText('poradatel_mail', 'E-mail pořadatele pro registraci', 35)
            ->setEmptyValue('@')
            ->addRule(\Nette\Forms\Form::FILLED, 'Vyplňte e-mail pořadatele.')
            ->addCondition(\Nette\Forms\Form::FILLED)
            ->addRule(\Nette\Forms\Form::EMAIL, 'Neplatná emailová adresa');

       // $form->addText('stav', 'Stav:'); // vždy z počátku stejný - registrace povolena

        $pro = array(
            'OPEN' => 'OPEN',
            'ELITE' => 'ELITE',
            'MASTERS' => 'MASTERS',
            'OPEN i ELITE' => 'OPEN i ELITE',
            'OPEN i ELITE i MASTERS'  => 'OPEN i ELITE i MASTERS',
        );

        $form->addSelect('pro', 'Pro:', $pro)
            ->setPrompt('Obtížnost události');


        $kvalifikace = array(
            'Ne' => 'Ne',
            'Ano' => 'Ano',
        );

        $form->addSelect('kvalifikace', 'Kvalifikace:', $kvalifikace)
            ->setPrompt('Je vyžadována?');

        $soutezi = array(
            'Jednotlivci' => 'Jednotlivci',
            'Týmy' => 'Týmy',
            'Jednotlivci i týmy'  => 'Jednotlivci i týmy',
        );

        $form->addSelect('soutezijo', 'Soutěží:', $soutezi)
            ->setPrompt('Kdo se může přihlásit');

        $form->addText('startovne', 'Startovné:');
        $form->addText('pocet_ucastniku', 'Počet účastníků:');

        $form->addText('kdy', 'Od kdy:')
            ->setRequired()
         //   ->setFormat('Y/m/d/')
            ->setAttribute('class', 'form-control datepicker');

        $form->addText('kdy_do', 'Do kdy:')
            //   ->setFormat('Y/m/d/')
            ->setAttribute('class', 'form-control datepicker');


        $form->addText('cas_od', 'Od kolika:')
            ->setRequired()
         //   ->setFormat('H:i')
            ->setAttribute('class', 'form-control datetimepicker');
/*
        $form->addText('cas_do', 'Do kolika (přibližně):')
            ->setRequired()
        //    ->setFormat('H:i')
            ->setAttribute('class', 'form-control datetimepicker');*/

        $form->addGroup();
        $form->addUpload('foto_udalosti', 'Fotografie události:', FALSE)
            ->addRule(Form::IMAGE, 'Obrázek k události musí být JPEG, PNG nebo GIF.')
            ->addRule(Form::MAX_FILE_SIZE, 'Maximální velikost souboru je 1024 kB.', 1024 * 1024 /* v bytech */)->setAttribute('class', 'file-loading')->setAttribute('id', 'avatar');


        $form->addText('seo_description', 'SEO Description:')
            ->setAttribute('title', 'Popis události, cca 155 znaků kvůli SEO.');

        $form->addText('seo_kw', 'SEO Klíčová slova:')
            ->setAttribute('title', 'Klíčová slova oddělená čárkou pro vyhledávače.');

        $form->addText('site_name', 'SEO Site name:')
            ->setAttribute('title', 'Název stránky pro vyhledávače a soc. sítě.');

        $form->addSubmit('send', 'Vytvořit novou událost');
        $form->onSuccess[] = $this->addFormSubmitted;

        // setup form rendering
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-9';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-2 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
// make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-horizontal');

        return $form;
    }

    public function addFormSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();
        $file = $values['foto_udalosti'];

        $dateKdy = $values['kdy'];
        $dateKdyPole = explode('.',$dateKdy);
        $retezecFinal = "";
        foreach (array_reverse($dateKdyPole) as &$date) {
            if($retezecFinal==""){
                $retezecFinal = $retezecFinal.$date;
                continue;
            }
            $retezecFinal=$retezecFinal."-".$date;
        }

        $dateKdyDo = $values['kdy_do'];
        $dateKdyPoleDo = explode('.',$dateKdyDo);
        $retezecFinalDo = "";
        foreach (array_reverse($dateKdyPoleDo) as &$dateDo) {
            if($retezecFinalDo==""){
                $retezecFinalDo = $retezecFinalDo.$dateDo;
                continue;
            }
            $retezecFinalDo=$retezecFinalDo."-".$dateDo;
        }

        $values['kdy'] = $retezecFinal;
        $values['kdy_do'] = $retezecFinalDo;
        $timeOdFinal = $retezecFinal." ".$values['cas_od'].":00";
     //   $timeDoFinal = $retezecFinal." ".$values['cas_do'].":00";
        $values['cas_od'] = $timeOdFinal;
    //    $values['cas_do'] = $timeDoFinal;

     //  dump($values);

        /** Tady menim nazev souboru na ojedinely hash **/

        $nazevComplete =  $this->uploadImage->getPhotoName($file);
        $priponaObrazku = pathinfo($nazevComplete, PATHINFO_EXTENSION);

        $arrayOfUsers = $this->database->table('udalosti');
        $pocetRadku = $arrayOfUsers->count("*");

        $nazevSedi = true;
        $nazevHash = "tady_nic";

        while($nazevSedi){
            $countOfOK = 0;
            $nazevHash = Random::generate(6, '0-9a-z');
            foreach ($arrayOfUsers as $userInTable) {
                $nazevExistujiciPhoto = $userInTable->foto_udalosti;
                $nazevExistujiciPhotoWithoutExtension = preg_replace('/\\.[^.\\s]{3,4}$/', '', $nazevExistujiciPhoto);

                if ($nazevExistujiciPhotoWithoutExtension != $nazevHash) {
                    $countOfOK++;
                }
            }
            if($countOfOK==$pocetRadku){
                $nazevSedi = false;
            }
        }
        /** jestli dojdeme až sem, můžeme použít hash jako název fotky*/
        $hashNameOfPhoto = $nazevHash.".".$priponaObrazku; //vytvori nazev souboru - hash + puvodni pripona
        $this->uploadImage->saveEvent($file,$hashNameOfPhoto); //zapise obrazek
        //nahrání názvu nové fotky do pole údajů
        $values["foto_udalosti"] = $hashNameOfPhoto;

        $values["stav"] = "reg_yes";
        $values["status"] = "Deactive";

        $variable = $this->database->table('udalosti')->insert($values);

        if(!$variable){
            $form->addError("Událost se nepodařilo uložit.");
        }else{
            $this->flashMessage('Událost byla úspěšně uložena.');
            $this->redirect('Events:eventsView');
        }
    }

    protected function createComponentFilterForm() {
        $form = new \Nette\Application\UI\Form();

        $form->addGroup();
        $form->addCheckboxList('kraje', 'Kraje:', array(
            'A' => 'Hlavní město Praha',
            'S' => 'Středočeský kraj',
            'C' =>'Jihočeský kraj',
            'P' => 'Plzeňský kraj',
            'K' => 'Karlovarský kraj',
            'U' => 'Ústecký kraj',
            'L' => 'Liberecký kraj',
            'H' => 'Královéhradecký kraj',
            'E' => 'Pardubický kraj',
            'J' => 'Kraj Vysočina',
            'B' => 'Jihomoravský kraj',
            'M' => 'Olomoucký kraj',
            'Z' => 'Zlínský kraj',
            'T' => 'Moravskoslezský kraj',
        ));

        $form->addCheckboxList('soutez', 'Soutěž:', array(
            'Týmy' => 'Týmy',
            'Jednotlivci' => 'Jednotlivci',
            'Jednotlivci i týmy' => 'Jednotlivci i týmy',
            'OPEN' =>'Open',
            'ELITE' => 'Elite',
            'MASTERS' =>'Masters',
            'OPEN i ELITE' =>'Open i elite',
            'OPEN i ELITE i MASTERS' => 'Open,elite i masters',
        ));

        $form->addGroup();
        $form->addSubmit('submit', 'Nuže filtruj');

// setup form rendering
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class="form-group col-md-6"';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-10';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-2 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
// make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-horizontal');

        foreach ($form->getControls() as $control) {
            if ($control instanceof Controls\Button) {
                $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
                $usedPrimary = TRUE;
            } elseif ($control instanceof Controls\TextBase || $control instanceof Controls\SelectBox || $control instanceof Controls\MultiSelectBox) {
                $control->getControlPrototype()->addClass('form-control');
            } elseif ($control instanceof Controls\Checkbox || $control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList) {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }

        $form->onSuccess[] = $this->filterFormSubmitted;
        return $form;
    }

    public function filterFormSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();
        $this->valuesFilterForm = $values;
        $this->flashMessage('Události jsou nyní filtrovány.');
    }

    public function renderEventsListPrevious(){

        $poleUdalosti = array();
        $i = 0;

        $eventEditing = $this->database->table('udalosti')->order('kdy DESC');;
        foreach ($eventEditing as $eventInTable) {
            $datumUdalosti = $eventInTable->kdy;

            if(date('Y-m-d')>$datumUdalosti->format('Y-m-d')){
                $idOfEvent = $eventInTable->id;

                $poleUdalosti[$i] = $eventInTable;
                $i++;
            }
        }

        $pocetUdalosti = count($poleUdalosti);

        $itemsPerPage = 10;

        $poleUdalosti2 = array();
        $countOfNumbers = ceil($pocetUdalosti/$itemsPerPage);

        $cislo = $this->number;

        $positionZacatek = ($cislo*$itemsPerPage)-$itemsPerPage;
        $positionKonec = $positionZacatek+$itemsPerPage;

        for($j = $positionZacatek; $j<$positionKonec;$j++){

            if($j==$pocetUdalosti){
                break;
            }else{
               $poleUdalosti2[$j] = $poleUdalosti[$j];
            }
        }

        $parametry = ['pocetUdalosti' => $pocetUdalosti, 'current' =>$cislo, 'pocetStranek'=>$countOfNumbers];
        $this->template->paginationParametrs = $parametry;
        $this->template->events = $poleUdalosti2;

        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $sledovaneUdalostiRetezec = $thisUserRow['sledovane_udalosti'];
        $sledovaneUdalostiPole = explode(';',$sledovaneUdalostiRetezec);
        $this->template->sledovaneUdalosti = $sledovaneUdalostiPole;
    }

    public function handleSetNumber($numberNew){
        $this->number = $numberNew;
    }

    public function renderEventsList(){

        $this->eventsManager->nastavUdalostiPodleData();

        $pocet = 0;
        $filter = false;

        $krajeFilter = $this->valuesFilterForm['kraje'];
        $soutezFilter = $this->valuesFilterForm['soutez'];

         if (!empty($krajeFilter) && !empty($soutezFilter)) {
            $filterBoth = false;

            $pro ="";
            $soutezijo = "";
            $j = 0;
            $i =0;
            if($soutezFilter[0]=='Týmy'){
                $soutezijo[$j] = "Týmy";
                $j++;
            }
            if($soutezFilter[0]=='Jednotlivci'){
                $soutezijo[$j] = "Jednotlivci";
                $j++;
            }
            if($soutezFilter[0]=='OPEN'){
                $pro[$i] = "OPEN";
                $i++;
            }
            if($soutezFilter[0]=='ELITE'){
                $pro[$i] = "ELITE";
                $i++;
            }

            if($soutezFilter[0]=='MASTERS'){
                $pro[$i] = "MASTERS";
                $i++;
            }

            if($soutezFilter[0]=='OPEN i ELITE'){
                $pro[$i] = "OPEN i ELITE";
                $i++;
            }

            if($soutezFilter[0]=='Jednotlivci i týmy'){
                $soutezijo[$j] = "Jednotlivci i týmy";
                $j++;
            }

            if($soutezFilter[0]=='OPEN i ELITE i MASTERS'){
                $pro[$i] = "OPEN i ELITE i MASTERS";
                $i++;
            }

            if(count($soutezFilter)>1){
                if($soutezFilter[1]=='Jednotlivci'){
                    $soutezijo[$j] = "Jednotlivci";
                    $j++;
                }

                if($soutezFilter[1]=='ELITE'){
                    $pro[$i] = "ELITE";
                    $i++;
                }
                if($soutezFilter[1]=='OPEN'){
                    $pro[$i] = "OPEN";
                    $i++;
                }

                if($soutezFilter[1]=='MASTERS'){
                    $pro[$i] = "MASTERS";
                    $i++;
                }

                if($soutezFilter[1]=='OPEN i ELITE'){
                    $pro[$i] = "OPEN i ELITE";
                    $i++;
                }

                if($soutezFilter[1]=='Jednotlivci i týmy'){
                    $soutezijo[$j] = "Jednotlivci i týmy";
                    $j++;
                }

                if($soutezFilter[1]=='OPEN i ELITE i MASTERS'){
                    $pro[$i] = "OPEN i ELITE i MASTERS";
                    $i++;
                }

                if(count($soutezFilter)>2){
                    if($soutezFilter[2]=='OPEN'){
                        $pro[$i] = "OPEN";
                        $i++;
                    }

                    if($soutezFilter[2]=='ELITE'){
                        $pro[$i] = "ELITE";
                        $i++;
                    }

                    if($soutezFilter[2]=='MASTERS'){
                        $pro[$i] = "MASTERS";
                        $i++;
                    }

                    if($soutezFilter[2]=='OPEN i ELITE'){
                        $pro[$i] = "OPEN i ELITE";
                        $i++;
                    }

                    if($soutezFilter[2]=='Jednotlivci i týmy'){
                        $soutezijo[$j] = "Jednotlivci i týmy";
                        $j++;
                    }

                    if($soutezFilter[2]=='OPEN i ELITE i MASTERS'){
                        $pro[$i] = "OPEN i ELITE i MASTERS";
                        $i++;
                    }
                }
                if(count($soutezFilter)>3){
                    if($soutezFilter[3]=='ELITE'){
                        $pro[$i] = "ELITE";
                        $i++;
                    }
                    if($soutezFilter[3]=='OPEN'){
                        $pro[$i] = "OPEN";
                        $i++;
                    }

                    if($soutezFilter[3]=='MASTERS'){
                        $pro[$i] = "MASTERS";
                        $i++;
                    }

                    if ($soutezFilter[3] == 'OPEN i ELITE') {
                        $pro[$i] = "OPEN i ELITE";
                        $i++;
                    }

                    if($soutezFilter[3]=='OPEN i ELITE i MASTERS'){
                        $pro[$i] = "OPEN i ELITE i MASTERS";
                        $i++;
                    }
                }
                if(count($soutezFilter)>4) {
                    if ($soutezFilter[4] == 'ELITE') {
                        $pro[$i] = "ELITE";
                        $i++;
                    }

                    if ($soutezFilter[4] == 'MASTERS') {
                        $pro[$i] = "MASTERS";
                        $i++;
                    }

                    if ($soutezFilter[4] == 'OPEN i ELITE') {
                        $pro[$i] = "OPEN i ELITE";
                        $i++;
                    }

                    if ($soutezFilter[4] == 'OPEN i ELITE i MASTERS') {
                        $pro[$i] = "OPEN i ELITE i MASTERS";
                        $i++;
                    }
                }

                if(count($soutezFilter)>5){
                    if($soutezFilter[5]=='OPEN i ELITE i MASTERS'){
                        $pro[$i] = "OPEN i ELITE i MASTERS";
                        $i++;
                    }

                    if ($soutezFilter[5] == 'MASTERS') {
                        $pro[$i] = "MASTERS";
                        $i++;
                    }

                    if($soutezFilter[5]=='OPEN i ELITE'){
                        $pro[$i] = "OPEN i ELITE";
                        $i++;
                    }

                }

                if(count($soutezFilter)>6){
                    if($soutezFilter[6]=='OPEN i ELITE i MASTERS'){
                        $pro[$i] = "OPEN i ELITE i MASTERS";
                        $i++;
                    }

                    if($soutezFilter[6]=='OPEN i ELITE'){
                        $pro[$i] = "OPEN i ELITE";
                        $i++;
                    }

                }

                if(count($soutezFilter)>7){
                    if($soutezFilter[7]=='OPEN i ELITE i MASTERS'){
                        $pro[$i] = "OPEN i ELITE i MASTERS";
                        $i++;
                    }

                }
            }


            if($pro!="" && $soutezijo==""){
                $filter = true;
                $this->template->events = $this->database->table('udalosti')->where('kraj', $krajeFilter)->where('pro', $pro)->order('kdy DESC');
            }else if($pro=="" && $soutezijo!=""){
                $filter = true;
                $this->template->events = $this->database->table('udalosti')->where('kraj', $krajeFilter)->where('soutezijo', $soutezijo)->order('kdy DESC');
            }else if($pro!="" && $soutezijo!=""){
                $filter = true;
                $this->template->events = $this->database->table('udalosti')->where('kraj', $krajeFilter)->where('soutezijo', $soutezijo)->where('pro', $pro)->order('kdy DESC');
            }else{
                $filter = true;
                $this->template->events = $this->database->table('udalosti')->where('kraj', $krajeFilter)->order('kdy DESC');
            }

        }else{
            $filterBoth = true;
        }

        if (!empty($krajeFilter)) {
            if($filterBoth){
                $filter = true;
                  $this->template->events = $this->database->table('udalosti')->where('kraj', $krajeFilter)->order('kdy DESC');
            }
        }

        if (!empty($soutezFilter)) {
            if($filterBoth){
                $pro ="";
                $soutezijo = "";
                $j = 0;
                $i =0;
                if($soutezFilter[0]=='Týmy'){
                    $soutezijo[$j] = "Týmy";
                    $j++;
                }
                if($soutezFilter[0]=='Jednotlivci'){
                    $soutezijo[$j] = "Jednotlivci";
                    $j++;
                }
                if($soutezFilter[0]=='OPEN'){
                    $pro[$i] = "OPEN";
                    $i++;
                }
                if($soutezFilter[0]=='ELITE'){
                    $pro[$i] = "ELITE";
                    $i++;
                }

                if($soutezFilter[0]=='MASTERS'){
                    $pro[$i] = "MASTERS";
                    $i++;
                }

                if($soutezFilter[0]=='OPEN i ELITE'){
                    $pro[$i] = "OPEN i ELITE";
                    $i++;
                }

                if($soutezFilter[0]=='Jednotlivci i týmy'){
                    $soutezijo[$j] = "Jednotlivci i týmy";
                    $j++;
                }

                if($soutezFilter[0]=='OPEN i ELITE i MASTERS'){
                    $pro[$i] = "OPEN i ELITE i MASTERS";
                    $i++;
                }

                if(count($soutezFilter)>1){
                    if($soutezFilter[1]=='Jednotlivci'){
                        $soutezijo[$j] = "Jednotlivci";
                        $j++;
                    }

                    if($soutezFilter[1]=='ELITE'){
                        $pro[$i] = "ELITE";
                        $i++;
                    }
                    if($soutezFilter[1]=='OPEN'){
                        $pro[$i] = "OPEN";
                        $i++;
                    }

                    if($soutezFilter[1]=='MASTERS'){
                        $pro[$i] = "MASTERS";
                        $i++;
                    }

                    if($soutezFilter[1]=='OPEN i ELITE'){
                        $pro[$i] = "OPEN i ELITE";
                        $i++;
                    }

                    if($soutezFilter[1]=='Jednotlivci i týmy'){
                        $soutezijo[$j] = "Jednotlivci i týmy";
                        $j++;
                    }

                    if($soutezFilter[1]=='OPEN i ELITE i MASTERS'){
                        $pro[$i] = "OPEN i ELITE i MASTERS";
                        $i++;
                    }

                    if(count($soutezFilter)>2){
                        if($soutezFilter[2]=='OPEN'){
                            $pro[$i] = "OPEN";
                            $i++;
                        }

                        if($soutezFilter[2]=='ELITE'){
                            $pro[$i] = "ELITE";
                            $i++;
                        }

                        if($soutezFilter[2]=='MASTERS'){
                            $pro[$i] = "MASTERS";
                            $i++;
                        }

                        if($soutezFilter[2]=='OPEN i ELITE'){
                            $pro[$i] = "OPEN i ELITE";
                            $i++;
                        }

                        if($soutezFilter[2]=='Jednotlivci i týmy'){
                            $soutezijo[$j] = "Jednotlivci i týmy";
                            $j++;
                        }

                        if($soutezFilter[2]=='OPEN i ELITE i MASTERS'){
                            $pro[$i] = "OPEN i ELITE i MASTERS";
                            $i++;
                        }
                    }
                    if(count($soutezFilter)>3){
                        if($soutezFilter[3]=='ELITE'){
                            $pro[$i] = "ELITE";
                            $i++;
                        }
                        if($soutezFilter[3]=='OPEN'){
                            $pro[$i] = "OPEN";
                            $i++;
                        }

                        if($soutezFilter[3]=='MASTERS'){
                            $pro[$i] = "MASTERS";
                            $i++;
                        }

                        if ($soutezFilter[3] == 'OPEN i ELITE') {
                            $pro[$i] = "OPEN i ELITE";
                            $i++;
                        }

                        if($soutezFilter[3]=='OPEN i ELITE i MASTERS'){
                            $pro[$i] = "OPEN i ELITE i MASTERS";
                            $i++;
                        }
                    }
                    if(count($soutezFilter)>4) {
                        if ($soutezFilter[4] == 'ELITE') {
                            $pro[$i] = "ELITE";
                            $i++;
                        }

                        if ($soutezFilter[4] == 'MASTERS') {
                            $pro[$i] = "MASTERS";
                            $i++;
                        }

                        if ($soutezFilter[4] == 'OPEN i ELITE') {
                            $pro[$i] = "OPEN i ELITE";
                            $i++;
                        }

                        if ($soutezFilter[4] == 'OPEN i ELITE i MASTERS') {
                            $pro[$i] = "OPEN i ELITE i MASTERS";
                            $i++;
                        }
                    }

                    if(count($soutezFilter)>5){
                        if($soutezFilter[5]=='OPEN i ELITE i MASTERS'){
                            $pro[$i] = "OPEN i ELITE i MASTERS";
                            $i++;
                        }

                        if ($soutezFilter[5] == 'MASTERS') {
                            $pro[$i] = "MASTERS";
                            $i++;
                        }

                        if($soutezFilter[5]=='OPEN i ELITE'){
                            $pro[$i] = "OPEN i ELITE";
                            $i++;
                        }

                    }

                    if(count($soutezFilter)>6){
                        if($soutezFilter[6]=='OPEN i ELITE i MASTERS'){
                            $pro[$i] = "OPEN i ELITE i MASTERS";
                            $i++;
                        }

                        if($soutezFilter[6]=='OPEN i ELITE'){
                            $pro[$i] = "OPEN i ELITE";
                            $i++;
                        }

                    }

                    if(count($soutezFilter)>7){
                        if($soutezFilter[7]=='OPEN i ELITE i MASTERS'){
                            $pro[$i] = "OPEN i ELITE i MASTERS";
                            $i++;
                        }

                    }
                }

                if($pro!="" && $soutezijo==""){
                    $filter = true;
                    $this->template->events = $this->database->table('udalosti')->where('pro', $pro)->order('kdy DESC');
                }else if($pro=="" && $soutezijo!=""){
                    $filter = true;
                    $this->template->events = $this->database->table('udalosti')->where('soutezijo', $soutezijo)->order('kdy DESC');
                }else if($pro!="" && $soutezijo!=""){
                    $filter = true;
                    $this->template->events = $this->database->table('udalosti')->where('soutezijo', $soutezijo)->where('pro', $pro)->order('kdy DESC');
                }else{
                    $filter = true;
                    $this->template->events = $this->database->table('udalosti')->order('kdy DESC');
                }
            }
        }

        if (empty($krajeFilter) && empty($soutezFilter)) {

            $udalosti =  $this->database->table('udalosti')->order('kdy DESC');
            $pocetUdalosti = count($udalosti);

            $poleIds = array();

            for($i = 0; $i<$pocetUdalosti;$i++){
                $row =  $udalosti->fetch();
                $poleIds[$i] = $row['id'];
            }

            $itemsPerPage = 2;

            $poleUdalosti = array();
            $countOfNumbers = ceil($pocetUdalosti/$itemsPerPage);

            $cislo = $this->number;

            $positionZacatek = ($cislo*$itemsPerPage)-$itemsPerPage;
            $positionKonec = $positionZacatek+$itemsPerPage;

            for($j = $positionZacatek; $j<$positionKonec;$j++){

                if($j==$pocetUdalosti){
                    break;
                }else{
                    $poleUdalosti[$j] = $udalosti->get($poleIds[$j]);
                }
            }

            $parametry = ['pocetUdalosti' => $pocetUdalosti, 'current' =>$cislo, 'pocetStranek'=>$countOfNumbers];
            $this->template->paginationParametrs = $parametry;
            $this->template->events = $poleUdalosti;

        }

        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $sledovaneUdalostiRetezec = $thisUserRow['sledovane_udalosti'];
        $sledovaneUdalostiPole = explode(';',$sledovaneUdalostiRetezec);
        $this->template->sledovaneUdalosti = $sledovaneUdalostiPole;

        $parametry = ['pocetUdalosti' => $pocet, 'filterace' =>$filter];
        $this->template->hodnocenicko = $parametry;

    }

    public function renderEventDetail($eventId)
    {
        $udalost = $this->database->table('udalosti')->get($eventId);
        if(!$udalost){
            $this->error("Nezadávej do tý url nesmysly!");
        }
        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $sledovaneUdalostiRetezec = $thisUserRow['sledovane_udalosti'];
        $sledovaneUdalostiPole = explode(';',$sledovaneUdalostiRetezec);

        $this->template->sledovaneUdalosti = $sledovaneUdalostiPole;
        $this->template->event = $udalost;

        $hodnoceniCelkemRows = $this->database->table('hodnoceniudalosti');
        $uzivatelUzHodnotilRow = $this->database->table('hodnoceniudalosti')->where('eventID =?',$eventId);

        $hodnoceniSum = 0;
        $pocetHodnoceni = 0;
        $hodnoceniFinal = 0;

        $uzivatelUzHodnotil = true;

        foreach ($hodnoceniCelkemRows as $hodnoceniRow) {
            if($hodnoceniRow->eventID==$eventId){
                $hodnoceniNow = $hodnoceniRow->hodnoceni;
                $hodnoceniSum = $hodnoceniSum + $hodnoceniNow;
                $pocetHodnoceni++;
            }
        }
        foreach($uzivatelUzHodnotilRow as $UzivatelRow){
            if($UzivatelRow->userID==$this->getUser()->getId()){
                $uzivatelUzHodnotil = false;
            }
        }
        if($pocetHodnoceni!=0){
            $hodnoceniFinal = $hodnoceniSum/$pocetHodnoceni;
        }
        if($hodnoceniFinal==100){
            $hodnoceniFinal = substr($hodnoceniFinal, 0, 3);
        }else{
            $hodnoceniFinal = substr($hodnoceniFinal, 0, 2);
        }

        $udalostProbehla = TRUE;

        if(date('Y-m-d')<$udalost['kdy']->format('Y-m-d')){
            $udalostProbehla = FALSE;
        }

        $hodnoceniPole = ['uzivatel' => $uzivatelUzHodnotil,'hodnoceniCelkem' => $hodnoceniFinal,'udalostProbehla' => $udalostProbehla];
        $this->template->hodnocenicko = $hodnoceniPole;
    }

    public function handleRateEvent($id,$rating)
    {
        $values['eventID'] = $id;
        $values['hodnoceni'] = $rating;
        $values['userID'] = $this->getUser()->getId();
        $hodnoceniCelkemRows = $this->database->table('hodnoceniudalosti')->where('eventID =?',$id);
        $pocetRadku = $hodnoceniCelkemRows->count("*");

        $uzivatelUzHodnotil = true;
        foreach ($hodnoceniCelkemRows as $hodnoceniRow) {
            if($hodnoceniRow->userID==$this->getUser()->getId()){
                $uzivatelUzHodnotil = false;
            }
        }
        if($uzivatelUzHodnotil){
            $this->database->table('hodnoceniudalosti')->insert($values);
            $this->flashMessage('Vaše hodnocení jsme uložili, děkujeme.');
        }else{
            $this->flashMessage('Tuto událost jste již ohodnotili. Děkujeme.');
        }
    }

    public function renderEventAdd()
    {
       // $this->template->userToEdit = $userToEdit;
    }

    public function handleActivate($id)
    {
        $eventToEdit = $this->database->table('udalosti')->get($id);
        $eventStatus = $eventToEdit['status'];

        if($eventStatus === 'Active'){
            $this->flashMessage('Událost již je aktivována.');
        }else if($eventStatus == 'Deactive') {

            $this->database->table('udalosti')->where('id', $id)->update(Array('status' => 'Active'));
            $this->flashMessage('Událost byla aktivována.');
        }
    }

    public function handleDeactivate($id)
    {
        $eventToEdit = $this->database->table('udalosti')->get($id);
        $eventStatus = $eventToEdit['status'];

        if($eventStatus === 'Deactive'){
            $this->flashMessage('Událost již je deaktivována.');
        }else if($eventStatus == 'Active') {

            $this->database->table('udalosti')->where('id', $id)->update(Array('status' => 'Deactive'));
            $this->flashMessage('Událost byla deaktivována. Událost se nyní nezobrazuje ve výpisech.');
        }
    }

    public function handleDeleteEvent($id)
    {
        $eventEditing = $this->database->table('udalosti')->get($id);
        $this->uploadImage->removeEventPhoto($eventEditing['foto_udalosti']); // smaže fotku
        $this->database->table('udalosti')->where('id', $id)->delete();;   // smaže událost
        $this->flashMessage('Událost byla trvale odstraněna.');
    }

    public function handleStavChange($id, $stav)
    {
        $eventToEdit = $this->database->table('udalosti')->get($id);

        $eventToEditStav = $eventToEdit['stav'];

        if($eventToEditStav === 'reg_yes'&& $stav==='reg_yes' ){
            $this->flashMessage('Tato událost má již přihlašování povoleo.');
        }else if($eventToEditStav === 'reg_no'&& $stav==='reg_no' ){
            $this->flashMessage('Tato událost má již přihlašování zakázáno.');
        }else if($eventToEditStav === 'reg_no'&& $stav==='reg_yes' ){
            $this->database->table('udalosti')->where('id', $id)->update(Array('stav' => 'reg_yes'));
            $this->flashMessage('Přihlašování k této události bylo povoleno.');
        }else if($eventToEditStav === 'reg_yes'&& $stav==='reg_no' ){
           $this->database->table('udalosti')->where('id', $id)->update(Array('stav' => 'reg_no'));
           $this->flashMessage('Přihlašování k této události bylo zakázáno.');
        }
    }

    public function renderEventEdit($id)
    {
        $this->editEventId = $id;

        if ($this->user->isLoggedIn()) {
            $userToEdit = $this->database->table('udalosti')->get($id);
            $this->template->eventToEdit = $userToEdit;
        }else{
            $this->flashMessage('Nejdříve je třeba se přihlásit.');
            $this->redirect('Homepage:default');
        }

    }

    protected function createComponentEventsDataGrid($name) {
        $selection = $this->database->table('udalosti')->order('kdy DESC');
        $source = new NetteDbDataSource($selection);

        $grid = new Grid($this, $name);
        $grid->setPrimaryKey('id'); // primary key is now used always
        // set locale to Czech
        $grid->setLocale('cs');
        $table_id = 'id';

        $grid->setDataSource($source);
        $grid->addText('id', 'ID');
        $grid->addText('nazev', 'Název akce');
        $grid->addText('mesto', 'Město');
        $grid->addText('misto', 'Místo');
        $grid->addText('poradatel', 'Pořadatel');
     //   $grid->addText('pro', 'Pro');
     //   $grid->addText('soutezijo', 'Soutěží');

        $grid->addDate('kdy', 'Datum konání')
            ->setFormat('j.n.Y');

        $grid->addText('stav', 'Stav akce');

        $grid->addText('status', 'Status');

        $actions = $grid->addActions('');
        $dropDown = $actions->addDropDown()
            ->setName("Rychlé akce")
            ->setType('btn-default');

        /*   $dropDown->addHeader('Skupiny')
               ->setAttribute('class', 'db-head');*/

        $dropDown->addLink('Aktivovat', new Link('activate!', array(
            'id' => '{' . $table_id . '}')))
            ->setAttribute('class', 'dd-authority-user');

        $dropDown->addLink('Deaktivovat', new Link('deactivate!', array(
            'id' => '{' . $table_id . '}')))
            ->setAttribute('class', 'dd-authority-redaktor')
            ->setConfirm('Událost bude stále uložena v databázi, ale nebude zobrazena.');

        $dropDown->addLink('Ukončit přihlašování', new Link('stavChange!', array(
            'id' => '{' . $table_id . '}','stav' => 'reg_no')))
            ->setAttribute('class', 'dd-authority-user')
            ->setConfirm('Nastaví ukončení možnosti odesílání e-mailů pořadateli.');

        $dropDown->addLink('Povolit přihlašování', new Link('stavChange!', array(
            'id' => '{' . $table_id . '}','stav' => 'reg_yes')))
            ->setAttribute('class', 'dd-authority-user');


        $actions->addButton()
            ->setType('btn-default')
            ->setIcon('glyphicon-pencil')
            ->setTitle('Upravit událost')
            ->setAttribute('href', new Link('Events:eventEdit', array(
                'id' => '{' . $table_id . '}'
            )));

        $actions->addButton()
            ->setType('btn-danger')
            ->setIcon('glyphicon-trash')
            ->setConfirm('Opravdu chcete tuto událost trvale smazat?')
            ->setTitle('Smazat událost')
            ->setAttribute('href', new Link('deleteEvent!', array(
                'id' => '{' . $table_id . '}'
            )));

        // enable pager
        $grid->enablePager(15); // set limit for page to 5, default = 20
        return $grid;
    }

    public function renderEventsView(){
     //   $this->template->events = $this->database->table('udalosti')->order('kdy DESC');
    }

    public function handleSledovatUdalost($idUdalosti)
    {
        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $retezec = $thisUserRow['sledovane_udalosti'];
        $retezec = $retezec.";".$idUdalosti;

        $this->database->table('users')->where('id', $thisUserID)->update(Array('sledovane_udalosti' => $retezec));
        $this->flashMessage('Událost sledujete.');
    }
}