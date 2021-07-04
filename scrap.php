<?php
define('BASE',"./");
$per_run = 20;
$start = (((int)$_SERVER['argv'][1])*$per_run)+2;
$end = $start+$per_run;
$sad = $_SERVER['argv'][2];
$symbol = $_SERVER['argv'][3];
$mode = $_SERVER['argv'][4];
var_dump($_SERVER['argv']);

if (empty($mode) || strlen($mode) == 0){
  $mode == 'uzasadnione';
};

if($mode == 'uzasadnione'){
  $uzasadnienia = 'takUzasadnienie=on&';
}else{
  $uzasadnienia = '';
};

if(!isset($_SERVER["HTTP_PROXY"])){
  $_SERVER['HTTP_PROXY'] = "https://127.0.0.1:8080";
}

error_reporting(E_ALL);
set_time_limit(0);
 // ignore_user_abort(true);
include('class.php');
include('simple_html_dom.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-6.5.0/src/Exception.php';
require 'PHPMailer-6.5.0/src/PHPMailer.php';
require 'PHPMailer-6.5.0/src/SMTP.php';
function mail_html($to, $subject, $html, $symbol){
    $mail = new PHPMailer(true);
    $mail->CharSet = "UTF-8";
    $mail->SMTPDebug = 2;
    // $mail->SMTPAutoTLS = false;
    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = $_SERVER['SMTP_HOST']; // Specify main and backup SMTP servers
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = $_SERVER['SMTP_USER']; // SMTP username
    $mail->Password = $_SERVER['SMTP_PASSWORD']; // SMTP password
    $mail->Port = 587; // TCP port to connect to

    $mail->From = $_SERVER['SMTP_FROM'];
    $mail->FromName = str_replace("*", "", "CBOSA-${symbol}");
    $mail->addAddress($to); // Add a recipient

    $mail->WordWrap = 50; // Set word wrap to 50 characters
    $mail->isHTML(true); // Set email format to HTML

    $mail->Subject = $subject;
    $mail->Body    = $html;
    if (!$mail->send()) {
	echo 'Mailer Error: ' . $mail->ErrorInfo;
    } else {
	echo 'Message sent!';
    }
}

function sleep_visual($time){
  echo $time.': ';
  for($i=0;$i<$time; $i++){
    echo '.';
    sleep(1);
  };
  echo "\r";
}
function get($curl, $url,$ref_update = True, $sleep = True){
  if($sleep) sleep_visual(rand(5,10));
  echo "[GET] $url\n";
  $data = $curl -> get($url);
  $curl -> referer = $url;
  return str_get_html($data);
}
function post($curl, $url, $payload = array(), $ref_update = True, $sleep = True){
  echo "$payload\n";
  if($sleep) sleep_visual(rand(5,10));
  echo "[POST] $url\n";
  $data = $curl -> post($url,$payload);
  $curl -> referer = $url;
  return str_get_html($data);
}
$curl = new cURL();

$curl->set_proxy($_SERVER['HTTP_PROXY']);

var_dump($curl -> get('http://httpbin.org/ip'));

mail_html('naczelnik@jawnosc.tk','naczelnik@jawnosc.tk','naczelnik@jawnosc.tk','naczelnik@jawnosc.tk');
$data = get($curl, 'https://orzeczenia.nsa.gov.pl/cbo/query', True);
$payload = "wszystkieSlowa=&wystepowanie=gdziekolwiek&odmiana=on&sygnatura=&sad={$sad}&rodzaj=dowolny&symbole={$symbol}&odDaty=&doDaty=&sedziowie=&funkcja=dowolna&${uzasadnienia}rodzaj_organu=&hasla=&akty=&przepisy=&publikacje=&glosy=&submit=Szukaj";
$html = post($curl, 'https://orzeczenia.nsa.gov.pl/cbo/search', $payload);

function parse_serp($html){
  $row = $html->find('table.info-list');
  if(empty($row)){
    return false;
  }else{
    $return = array();
    foreach($row as $value){
      $return[trim($value->find('a',0)->plaintext)] = $value;
    };
    return $return;
  }
};

$output = ''; 
$all = 0;
$new = 0;
$json = json_decode((file_exists(BASE."${symbol}-${mode}.json") ? file_get_contents(BASE."${symbol}-${mode}.json") : "[]"),True);
for($i=$start; $i<=$end; $i++){
  $row = parse_serp($html);
  if($row === false) { echo "Przerwano z powodu wykrycia CAPTCHY"; break; };
  if($new >= 20) { echo "Przerwano z powodu duzej wiadomości."; break; }
  foreach($row as $key=>$value){
    if(in_array($key,$json) === false){
      $output.= $value;
      $json[] = $key;
      $new+=1;
    };
    $all+=1;
  }
  $html = get($curl,"https://orzeczenia.nsa.gov.pl/cbo/find?p=".$i);
};


if($new > 200){
     throw new Exception("Notification overload ($new > 200). Has there been a filter failure?");
};

file_put_contents(BASE."${symbol}-${mode}.json", json_encode($json, JSON_PRETTY_PRINT));
file_put_contents(BASE.strftime("artifact/%Y-%m-%d-%H-%M.json"), json_encode($json, JSON_PRETTY_PRINT));

$to  = $_SERVER['SMTP_TO'];
$subject = strftime("Orzecznictwo ${mode} na dzień %d.%m.%Y");
 // message
$message = '<head><html><meta charset="utf-8"><base href="http://orzeczenia.nsa.gov.pl/" target="_blank">';
$message.= '<style>a:link, a:active {color:#1155CC; text-decoration:none} a:hover {text-decoration:underline; cursor: pointer} a:visited{color:##6611CC}</style>';
$message.= '</head><body>';
$message.= $output;
$message.= "<p>Przeanalizowano $i stron wyników dla $symbol ($start-$end) znajdując $all orzeczeń, w tym $new nowych.</p>";
$message.= '</body></html>';
file_put_contents(BASE.strftime("artifact/%Y-%m-%d-%H-%M.html"),$message);

if($new > 0){
	echo "Wysłano powiadomienie";
	var_dump(mail_html($to, $subject, $message, $symbol));
}else{
	echo "Wstrzymano się od powiadomienia";
}
