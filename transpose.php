<?php
$notearray[] = array("C","Db","D","Eb","E","F","F#","G","Ab","A","Bb","B");
$notearray[] = array("Db","D","Eb","E","F","Gb","G","Ab","A","Bb","B","C");
$notearray[] = array("D","D#","E","F","F#","G","G#","A","A#","B","C","C#");
$notearray[] = array("Eb","E","F","Gb","G","Ab","A","Bb","B","C","Db","D");
$notearray[] = array("E","F","F#","G","G#","A","A#","B","C","C#","D","D#");
$notearray[] = array("F","Gb","G","Ab","A","Bb","B","C","Db","D","Eb","E");
$notearray[] = array("F#","G","G#","A","A#","B","C","C#","D","D#","E","E#");
$notearray[] = array("G","G#","A","A#","B","C","C#","D","D#","E","F","F#");
$notearray[] = array("Ab","A","Bb","B","C","Db","D","Eb","E","F","Gb","G");
$notearray[] = array("A","A#","B","C","C#","D","D#","E","F","F#","G","G#");
$notearray[] = array("Bb","B","C","Db","D","Eb","E","F","Gb","G","Ab","A");
$notearray[] = array("B","C","C#","D","D#","E","F","F#","G","G#","A","A#");
$keyarray = array("C"=>0,"Db"=>1,"D"=>2,"Eb"=>3,"E"=>4,"F"=>5,"F#"=>6,"G"=>7,"Ab"=>8,"A"=>9,"Bb"=>10,"B"=>11,
"Am"=>0,"Bbm"=>1,"Bm"=>2,"Cm"=>3,"C#m"=>4,"Dm"=>5,"D#m"=>6,"Em"=>7,"Fm"=>8,"F#m"=>9,"Gm"=>10,"G#m"=>11);

/* function transpose() transposes a string of chords and/or bass notes */
/* $input cannot have any letters or numbers unrelated to the chord or note, but punctuation is allowed */
/* $keystr can only be A-G followed by possibly # or b and possibly "m" for minor */
/* $offset is an integer, normally from -6 to +6 */
function transpose($input,$key,$offset) {
  global $notearray, $keyarray;
  if ($offset == 0) return $input;
  $ch = str_split($input);
  $keynum = $tempkeynum = $keyarray[$key];  //integer value for key
  /* Bass notes should be based on the key signature of the chord, not the song, if possible - $tempkeynum is for that. */
  $output = "";
  $expectbassnote = FALSE;
  $cnt = count($ch);
  for ($i=0; $i<$cnt; $i++) {
    if (strpos("ABCDEFG",$ch[$i])!==FALSE) {  //new note or chord
      $note = $ch[$i];
      if (($i+1)<$cnt && strpos("#b",$ch[$i+1])!==FALSE) {  //sharp or flat
        $note .= $ch[++$i];
      }
      if (!$expectbassnote) {  //it's a new chord
        $tempkey = $note;
        if (($i+1<$cnt && $ch[$i+1]=="m") && ($i+2==$cnt || $ch[$i+2]!=="a")) {  //minor
          $tempkey .= $ch[$i+1];
        }
        $tempkeynum = (array_key_exists($notearray[($tempkeynum+$offset)%12][$notenum],$keyarray) ? $keyarray[$tempkey] : $keynum);
        $notenum = array_search($note, $notearray[$keynum]);
        $output .= ($notenum!==FALSE ? $notearray[($keynum+$offset)%12][$notenum] : "??");
      } else {  //additional note (bass, etc.)
        $notenum = array_search($note, $notearray[$tempkeynum]);
        $output .= ($notenum!==FALSE ? $notearray[($tempkeynum+$offset)%12][$notenum] : "??");
        $expectbassnote = FALSE;  //go back to mode of assuming a letter is a new chord
      }
    } else {
      $output .= $ch[$i];
      if ($ch[$i]=="/") $expectbassnote = TRUE;
    }
  }
  return $output;
}
?>
