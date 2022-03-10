<?php

namespace App;


use DiDom\Document;
use GuzzleHttp\Client;

class MainWorker
{
    private $url;
    private $client;
    private $resultCount;

    public function __construct($url)
    {
        $this->url = $url;
        $this->client = (new Client());
    }

    public function getResultCount()
    {
        return $this->resultCount;
    }

    public function results(): array
    {
        $this->resultCount = $this->objectsCount();
        $pages = intdiv($this->resultCount, 100);

        $resultPage = $this->client->get($this->url)->getBody();
        $results = $this->parseResultPage((string)$resultPage);

        for ($p = 1; $p <= $pages; $p++) {
            $resultPage = $this->client->get($this->url . '&p=' . $p)->getBody();
            $results = array_merge($results, $this->parseResultPage((string)$resultPage));
        }

        return $results;
    }

    private function objectsCount()
    {
        $resultPage = $this->client->get($this->url)->getBody();
        $dom = new Document((string)$resultPage);
        $count = $dom->first(".number.qa-count")->text();
        return (int)$count;
    }

    private function parseResultPage($resultPage): array
    {
        $objects = [];
        $doc = new Document($resultPage);
        foreach ($doc->find('tbody') as $element) {
            $obj['number'] = $element->first('.number')->first('a')->text();
            if ($logo = $element->first('.trademark.image')) {
                $obj['logo_url'] = $logo->first('img')->getAttribute('src');
            } else {
                $obj['logo_url'] = '';
            }
            $obj['name'] = trim($element->first('.trademark.words')->text());
            $obj['classes'] = trim(str_replace(PHP_EOL, ' ', $element->first('.classes ')->text()));
            $status = $element->first('.status');
            $obj['status1'] = trim(str_replace('status', '', $status->first('i')->getAttribute('class')));

            if (!$status->first('span')) {
                $obj['status2'] = 'Status not available';
            } else {
                $obj['status2'] = trim($status->first('span')->text());
            }
            $obj['details_page_url'] = 'https://search.ipaustralia.gov.au/trademarks/search/view/' . $obj['number'];
            $objects[] = $obj;
        }

        return $objects;
    }
}