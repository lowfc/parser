<?
namespace Foo;

error_reporting(E_ERROR | E_PARSE);

include 'simple_html_dom.php';
require 'rb-mysql.php';

use DOMDocument as DOMDocument;
use DOMXPath as DOMXPath;

set_time_limit(400);

function returnError()
{
    echo json_encode(["error"=>1]);
    die();
}

$FROM_PAGE = 1; // начиная со страницы
$PAGE_COUNT = 1; // парсить N страниц

// валидация GET параметров
if (isset($_GET['from_page'])){
    try{
        if ((int) $_GET['from_page'] != 0){
            $FROM_PAGE = (int) $_GET['from_page'];
        }
    }
    catch(Exception $e){
        returnError();
    }
}

if (isset($_GET['page_count'])){
    try{
        if ((int) $_GET['page_count'] != 0 and (int) $_GET['page_count'] <= 20){
            $PAGE_COUNT = (int) $_GET['page_count'];
        }
    }
    catch(Exception $e){
        returnError();
    }
}

class WebPage 
{

    private $url;

	public function __construct($url) 
    {
		$this->url = $url;
	}

	public function getPage() 
    {
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        try 
        {
            $html = file_get_html($this->url, false, stream_context_create($arrContextOptions));
            if ($html != false)
            {
                return $html;
            }
            else
            {
                return NULL;
            } 
        }
        catch(Exception $e)
        {
            return NULL;
        }
	}

}

class Parser 
{

    private $temp;
    
    public function __construct($temp) 
    {
        if ($temp !== NULL)
        {
            $this->temp = $temp;
        }
        else
        {
            returnError();
        } 
    }


    public function getShortNumbers() 
    {

        $array_result = array();
    
        if($this->temp->innertext!='') 
        {
 
            foreach ($this->temp->find('dt a') as $a) 
            {
        
                if ($a->plaintext!=" ") 
                {

                    array_push($array_result, str_replace(" ", "", explode("№", $a->plaintext)[1]));
                    
                } 
                
            }            
        }
        return $array_result;
    }
    
    
    public function getLargeNumbers() 
    {

        $array_result = array(); 

        if($this->temp->innertext!='') 
        {

            foreach ($this->temp->find('.descriptTenderTd span') as $a) 
            {
        
                if ($a->plaintext!=" ") 
                {

                    array_push($array_result, explode(": ", $a->plaintext)[1]);

                } 
                
            }            
        }
        return $array_result;
    }


    public function getLinks()
    {
        $best_array_result = array();

        $dom = new DOMDocument();
        @$dom->loadHTML($this->temp);
        
        $domXPATH = new DOMXPath($dom);
        $filteredBlocks = $domXPATH->query("//div[@class='registerBox procedure-list-item']");

        foreach ($filteredBlocks as $block) 
        {
    
            $card = ($block->childNodes[1]->childNodes[1]->childNodes[1]->childNodes[1]->childNodes[1]->childNodes[1]->childNodes[0]->attributes[1]->textContent);

            array_push($best_array_result, "https://etp.eltox.ru" . $card);
            
        }
        return $best_array_result;
    }

    public function getMail()
    {
        $arr_links = $this->getLinks();
        $mail = array();

        foreach ($arr_links as $temp)
        {
            $instance = new WebPage($temp); 
            $dom = new DOMDocument();
            @$dom->loadHTML($instance->getPage());
            $domXPATH = new DOMXPath($dom);
            $filteredBlocks = $domXPATH->query("//div[@class='tab-content']"); 
            $mathces = array();
            $sort = preg_match("/[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+.[a-zA-Z]/", $filteredBlocks[0]->nodeValue, $mathces);
            array_push($mail, $mathces[0]); 
        }
        return $mail;
    }

