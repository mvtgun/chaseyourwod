<?php

namespace App\Model;

class ImageDirectories
{

    /** @var string */
    private $profilePath;
    private $articlesPath;
    private $eventsPath;

    /**
     * @param string $profilePath
     * @param string $articlesPath
     * * @param string $eventsPath
     */
    public function __construct($profilePath,$articlesPath,$eventsPath)
    {
        $this->profilePath = $profilePath;
        $this->articlesPath = $articlesPath;
        $this->eventsPath = $eventsPath;
    }

    /**
     * @return string
     */
    public function getProfilePath()
    {
        return $this->profilePath;
    }

    /**
     * @return string
     */
    public function getArticlesPath()
    {
        return $this->articlesPath;
    }

    /**
     * saves the photo
     */
    public function saveProfile($file,$novyNazev)
    {
      //  $nameOfFile = $file->getName();
        $file->move($this->profilePath.$novyNazev);
    }

    /**
     * deletes the photo
     */
    public function removeProfile($file)
    {
        //$nameOfFile = $file->getName();
        unlink($this->profilePath.$file);
    }

    /**
     * saves the photo
     */
    public function saveArticle($file, $novyNazev)
    {
      //  $nameOfFile = $file->getName();
        $file->move($this->articlesPath.$novyNazev);
    }


    /**
     * deletes the photo
     */
    public function removeArticlePhoto($file)
    {
        //$nameOfFile = $file->getName();
        unlink($this->articlesPath.$file);
    }

    /**
     * deletes the photo
     */
    public function removeEventPhoto($file)
    {
        //$nameOfFile = $file->getName();
        unlink($this->eventsPath.$file);
    }

    /**
     * saves the photo
     */
    public function saveEvent($file, $novyNazev)
    {
        //  $nameOfFile = $file->getName();
        $file->move($this->eventsPath.$novyNazev);
    }

    /**
     * @return string
     */
    public function getPhotoName($file)
    {
        return $file->getName();
    }

}