<?php
mb_internal_encoding ("UTF-8");
Lexique::init();
Lexique::words();
/*
 * Boucler sur un lexique
 * Tester si la déscaccentuation est complète
 *
 */

class Lexique {
  static $accents;

  static function init()
  {
    self::$accents = include("lib/accents.php");
  }

  static public function words()
  {
    $dic = array();
    $tr = self::$accents;
    // charger d'abord lexique Alix, plus fiable sur les hautes fréquences
    $id = $fid = $flexion = $lemma = $line = null;
    $handle = fopen("datalecte/lexique.txt", "r");
    fgets($handle);// passer a première ligne
    while ($line = fgets($handle)) {
      /*
      list($flexion, $cat, $lemma) = explode(";", $line);
      if (count(explode(";", $line))< 3)  {
        echo "ERROR ?", $line, "\n";
      }
      */
      list($id, $fid, $flexion, $lemma) = explode("\t", $line);
      $ascii = strtr($lemma, $tr);
      if(isset($dic[$ascii])) {
        $last = $dic[$ascii];
        if (!$last) continue;
        if ($last == $lemma) continue;
        echo $ascii, " ", $last, " ", $lemma, "\n";
      }
      else $dic[$ascii] = $lemma;
    }
    fclose($handle);

  }
}


 ?>
