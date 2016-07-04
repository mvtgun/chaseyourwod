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
    Mesour\DataGrid\Components\Link,
    Nette\Application\UI;
use Tracy\Debugger;

class WodPresenter extends BasePresenter
{

    private $database;
    private $uploadImage;
    private $editWodId;
    private $valuesFilterForm;

    public function __construct(Nette\Database\Context $database, ImageDirectories $uploadImage)
    {
        $this->database = $database;
        $this->uploadImage = $uploadImage;
        $this->editWodId = 1;
        $this->valuesFilterForm = 0;
    }

    public function handleActivate($id)
    {
        $wodToEdit = $this->database->table('wod')->get($id);
        $wodStatus = $wodToEdit['status'];

        if($wodStatus === 'Schváleno'){
            $this->flashMessage('Cvičení již je schváleno.');
        }else if($wodStatus == 'Neschváleno') {
            $this->database->table('wod')->where('id', $id)->update(Array('status' => 'Schváleno'));
            $this->flashMessage('Cvičení bylo schváleno. Nyní se objevuje v generátoru cvičení.');
        }
    }

    public function handleDeactivate($id)
    {
        $wodToEdit = $this->database->table('wod')->get($id);
        $wodStatus = $wodToEdit['status'];

        if($wodStatus === 'Neschváleno'){
            $this->flashMessage('Cvičení již je neschváleno.');
        }else if($wodStatus == 'Schváleno') {

            $this->database->table('wod')->where('id', $id)->update(Array('status' => 'Neschváleno'));
            $this->flashMessage('WOD nebyl schválen.');
        }
    }

    public function handleDeleteWod($id)
    {
        $this->database->table('wod')->where('id', $id)->delete();;   // smaže wod
        $this->flashMessage('WOD byl trvale odstraněn.');
    }


    public function renderWodEdit($id)
    {
        $this->editWodId = $id;

        $wodToEdit = $this->database->table('wod')->get($id);
        $this->template->wodToEdit = $wodToEdit;
    }

    protected function createComponentEditWodForm() {
        $form = new \Nette\Application\UI\Form();

        $wodEditing = $this->database->table('wod')->get($this->editWodId);

        $form->addGroup();
        $form->addText('id', 'ID:')
            ->setAttribute('value', $wodEditing['id']);
        $form->addText('title', 'Nadpis:')
            ->setValue($wodEditing['title']);
        $form->addTextArea('text', 'Náplň WODu:')
            ->setAttribute('class', 'mceEditor')
            ->setValue($wodEditing['text']);

        $typy = array(
            'AMRAP' => 'AMRAP',
            'Time Cap' => 'Time Cap',
            'EMOM' =>'EMOM',
            'Warm-up' =>'E2MOM',
            'Tabata' =>'Tabata',
            'Warm-Up' =>'Warm Up',
            'Strength' =>'Strength',
	        'For time' =>'For time',
	        'Benchmark' =>'Benchmark',
        );


        $form->addSelect('typ', 'Vyber typ WODu:', $typy)
            ->setValue($wodEditing['typ']);

        $form->addSubmit('send', 'Upravit WOD');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');

        $form->onSuccess[] = $this->WodEditFormSubmitted;

        // setup form rendering
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-10';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-2 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
// make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-horizontal');

        return $form;
    }

    public function WodEditFormSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();

