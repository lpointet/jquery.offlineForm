<?php
define('CFG_FILE', 'data.txt');
define('CFG_FILE_DOUBLE', 'data_double.txt');
define('CFG_SEP', ' => ');

if(!empty($_POST)) {
    $filename = !empty($_POST['double'])?CFG_FILE_DOUBLE:CFG_FILE;
    $f = fopen($filename, 'w');
    foreach($_POST as $k => $v) {
        if(is_array($v))
            $v = 'ARRAY:'.implode(',',$v);
        fwrite($f, $k.CFG_SEP.str_replace("\n", '', nl2br(str_replace("\r", '', $v)))."\n");
    }
    fclose($f);

    $manifest = file_get_contents('manifest.appcache');
    preg_match('/# Version ([0-9]+)/', $manifest, $match);
    file_put_contents('manifest.appcache', str_replace('# Version '.$match[1], '# Version '.++$match[1], $manifest));
}

if(!empty($_FILES)) {
    foreach($_FILES as $k => $v) {
        move_uploaded_file($_FILES[$k]['tmp_name'], 'upload_dir/'.$v['name']);
        $content = file_get_contents('upload_dir/'.$v['name']);
        if($new = strstr($content, 'base64,')) {
            file_put_contents('upload_dir/'.$v['name'], base64_decode(substr($new, 7)));
        }
    }
}

$data = array(
    'input_text' => '',
    'input_email' => '',
    'input_checkbox' => '',
    'input_radiobox' => '',
    'textarea' => '',
    'select' => '',
    'select_multiple' => array(),
);
$data_double = array(
    'input_text' => '',
    'input_email' => '',
    'input_checkbox' => '',
    'input_radiobox' => '',
    'textarea' => '',
    'select' => '',
    'select_multiple' => array(),
);

if(file_exists(CFG_FILE)) {
    $file = file(CFG_FILE);
    foreach($file as $ligne) {
        $ligne = trim($ligne);
        $tmp = explode(CFG_SEP, $ligne);
        $tmp[1] = preg_replace('/<br(\s)*(\/)?>/', "\n", $tmp[1]);
        $r = strpos($tmp[1], 'ARRAY:');
        if(!$r && $r !== FALSE)
            $tmp[1] = explode(',', str_replace('ARRAY:', '', $tmp[1]));
        $data[$tmp[0]] = $tmp[1];
    }
}

if(file_exists(CFG_FILE_DOUBLE)) {
    $file = file(CFG_FILE_DOUBLE);
    foreach($file as $ligne) {
        $ligne = trim($ligne);
        $tmp = explode(CFG_SEP, $ligne);
        $tmp[1] = preg_replace('/<br(\s)*(\/)?>/', "\n", $tmp[1]);
        $r = strpos($tmp[1], 'ARRAY:');
        if(!$r && $r !== FALSE)
            $tmp[1] = explode(',', str_replace('ARRAY:', '', $tmp[1]));
        $data_double[$tmp[0]] = $tmp[1];
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
    $.offlineForm.defaultOptions = {
        dataSubmittedEvent: 'dataSubmitted',
        offlineSubmitEvent: 'formValidated',
        fileTooBigEvent: 'fileTooBig',
        displayUploadedFilesEvent: 'displayUploadedFiles',
        fileDeletedEvent: 'fileDeleted'
    };
    $('form').bind('dataSubmitted', function(e) {
        webappCache.update();
        alert('Data submitted !');
    }).bind('formValidated', function(e) {
        alert('Data stored !');
    }).bind('fileTooBig', function(e, filename) {
        alert(filename + "is too big wooooo!!!!");
    }).bind('displayUploadedFiles', function(e, files) {
        var this_form = $(this);
        $.each(files, function(i, v) {
            this_form.find('[name='+i+']').hide();
            for(var j = 0, l = v.length; j < l; j++) {
                if(v[j].type.match('image.*')) {
                    var div = $('<div><img src="data:'+v[j].type+';base64,'+$.offlineForm.base64.encode(v[j].value)+'" alt="test"/></div>');
                    div.append($('<a data-inputname="'+i+'" data-index="'+j+'" href="#">Suppr</a>').click(deleteFile));
                    this_form.append(div);
                }
            }
        });
    }).bind('fileDeleted', function(e, inputName, index) {
        var this_form = $(this);
        this_form.find('a[data-inputname='+inputName+'][data-index='+index+']').closest('div').remove();
        this_form.find('[name='+inputName+']').show();
    }).offlineForm();
    webappCache.update();

    function deleteFile(e) {
        var a = $(this), inputName = a.data('inputname'), index = a.data('index');
        e.preventDefault();
        a.trigger('deleteFile', [inputName, index]);
    }

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
    <select name="select_multiple[]" multiple><option value="1" <?php echo in_array(1, $data['select_multiple'])?'selected':''; ?>>Option 1</option><option value="2" <?php echo in_array(2, $data['select_multiple'])?'selected':''; ?>>Option 2</option></select><br/>
    <input type="submit" value="Envoyer" />
</form>
<form action="" method="post" id="form_test_double" enctype="multipart/form-data">
    <input type="text" name="input_text" value="<?php echo $data_double['input_text']; ?>" /><br/>
    <input type="email" name="input_email" value="<?php echo $data_double['input_email']; ?>" /><br/>
    <input type="checkbox" name="input_checkbox" value="1" <?php echo $data_double['input_checkbox']?'checked':''; ?>/><br/>
    <input type="radio" name="input_radiobox" value="1" <?php echo $data_double['input_radiobox'] == 1?'checked':''; ?>/><input type="radio" name="input_radiobox" value="2" <?php echo $data_double['input_radiobox'] == 2?'checked':''; ?>/><input type="radio" name="input_radiobox" value="3" <?php echo $data_double['input_radiobox'] == 3?'checked':''; ?>/><br/>
    <textarea name="textarea"><?php echo $data_double['textarea']; ?></textarea><br/>
    <select name="select"><option value="">Select</option><option value="1" <?php echo $data_double['select'] == 1?'selected':''; ?>>Option 1</option><option value="2" <?php echo $data_double['select'] == 2?'selected':''; ?>>Option 2</option></select><br/>
    <select name="select_multiple[]" multiple><option value="1" <?php echo in_array(1, $data_double['select_multiple'])?'selected':''; ?>>Option 1</option><option value="2" <?php echo in_array(2, $data_double['select_multiple'])?'selected':''; ?>>Option 2</option></select><br/>
    <input type="file" name="input_file" /><br/>
    <input type="hidden" name="double" value="1" />
    <input type="submit" value="Envoyer" />
</form>
</body>
</html>