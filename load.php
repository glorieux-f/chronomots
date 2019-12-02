<?php
mb_internal_encoding ("UTF-8");
// G1gram::count();
// G1gram::lexique();
G1gram::connect("g1gram.sqlite");
// G1gram::global();
// G1gram::walk();
// G1gram::ranks();
G1gram::ranks();


// G1gram::ranks();

class G1gram {
  /** lien à la base de donnée */
  static $pdo;
  /** Nombre de mots par an */
  static $years = array();
  /** dicolecte */
  static $dic;

  /**
   * Charger les stats globales
   */
  static public function global()
  {
    self::$pdo->beginTransaction();
    $insert = self::$pdo->prepare(
      "INSERT INTO year (id, count) VALUES (?, ?)"
    );
    $id = $year = 0;
    $insert->bindParam(1, $id, PDO::PARAM_INT);
    $insert->bindParam(2, $count, PDO::PARAM_INT);
    $handle = fopen("data/googlebooks-fre-all-totalcounts-20120701.txt", "r");
    fgets($handle, 1000); // skip first line
    while (($line = fgets($handle, 4096)) !== FALSE) {
      list($id, $count) = explode(",", $line);
      // $insert->execute();
      $years[$id] = $count;
    }
    self::$pdo->commit();
    self::$years = $years;
  }

  /**
   * Charger lexique en mémoire
   */
  static public function lexique()
  {
    //fgetcsv: 3,053 s.
    // ne pas utiliser scanf, confond les espaces et les tabulations, La devient Rochelle
    // scanf:  0.238 s.
    // fgets + explode: 0,325 s.
    $start = microtime(true);
    // echo "mem=",memory_get_usage(),"\n";
    $i = 0;
    $dic = array();
    // charger d'abord lexique Alix, plus fiable sur les hautes fréquences
    $id = $fid = $flexion = $lemma = $line = null;
    $handle = fopen("datalecte/word.csv", "r");
    fgets($handle);// passer a première ligne
    while ($line = fgets($handle)) {
      list($flexion, $cat, $lemma) = explode(";", $line);
      $flexion = str_replace(array("œ", "Œ", "æ", "Æ"), array("oe", "Oe", "ae", "Ae"), $flexion);
      if (!$lemma) $lemma = $flexion;
      $lemma = str_replace(array("œ", "Œ", "æ", "Æ"), array("oe", "Oe", "ae", "Ae"), $lemma);
      if (isset($dic[$flexion])) continue;
      $dic[$flexion] = $lemma;
    }
    fclose($handle);


    $handle = fopen("datalecte/lexique.txt", "r");
    while ($line = fgets($handle)) {
      list($id, $fid, $flexion, $lemma) = explode("\t", $line);
      $flexion = str_replace(array("œ", "Œ", "æ", "Æ"), array("oe", "Oe", "ae", "Ae"), $flexion);
      $lemma = str_replace(array("œ", "Œ", "æ", "Æ"), array("oe", "Oe", "ae", "Ae"), $lemma);
      if (isset($dic[$flexion])) continue;
      $dic[$flexion] = $lemma;
      // $rank[$flexion] = $i;
      // echo $i,". ",$flexion," - ",$lemma,"\n";
    }
    fclose($handle);
    self::$dic = $dic;
    fwrite(STDERR, (microtime(true) - $start)." s.\n");
    /*
    $i = 1000;
    foreach ($dic as $key=>$value) {
      echo $key, " ", $value, "\n";
      if(--$i <= 0) break;
    }
    */
  }

  static function lemma()
  {

    return;
    $sql = <<<AAA
      INSERT INTO lemma (year, form, count)
      SELECT year, lemma, SUM(count) as sum FROM word GROUP BY year, lemma ORDER BY year, sum DESC;
    AAA;
    $pdo = self::$pdo;
    $dic = self::$dic;
    $pdo->beginTransaction();
    $id = 0;
    $form = $lemma = null;
    $select = $pdo->prepare(
      "SELECT id, form FROM word;"
    );
    $update = $pdo->prepare(
      "UPDATE word SET lemma = ? WHERE id = ?;"
    );
    $update->bindParam(1, $lemma, PDO::PARAM_STR);
    $update->bindParam(2, $id, PDO::PARAM_INT);
    $select->execute();
    while(list($id, $form) = $select->fetch()) {
      $lemma = $dic[$form];
      if (!$lemma) echo $form,"\n";
      $update->execute();
    }
    $pdo->commit();
  }


