<?php
define('CFG_FILE', 'data.txt');
define('CFG_SEP', ' => ');

if(!empty($_POST)) {
    $f = fopen(CFG_FILE, 'w');
    foreach($_POST as $k => $v) {
        fwrite($f, $k.CFG_SEP.$v."\n");
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
);

if(file_exists(CFG_FILE)) {
    $file = file(CFG_FILE);
    foreach($file as $ligne) {
        $tmp = explode(CFG_SEP, $ligne);
        $data[$tmp[0]] = $tmp[1];
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
    <input type="submit" value="Envoyer" />
</form>
</body>
</html>