    public function getFile()
    {
        $arr_files = $this->getLinks();
        $files = array();

        foreach ($arr_files as $temp)
        {
            $data = array();
            $instance = new WebPage($temp); 
            $dom = new DOMDocument();
            @$dom->loadHTML($instance->getPage());
            $domXPATH = new DOMXPath($dom);

            $filteredTabs = $domXPATH->query("//div[@class='tab-content']");
            $filteredLinks = $domXPATH->query("//script[@type='text/javascript']");

            $arrFilteredCodedNames = array();
            $fileName = preg_match('/"name":"(.*?)",/', $filteredLinksStr, $arrFilteredCodedNames);
            
            $filteredLinksStr = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
            }, $filteredLinks[7]->textContent);
            
            $arrFilteredNames = array();
            $fileName = preg_match('/"alias":"(.*?)",/', $filteredLinksStr, $arrFilteredNames);
            array_push($data, $arrFilteredNames[1]);
    
            $arrFilteredCodedNames = array();
            $fileName = preg_match('/"name":"(.*?)",/', $filteredLinksStr, $arrFilteredCodedNames);
    
            $arrFilteredUrls = array();
            $fileUrl = preg_match('/"path":"(.*[^\.])"/', $filteredLinksStr, $arrFilteredUrls);
            array_push($data, "https://storage.eltox.ru/" . explode('","', $arrFilteredUrls[1])[0] . "/" . $arrFilteredCodedNames[1]); 
            array_push($files, $data);
        }
        return $files;
    } 


}

header('Content-Type: application/json; charset=utf-8');

$temp_large_num = array();
$dat = array();
$page_id = $FROM_PAGE;
$inc = 0;

while(TRUE) {
    $inc++;
    $array_print = array();
    $instance = new WebPage("https://etp.eltox.ru/registry/procedure/page/$page_id?type=1"); 
    $page = $instance->getPage();
    $exam = new Parser($page);
    $large_num = $exam->getLargeNumbers();
    if ($large_num == $temp_large_num or $inc > $PAGE_COUNT){
        break;
    }
    $temp_large_num = $large_num;
    $short_num = $exam->getShortNumbers();
    $links = $exam->getLinks();
    $mails = $exam->getMail();
    $files = $exam->getFile();
    array_push($array_print, $short_num);
    array_push($array_print, $large_num);
    array_push($array_print, $links);
    array_push($array_print, $mails);
    array_push($array_print, $files);
    
    array_push($dat, $array_print);

    $temp_large_num = $large_num;

    $page_id++;
}

class WorkerDB
{

    public function connectDB()
    {
        \R::setup('mysql:host=localhost; dbname=parser', 'root', '');
        
        if (!\R::testConnection()) die("Не удалось подключиться к базе данных");
    }

    public function setDB($dat)
    {

        foreach ($dat as $temp)
        {
            for ($i = 0; $i < count($temp[0]); $i++)
            {
                $tempCheck = \R::findOne('infocard', 'proc_num = ? AND ooc_proc_num = ? ',
                [ $temp[0][$i], $temp[1][$i] ] );
    
                if($tempCheck == NULL)
                {   
                    $row = \R::dispense('infocard');
                    $row->proc_num = $temp[0][$i];
                    $row->ooc_proc_num = $temp[1][$i];
                    $row->link = $temp[2][$i];
                    $row->mail = $temp[3][$i];
                    $row->file_name = $temp[4][$i][0];
                    $row->file_link = $temp[4][$i][1];
    
                    \R::store($row);
                }
            }
            
        }
    }
}

$db = new WorkerDB();
$db->connectDB();
$db->setDB($dat);


$jeka = array();
foreach ($dat as $iter){

    for($i = 0; $i < count($iter[0]); $i++) 
    {
        $arr = array();
        $arr['proc_num'] =$iter[0][$i];
        $arr['ooc_proc_num'] = $iter[1][$i];
        $arr['link'] = $iter[2][$i];
        $arr['mails'] = $iter[3][$i];
        $arr['file_name'] = $iter[4][$i][0];
        $arr['file_link'] = $iter[4][$i][1];
        array_push($jeka, $arr);
    }
    
}

echo json_encode(["error"=>0, "data"=>$jeka, "total"=>count($jeka)]);


