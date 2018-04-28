<?php
include('functions.php');
include('accesscontrol.php');

$tmppath = '/var/www/tmp/';
$fileroot = CLIENT.'-'.$_SESSION['userid'].'-songs-'.date('His');
$linebreak = (!empty($_GET['pp_crlf']) ? "\r\n" : "\n");

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
  (($song->Composer && $song->Copyright) ? '; ' : '') . (($song->Copyright!='' && $song->Copyright!='Public Domain') ?
          (empty($_GET['pp_sjis'])?'©':'(c) ') : '').$song->Copyright;
  $stanzas = preg_split('/\n-*\s*\n/u',$song->Lyrics);
  $i = 0;
  foreach ($stanzas as $stanza) {
    $songs['s'.$song->SongID.chr($i+65)] = $stanza;
    $i++;
  }
}

$thisslide = $thisstanza = [];
$output = $thistitle = '';
$slidenum = 1; //just in case

foreach ($steps as $step) {
  if (substr($step,0,3)=='br-')  $step = substr($step,3);  // Remove unused break indicator
  preg_match('/s([0-9]*)(.)/',$step,$matches);
  //print_r($matches);
  $key = $matches[0];
  $songid = $matches[1];
  $piecetype = $matches[2];

  switch(substr($piecetype,0,1)) {
  case 't':
    if (!empty($thistitle) && count($thisslide)) { //New song
      //Dump the last of the previous song plus its credits
      $output .= $thistitle.' ['.$slidenum++.'/]'.$linebreak.implode($linebreak,$thisslide).$linebreak;
      if (!empty($songs[substr($key,0,-1).'c'])) $output .= "\t\t\t".$songs[substr($key,0,-1).'c'].$linebreak; //uses 3rd outline level
      $thisslide = [];
    }
    $thistitle = $songs[$key];
    $slidenum = 1;
    break;
  case 'i':
    break;
  case 'c':
    break;
  default:  //stanza
    if ($_GET['pp_romaji'] == 'hide' && preg_match('/\n(?!\[r\])/iu',"\n".$songs[$key]) === 0) break; //every line is romaji, so skip whole stanza
    $romajionly = ($_GET['pp_romaji'] == 'only' && stripos($songs[$key],'[r]') !== FALSE); //signal to omit non-romaji lines in this stanza
    $lines = explode("\n",$songs[$key]);
    $thisstanza = [];
    foreach ($lines as $line) {
      if (strtolower(substr($line,0,3)) == '[r]') {  //romaji line
        if ($_GET['pp_romaji'] != 'hide') {
          $thisstanza[] = ($romajionly?'':"\t")."\t".clean_lyrics($line);
        }
      } else { //line is not romaji
        if (!$romajionly) {
          $thisstanza[] = "\t".clean_lyrics($line);
        }
      }
    }
    // end of stanza, so decide if it fits on this slide
    if (count($thisslide) + count($thisstanza) < $_GET['pp_lines']) {
      // This stanza will fit on the same slide
      if (count($thisslide) > 0) $thisslide[] = "\t\t "; //blank line in same size as Romaji
      $thisslide = array_merge($thisslide, $thisstanza);
      $thisstanza = [];
    } else {
      // Output the previous content as a slide and then start fresh with this stanza
      if (!empty($thisslide)) $output .= $thistitle.' ['.$slidenum++.'/]'.$linebreak.implode($linebreak,$thisslide).$linebreak;
      $thisslide = $thisstanza;
      $thisstanza = [];
    }
  }
}  //end while looping through items to print
//output remaining content
if (!empty($thistitle) && count($thisslide)) {
  $output .= $thistitle.' ['.$slidenum.'/]'.$linebreak.implode($linebreak,$thisslide).$linebreak;
  if (!empty($songs[substr($key,0,-1).'c'])) $output .= "\t\t\t".$songs[substr($key,0,-1).'c'].$linebreak; //uses 3rd outline level
}

// Insert slides-per-song in output string
$slidecount = 1;
$offset = 0; //only used if we find an irrelevent case of '/]'
while ($slashpos = strrpos($offset?substr($output,0,$offset):$output, '/]')) {
  $bracketpos = strrpos(substr($output,0,$slashpos), '[');
  if ($bracketpos === FALSE) break;
  if (!ctype_digit(substr($output, $bracketpos+1, $slashpos-$bracketpos-1))) {
    $offset = $slashpos;
    continue;
  }
  $slidenum = substr($output, $bracketpos+1, $slashpos-$bracketpos-1);
  if ($slidenum > $slidecount) $slidecount = $slidenum;  //next song
  $output = substr_replace($output, strval($slidecount), $slashpos+1, 0);
  if ($slidenum == 1) $slidecount = 1;  //finished with song, so reset
}
if (!empty($_GET['pp_sjis']))  echo mb_convert_encoding($output, "SJIS");
else echo $output; //send final product


function clean_lyrics($text) {
  $text = preg_replace('#\[[^\[]*\]#u','',preg_replace('/　+$/u','',rtrim($text)));
  if (!empty($_GET['pp_trim']))  $text = ltrim($text);
  return $text;
}
?>
