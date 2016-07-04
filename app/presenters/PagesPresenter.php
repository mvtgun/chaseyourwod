<?php

namespace App\Presenters;

use App\Model;
use Nette,
    App\Model\ImageDirectories,
    Nette\Application\UI\Form,
    Nette\Utils\Random,
    Mesour\DataGrid\NetteDbDataSource,
    Mesour\DataGrid\Grid,
    Mesour\DataGrid\Components\Link,
    Nette\Application\UI;


class PagesPresenter extends BasePresenter
{

    const
        ID_ABOUT = 1,
        ID_KONTAKT = 2;

    private $database;
    private $uploadImage;
    private $editPageId;

    public function __construct(Nette\Database\Context $database, ImageDirectories $uploadImage)
    {
        $this->database = $database;
        $this->uploadImage = $uploadImage;
        $this->editPageId = 1;
    }

    protected function createComponentPageEditForm() {
        $form = new \Nette\Application\UI\Form();

        $pageEditing = $this->database->table('pages')->get($this->editPageId);

        $form->addGroup();
        $form->addText('id', 'ID:')
            ->setAttribute('value', $pageEditing['id']);
        $form->addText('title', 'Titulek podstránky:')
             ->setValue($pageEditing['title']);
        $form->addTextArea('text', 'Celý text podstránky:')
            ->setAttribute('class', 'mceEditor2')
            ->setValue($pageEditing['text']);

        $form->addSubmit('send', 'Upravit podstránku');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');

        $form->onSuccess[] = $this->PageEditFormSubmitted;

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

    public function PageEditFormSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();

        //update dotaz
        $this->database->table('pages')->where('id', $values['id'])->update(Array('title' => $values['title'],'text' => $values['text']));
        $this->flashMessage('Aktualizace údajů se zdařila.');
    }


    protected function createComponentPageDataGrid($name) {

        $selection = $this->database->table('pages')->order('id ASC');
        $source = new NetteDbDataSource($selection);

        $grid = new Grid($this, $name);
        $grid->setPrimaryKey('id'); // primary key is now used always
        // set locale to Czech
        $grid->setLocale('cs');
        $table_id = 'id';

        $grid->setDataSource($source);
        $grid->addText('id', 'ID');
        $grid->addText('title', 'Titulek');

        $actions = $grid->addActions('');

        $actions->addButton()
            ->setType('btn-default')
            ->setIcon('glyphicon-pencil')
            ->setTitle('Upravit článek')
            ->setAttribute('href', new Link('Pages:pageEdit', array(
                'id' => '{' . $table_id . '}'
            )));


        // enable pager
        $grid->enablePager(15); // set limit for page to 5, default = 20
        return $grid;
    }

    public function renderPageEdit($id)
    {
        $this->editPageId = $id;

        $pageToEdit = $this->database->table('pages')->get($id);
        $this->template->pageToEdit = $pageToEdit;
    }

    public function renderPageView(){
       /* $this->template->articles = $this->database->table('clanky')->order('date DESC');*/
    }

    public function renderPageAbout(){
        $this->template->about = $this->database->table('pages')->get(self::ID_ABOUT);
    }
}
