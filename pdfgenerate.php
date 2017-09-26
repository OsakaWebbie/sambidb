<?php
include('functions.php');
include('accesscontrol.php');
include('transpose.php');

$tmppath = '/var/www/tmp/';
$fileroot = CLIENT.'-'.$_SESSION['userid'].'-songs-'.date('His');

$sql = "SELECT * FROM pdfformat WHERE FormatName='".mysqli_real_escape_string($db,urldecode($_GET['formatname']))."'";
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result) < 1) die("Format specs not found in database.");
$format = mysqli_fetch_object($result);

$steps = explode(',',$_GET['order']);

if ($_GET['ttype']=='main') $titlefield = 'Title';
elseif ($_GET['ttype']=='orig') $titlefield = 'OrigTitle';
else $titlefield = "IF(Title LIKE CONCAT('%',OrigTitle,'%'), Title, CONCAT(Title,' (',OrigTitle,')'))";  //ttype='paren'
$sql = "SELECT SongID,$titlefield AS title,Composer,Copyright,SongKey,Lyrics,".
"Instruction,Pattern FROM song WHERE SongID IN ($sid_list) ORDER BY FIELD(SongID,$sid_list)";
$result = sqlquery_checked($sql);

$songs = array();
while ($song = mysqli_fetch_object($result)) {
  if (!isset($_GET['trans'.$song->SongID])) {
    $_GET['trans'.$song->SongID] = 0;
  }
  $songs['s'.$song->SongID.'t'] = escape_all($song->title) . escape_most(($_GET['tkey']&&preg_match('/^[A-G]/',$song->SongKey) ?
      ' ['.str_replace('#','\\Sharp{}',str_replace('b','\\Flat{}',transpose($song->SongKey,preg_replace('/^([A-G][#b]?m?).*$/',
      "$1",$song->SongKey),$_GET['trans'.$song->SongID]))).']' : ''));
  $songs['s'.$song->SongID.'i'] = ($_GET['ilong'] ? escape_all(mb_ereg_replace('\[|]','',$song->Instruction)) :
  escape_all(mb_ereg_replace('\[[^\[]*\]','',$song->Instruction)));
  $songs['s'.$song->SongID.'c'] = ($song->Composer!='' ? 'By '.escape_all($song->Composer) : '').
  (($song->Composer && $song->Copyright) ? ($_GET['copy2']?'\\\\*\n':'; ') : '').
  (($song->Copyright!='' && $song->Copyright!='Public Domain') ? '©' : '').escape_all($song->Copyright);
  $songs['s'.$song->SongID.'k'] = preg_replace('/^([A-G][#b]?m?).*$/','$1',$song->SongKey);
  $stanzas = preg_split('/\n-*\s*\n/u',$song->Lyrics);
  $i = 0;
  foreach ($stanzas as $stanza) {
    $songs['s'.$song->SongID.chr($i+65)] = $stanza;
    $i++;
  }
}

/* PREP TO MAKE THINGS EASIER TO READ */
list($lyricsize,$lyricleading) = explode(',',$format->LyricsSizeSpace,2);

/* ALL OUTPUT FROM NOW GOES INTO THE FILE */

ob_start();
echo "\xEF\xBB\xBF";  //UTF-8 Byte Order Mark
?>
\documentclass{ujarticle}
\usepackage[<?=$_GET['papersize'] ? $_GET['papersize'].'paper'.
substr($format->LayoutParams,strpos($format->LayoutParams,',',
strrpos($format->LayoutParams,'paper'))) : $format->LayoutParams?>]{geometry}
<?php if ($format->NumColumns > 1) echo "\usepackage{multicol}\n"; ?>
\usepackage{needspace}
\usepackage{hanging}
\usepackage{color}
\usepackage{ulem}
\usepackage[T1]{fontenc}
\usepackage{lmodern}

