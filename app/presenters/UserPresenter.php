<?php

namespace App\Presenters;

use Nette,
    App\Model,
    App\Model\ImageDirectories,
    Nette\Forms\Controls,
    Mesour\DataGrid\NetteDbDataSource,
    Mesour\DataGrid\Grid,
    Mesour\DataGrid\Components\Link,
    Nette\Application\UI,
    Nette\Utils\Random,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    Nette\Security\User,
    Nette\Security\IIdentity,
    Nette\Security\Identity,
	Nette\Security\Passwords,
    Nette\Bridges\ApplicationLatte\UIMacros;

use Tracy\Debugger;

class UserPresenter extends BasePresenter
{
    private $database;

    private $editUserId;
    private $uploadImage;

    /** @var \Nette\Application\UI\ITemplateFactory */
    private $application;

    public function __construct(Nette\Database\Context $database, ImageDirectories $uploadImage,\Nette\Application\Application $application)
    {
        $this->database = $database;
        $this->uploadImage = $uploadImage;
        $this->editUserId = 1;

        $this->application = $application;
    }

	protected function createComponentUserChangePasswdForm()
	{
		$form = new \Nette\Application\UI\Form();

		$form->addGroup();

		$userEditing = $this->database->table('users')->get($this->editUserId);

		$form->addGroup();
		$form->addText('id', 'ID:')
			->setAttribute('value', $userEditing['id']);

		$form->addPassword('passwordOld', 'Současné heslo:')
			->setRequired('Zadejte současné heslo');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Zvolte si heslo')
			->addRule(\Nette\Forms\Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaky', 3);

		$form->addPassword('passwordVerify', 'Heslo pro kontrolu:')
			->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu')
			->addRule(\Nette\Forms\Form::EQUAL, 'Hesla se neshodují', $form['password']);

		$form->addSubmit('send', 'Ulož nové heslo');
		$form->onSuccess[] = $this->userChangePasswdFormSubmitted;

		// setup form rendering
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = 'div class=form-group';
		$renderer->wrappers['pair']['.error'] = 'has-error';
		$renderer->wrappers['control']['container'] = 'div class=col-sm-7';
		$renderer->wrappers['label']['container'] = 'div class="col-sm-5 control-label"';
		$renderer->wrappers['control']['description'] = 'span class=help-block';
		$renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
// make form and controls compatible with Twitter Bootstrap
		$form->getElementPrototype()->class('form-horizontal');

		return $form;
	}

	public function userChangePasswdFormSubmitted(\Nette\Application\UI\Form $form)
	{
		$values = $form->getValues();
		unset($values["passwordVerify"]);

		if($this->getUser()->getId()==$values['id']){
			$row = $this->database->table('users')->where("id",$this->editUserId)->fetch();
			if(Nette\Security\Passwords::verify($values['passwordOld'],$row['password'])){
				$noveHesloHash = \Nette\Security\Passwords::hash($values['password']);
				$this->database->table('users')->where("id",$this->editUserId)->update(Array('password' => $noveHesloHash));
				$this->flashMessage('Heslo bylo v pořádku změněno. Nyní platí nové.');

			}else{
				$this->flashMessage('Původní heslo nesedí. Zkuste to prosím ještě jednou.');
			}
		}else{
			$this->flashMessage('Tohle není dobrý nápad.');
		}

	}

    protected function createComponentUserForgotPasswdForm()
    {
        $form = new \Nette\Application\UI\Form();

	    $form->addGroup();

	    $form->addText('mail', 'E-mail uvedený při registraci:', 35)
		    ->setEmptyValue('@')
		    ->addRule(\Nette\Forms\Form::FILLED, 'Vyplňte Váš email')
		    ->addCondition(\Nette\Forms\Form::FILLED)
		    ->addRule(\Nette\Forms\Form::EMAIL, 'Neplatná emailová adresa');


        $form->addSubmit('send', 'Zažádat o heslo');
        $form->onSuccess[] = $this->userForgotPasswdFormSubmitted;

        // setup form rendering
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-7';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
// make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-horizontal');

        return $form;
    }

    public function userForgotPasswdFormSubmitted(\Nette\Application\UI\Form $form)
    {
	    $values = $form->getValues();

	    $zadanyMail = $values['mail'];

	    $userEditing = $this->database->table('users')->where('mail =?',$zadanyMail)->fetch();


	    if($userEditing['id']!=NULL){
		    $noveHeslo = Random::generate(9, '0-9a-z');
		    $idOfUser = $userEditing['id'];
		    $this->createAndSendMailWithPasswd("Chaseyourwod.com <info@chaseyourwod.com>",$values['mail'],"Nové heslo",$noveHeslo);
		    $noveHesloHash = \Nette\Security\Passwords::hash($noveHeslo);
		    $this->database->table('users')->where('id', $idOfUser)->update(Array('password' => $noveHesloHash));

		    $this->flashMessage('Nové heslo bylo odesláno na zadaný e-mail.');

	    }else{
		    $form->addError("Zadaný e-mail se nenachází v naší databázi.");
		    $this->flashMessage('Pokud u nás nemáte ještě účet, můžete se zaregistrovat.');
	    }

    }

    public function createAndSendMailWithPasswd($from,$to,$subject,$noveHeslo)
    {
        $mail = new Message;

        /** @var \Nette\Application\IPresenter */
        $presenter = $this->application->getPresenter();

        $latte = new \Latte\Engine;

        $params = array(
            'newPasswd' => $noveHeslo,
            '_presenter' => $presenter, // kvůli makru {plink}
            '_control' => $presenter    // kvůli makru {link}
        );

        UIMacros::install($latte->getCompiler()); // Kromě jiných zaregistruje makro link a plink

        // a vygenerujeme HTML email
        $html = $latte->renderToString(WWW_DIR.'/presenters/templates/email_passwd.latte', $params);

        $mail->setFrom($from)
            ->addTo($to)
            ->setSubject($subject)
            ->setHTMLBody($html);

        $mailer = new SendmailMailer;
        $mailer->send($mail);
    }


    protected function createComponentUserEditForm()
    {
        $form = new \Nette\Application\UI\Form();

        $userEditing = $this->database->table('users')->get($this->editUserId);

        $form->addGroup();
        $form->addText('id', 'ID:')
            ->setAttribute('value', $userEditing['id']);
        $form->addText('name', 'Jméno:')
            ->setAttribute('value', $userEditing['name']);
        $form->addText('surname', 'Příjmení:')
            ->setAttribute('value', $userEditing['surname']);

        $form->addGroup();
        $form->addUpload('profile_photo', 'Profilová fotografie:', FALSE)
            ->setAttribute('class', 'file-loading')->setAttribute('id', 'avatar');

        $form->addSubmit('send', 'Upravit údaje');
        $form->onSuccess[] = $this->userEditFormSubmitted;

        // setup form rendering
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-7';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
// make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-horizontal');

        return $form;
    }

    public function userEditFormSubmitted(\Nette\Application\UI\Form $form)
    {
        $values = $form->getValues();

        $file = $values['profile_photo'];

        if ($file->name == NULL) {
            //update dotaz
            $this->database->table('users')->where('id', $values['id'])->update(Array('name' => $values['name'], 'surname' => $values['surname']));
            $this->flashMessage('Aktualizace údajů se zdařila.');
        } else {

            /** Tady smažu původní fotografii */

            $userEditing = $this->database->table('users')->get($values['id']);

            if ($userEditing['profile_photo'] != "default.jpg") {
                $this->uploadImage->removeProfile($userEditing['profile_photo']); // smaže fotku
            }

            /** Tady menim nazev souboru na ojedinely hash **/

            $nazevComplete = $this->uploadImage->getPhotoName($file);
            $priponaObrazku = pathinfo($nazevComplete, PATHINFO_EXTENSION);

            $arrayOfUsers = $this->database->table('users');
            $pocetRadku = $arrayOfUsers->count("*");

            $nazevSedi = true;
            $nazevHash = "tady_nic";

            while ($nazevSedi) {
                $countOfOK = 0;
                $nazevHash = Random::generate(6, '0-9a-z');
                foreach ($arrayOfUsers as $userInTable) {
                    $nazevExistujiciPhoto = $userInTable->profile_photo;
                    $nazevExistujiciPhotoWithoutExtension = preg_replace('/\\.[^.\\s]{3,4}$/', '', $nazevExistujiciPhoto);

                    if ($nazevExistujiciPhotoWithoutExtension != $nazevHash) {
                        $countOfOK++;
                    }
                }
                if ($countOfOK == $pocetRadku) {
                    $nazevSedi = false;
                }
            }
            /** jestli dojdeme až sem, můžeme použít hash jako název fotky*/
            $hashNameOfPhoto = $nazevHash . "." . $priponaObrazku; //vytvori nazev souboru - hash + puvodni pripona
            $this->uploadImage->saveProfile($file, $hashNameOfPhoto); //zapise obrazek
            //nahrání názvu nové fotky do pole údajů
            $values["profile_photo"] = $hashNameOfPhoto;

            $this->database->table('users')->where('id', $values['id'])->update(Array('name' => $values['name'], 'surname' => $values['surname'], 'profile_photo' => $values['profile_photo']));
            $this->flashMessage('Aktualizace údajů se zdařila.');
        }
    }


    public function handleDeleteUser($id)
    {
        if ($id == $this->getUser()->getId()) {
            $this->flashMessage('Nemůžeš smazat sám sebe.');
        } else {
            $userEditing = $this->database->table('users')->get($id);
            if ($userEditing['profile_photo'] != "default.jpg") {
                $this->uploadImage->removeProfile($userEditing['profile_photo']); // smaže fotku
            }
            $this->database->table('users')->where('id', $id)->delete(); //smaže uživatele
            $this->flashMessage('Uživatel byl odstraněn.');
        }
    }

    public function handlePromoteUser($id, $rights)
    {
        $userAuthorityWant = $rights;
        $userToEdit = $this->database->table('users')->get($id);
        $userAuthorityHas = $userToEdit['role'];
        if ($userAuthorityHas === $userAuthorityWant) {
            $this->flashMessage('Uživatel už má tato práva nastavena.');
        } else if ($userAuthorityHas == 'guest' && $userAuthorityWant == 'redaktor') {
            // změnit role - guest na redaktor
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva redaktora.');
        } else if ($userAuthorityHas == 'guest' && $userAuthorityWant == 'spravce') {
            // změnit role - guest na spravce
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva redaktora.');
        } else if ($userAuthorityHas == 'guest' && $userAuthorityWant == 'admin') {
            // změnit role - guest na admin
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena nejvyšší práva administrátora.');
        } else if ($userAuthorityHas == 'redaktor' && $userAuthorityWant == 'admin') {
            // změnit role - redaktor na admin
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena nejvyšší práva administrátora.');
        } else if ($userAuthorityHas == 'redaktor' && $userAuthorityWant == 'guest') {
            // změnit role - redaktor na guest
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva uživatele.');
        } else if ($userAuthorityHas == 'redaktor' && $userAuthorityWant == 'spravce') {
            // změnit role - redaktor na spravce
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva uživatele.');
        } else if ($userAuthorityHas == 'admin' && $userAuthorityWant == 'redaktor') {
            // změnit role - admin na redaktor
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva redaktora.');
        } else if ($userAuthorityHas == 'admin' && $userAuthorityWant == 'spravce') {
            // změnit role - admin na spravce
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva redaktora.');
        } else if ($userAuthorityHas == 'admin' && $userAuthorityWant == 'guest') {
            // změnit role - admin na guest
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva uživatele.');
        } else if ($userAuthorityHas == 'spravce' && $userAuthorityWant == 'redaktor') {
            // změnit role - spravce na redaktor
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva redaktora.');
        } else if ($userAuthorityHas == 'spravce' && $userAuthorityWant == 'guest') {
            // změnit role - spravce na guest
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva uživatele.');
        } else if ($userAuthorityHas == 'spravce' && $userAuthorityWant == 'admin') {
            // změnit role - spravce na admin
            $this->database->table('users')->where('id', $id)->update(Array('role' => $userAuthorityWant));
            $this->flashMessage('Uživateli byla nastavena práva uživatele.');
        }
    }

    public function renderUserEdit($id)
    {
        $this->editUserId = $id;
        $userToEdit = $this->database->table('users')->get($id);

        $thisUser = $this->database->table('users')->get($this->user->getId());

        if ($thisUser['role'] =='admin' || $this->user->getId()==$id) {

            $this->template->userToEdit = $userToEdit;
	        $this->template->userLogginIn = $thisUser;

        }else if($this->user->getId()!=$id){
            $this->flashMessage('Tohle není dobrý nápad.');
            $this->redirect('User:userProfile');
        }

    }

	public function renderUserPasswd($id)
	{
		$this->editUserId = $id;
		$userToEdit = $this->database->table('users')->get($id);

		$thisUser = $this->database->table('users')->get($this->user->getId());

		if ($thisUser['role'] =='admin' || $this->user->getId()==$id) {

			$this->template->userToEdit = $userToEdit;

		}else if($this->user->getId()!=$id){
			$this->flashMessage('Tohle není dobrý nápad.');
			$this->redirect('User:userProfile');
		}

	}


	public function renderUserProfile()
    {
        $user = $this->database->table('users')->get($this->user->getId());

        if ($this->user->isLoggedIn()) {


            $thisUserID = $this->getUser()->getId();
            $thisUserRow = $this->database->table('users')->get($thisUserID);

            $sledovaneWodsRetezec = $thisUserRow['sledovane_wods'];
            $sledovaneWodsPole = explode(';',$sledovaneWodsRetezec);

            $this->template->userSledovaneWods = $sledovaneWodsPole;
            $this->template->userLogginIn = $user;
        }else{
            $this->flashMessage('Nejdříve je třeba se přihlásit.');
            $this->redirect('Homepage:default');
        }
    }

    public function renderUserConfirm($userHash)
    {
       $userConfirmer = $this->database->table('users')->where('potvrzeni_uzivatele =?', $userHash)->update(Array('potvrzeni_uzivatele' => 'A'));
       $this->template->userConfirmer = $userConfirmer;
    }

    protected function createComponentBasicDataGrid($name) {

        $selection = $this->database->table('users');
        $source = new NetteDbDataSource($selection);

        $grid = new Grid($this, $name);
        $grid->setPrimaryKey('id'); // primary key is now used always
        // set locale to Czech
        $grid->setLocale('cs');
        $table_id = 'id';

        $grid->setDataSource($source);
        $grid->addText('id', 'ID');
        $grid->addText('name', 'Jméno');
        $grid->addText('surname', 'Příjmení');
        $grid->addText('mail', 'E-mail');
        $grid->addText('potvrzeni_uzivatele', 'Potvrzen');
        $grid->addText('role', 'Práva');

        $actions = $grid->addActions('');
        $dropDown = $actions->addDropDown()
            ->setName("Oprávnění")
            ->setType('btn-default');

        $dropDown->addHeader('Skupiny')
            ->setAttribute('class', 'db-head');

        $dropDown->addLink('Uživatel', new Link('promoteUser!', array(
            'id' => '{' . $table_id . '}','rights' => 'guest')))
            ->setAttribute('class', 'dd-authority-user');

        $dropDown->addLink('Redaktor', new Link('promoteUser!', array(
            'id' => '{' . $table_id . '}','rights' => 'redaktor')))
            ->setAttribute('class', 'dd-authority-redaktor');

        $dropDown->addLink('Správce', new Link('promoteUser!', array(
            'id' => '{' . $table_id . '}','rights' => 'spravce')))
            ->setAttribute('class', 'dd-authority-spravce');

        $dropDown->addLink('Administráror',
            new Link('promoteUser!',
                array(
                'id' => '{' . $table_id . '}',
                'rights' => 'admin')
            ))
            ->setAttribute('class', 'dd-authority-admin')
            ->setConfirm('Opravdu tomuto uživateli chcete nastavit nejvyšší práva?');

        $actions->addButton()
            ->setType('btn-default')
            ->setIcon('glyphicon-pencil')
            ->setTitle('Upravit údaje')
            ->setAttribute('href', new Link('User:userEdit', array(
                'id' => '{' . $table_id . '}'
            )));

        $actions->addButton()
            ->setType('btn-danger')
            ->setIcon('glyphicon-trash')
            ->setConfirm('Opravdu chcete tohoto uživatele smazat?')
            ->setTitle('Smazat uživatele')
            ->setAttribute('href', new Link('deleteUser!', array(
                'id' => '{' . $table_id . '}'
            )));

        // enable pager
        $grid->enablePager(15); // set limit for page to 5, default = 20
        return $grid;
    }


    protected function createComponentSledovaneUdalostiGrid($name) {

        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $sledovaneUdalostiRetezec = $thisUserRow['sledovane_udalosti'];
        $sledovaneUdalostiPole = explode(';',$sledovaneUdalostiRetezec);

        $data = array();
        $i = 0;

        foreach ($sledovaneUdalostiPole as &$polozkaVPoli) {
            if($polozkaVPoli!=0){
                $row = $this->database->table('udalosti')->get($polozkaVPoli);
                $data[$i]=['id'=>$row['id'],'nazev'=>$row['nazev']];
            }
                $i++;
        }

        $source = new \Mesour\DataGrid\ArrayDataSource($data);

        $grid = new Grid($this, $name);
        $grid->setPrimaryKey('id'); // primary key is now used always
        // set locale to Czech
        $grid->setLocale('cs');
        $table_id = 'id';

        $grid->setDataSource($source);
        $grid->addText('id', 'ID');
        $grid->addText('nazev', 'Název akce');

        $actions = $grid->addActions('');

             $actions->addButton()
            ->setType('btn-default')
            ->setIcon('glyphicon-search')
            ->setTitle('Prohlédnout událost')
            ->setAttribute('href', new Link('Events:eventDetail', array(
                'eventId' => '{' . $table_id . '}'
            )));

             $actions->addButton()
            ->setType('btn-danger')
            ->setIcon('glyphicon-remove')
            ->setTitle('Přestat sledovat událost')
            ->setAttribute('href', new Link('PrestatSledovatUdalost!', array(
                'id' => '{' . $table_id . '}'
            )));

        // enable pager
        $grid->enablePager(15); // set limit for page to 5, default = 20
        return $grid;
    }


    protected function createComponentSledovaneWodsGrid($name) {

        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $sledovaneWodsRetezec = $thisUserRow['sledovane_wods'];
        $sledovaneWodsPole = explode(';',$sledovaneWodsRetezec);


        $data = array();
        $i = 0;

        foreach ($sledovaneWodsPole as &$polozkaVPoli) {

            if(strlen($polozkaVPoli)>0){
                $polozkaVPoli = $polozkaVPoli[0];
            }

            if($polozkaVPoli!=0){
                $row = $this->database->table('wod')->get($polozkaVPoli);
                $data[$i]=['id'=>$row['id'],'title'=>$row['title'],'typ'=>$row['typ']];
            }
            $i++;
        }

        $source = new \Mesour\DataGrid\ArrayDataSource($data);

        $grid = new Grid($this, $name);
        $grid->setPrimaryKey('id'); // primary key is now used always
        // set locale to Czech
        $grid->setLocale('cs');
        $table_id = 'id';

        $grid->setDataSource($source);
        $grid->addText('id', 'ID')->setAttribute("class","idOfWod");
        $grid->addText('title', 'Název');
        $grid->addText('typ', 'Typ');

        $actions = $grid->addActions('');

        $actions->addButton()
            ->setType('btn-default')
            ->setIcon('glyphicon-search')
            ->setTitle('Prohlédnout WOD')
            ->setAttribute('href', new Link('Wod:wodDetailKonkretni', array(
                'wodId' => '{' . $table_id . '}'
            )));

        $actions->addButton()
            ->setType('btn-success')
            ->setIcon('glyphicon-ok')
            ->setTitle('Označit jako hotový (odcvičený)')
            ->setAttribute('href', new Link('OznacWodA!', array(
                'wodId' => '{' . $table_id . '}'
            )));

        $actions->addButton()
            ->setType('btn-danger')
            ->setIcon('glyphicon-remove')
            ->setTitle('Přestat sledovat WOD')
            ->setAttribute('href', new Link('PrestatSledovatWod!', array(
                'id' => '{' . $table_id . '}'
            )));

        // enable pager
        $grid->enablePager(15); // set limit for page to 5, default = 20
        return $grid;
    }

    protected function createComponentNavrzeneWodyGrid($name) {

        $thisUserID = $this->getUser()->getId();
        $wodsRows = $this->database->table('wod')->where('userID = ?',$thisUserID);

        $data = array();
        $i = 0;
        foreach ($wodsRows as $wodRow) {
                $data[$i]=['id'=>$wodRow->id,'title'=>$wodRow->title,'status'=>$wodRow->status];
            $i++;
        }

        $source = new \Mesour\DataGrid\ArrayDataSource($data);

        $grid = new Grid($this, $name);
        $grid->setPrimaryKey('id'); // primary key is now used always
        // set locale to Czech
        $grid->setLocale('cs');
        $table_id = 'id';

        $grid->setDataSource($source);
        $grid->addText('id', 'ID');
        $grid->addText('title', 'Název WOD');
        $grid->addText('status', 'Stav');

        $actions = $grid->addActions('');

        $actions->addButton()
            ->setType('btn-default')
            ->setIcon('glyphicon-search')
            ->setTitle('Prohlédnout WOD')
            ->setAttribute('href', new Link('Wod:wodDetailKonkretni', array(
                'wodId' => '{' . $table_id . '}'
            )));
/*
        $actions->addButton()
            ->setType('btn-danger')
            ->setIcon('glyphicon-remove')
            ->setTitle('Smazat navržený WOD')
            ->setAttribute('href', new Link('DeleteWod!', array(
                'id' => '{' . $table_id . '}'
            )));*/

        // enable pager
        $grid->enablePager(15); // set limit for page to 5, default = 20
        return $grid;
    }

    public function renderUserView()
    {
        $this->template->usersSummary = $this->database->table('users')->order('id ASC');
    }

    public function renderForgotPasswd()
    {
    }

    public function handleDeleteWod($id)
    {
        $this->database->table('wod')->where('id', $id)->delete();;   // smaže wod
        $this->flashMessage('WOD byl trvale odstraněn.');
    }

    public function handleOznacWodA($wodId)
    {
       $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $retezec = $thisUserRow['sledovane_wods'];
        $sledovaneWodsPole = explode(';',$retezec);

        $retezecFinal = "";
        $retezecVsuvka = $wodId."A";

        foreach ($sledovaneWodsPole as &$polozkaVPoli) {

            if(strlen($polozkaVPoli)>0){
                $polozkaVPoliID = $polozkaVPoli[0];
            }else{
                $polozkaVPoliID = 0;
            }

            if($polozkaVPoli!=""){
                if($polozkaVPoliID==$wodId){
                    $retezecFinal = $retezecFinal.$retezecVsuvka.";";
                }else{
                    $retezecFinal = $retezecFinal.$polozkaVPoli.";";
                }
            }
        }
        $this->database->table('users')->where('id', $thisUserID)->update(Array('sledovane_wods' => $retezecFinal));
        $this->flashMessage('WOD '. $wodId .' je označen jako hotový.');
    }

    public function handlePrestatSledovatWod($id)
    {
        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $retezec = $thisUserRow['sledovane_wods'];
        $sledovaneWodsPole = explode(';',$retezec);

        $retezecFinal = "0N;";
        $polozkaVPoliPriznak = "N";

        foreach ($sledovaneWodsPole as &$polozkaVPoli) {

            $polozkaVPoliID = substr($polozkaVPoli,0,1);
            $polozkaVPoliPriznak = substr($polozkaVPoli,1,2);

            if($polozkaVPoliID != $id){
                if($polozkaVPoliID != 0){
                    $retezecFinal = $retezecFinal.$polozkaVPoliID.$polozkaVPoliPriznak.";";
                }
            }
        }

        $this->database->table('users')->where('id', $thisUserID)->update(Array('sledovane_wods' => $retezecFinal));
        $this->flashMessage('WOD '. $id .' již nesledujete.');
    }

    public function handlePrestatSledovatUdalost($id)
    {
        $thisUserID = $this->getUser()->getId();
        $thisUserRow = $this->database->table('users')->get($thisUserID);

        $retezec = $thisUserRow['sledovane_udalosti'];
        $sledovaneUdalostiPole = explode(';',$retezec);

        $retezecFinal = "0;";

        foreach ($sledovaneUdalostiPole as &$polozkaVPoli) {
            if($polozkaVPoli != $id){
                if($polozkaVPoli != 0){
                    $retezecFinal = $retezecFinal.$polozkaVPoli.";";
                }

            }
        }

        $this->database->table('users')->where('id', $thisUserID)->update(Array('sledovane_udalosti' => $retezecFinal));
        $this->flashMessage('Událost již nesledujete.');
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
