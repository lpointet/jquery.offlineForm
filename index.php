<?php
define('CFG_FILE', 'data.txt');
define('CFG_SEP', ' => ');

if(!empty($_POST)) {
    $f = fopen(CFG_FILE, 'w');
    foreach($_POST as $k => $v) {
        fwrite($f, $k.CFG_SEP.str_replace("\n", '', nl2br(str_replace("\r", '', $v)))."\n");
    }
    fclose($f);
    
    $manifest = file_get_contents('manifest.appcache');
    preg_match('/# Version ([0-9]+)/', $manifest, $match);
    file_put_contents('manifest.appcache', str_replace('# Version '.$match[1], '# Version '.++$match[1], $manifest));
}

$data = array(
    'input_text' => '',
    'input_email' => '',
    'input_checkbox' => '',
    'input_radiobox' => '',
    'textarea' => '',
    'select' => '',
);

if(file_exists(CFG_FILE)) {
    $file = file(CFG_FILE);
    foreach($file as $ligne) {
        $tmp = explode(CFG_SEP, $ligne);
        $data[$tmp[0]] = preg_replace('/<br(\s)*(\/)?>/', "\n", $tmp[1]);
    }
}

?>
<!DOCTYPE html>
<html manifest="manifest.appcache">
<head>
<script src="jquery.js"></script>
<script src="jquery.offlineform.js"></script>
<script>
$(function() {
    var webappCache = window.applicationCache, body = $('body');
    $('form').bind('dataSubmitted', function(e) {
        webappCache.update();
        alert('Data submitted !');
    }).bind('formValidated', function(e) {
        alert('Data stored !');
    }).offlineForm({
        dataSubmittedEvent: 'dataSubmitted',
        offlineSubmitEvent: 'formValidated'
    });
    webappCache.update();
    
    function updateCache() {
        body.find('b').remove();
        webappCache.swapCache();
    }
    
    function waitForComplete() {
        body.append($('<b>Wait...</b>'));
    }
    
    webappCache.addEventListener("updateready", updateCache, false);
    webappCache.addEventListener("downloading", waitForComplete, false);
});
</script>
</head>
<body>
<form action="" method="post" id="form_test">
    <input type="text" name="input_text" value="<?php echo $data['input_text']; ?>" /><br/>
    <input type="email" name="input_email" value="<?php echo $data['input_email']; ?>" /><br/>
    <input type="checkbox" name="input_checkbox" value="1" <?php echo $data['input_checkbox']?'checked':''; ?>/><br/>
    <input type="radio" name="input_radiobox" value="1" <?php echo $data['input_radiobox'] == 1?'checked':''; ?>/><input type="radio" name="input_radiobox" value="2" <?php echo $data['input_radiobox'] == 2?'checked':''; ?>/><input type="radio" name="input_radiobox" value="3" <?php echo $data['input_radiobox'] == 3?'checked':''; ?>/><br/>
    <textarea name="textarea"><?php echo $data['textarea']; ?></textarea><br/>
    <select name="select"><option value="">Select</option><option value="1" <?php echo $data['select'] == 1?'selected':''; ?>>Option 1</option><option value="2" <?php echo $data['select'] == 2?'selected':''; ?>>Option 2</option></select><br/>
    <input type="submit" value="Envoyer" />
</form>
</body>
</html>