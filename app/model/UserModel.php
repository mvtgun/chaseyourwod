<?php

namespace App\Model;

class UserModel extends \Nette\Object {

    /** @const */
    const TABLE_NAME = "users";

    /** @var \Nette\Database\Context */
    private $database;


    function __construct(\Nette\Database\Context $database) {
        $this->database = $database;
    }

    public function register(\Nette\Utils\ArrayHash $values) {
        if(!isset($values['password'])) {
            throw new \Nette\InvalidArgumentException("Not found password key");
        }
        $values['password'] = \Nette\Security\Passwords::hash($values['password']);
        $mailDB  =  $this->database->table(self::TABLE_NAME)->where('mail', $values['mail'])->fetch();

        if($mailDB){
            return "Uživatel s tímto e-mailem už u nás existuje.";
        }else{
            return $this->database->table(UserModel::TABLE_NAME)->insert($values);
        }
    }

}
