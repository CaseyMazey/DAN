<?php
session_start();
include('db.php');

if (!isset($_GET['id'])) die("Bericht-ID fehlt");
$bericht_id = intval($_GET['id']);

// Bericht abrufen
$bericht_result = $conn->query("
    SELECT wb.*, u.vorname, u.nachname, u.ausbildungsjahr, u.abteilung
    FROM wochenberichte wb
    JOIN users u ON wb.azubi_id = u.id
    WHERE wb.id = $bericht_id
");
if ($bericht_result->num_rows == 0) die("Bericht nicht gefunden");
$bericht = $bericht_result->fetch_assoc();

// Signatur speichern
if (isset($_POST['signieren']) && !empty($_POST['signatur'])) {
    $img = $_POST['signatur'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = base64_decode($img);
    $stmt = $conn->prepare("UPDATE wochenberichte SET signiert = 1, signiert_am = NOW(), signatur_img = ? WHERE id = ?");
    $stmt->bind_param("bi", $img, $bericht_id);
    $stmt->send_long_data(0, $img);
    $stmt->execute();
    $stmt->close();
    echo "<script>location.href='".$_SERVER['REQUEST_URI']."';</script>";
}

// Aufgaben abrufen
$aufgaben_result = $conn->query("SELECT * FROM aufgaben WHERE wochenbericht_id = $bericht_id");

// Aufgaben nach Kategorie trennen
$aufgaben_betrieb = [];
$aufgaben_berufsschule = [];
while ($row = $aufgaben_result->fetch_assoc()) {
    if ($row['kategorie'] == 'Betrieb') $aufgaben_betrieb[] = $row;
    else $aufgaben_berufsschule[] = $row;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Wochenbericht IHK</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial, sans-serif; background:#f8f9fa; padding:20px; }
h2 { color:#0d6efd; margin-bottom:20px; }
.table { margin-bottom:30px; }
.signature { border-top:1px solid #000; width:300px; text-align:center; padding-top:5px; margin-right:50px; display:inline-block; }
canvas { border:1px solid #000; }
</style>
</head>
<body>

<h2>Ausbildungsnachweis (wöchentlich)</h2>

<p><strong>Name des/der Auszubildenden:</strong> <?= htmlspecialchars($bericht['vorname'].' '.$bericht['nachname']) ?><br>
<strong>Ausbildungsjahr:</strong> <?= htmlspecialchars($bericht['ausbildungsjahr']) ?> 
<?php if($bericht['abteilung']): ?> | <strong>Abteilung:</strong> <?= htmlspecialchars($bericht['abteilung']) ?><?php endif; ?><br>
<strong>Ausbildungswoche vom:</strong> <?= $bericht['startdatum'] ?> bis <?= $bericht['enddatum'] ?></p>

<h4>Betriebliche Tätigkeiten</h4>
<table class="table table-bordered">
<tr><th>Tätigkeit</th><th>Stunden</th></tr>
<?php foreach($aufgaben_betrieb as $aufg): ?>
<tr>
<td><?= htmlspecialchars($aufg['titel']) ?><?= $aufg['beschreibung'] ? ' â€“ '.htmlspecialchars($aufg['beschreibung']) : '' ?></td>
<td></td>
</tr>
<?php endforeach; ?>
</table>

<h4>Unterweisungen / Schulungen</h4>
<table class="table table-bordered">
<tr><th>Thema</th><th>Stunden</th></tr>
<tr><td>â€“</td><td></td></tr>
</table>

<h4>Themen des Berufsschulunterrichts</h4>
<table class="table table-bordered">
<tr><th>Thema</th><th>Stunden</th></tr>
<?php foreach($aufgaben_berufsschule as $aufg): ?>
<tr>
<td><?= htmlspecialchars($aufg['titel']) ?><?= $aufg['beschreibung'] ? ' â€“ '.htmlspecialchars($aufg['beschreibung']) : '' ?></td>
<td></td>
</tr>
<?php endforeach; ?>
</table>

<h4>Unterschriften</h4>
<?php if(!$bericht['signiert']): ?>
<form method="post">
<canvas id="canvas" width="400" height="150"></canvas><br>
<button type="button" id="clearBtn" class="btn btn-secondary mt-2">Zurücksetzen</button>
<input type="hidden" name="signatur" id="signaturInput">
<button type="submit" name="signieren" class="btn btn-primary mt-2">Signieren</button>
</form>

<script>
var canvas = document.getElementById("canvas");
var ctx = canvas.getContext("2d");
var drawing = false;

canvas.addEventListener("mousedown", function(e){ drawing=true; ctx.moveTo(e.offsetX, e.offsetY); });
canvas.addEventListener("mouseup", function(){ drawing=false; ctx.beginPath(); });
canvas.addEventListener("mousemove", function(e){ if(drawing){ ctx.lineTo(e.offsetX,e.offsetY); ctx.stroke(); } });

document.getElementById("clearBtn").onclick = function(){ ctx.clearRect(0,0,canvas.width,canvas.height); }

document.querySelector("form").onsubmit = function(){
    document.getElementById("signaturInput").value = canvas.toDataURL("image/png");
}
</script>
<?php else: ?>
    <div class="signature">Datum, Unterschrift Auszubildende/r</div>
    <div class="signature">Datum, Unterschrift Ausbildende/r</div>
    <?php
    if ($bericht['signatur_img']) {
        $img_data = base64_encode($bericht['signatur_img']);
        echo '<p>Signatur:</p><img src="data:image/png;base64,'.$img_data.'" width="400" height="150">';
    }
    ?>
<?php endif; ?>

</body>
</ht