  static public function count()
  {
    $glob = dirname(__FILE__).'/data/googlebooks-fre-all-1gram-20120701-*.gz';
    $dic = array();
    foreach(glob($glob) as $srcFile) {
      $start = microtime(true);
      fwrite(STDERR, $srcFile);
      $handle = fopen("compress.zlib://".$srcFile, "r");
      while (($line = fgets($handle, 4096)) !== FALSE) {
        list($form, $year, $count) = explode("\t", $line);
        if (isset($dic[$form])) $dic[$form] += $count;
        else $dic[$form] = $count;
      }
      fwrite(STDERR, " ".(microtime(true) - $start)."\n");
    }
    echo "wc=".(count($dic)); // 9 663 037
    arsort($dic);
    print_r(array_slice($dic, 0, 1000));
  }

  static public function walk()
  {
    // gzopen 112s.
    // gzfile 111s.
    // fopen("compress.zlib://") 104s.
    // fopen+getcsv timeout 179 s.
    $glob = dirname(__FILE__).'/data/googlebooks-fre-all-1gram-20120701-*.gz';
    $years = self::$years;
    $dic = self::$dic;
    self::$pdo->beginTransaction();
    $word = self::$pdo->prepare(
      "INSERT INTO word (year, form, count, lemma) VALUES (?, ?, ?, ?)"
    );
    $more = self::$pdo->prepare(
      "INSERT INTO more (year, form, count) VALUES (?, ?, ?)"
    );
    $form = $allograph = $lemma = $year = $count = $line = null;
    $word->bindParam(1, $year, PDO::PARAM_INT);
    $word->bindParam(2, $form, PDO::PARAM_STR);
    $word->bindParam(3, $count, PDO::PARAM_INT);
    $word->bindParam(4, $lemma, PDO::PARAM_STR);
    $more->bindParam(1, $year, PDO::PARAM_INT);
    $more->bindParam(2, $allograph, PDO::PARAM_STR);
    $more->bindParam(3, $count, PDO::PARAM_INT);
    $noise = array();
    foreach(glob($glob) as $srcFile) {
      $start = microtime(true);
      fwrite(STDERR, $srcFile);
      $handle = fopen("compress.zlib://".$srcFile, "r");
      if(!$handle) exit("pb avec ".$srcFile);
      while (($line = fgets($handle, 4096)) !== FALSE) {
        list($form, $year, $count) = explode("\t", $line);
        if ($form[0] == '.') continue;
        if (preg_match("@[\[\]\-\"\\/*%§^<>_0123456789',{}~();:|«»“”?!•=+►♦□■°]@u", $form)) continue;
        // oeuvre et œuvre
        if (isset($dic[$form])) {
          $lemma = $dic[$form];
          $word->execute();
          continue;
        }
        $allograph = mb_strtolower(str_replace(array("œ", "Œ", "æ", "Æ"), array("oe", "Oe", "ae", "Ae"), $form));
        if (isset($dic[$allograph])) {
          $more->execute();
          continue;
        }
        if ($allograph[0] == 'e') {
          $allograph = "é".mb_substr($allograph, 1);
          if (isset($dic[$allograph])) {
            $more->execute();
            continue;
          }
          $allograph = "É".mb_substr($allograph, 1);
          if (isset($dic[$allograph])) {
            $more->execute();
            continue;
          }
        }
        if (isset($noise[$form])) $noise[$form] += $count;
        else $noise[$form] = $count;
      }
      fwrite(STDERR, " ".(microtime(true) - $start)."\n");
    }
    self::$pdo->commit();
    arsort($noise);
    print_r(array_slice($noise, 0, 1000));
  }
  /**
   * Connexion à la base de données
   */
  static function connect($sqlfile, $create=false)
  {
    $dsn = "sqlite:".$sqlfile;
    if($create && file_exists($sqlfile)) unlink($sqlfile);
    // create database
    if (!file_exists($sqlfile)) { // if base do no exists, create it
      echo "Base, création ".$sqlfile."\n";
      if (!file_exists($dir = dirname($sqlfile))) {
        mkdir($dir, 0775, true);
        @chmod($dir, 0775);  // let @, if www-data is not owner but allowed to write
      }
      self::$pdo = new PDO($dsn);
      self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      @chmod($sqlfile, 0775);
      self::$pdo->exec(file_get_contents(dirname(__FILE__)."/G1gram.sql"));
      return;
    }
    else {
      // absolute path needed ?
      self::$pdo = new PDO($dsn);
      self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
  }

  /**
   *
   */
  static function more()
  {
    return; // done
    $pdo = self::$pdo;
    $pdo->beginTransaction();
    $count = $year = 0;
    $form = null;
    $select = $pdo->prepare(
      "SELECT count, form, year FROM more;"
    );
    $update = $pdo->prepare(
      "UPDATE word SET count = count + ? WHERE form = ? AND year = ?;"
    );
    $update->bindParam(1, $count, PDO::PARAM_INT);
    $update->bindParam(2, $form, PDO::PARAM_STR);
    $update->bindParam(3, $year, PDO::PARAM_INT);
    $select->execute();
    while(list($count, $form, $year) = $select->fetch()) {
      $update->execute();
    }
    self::$pdo->commit();
  }


  static function ranks()
  {
    $pdo = self::$pdo;
    // same count, same rank
    self::$pdo->beginTransaction();
    $id = $year = $lastyear = $count = 0;
    $select = $pdo->prepare(
      "SELECT id, year, count FROM lemma ORDER BY year, count DESC;"
    );
    $update = $pdo->prepare(
      "UPDATE lemma SET rank = ? WHERE id = ?;"
    );
    $update->bindParam(1, $rank, PDO::PARAM_INT);
    $update->bindParam(2, $id, PDO::PARAM_INT);
    $select->execute();
    $i = 0;
    while(list($id, $year, $count) = $select->fetch()) {
      if ($lastyear != $year) {
        $i=0;
        echo $year, "\n";
      }
      $i++;
      $lastyear = $year;
      if ($count != $lastcount) $rank = $i;
      $lastcount = $count;
      $update->execute();
    }
    self::$pdo->commit();

    return;
    // Ces commandes sont très longues et ont été appliquée à la main dans la console SQLite
    $sql = <<<AAA
      -- trop long
      -- UPDATE word SET rank = (SELECT COUNT(*) FROM word AS w2 WHERE w2.year = word.year AND w2.count >= word.count);



      CREATE TABLE ranks(
        year        INTEGER,
        wordid      INTEGER,
        rank        INTEGER,
        id          INTEGER, -- rowid auto
        PRIMARY KEY(id ASC)
      );

      INSERT INTO ranks (year, wordid)
        SELECT year, id FROM word
        ORDER BY year, count DESC
      ;
      CREATE UNIQUE INDEX ranks_wordid ON ranks(wordid);
    AAA;

    self::$pdo->beginTransaction();
    $select = $pdo->prepare(
      "SELECT year, id FROM ranks"
    );
    $update = $pdo->prepare(
      "UPDATE ranks SET rank = 1 + id - ? WHERE id = ? "
    );
    $year = $yearid = $id = $lastyear = 0;
    $update->bindParam(1, $yearid, PDO::PARAM_INT);
    $update->bindParam(2, $id, PDO::PARAM_INT);
    $select->execute();
    while(list($year, $id) = $select->fetch()) {
      if ($lastyear != $year) {
        $yearid = $id;
        $lastyear = $year;
        echo $year,"\n";
      }
      $update->execute();
    }
    self::$pdo->commit();
    $sql = <<<AAA
    UPDATE word SET rank=(SELECT rank FROM ranks WHERE wordid=word.id);
    AAA;

  }
}



 ?>
