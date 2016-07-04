<?php

namespace App\Presenters;
use App\Model\ImageDirectories;
use App\Model\UserModel;
use Nette\Http\FileUpload;
use Nette\Application\UI,
    Nette\Utils\Random,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    Nette\Bridges\ApplicationLatte\UIMacros,
    Nette\Application\UI\Form as Form;

class RegisterPresenter extends BasePresenter {

  //  /** @var \App\Model\UserModel @inject */  // neni dobre pouzivat, lepsi konstruktor:
    private $userModel;
    private $uploadImage;
    private $database;

    /** @var \Nette\Application\UI\ITemplateFactory */
    private $application;

    public function __construct(UserModel $userModel, ImageDirectories $uploadImage, \Nette\Database\Context $database, \Nette\Application\Application $application)
    {
        $this->uploadImage = $uploadImage;
        $this->userModel = $userModel;
        $this->database = $database;

        $this->application = $application;
    }
    
    protected function createComponentRegisterForm() {
        $form = new \Nette\Application\UI\Form();
        $form->addGroup();
        $form->addText('name', 'Jméno:')
             ->setRequired("Vyplňte prosím jméno");
        $form->addText('surname', 'Příjmení:')
            ->setRequired("Vyplňte prosím příjmení");

        $form->addText('mail', 'Přihlašovací e-mail:', 35)
                ->setEmptyValue('@')
                ->setAttribute('type','email')
                ->addRule(\Nette\Forms\Form::FILLED, 'Vyplňte Váš email')
                ->addCondition(\Nette\Forms\Form::FILLED)
                ->addRule(\Nette\Forms\Form::EMAIL, 'Neplatná emailová adresa');
        $form->addPassword('password', 'Heslo:')
                ->setRequired('Zvolte si heslo')
                ->addRule(\Nette\Forms\Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaky', 3);
        $form->addPassword('passwordVerify', 'Heslo pro kontrolu:')
                ->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu')
                ->addRule(\Nette\Forms\Form::EQUAL, 'Hesla se neshodují', $form['password']);


        $form->addGroup();
        $form->addUpload('profile_photo', 'Profilová fotografie:', FALSE)
            ->setAttribute('class', 'file-loading')->setAttribute('id', 'avatar')
            ->addConditionOn($form['profile_photo'], Form::EQUAL, TRUE)
                ->addRule(Form::IMAGE, 'Avatar musí být JPEG, PNG nebo GIF.')
                ->addRule(Form::MAX_FILE_SIZE, 'Maximální velikost souboru je 512 kB.', 512 * 1024 /* v bytech */);

        $form->addSubmit('send', 'Registrovat');
        $form->onSuccess[] = $this->registerFormSubmitted;

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

    public function createAndSendMail($from,$to,$subject,$idOfUser)
    {
        $mail = new Message;

        /** @var \Nette\Application\IPresenter */
        $presenter = $this->application->getPresenter();

        $latte = new \Latte\Engine;

        $params = array(
            'idOfUser' => $idOfUser,
            '_presenter' => $presenter, // kvůli makru {plink}
            '_control' => $presenter    // kvůli makru {link}
        );

        UIMacros::install($latte->getCompiler()); // Kromě jiných zaregistruje makro link a plink

        // a vygenerujeme HTML email
       $html = $latte->renderToString(WWW_DIR.'/presenters/templates/email.latte', $params);

        $mail->setFrom($from)
            ->addTo($to)
            ->setSubject($subject)
            ->setHTMLBody($html);

        $mailer = new SendmailMailer;
        $mailer->send($mail);
    }

    public function registerFormSubmitted(\Nette\Application\UI\Form $form) {
        $values = $form->getValues();
        unset($values["passwordVerify"]);
        $values["role"] = "guest";

        if($values['profile_photo']->name == NULL){
            $values['profile_photo'] = "default.jpg";
        }else{
            $file = $values['profile_photo'];

            /** Tady menim nazev souboru na ojedinely hash **/

            $nazevComplete =  $this->uploadImage->getPhotoName($file);
            $priponaObrazku = pathinfo($nazevComplete, PATHINFO_EXTENSION);
            $arrayOfUsers = $this->database->table('users');
            $pocetRadku = $arrayOfUsers->count("*");

            $nazevSedi = true;
            $nazevHash = "tady_nic";

            while($nazevSedi){
                $countOfOK = 0;
                $nazevHash = Random::generate(6, '0-9a-z');
                foreach ($arrayOfUsers as $userInTable) {
                    $nazevExistujiciPhoto = $userInTable->profile_photo;
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
            $this->uploadImage->saveProfile($file,$hashNameOfPhoto); //zapise obrazek
            //nahrání názvu nové fotky do pole údajů
            $values["profile_photo"] = $hashNameOfPhoto;
        }

        $values["sledovane_udalosti"] = "0;";
        // registrace uživatele

        $values['potvrzeni_uzivatele'] = Random::generate(9, '0-9a-z');

        $variable = $this->userModel->register($values);
        if($variable == "Uživatel s tímto e-mailem už u nás existuje."){
            $form->addError($variable);
        }else{

            $this->createAndSendMail("Chaseyourwod.com <info@chaseyourwod.com>",$values['mail'],"Potvrzení registrace",$values['potvrzeni_uzivatele']);

            $this->flashMessage('Registrace se úspěšně zdařila. Účet bude třeba nejprve potvrdit v zadaném e-mailu.');
            $this->redirect('Sign:in');
        }
    }

}
