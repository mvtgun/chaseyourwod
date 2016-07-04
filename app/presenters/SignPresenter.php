<?php

namespace App\Presenters;

use Nette;
use App\Forms\SignFormFactory;
use App\Model\UserManager;
use Nette\Security\User;
use Tracy\Debugger;


class SignPresenter extends BasePresenter
{
	/** @var SignFormFactory @inject */
	public $factory;

	private $authenticator;
	private $database;


	public function __construct(UserManager $authenticator, Nette\Database\Context $database)
	{
		$this->authenticator = $authenticator;
		$this->database = $database;
	}


	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addText('mail', 'Přihlašovací e-mail:', 35)
			->setEmptyValue('@')
			->addRule(\Nette\Forms\Form::FILLED, 'Vyplňte Váš email')
			->addCondition(\Nette\Forms\Form::FILLED)
			->addRule(\Nette\Forms\Form::EMAIL, 'Neplatná emailová adresa')
			->setRequired('Prosím vyplňte své uživatelské jméno.');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Prosím vyplňte své heslo.');

		$form->addCheckbox('remember', 'Zůstat přihlášen');

		$form->addSubmit('send', 'Přihlásit');

		$form->onSuccess[] = array($this, 'signInFormSucceeded');


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

	public function signInFormSucceeded($form)
	{
		$values = $form->getValues();

		$arrayFinal = array($values['mail'],$values['password'],$values['remember']);

		$vracena = $this->authenticator->authenticate($arrayFinal);

		if($vracena == "Uživatel s tímto e-mailem u nás neexistuje."){
			$form->addError($vracena);
		}else if($vracena == "Zadané heslo není správné") {
			$form->addError($vracena);
		}else if($vracena == "Tento uživatel čeká na potvrzení e-mailu"){
				$form->addError($vracena);
		}else{
			$user = $vracena; //identity

			if($values->remember){
				// přihlášení vyprší po 2 dnech
				$this->getUser()->setExpiration('1000 days', FALSE);

			}else{
				// odhlásit uživatele až zavře prohlížeč (bez časového limitu)
				$this->getUser()->setExpiration(0, TRUE);
			}

			$this->getUser()->login($values->mail, $values->password);

			$infoUsers = $this->database->table('users')->get($user->getId());

			$this->template->user_info = $infoUsers;

			$this->redirect('Homepage:default');
		}

	}

	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Odhlášení proběhlo úspěšně.');
		$this->redirect('Homepage:default');
	}

}