\newcommand\Sharp{\ensuremath{^{\sharp}}}
\newcommand\Flat{\ensuremath{^{\flat}}}
\newcommand\Bass[1]{{<?=$_GET['color']?'\color[rgb]{1,0.5,0.9}':''?>{\kern -0.4ex/\kern -0.2ex}#1}}
\newcommand\Sup[1]{\textsuperscript{#1}}
\newcommand{\songtitle}[1]{\pagebreak[2]{\color<?=$_GET['color']?'[rgb]{0,0.3,0.8}':'{black}'?>%
  \fontsize{<?=str_replace(',','}{',$format->TitleSizeSpace)?>}\selectfont<?=$format->TitleStyle?><?php
  if ($format->TitleHanging!='') echo '\hangpara{'.$format->TitleHanging.'}{1}'; ?>#1<?php
  echo str_repeat('}',substr_count($format->TitleStyle,'{')); ?>}\\}
\newcommand{\instr}[1]{%
  \nopagebreak[4]{<?=$_GET['color']?'\color[rgb]{0,0.5,0}':''?>%
  \fontsize{<?=str_replace(',','}{',$format->InstructionSizeSpace)?>}\selectfont<?php
  if ($format->InstructionHanging!='') echo '\begin{hangparas}{'.$format->InstructionHanging.'}{1}'; ?>
<?=$format->InstructionStyle?>{#1}<?php echo str_repeat('}',substr_count($format->InstructionStyle,'{')); ?>
<?=($format->InstructionHanging!='')?'\\end{hangparas}':''?>}\\}

\newcommand{\credit}[1]{%
  \nopagebreak[4]\begin{flush<?=$format->CreditAlign?>}%
  \fontsize{<?=str_replace(',','}{',$format->CreditSizeSpace)?>}\selectfont<?php
  if ($format->CreditHanging!='') echo '\\begin{hangparas}{'.$format->CreditHanging.'}{1}';
  ?><?=$format->CreditStyle?>#1<?php echo str_repeat('}',substr_count($format->CreditStyle,'{')); ?><?php
  if ($format->CreditHanging!='') echo '\\end{hangparas}'; ?>\end{flush<?=$format->CreditAlign?>}}

\newcommand{\chord}[1]{%
    \fontsize{<?=str_replace(',','}{',$format->LyricsSizeSpace)?>}\selectfont%
    \textcolor<?=$_GET['color']?'[rgb]{1,0,0}':'{black}'?>{\textbf{#1}}}
\newcommand{\lyric}[1]{\fontsize{<?=$lyricsize.'}{'.$lyricleading?>}\selectfont\textcolor{black}{#1}}
\newcommand{\romaji}[1]{\begingroup\fontsize{<?=str_replace(',','}{',$format->RomajiSizeSpace)?>}\selectfont
  <?=$format->RomajiStyle?>{#1}<?php echo str_repeat('}',substr_count($format->RomajiStyle,'{')); ?>\endgroup}
\newcommand{\chordset}[2]{%
   {\begin{tabular}[b]{@{}l@{}}\chord{#1}\\[-0.6ex]\lyric{#2}\end{tabular}}}

\newenvironment{stanza}
{
  \vspace{1em}
  \setlength{\parskip}{<?=$format->LyricsParskip?>}
<?php if ($format->LyricsHanging!='') echo "  \\begin{hangparas}{".$format->LyricsHanging."}{1}\n"; ?>
  \fontsize{<?=$lyricsize.'}{'.$lyricleading?>}\selectfont
}
{
<?php if ($format->LyricsHanging!='') echo "  \\end{hangparas}\n"; ?>
\pagebreak[1]
}            

\newenvironment{stanza-chords}
{
  \vspace{1em}
  \setlength{\parskip}{<?=$format->LyricsParskip?>}
<?php if ($format->LyricsHanging!='') echo "  \\begin{hangparas}{".$format->LyricsHanging."}{1}\n"; ?>
  \fontsize{<?=$lyricsize.'}{'.$lyricleading?>}\selectfont
}
{
<?php if ($format->LyricsHanging!='') echo "  \\end{hangparas}\n"; ?>
\pagebreak[1]
}            

\begin{document}

\sffamily
\gtfamily
\raggedright
\raggedbottom
\clubpenalty=10000
\widowpenalty=10000
\pagestyle{empty}
<?php if ($format->NumColumns > 1) {
  echo "\\raggedcolumns\n\\setlength{\\columnsep}{".($format->Gutter)."mm}\n";
  echo "\\begin{multicols*}{".$format->NumColumns."}\n";
}  ?>

<?php
$songnum = 1;
$numbertext = '';
foreach ($steps as $step) {
  if (substr($step,0,3)=='br-') {
    echo ($format->NumColumns>1) ? "\\columnbreak\n" : "\\newpage\n";
    $step = substr($step,3);
  }
  preg_match('/s([0-9]*)(.)/',$step,$matches);
  //print_r($matches);
  $key = $matches[0];
  $songid = $matches[1];
  $piecetype = $matches[2];
  $printchords = strpos($step,'-ch')!==FALSE;
  
  switch(substr($piecetype,0,1)) {
  case 't':
    if ($songnum>1) echo $format->BetweenSongs;
    if ($_GET['tnum']=='circle') $numbertext = '\symbol{"24'.dechex(95+$songnum).'} ';
    elseif ($_GET['tnum']=='basic') $numbertext = $songnum . '. ';
    echo "\\songtitle{".$numbertext.$songs[$key]."}\n\n";
    $songnum++;
    break;
  case 'i':
    echo "\\instr{".preg_replace("~\r\n|\n|\r~","\\\\\\\\*\n",$songs[$key])."}\n\n"; //yes, eight slashes become two!
    break;
  case 'c':
    echo "\\credit{".$songs[$key]."}\n\n";
    break;
  default:  //assume to be a stanza
    if ($_GET['romaji'] == 'hide' && preg_match('/\n(?!\[r\])/iu',"\n".$songs[$key]) === 0) break; //every line is romaji, so skip whole stanza
    $romajionly = ($_GET['romaji'] == 'only' && stripos($songs[$key],'[r]') !== FALSE); //signal to omit non-romaji lines in this stanza
    $thislineromaji = TRUE; //illogical perhaps, but it guarantees normal line spacing if the first line is romaji
    $lines = explode("\n",$songs[$key]);
    echo "\\begin{stanza".($printchords?"-chords":"")."}\n";
    $firstline = true;
    foreach ($lines as $line) {
      if (strtolower(substr($line,0,3)) == '[r]') {  //romaji line
        // DO SPECIAL STUFF TO FORMAT OR OMIT ROMAJI
        if ($_GET['romaji'] == 'hide') {
          continue;
        } else { //we show the romaji line
          if (!$thislineromaji) {
            echo '\\nopagebreak[4]\\vspace{-'.$format->LyricsParskip.'}\\nopagebreak[4]';  //romaji after non-romaji, so nullify the parskip space
          } else {
            echo '\\nopagebreak[3]';  //not following a non-romaji line, so relax the rule a bit
          }
          $thislineromaji = TRUE;
          $line = substr($line,3);
        }
      } else { //line is not romaji
        if ($romajionly) continue;
        $thislineromaji = FALSE;
        if (!$firstline) echo '\\nopagebreak[4]';
        else $firstline = false;
      }
      if ($printchords && !($thislineromaji && $_GET['romaji']=='chordless')) { //we will print any chords on this line
        $chordsets = explode('[',rtrim($line));
        $lastset = count($chordsets)-1;
        $insideword = false;
        foreach ($chordsets as $cs_index => $chordset) {
          if (strpos($chordset,']')===false) {  //plain lyrics at beginning of line
            echo str_replace(' ','\\ ',escape_most($chordset));
            $lastchar = mb_substr($chordset,mb_strlen($chordset)-1,1);
            $insideword = (strlen($lastchar)==1 && $lastchar!=' ');  //ASCII character and not space
          } else {  //expecting chord + closing bracket + lyric
            $clpair = explode(']',$chordset);  //chord is #0, lyric is #1
            if ($cs_index < $lastset) $clpair[0] .= ' ';
            $lyriccounter = 0.0;
            $chordsize = strlen($clpair[0]);
            $lyricsize = mb_strlen($clpair[1]);
            if ($insideword && mb_substr($clpair[1],0,1) != ' ') {  //this spot is the middle of a word
              echo '\\nolinebreak[4]';  //discourage linebreak in middle of word
            } elseif ($cs_index > 1) {  //if not the beginning of the line
              echo '\\linebreak[0]';  //I don't actually know why this is needed, but without it crazy things happen
            }
            if ($chordsize > $lyricsize) {  //the whole lyric will be inside the chordset
              if ($insideword && ($chordsize > $lyricsize+4) && ($cs_index < $lastset) && (strlen(mb_substr($clpair[1],0,1))>1)
              && (mb_substr($clpair[1],0,1) != '')) //inside a word with roman characters and chord is extra long
                  $clpair[1] .= ' - ';
              echo makechordset(transpose($clpair[0],$songs['s'.$songid.'k'],$_GET['trans'.$songid]), $clpair[1], $thislineromaji);
            } else {  //might be able to leave some of the lyric outside the chordset (to allow wrapping)
              for ($charindex=0; $charindex < $lyricsize; $charindex++)  {
                $char = mb_substr($clpair[1], $charindex, 1);
                if (strlen($char) > 1) {  //multi-byte character
                  $lyriccounter += 1.1;  //adjust this value to balance between allowing wrapping and avoiding unneeded spacing
                  if ($lyriccounter >= $chordsize) break;
                } else {  //single-byte character
                  $lyriccounter += 0.5;  //adjust this value to balance between allowing wrapping and avoiding unneeded spacing
                  if ($lyriccounter >= $chordsize && $char == ' ') break;
                }
              } //end of for loop to choose what goes inside chordset
              echo makechordset(transpose($clpair[0],$songs['s'.$songid.'k'],$_GET['trans'.$songid]),
                  mb_substr($clpair[1],0,$charindex+1),$thislineromaji) . str_replace(' ','\\ ',escape_most(mb_substr($clpair[1],$charindex+1)));
              $insideword = (strlen(rtrim($chordset)) == mb_strlen($chordset));
            } //end of if/else re: relative length of chord and lyric
          }
        }
        echo "\\par\n";
      } else {
        //simply remove all chords
        if ($thislineromaji) echo '\\romaji{'.str_replace(' ','\\ ',escape_braces(escape_most(preg_replace('#\[[^\[]*\]#','',rtrim($line)))))."\\par}\n";
        else echo str_replace(' ','\\ ',escape_braces(escape_most(preg_replace('#\[[^\[]*\]#','',rtrim($line)))))."\\par\n";
      }
    }
    echo '\\end{stanza'.($printchords?'-chords':'')."}\n\n";
  }
}  //end while looping through items to print

if ($format->NumColumns > 1)  echo "\\end{multicols}\n";
echo "\\end{document}\n";

file_put_contents($tmppath.$fileroot.'.tex',ob_get_contents()) or die('Failed to write to '.$tmppath.$fileroot.'.tex');
ob_end_clean();

// RUN TEX COMMANDS TO MAKE PDF

exec("cd $tmppath;/usr/local/bin/uplatex -interaction=batchmode --output-directory=$tmppath $fileroot", $output, $return);
if (!is_file("$tmppath$fileroot.dvi")) {
  die("Error processing '$tmppath$fileroot.tex':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}
//unlink("$tmppath$fileroot.tex");
exec("cd $tmppath;/usr/local/bin/dvipdfmx $fileroot", $output, $return);
//unlink("$tmppath$fileroot.dvi");
if (!is_file("$tmppath$fileroot.pdf")) {
  die("Error processing '$tmppath$fileroot.dvi':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}

// DELIVER PDF CONTENT TO BROWSER
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="songs_'.date('Y-m-d').'.pdf"');
header('Content-Transfer-Encoding: binary');
@readfile("$tmppath$fileroot.pdf");

// DELIVER PDF CONTENT TO BROWSER

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="songs_'.date('Y-m-d').'.pdf"');
header('Content-Transfer-Encoding: binary');
@readfile("$tmppath$fileroot.pdf");

function escape_most($data) {
  $search_array = array('#','$','%','&','~','_');
  $replace_array = array('\\#','\\$','\\%','\\&','\\~{}','\\_','\\^{}');
  return str_replace($search_array, $replace_array, $data);
}
function escape_braces($data) {
    return str_replace('{','\\{',str_replace('}','\\}',$data));
}
function escape_backslash($data) {
    return str_replace('\\','\\textbackslash{}',$data);
}
function escape_all($data) {
    return escape_most(escape_braces($data));
}

function makechordset($chord,$lyric,$romaji=FALSE) {
  $chord = preg_replace('@(/)([^ \]]+)@','\\Bass{$2}',$chord);
  $chord = str_replace('#','\\Sharp{}',$chord);
  $chord = str_replace('b','\\Flat{}',$chord);
  $chord = preg_replace('/([0-9]+)/','\\Sup{$1}',$chord);
  $chord = escape_most($chord);
  $lyric = str_replace(' ','\\ ',escape_braces(escape_most($lyric)));
  return('\\chordset{'.$chord.'}{'.$lyric.'}');
}

?>
