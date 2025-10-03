<?php
$datemin = 1550;
$datemax = 2019;

$lines = file("lib/stop.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$stop = array_flip($lines);

$start1 = 1919;
if (isset($_REQUEST['start1']) &&  is_numeric($_REQUEST['start1'])) $start1 = $_REQUEST['start1'];
if ($start1 < $datemin) $start1 = $datemin;
if ($start1 > $datemax) $start1 = $datemax;

$end1 = 1938;
if (isset($_REQUEST['end1']) && is_numeric($_REQUEST['end1'])) $end1 = $_REQUEST['end1'];
if ($end1 < $start1) $end1 = $start1;
if ($end1 > $datemax) $end1 = $datemax;

$start2 = 1939;
if (isset($_REQUEST['start2']) &&  is_numeric($_REQUEST['start2'])) $start2 = $_REQUEST['start2'];
if ($start2 < $datemin) $start2 = $datemin;
if ($start2 > $datemax) $start2 = $datemax;

$end2 = 1945;
if (isset($_REQUEST['end2']) && is_numeric($_REQUEST['end2'])) $end2 = $_REQUEST['end2'];
if ($end2 < $start2) $end2 = $start2;
if ($end2 > $datemax) $end2 = $datemax;

$pc = 80;
if (isset($_REQUEST['pc']) && is_numeric($_REQUEST['pc'])) $pc = $_REQUEST['pc'];
$pc = abs($pc);

$rankinf = 1000;
if (isset($_REQUEST['rankinf']) && is_numeric($_REQUEST['rankinf'])) $rankinf = $_REQUEST['rankinf'];


$pdo = new PDO('sqlite:lexichrone.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$pdo->exec("pragma synchronous = off;");


?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <link rel="stylesheet" type="text/css" href="lexichrone.css"/>
  </head>
  <body>
     <?php include("header.php") ?>
    <table align="center">
      <form>
        <caption>
           différence des rangs >
          <input class="year" tabindex="-1"
            size="4" name="pc" value="<?= $pc ?>"
          />%
          <button tabindex="-1" type="submit">Lancer</button>
        </caption>
        <thead>
          <tr>
            <th style="text-align: left;">
              <input class="year" size="4"
                name="start1" value="<?= $start1 ?>"
              />
            </th>
            <th style="text-align: right">
              <input class="year" size="4"
                name="end1" value="<?= $end1 ?>"
              />
            </th>
            <th style="text-align: left;">
              <input class="year" size="4"
                name="start2" value="<?= $start2 ?>"
              />
            </th>
            <th style="text-align: right;">
              <input class="year" size="4"
                name="end2" value="<?= $end2 ?>"
              />
            </th>
        </form>
      </thead>
<?php
$select = $pdo->prepare("SELECT form, avg(rank) AS ranker FROM lemma WHERE year >= ? AND year <= ? GROUP BY form ORDER BY ranker ASC LIMIT 100000;");
$form = null;
$avg = 0.0;

$period1 = array();
$select->execute(array($start1, $end1));
while((list($form, $avg) = $select->fetch(PDO::FETCH_NUM))) {
  $period1[$form] = $avg;
}

$period2 = array();
$select->execute(array($start2, $end2));
while((list($form, $avg) = $select->fetch(PDO::FETCH_NUM))) {
  $period2[$form] = $avg;
}


$tab = null;
$limit = 100;
 ?>
      <tbody>
        <tr>
          <td style="text-align:left; vertical-align: top;" colspan="2">
<?php
$tab = filter($period1, $period2, 1 + abs($pc)/100 );
$i = $limit;
foreach ($tab as $form => $data) {
  if($i <= 0) break;
  if($data[1] < 0) $dif = "∞";
  else $dif = round(100 * ($data[1] - $data[0]) / $data[0]);
  echo "<div>",round($data[0]),"# <b>",$form,"</b> ", $dif,"%</div>";
  $i--;
}
?>
          </td>
<?php
/*
          <td style="text-align:center; vertical-align: top;">
$marg = 1;
$ratio = 1 + $marg/100;
$i = $limit;
foreach ($period1 as $form => $rank) {
  if($i <= 0) break;
  if($rank < $rankinf) continue;
  if (!isset($period2[$form])) continue;
  $rank2 = $period2[$form];
  if($rank2 < $rankinf) continue;
  if (abs($rank2 - $rank) / ($rank2 + $rank) > 0.02) continue;
  // if($rank * $ratio >= $ratio) continue;
  echo "<div>",round($rank),"# <b>", $form,"</b> #",round($rank2),"</div>";
  $i--;
}
          </td>
*/
?>
          <td style="text-align:right;  vertical-align: top;" colspan="2">
<?php
$tab = filter($period2, $period1, 1 + abs($pc)/100);
$i = $limit;
foreach ($tab as $form => $data) {
  if($i <= 0) break;
  if($data[1] < 0) $dif = "∞";
  else $dif = round(100 * ($data[1] - $data[0]) / $data[0]);
  echo "<div>",$dif,"% <b>", $form,"</b> #",round($data[0]),"</div>";
  $i--;
}
?>
          </td>
        </tr>
      </tbody>
    </table>
  </body>
</html>
<?php
function filter(&$src, &$ref, $ratio)
{
  $out = array();
  foreach ($src as $form => $rank) {
    if (!isset($ref[$form])) {
      $out[$form] = array($rank, -1);
      continue;
    }
    $rank2 = $ref[$form];
    if($rank2 >= $rank * $ratio) $out[$form] = array($rank, $rank2);
  }
  return $out;
}



 ?>
