<?php
include('functions.php');
include('accesscontrol.php');

$tmppath = '/var/www/tmp/';
$fileroot = CLIENT.'-'.$_SESSION['userid'].'-songs-'.date('His');
$linebreak = (!empty($_GET['ppt_crlf']) ? "\r\n" : "\n");

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="songs_'.date('Y-m-d').'.txt"');

$steps = explode(',',$_GET['order']);

if ($_GET['ttype']=='main') $titlefield = 'Title';
elseif ($_GET['ttype']=='orig') $titlefield = 'OrigTitle';
else $titlefield = "IF(Title LIKE CONCAT('%',OrigTitle,'%'), Title, CONCAT(Title,' (',OrigTitle,')'))";  //ttype='paren'
$sql = "SELECT SongID,$titlefield AS title,Composer,Copyright,Lyrics FROM song WHERE SongID IN ($sid_list) ORDER BY FIELD(SongID,$sid_list)";
$result = sqlquery_checked($sql);

$songs = array();
while ($song = mysqli_fetch_object($result)) {
  if (!isset($_GET['trans'.$song->SongID])) {
    $_GET['trans'.$song->SongID] = 0;
  }
  $songs['s'.$song->SongID.'t'] = $song->title;
  $songs['s'.$song->SongID.'c'] = ($song->Composer!='' ? 'By '.$song->Composer : '').
  (($song->Composer && $song->Copyright) ? ';' : '') . (($song->Copyright!='' && $song->Copyright!='Public Domain') ? '©' : '').$song->Copyright;
  $songs['s'.$song->SongID.'k'] = preg_replace('/^([A-G][#b]?m?).*$/','$1',$song->SongKey);
  $stanzas = preg_split('/\n-*\s*\n/u',$song->Lyrics);
  $i = 0;
  foreach ($stanzas as $stanza) {
    $songs['s'.$song->SongID.chr($i+65)] = $stanza;
    $i++;
  }
}

$thisslide = $thisstanza = [];
$thistitle = '';
foreach ($steps as $step) {
  if (substr($step,0,3)=='br-')  $step = substr($step,3);  // Remove unused break indicator
  preg_match('/s([0-9]*)(.)/',$step,$matches);
  //print_r($matches);
  $key = $matches[0];
  $songid = $matches[1];
  $piecetype = $matches[2];

  switch(substr($piecetype,0,1)) {
  case 't':
    if (!empty($thistitle) && count($thisslide)) { //Need to dump the last of the previous song
      echo $thistitle."\r\n".implode("\r\n",$thisslide)."\r\n";
    }
    $thistitle = $songs[$key];
    break;
  case 'i':
    break;
  case 'c':
    // I don't yet know how to put the credit in a relevant spot on the PP slide
    //echo "\t\t\t".$songs[$key]."\n";
    break;
  default:  //stanza
    if ($_GET['romaji'] == 'hide' && preg_match('/\n(?!\[r\])/iu',"\n".$songs[$key]) === 0) break; //every line is romaji, so skip whole stanza
    $romajionly = ($_GET['romaji'] == 'only' && stripos($songs[$key],'[r]') !== FALSE); //signal to omit non-romaji lines in this stanza
    $lines = explode("\n",$songs[$key]);
    $thisstanza = [];
    foreach ($lines as $line) {
      if (strtolower(substr($line,0,3)) == '[r]') {  //romaji line
        if ($_GET['romaji'] != 'hide') {
          $thisstanza[] = ($romajionly?'':"\t")."\t".prepare($line);
        }
      } else { //line is not romaji
        if (!$romajionly) {
          $thisstanza[] = "\t" . prepare($line);
        }
      }
    }
    // end of stanza, so decide if it fits on this slide
    if (count($thisslide) + count($thisstanza) < $_GET['ppt_lines']) {
      // This stanza will fit on this slide
      if (count($thisslide) > 0) $thisslide[] = "\t\t "; //blank line in same size as Romaji
      $thisslide = array_merge($thisslide, $thisstanza);
      $thisstanza = [];
    } else {
      // Output the previous content as a slide and then start fresh with this stanza
      echo $thistitle."\r\n".implode("\r\n",$thisslide)."\r\n";
      $thisslide = $thisstanza;
      $thisstanza = [];
    }
  }
}  //end while looping through items to print

function prepare($text) {
  $text = preg_replace('#\[[^\[]*\]#','',rtrim($text));
  if (!empty($_GET['ppt_sjis']))  $text = mb_convert_encoding($text, "SJIS");
  return $text;
}
?>
