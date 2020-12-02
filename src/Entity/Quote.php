<?php

class Quote
{
    public $id;
    public $siteId;
    public $destinationId;
    public $dateQuoted;

    public function __construct($id, $siteId, $destinationId, $dateQuoted)
    {
        $this->id = $id;
        $this->siteId = $siteId;
        $this->destinationId = $destinationId;
        $this->dateQuoted = $dateQuoted;
    }

    public static function renderHtml(Quote $quote)
    {
        return '<p>' . $quote->id . '</p>';
    }

    public static function renderText(Quote $quote)
    {
        return (string) $quote->id;
    }

    //Getters
    public function getDestinationURL(){
        $siteRepository = new SiteRepository();
        $site = $siteRepository->getById(
            $this->siteId
        );
        return $site ? $site->url : null;
    }

    public function getDestinationName(){
        $destinationRepository  = new destinationRepository();
        $destination = $destinationRepository->getById(
            $this->destinationId
        );
        return $destination ? $destination->countryName : null;
    }

    public function getSummary($id)
    {
        return Quote::renderText($this->getById($this->id));
    }

    public function getSummaryHtml($id)
    {
        return Quote::renderText($this->getById($this->id));
    }
}