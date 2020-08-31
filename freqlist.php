<?php
$datemax = 2019;
$datemin = 1450;
$from = 2000;
$to = 2019;

if (isset($_REQUEST['from']) &&  is_numeric($_REQUEST['from'])) $from = $_REQUEST['from'];
if ($from < $datemin) $from = $datemin;
if ($from > $datemax) $from = $datemax;

if (isset($_REQUEST['to']) && is_numeric($_REQUEST['to'])) $to = $_REQUEST['to'];
if ($to < $datemin) $to = $datemax;
if ($to > $datemax) $to = $datemax;

$pdo = new PDO('sqlite:lexichrone.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$pdo->exec("pragma synchronous = off;");

$lines = file("lib/stop.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$stop = array_flip($lines);
$nostop = true;

$table = "lemma";

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <link rel="stylesheet" type="text/css" href="lexichrone.css"/>
  </head>
  <body>
     <?php include("header.php") ?>
    <table align="center" class="sortable">
      <colgroup>
        <col align="right"/>
        <col/>
        <col align="right"/>
      </colgroup>
      <caption style="white-space: nowrap;">
        <form>
          <input class="year" size="4"
            name="from" value="<?= $from ?>"
          />
          <input class="year" size="4"
            name="to" value="<?= $to ?>"
          />
          <button tabindex="-1" type="submit">Lancer</button>
        </form>
      </caption>
        <thead>
          <tr>
            <th>rang</th>
            <th>mot</th>
            <th>score</th>
          <th>
        </thead>
        <tbody>
<?php
$select = $pdo->prepare("SELECT form, avg(rank) AS ranker FROM $table WHERE year >= ? AND year <= ? GROUP BY form ORDER BY ranker ASC LIMIT 5000;");
$select->execute(array($from, $to));
$rank = 0;
while((list($form, $score) = $select->fetch(PDO::FETCH_NUM))) {
  $rank++;
  if ($nostop && isset($stop[$form])) continue;
  echo "
<tr>
  <td class='num'>$rank</td>
  <td>$form</td>
  <td class='num'>$score</td>
</tr>";
}
?>
      </tbody>
    </table>
  </body>
</html>
<?php

 ?>
