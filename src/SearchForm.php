<?php
namespace App;

use GuzzleHttp\Client;
use Exception;

class SearchForm
{
    const MAIN_PAGE = 'https://search.ipaustralia.gov.au/trademarks/search/advanced';
    const SEARCH_PAGE = 'https://search.ipaustralia.gov.au/trademarks/search/doSearch';

    private $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function searchWorker()
    {
        $client = (new Client(['cookies' => true]));
        $client->get(self::MAIN_PAGE);
        $cookieJar = $client->getConfig('cookies');
        $cookies = $cookieJar->toArray();

        $csrfToken = "";
        $cookiesArray = "";
        foreach ($cookies as $cookie) {
            if ($cookie['Name'] == "XSRF-TOKEN") {
                $csrfToken = $cookie['Value'];
            }

            $cookiesArray .= $cookie['Name']."=".$cookie['Value']."; ";
        }
        return new MainWorker($this->searchLinkByCookiesAndCsrf($cookiesArray, $csrfToken));
    }

    private function searchLinkByCookiesAndCsrf($cookies, $csrf)
    {
        $url = self::SEARCH_PAGE;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST,           true );
        curl_setopt($curl, CURLOPT_POSTFIELDS,     '_csrf='.$csrf.'&wv%5B0%5D='.$this->term.'&wt%5B0%5D=PART&weOp%5B0%5D=AND&wv%5B1%5D=&wt%5B1%5D=PART&wrOp=AND&wv%5B2%5D=&wt%5B2%5D=PART&weOp%5B1%5D=AND&wv%5B3%5D=&wt%5B3%5D=PART&iv%5B0%5D=&it%5B0%5D=PART&ieOp%5B0%5D=AND&iv%5B1%5D=&it%5B1%5D=PART&irOp=AND&iv%5B2%5D=&it%5B2%5D=PART&ieOp%5B1%5D=AND&iv%5B3%5D=&it%5B3%5D=PART&wp=&_sw=on&classList=&ct=A&status=&dateType=LODGEMENT_DATE&fromDate=&toDate=&ia=&gsd=&endo=&nameField%5B0%5D=OWNER&name%5B0%5D=&attorney=&oAcn=&idList=&ir=&publicationFromDate=&publicationToDate=&i=&c=&originalSegment=' );
        curl_setopt($curl, CURLOPT_HTTPHEADER,     array('Content-Type: text/plain'));

        $headers = [
            "cookie: ".$cookies,
            'origin: https://search.ipaustralia.gov.au',
            'referer: https://search.ipaustralia.gov.au/trademarks/search/advanced'
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$searchUrl)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) {
                    return $len;
                }
                if($header[0] == 'location') {
                    $searchUrl = trim($header[1]);
                }
                return $len;
            }
        );
        curl_exec($curl);
        curl_close($curl);

        if(!$searchUrl){
            throw new Exception("Search link can not defined");
        }

        return $searchUrl;
    }
}
