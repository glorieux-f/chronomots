<?php
$datemin = 1550;
$datemax = 2009;

$lines = file("lib/stop.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$stop = array_flip($lines);

$from = 1989;
if (isset($_REQUEST['from']) &&  is_numeric($_REQUEST['from'])) $from = $_REQUEST['from'];
if ($from < $datemin) $from = $datemin;
if ($from > $datemax) $from = $datemax;
$before = $from - 10;
if (isset($_REQUEST['before']) && is_numeric($_REQUEST['before'])) $before = $_REQUEST['before'];
if ($before > $from) $before = $from - 10;
$to = 1989;
if (isset($_REQUEST['to']) && is_numeric($_REQUEST['to'])) $to = $_REQUEST['to'];
if ($to < $from) $to = $from;
if ($to > $datemax) $to = $datemax;

$after = $to + 10;
if (isset($_REQUEST['after']) && is_numeric($_REQUEST['after'])) $after = $_REQUEST['after'];
if ($after < $to) $after = $to + 10;
$pc = 80;
if (isset($_REQUEST['pc']) && is_numeric($_REQUEST['pc'])) $pc = $_REQUEST['pc'];
$pc = abs($pc);


$pdo = new PDO('sqlite:g1gram.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$pdo->exec("pragma synchronous = off;");


?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <link rel="stylesheet" type="text/css" href="g1gram.css"/>
  </head>
  <body>
    <table align="center">
      <thead>
        <form>
          <th style="text-align: left;">
            <input class="year" size="4"
              onclick="this.selectionStart = this.selectionEnd = this.value.length;"
              name="before" value="<?= $before ?>"
            />
          </th>
          <th colspan="2" style="text-align: center">
            <input style="float: left;" class="year" size="4"
              onclick="this.selectionStart = this.selectionEnd = this.value.length;"
              name="from" value="<?= $from ?>"
            />
            <span>
              seuil
              <input class="year"
                size="4" name="pc" value="<?= $pc ?>"
              />%
              <button tabindex="-1" type="submit">▶</button>
            </span>
            <input  style="float: right;" class="year" size="4"
              onclick="this.selectionStart = this.selectionEnd = this.value.length;"
              name="to" value="<?= $to ?>"
            />
          </th>
          <th style="text-align: right;">
            <input class="year" size="4"
              onclick="this.selectionStart = this.selectionEnd = this.value.length;"
              name="after" value="<?= $after ?>"
            />
          </th>
        </form>
      </thead>
<?php
$select = $pdo->prepare("SELECT form, avg(rank) AS ranker FROM lemma WHERE year >= ? AND year <= ? GROUP BY form ORDER BY ranker ASC LIMIT 100000;");
$form = null;
$avg = 0.0;
$past = array();
$select->execute(array($before, $from - 1));
while((list($form, $avg) = $select->fetch(PDO::FETCH_NUM))) {
  $past[$form] = $avg;
}

$present = array();
$select->execute(array($from, $to));
while( (list($form, $avg) = $select->fetch(PDO::FETCH_NUM))) {
  $present[$form] = $avg;
}
$future = array();
$select->execute(array($to+1, $after));
while( (list($form, $avg) = $select->fetch(PDO::FETCH_NUM))) {
  $future[$form] = $avg;
}
$tab = null;
$limit = 100;
 ?>
      <tbody>
        <tr>
          <td style="text-align:left">
<?php
$tab = filter($past, $present, 1 + abs($pc)/100 );
$i = $limit;
foreach ($tab as $form => $data) {
  if($i <= 0) break;
  if($data[1] < 0) $dif = "∞";
  else $dif = round(100 * ($data[1] - $data[0]) / $data[0]);
  echo "<div><b>",$form,"</b> ", $dif,"%</div>";
  $i--;
}
?>
          </td>
          <td style="text-align:right; border-left: solid 1px; padding-right: 1rem;">
<?php
$tab = filter($present, $past, 1 + abs($pc)/100);
$i = $limit;
foreach ($tab as $form => $data) {
  if($i <= 0) break;
  if($data[1] < 0) $dif = "∞";
  else $dif = round(100 * ($data[1] - $data[0]) / $data[0]);
  echo "<div>",$dif,"% <b>", $form,"</b></div>";
  $i--;
}
?>
          </td>
          <td  style="text-align:left">
<?php
$tab = filter($present, $future, 1 + abs($pc)/100);
$i = $limit;
foreach ($tab as $form => $data) {
  if($i <= 0) break;
  if($data[1] < 0) $dif = "∞";
  else $dif = round(100 * ($data[1] - $data[0]) / $data[0]);
  echo "<div><b>",$form,"</b> ",$dif,"%</div>";
  $i--;
}
?>
          </td>
          <td  style="text-align:right; border-left: solid 1px;">
<?php
// search from future, to have absent words, adjust ration to have pc from present
$tab = filter($future, $present, 1 + abs($pc)/100  );
# 100/(100-abs($pc)) ?
$i = $limit;
foreach ($tab as $form => $data) {
  if($i <= 0) break;
  if($data[1] < 0) $dif = "∞";
  else $dif = round(100 * abs($data[1] - $data[0]) / $data[0]);
  echo "<div>", $dif,"% <b>",$form,"</b></div>";
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
