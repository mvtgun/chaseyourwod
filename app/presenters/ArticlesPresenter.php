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


class ArticlesPresenter extends BasePresenter
{

    private $database;
    private $uploadImage;
    private $editArticleId;

    private $number;

    public function __construct(Nette\Database\Context $database, ImageDirectories $uploadImage)
    {
        $this->database = $database;
        $this->uploadImage = $uploadImage;
        $this->editArticleId = 1;

        $this->number = 1;
    }

    protected function createComponentAddArticleForm() {
        $form = new \Nette\Application\UI\Form();

        $form->addGroup();
        $form->addText('title', 'Titulek článku:')
            ->setRequired("Určitě zadejte titulek článku");

        $form->addTextArea('perex', 'Krátký popisek pro úvod:')
            ->setAttribute('class', 'mceEditor')
            ->setRequired("Perex je potřeba.");

        $form->addTextArea('text', 'Celý článek:')
            ->setAttribute('class', 'mceEditor2')
            ->setRequired("Bez hlavního textu to nepůjde.");


        $form->addGroup();
        $form->addUpload('img_src', 'Obrázek k článku:', FALSE)
            ->addRule(Form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF.')
            ->addRule(Form::MAX_FILE_SIZE, 'Maximální velikost souboru je 1024 kB.', 1024 * 1024 /* v bytech */)->setAttribute('class', 'file-loading')->setAttribute('id', 'avatar');

        $form->addText('seo_description', 'SEO Description:')
            ->setAttribute('title', 'Popis události, cca 155 znaků kvůli SEO.');

        $form->addText('seo_kw', 'SEO Klíčová slova:')
            ->setAttribute('title', 'Klíčová slova oddělená čárkou pro vyhledávače.');

        $form->addText('site_name', 'SEO Site name:')
            ->setAttribute('title', 'Název stránky pro vyhledávače a soc. sítě.');

        $form->addSubmit('send', 'Přidat nový článek');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');

        $form->onSuccess[] = $this->addArticleFormSubmitted;

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

    public function addArticleFormSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();
        $file = $values['img_src'];

        //  $variable = $this->userModel->register($values); //

        /** Tady menim nazev souboru na ojedinely hash **/

        $nazevComplete =  $this->uploadImage->getPhotoName($file);
        $priponaObrazku = pathinfo($nazevComplete, PATHINFO_EXTENSION);

        $arrayOfUsers = $this->database->table('clanky');
        $pocetRadku = $arrayOfUsers->count("*");

        $nazevSedi = true;
        $nazevHash = "tady_nic";

        while($nazevSedi){
            $countOfOK = 0;
            $nazevHash = Random::generate(6, '0-9a-z');
            foreach ($arrayOfUsers as $userInTable) {
                $nazevExistujiciPhoto = $userInTable->img_src;
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
        $this->uploadImage->saveArticle($file,$hashNameOfPhoto); //zapise obrazek
        //nahrání názvu nové fotky do pole údajů
        $values["img_src"] = $hashNameOfPhoto;

       // $this->uploadImage->saveArticle($file);
     //   $values["img_src"] = $this->uploadImage->getPhotoName($file);

        $values["status"] = 'Active';

        //tady zapisu data do DB
        $variable = $this->database->table('clanky')->insert($values);

        if(!$variable){
            $form->addError("Článek se nepodařilo uložit.");
        }else{
            $this->flashMessage('Článek byl úspěšně uložen.');
            $this->redirect('Articles:articlesView');
        }
    }


    protected function createComponentArticleEditForm() {
        $form = new \Nette\Application\UI\Form();

        $articleEditing = $this->database->table('clanky')->get($this->editArticleId);

        $form->addGroup();
        $form->addText('id', 'ID:')
            ->setAttribute('value', $articleEditing['id']);
        $form->addText('title', 'Titulek článku:')
             ->setValue($articleEditing['title']);

        $form->addTextArea('perex', 'Krátký popisek pro úvod:')
            ->setAttribute('class', 'mceEditor')
            ->setValue($articleEditing['Perex']);

        $form->addTextArea('text', 'Celý článek:')
            ->setAttribute('class', 'mceEditor2')
            ->setValue($articleEditing['text']);

        $form->addGroup();
        $form->addUpload('img_src', 'Obrázek k článku:', FALSE)
         ->setAttribute('class', 'file-loading')->setAttribute('id', 'avatar');

        $form->addText('seo_description', 'SEO Description:')
            ->setAttribute('title', 'Popis události, cca 155 znaků kvůli SEO.')
            ->setValue($articleEditing['seo_description']);

        $form->addText('seo_kw', 'SEO Klíčová slova:')
            ->setAttribute('title', 'Klíčová slova oddělená čárkou pro vyhledávače.')
            ->setValue($articleEditing['seo_kw']);

        $form->addText('site_name', 'SEO Site name:')
            ->setAttribute('title', 'Název stránky pro vyhledávače a soc. sítě.')
            ->setValue($articleEditing['site_name']);


        $form->addSubmit('send', 'Upravit článek');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');

        $form->onSuccess[] = $this->ArticleEditFormSubmitted;

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

    public function ArticleEditFormSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();

        $file = $values['img_src'];

        if($file->name==NULL){
            //update dotaz
            $this->database->table('clanky')->where('id', $values['id'])->update(Array('title' => $values['title'],'text' => $values['text'],'Perex' => $values['perex'],'seo_description' => $values['seo_description'],'seo_kw' => $values['seo_kw'],'site_name' => $values['site_name']));
            $this->flashMessage('Aktualizace údajů se zdařila.');
        }else{

            /** Tady smažu původní fotografii */

            $userEditing = $this->database->table('clanky')->get($values['id']);
            $this->uploadImage->removeArticlePhoto($userEditing['img_src']); // smaže fotku

            /** Tady menim nazev souboru na ojedinely hash **/

            $nazevComplete =  $this->uploadImage->getPhotoName($file);
            $priponaObrazku = pathinfo($nazevComplete, PATHINFO_EXTENSION);

            $arrayOfUsers = $this->database->table('clanky');
            $pocetRadku = $arrayOfUsers->count("*");

            $nazevSedi = true;
            $nazevHash = "tady_nic";

            while($nazevSedi){
                $countOfOK = 0;
                $nazevHash = Random::generate(6, '0-9a-z');
                foreach ($arrayOfUsers as $userInTable) {
                    $nazevExistujiciPhoto = $userInTable->img_src;
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
            $this->uploadImage->saveArticle($file,$hashNameOfPhoto); //zapise obrazek
            //nahrání názvu nové fotky do pole údajů
            $values["img_src"] = $hashNameOfPhoto;

            $this->database->table('clanky')->where('id', $values['id'])->update(Array('title' => $values['title'],'text' => $values['text'],'Perex' => $values['perex'],'img_src' => $values['img_src'],'seo_description' => $values['seo_description'],'seo_kw' => $values['seo_kw'],'site_name' => $values['site_name']));
            $this->flashMessage('Aktualizace údajů se zdařila.');
        }
    }

    public function handleActivate($id)
    {
        $articleToEdit = $this->database->table('clanky')->get($id);
        $articleStatus = $articleToEdit['status'];

        if($articleStatus === 'Active'){
            $this->flashMessage('Článek již je aktivován.');
        }else if($articleStatus == 'Deactive') {

            $this->database->table('clanky')->where('id', $id)->update(Array('status' => 'Active'));
            $this->flashMessage('Článek byl aktivován.');
        }
    }

    public function handleDeactivate($id)
    {
        $articleToEdit = $this->database->table('clanky')->get($id);
        $articleStatus = $articleToEdit['status'];

        if($articleStatus === 'Deactive'){
            $this->flashMessage('Článek již je deaktivován.');
        }else if($articleStatus == 'Active') {

            $this->database->table('clanky')->where('id', $id)->update(Array('status' => 'Deactive'));
            $this->flashMessage('Článek byl deaktivován. Článek se nyní nezobrazuje ve výpisech.');
        }
    }

    public function handleDeleteArticle($id)
    {
        $articleEditing = $this->database->table('clanky')->get($id);
        $this->uploadImage->removeArticlePhoto($articleEditing['img_src']); // smaže fotku
        $this->database->table('clanky')->where('id', $id)->delete();   // smaže článek
        $this->flashMessage('Článek byl trvale odstraněn.');
    }

    protected function createComponentArticleDataGrid($name) {

        $selection = $this->database->table('clanky')->order('date DESC');
        $source = new NetteDbDataSource($selection);

        $grid = new Grid($this, $name);
        $grid->setPrimaryKey('id'); // primary key is now used always
        // set locale to Czech
        $grid->setLocale('cs');
        $table_id = 'id';

        $grid->setDataSource($source);
        $grid->addText('id', 'ID');
        $grid->addText('title', 'Titulek');
        $grid->addDate('date', 'Datum sepsání')
            ->setFormat('j.n.Y H:i:s');

        $grid->addText('status', 'Status článku');

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
            ->setConfirm('Článek bude stále uložen v databázi, ale nebude zobrazen.');

        $actions->addButton()
            ->setType('btn-default')
            ->setIcon('glyphicon-pencil')
            ->setTitle('Upravit článek')
            ->setAttribute('href', new Link('Articles:articleEdit', array(
                'id' => '{' . $table_id . '}'
            )));

        $actions->addButton()
            ->setType('btn-danger')
            ->setIcon('glyphicon-trash')
            ->setConfirm('Opravdu chcete tento článek trvale smazat?')
            ->setTitle('Smazat článek')
            ->setAttribute('href', new Link('deleteArticle!', array(
                'id' => '{' . $table_id . '}'
            )));

        // enable pager
        $grid->enablePager(15); // set limit for page to 5, default = 20
        return $grid;
    }


    public function renderArticleEdit($id)
    {
        $this->editArticleId = $id;

        $userToEdit = $this->database->table('clanky')->get($id);
        $this->template->articleToEdit = $userToEdit;
    }

    public function renderArticleDetail($articleId)
    {

        $clanek = $this->database->table('clanky')->get($articleId);

        if(!$clanek){
            $this->error("Nezadávej do tý url nesmysly!");
        }

        $this->template->article = $clanek;

        $hodnoceniCelkemRows = $this->database->table('hodnoceniclanky');
        $uzivatelUzHodnotilRow = $this->database->table('hodnoceniclanky')->where('articleID =?',$articleId);

        $hodnoceniSum = 0;
        $pocetHodnoceni = 0;
        $hodnoceniFinal = 0;

        $uzivatelUzHodnotil = true;

        foreach ($hodnoceniCelkemRows as $hodnoceniRow) {
            if($hodnoceniRow->articleID==$articleId){
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

        $hodnoceniPole = ['uzivatel' => $uzivatelUzHodnotil,'hodnoceniCelkem' => $hodnoceniFinal];
        $this->template->hodnocenicko = $hodnoceniPole;

    }

    public function handleRateArticle($id,$rating)
    {
        $values['articleID'] = $id;
        $values['hodnoceni'] = $rating;
        $values['userID'] = $this->getUser()->getId();

        $hodnoceniCelkemRows = $this->database->table('hodnoceniclanky')->where('articleID =?',$id);
        $pocetRadku = $hodnoceniCelkemRows->count("*");

        $uzivatelUzHodnotil = true;

        foreach ($hodnoceniCelkemRows as $hodnoceniRow) {
            if($hodnoceniRow->userID==$this->getUser()->getId()){
                $uzivatelUzHodnotil = false;
            }
        }

        if($uzivatelUzHodnotil){
            $this->database->table('hodnoceniclanky')->insert($values);
            $this->flashMessage('Vaše hodnocení jsme uložili, děkujeme.');
        }else{
            $this->flashMessage('Tuto událost jste již ohodnotili. Děkujeme.');
        }
    }


    public function renderArticlesView(){
        $this->template->articles = $this->database->table('clanky')->order('date DESC');
    }


    public function handleSetNumber($numberNew){
        $this->number = $numberNew;
    }


    public function renderArticleList(){

        $clanky =  $this->database->table('clanky')->order('date DESC');
        $pocetClanku = count($clanky);

        $poleIds = array();

        for($i = 0; $i<$pocetClanku;$i++){
            $row =  $clanky->fetch();
            $poleIds[$i] = $row['id'];
        }

        $itemsPerPage = 15;

        $poleClanku = array();
        $countOfNumbers = ceil($pocetClanku/$itemsPerPage);

        $cislo = $this->number;

        $positionZacatek = ($cislo*$itemsPerPage)-$itemsPerPage;
        $positionKonec = $positionZacatek+$itemsPerPage;

        for($j = $positionZacatek; $j<$positionKonec;$j++){

            if($j==$pocetClanku){
                break;
            }else{
                $poleClanku[$j] = $clanky->get($poleIds[$j]);
            }
        }

        $parametry = ['pocetUdalosti' => $pocetClanku, 'current' =>$cislo, 'pocetStranek'=>$countOfNumbers];
        $this->template->paginationParametrs = $parametry;
        $this->template->clanky = $poleClanku;
    }
}