        $this->database->table('wod')->where('id', $values['id'])->update(Array('title' => $values['title'],'text' => $values['text'],'typ' => $values['typ']));
        $this->flashMessage('Aktualizace údajů se zdařila.');

    }

	public function activeSelected($selected_items) {

		foreach($selected_items as $item){
			$wodToEdit = $this->database->table('wod')->get($item);
			$wodStatus = $wodToEdit['status'];

			if($wodStatus === 'Schváleno'){
				$this->flashMessage('Cvičení '.$item.' již je schváleno.');
			}else if($wodStatus == 'Neschváleno') {
				$this->database->table('wod')->where('id', $item)->update(Array('status' => 'Schváleno'));
				$this->flashMessage('Cvičení '.$item.' bylo schváleno. Nyní se objevuje v generátoru cvičení');
			}
		}
	}

	public function unactiveSelected($selected_items) {

		foreach($selected_items as $item){
			$wodToEdit = $this->database->table('wod')->get($item);
			$wodStatus = $wodToEdit['status'];

			if($wodStatus === 'Neschváleno'){
				$this->flashMessage('Cvičení '.$item.' již je neschváleno.');
			}else if($wodStatus == 'Schváleno') {

				$this->database->table('wod')->where('id', $item)->update(Array('status' => 'Neschváleno'));
				$this->flashMessage('WOD  '.$item.' nebyl schválen.');
			}
		}
	}

    protected function createComponentWodsDataGrid($name) {
        $selection = $this->database->table('wod')->order('id DESC');
        $source = new NetteDbDataSource($selection);

        $grid = new Grid($this, $name);
        $grid->setPrimaryKey('id'); // primary key is now used always
        // set locale to Czech
        $grid->setLocale('cs');
        $table_id = 'id';

        $grid->setDataSource($source);
        $grid->addText('id', 'ID');
        $grid->addText('title', 'Název wodu');
        $grid->addText('text', 'Popis');
        $grid->addText('typ', 'Typ');
        $grid->addText('status', 'Status');


	    // here enable selection
	    $selection = $grid->enableRowSelection();

	    $selection->addLink('Schválit')// add selection link
	    ->setAjax(FALSE)// disable AJAX
	    ->onCall[] = $this->activeSelected;


	    $selection->addLink('Neschválit')
		    ->setAjax(FALSE)// disable AJAX
		    ->onCall[] = $this->unactiveSelected;


        $actions = $grid->addActions('');
        $dropDown = $actions->addDropDown()
            ->setName("Rychlé akce")
            ->setType('btn-default');

        /*   $dropDown->addHeader('Skupiny')
               ->setAttribute('class', 'db-head');*/

        $dropDown->addLink('Schválit', new Link('activate!', array(
            'id' => '{' . $table_id . '}')))
            ->setAttribute('class', 'dd-authority-user');

        $dropDown->addLink('Neschválit', new Link('deactivate!', array(
            'id' => '{' . $table_id . '}')))
            ->setAttribute('class', 'dd-authority-redaktor');


        $actions->addButton()
            ->setType('btn-default')
            ->setIcon('glyphicon-pencil')
            ->setTitle('Upravit WOD')
            ->setAttribute('href', new Link('Wod:wodEdit', array(
                'id' => '{' . $table_id . '}'
            )));

        $actions->addButton()
            ->setType('btn-danger')
            ->setIcon('glyphicon-trash')
            ->setConfirm('Opravdu chcete tento WOD trvale smazat?')
            ->setTitle('Smazat WOD')
            ->setAttribute('href', new Link('deleteWod!', array(
                'id' => '{' . $table_id . '}'
            )));

        // enable pager
        $grid->enablePager(15); // set limit for page to 5, default = 20
        return $grid;
    }

    protected function createComponentAddWodForm() {
        $form = new \Nette\Application\UI\Form();

        $form->addGroup();
        $form->addText('title', 'Nadpis:')
            ->setRequired("Vyplňte pole Nadpis.");

        $form->addTextArea('text', 'Náplň WODu:')
            ->setAttribute('class', 'mceEditor')
            ->setRequired("Vyplňte náplň Wodu.");

        $typy = array(
            'AMRAP' => 'AMRAP',
            'Time Cap' => 'Time Cap',
            'EMOM' =>'EMOM',
            'Warm-up' =>'E2MOM',
            'Tabata' =>'Tabata',
            'Warm-Up' =>'Warm Up',
            'Strength' =>'Strength',
	        'For time' =>'For time',
	        'Benchmark' =>'Benchmark',
        );


        $form->addSelect('typ', 'Vyber typ WODu:', $typy)
            ->setPrompt('Vyber typ')
            ->setRequired("Vyplňte typ Wodu.");

        $form->addSubmit('send', 'Odeslat nový WOD');
        $form->onSuccess[] = $this->addWodFormSubmitted;

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

    public function addWodFormSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();

        $values["status"] = "Neschváleno";
        $values["userID"] = $this->getUser()->getId();

        $variable = $this->database->table('wod')->insert($values);

        if(!$variable){
            $form->addError("WOD se nepodařilo uložit.");
        }else{
            $this->flashMessage('WOD byl úspěšně uložen. Nyní ho zkontrolujeme a poté možná schválíme.');
        }
    }

    public function renderWodAdd()
    {

    }

    public function renderWodDetailKonkretni($wodId)
    {
        $this->template->wod = $this->database->table('wod')->get($wodId);
    }

    public function renderWodDetail()
    {

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

    }

}
