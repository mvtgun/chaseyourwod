<?php

namespace App\Presenters;

use Nette;
use App\Model;
use App\Model\UserManager;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    private $authenticator;
    private $database;


    public function __construct(UserManager $authenticator, Nette\Database\Context $database)
    {
        $this->authenticator = $authenticator;
        $this->database = $database;
    }


    public function beforeRender()
    {

        $this->template->addFilter('mesiceCeskySklonene', function ($number) {
            $nazvy = array(1 => 'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince');
            return $nazvy[$number];
        });

        parent::beforeRender();
        $this->template->menuItems = array(
            'Domů' => 'Homepage:',
            'Události' => 'Events:eventsList',
            'Články' => 'Articles:articleList',
            'Náhodný WOD' => 'Wod:wodDetail',
            'Předchozí' => 'Events:eventsListPrevious',
           /*     'Komunita' => 'Community:',*/
            'O portálu' => 'Pages:pageAbout',
        );


    }

}
