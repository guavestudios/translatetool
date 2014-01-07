<h1>Import CSV</h1>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="csv"><br>
    <input type="submit" value="Upload">
</form>
<?php if($imported and !empty($conflicts)): ?>
    <br><span style="color:red">Beim importieren wurden phrasen gefunden die nicht in der Datenbank existieren. Import abgebrochen.</span><br>
    <pre><?php var_dump($conflicts); ?></pre>
<?php elseif($imported): ?>
    <br><span style="color:green">Erfolgreich importiert.</span>
<?php endif; ?>
<br>
<h1>Format</h1>
<h3>Table</h3>
<table width="50%" border="1">
    <tr>
        <td>key</td>
        <?php foreach(config::get('languages') as $l): ?>
            <td><?= $l ?> (optional)</td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <td>dot.delimited.key</td>
        <?php foreach(config::get('languages') as $l): ?>
            <td><?= strtoupper($l) ?> string</td>
        <?php endforeach; ?>
    </tr>
</table>
<h3>Plain Text</h3>
<pre>"key"<?php foreach(config::get('languages') as $l): ?>;"<?= $l ?> (optional)"<?php endforeach; ?>

"dot.delimited.key"<?php foreach(config::get('languages') as $l): ?>;"<?= strtoupper($l) ?> string"<?php endforeach; ?></pre>