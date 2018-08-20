<?php

/*
$TITLE = 'Titre';
$PATH = '../images/';
$PASSWORD = 'password';

require_once('../galerie.php');
 */

session_start();
if (isset($_POST['passe']) && $_POST['passe'] === $PASSWORD) {
    $_SESSION[$TITLE] = true;
}

$isLogged = false;
if (isset($_SESSION[$TITLE]) && $_SESSION[$TITLE] === true) {
    $isLogged = true;
}

$folder = 'root';
$get = '';
$message = '';
$success = '';

if ($isLogged && isset($_GET['folder'])) {
    $folder = $_GET['folder'];
    if (
        strpos($folder, 'deleted__') !== false || 
        strpos($folder, '.') !== false || 
        strpos($folder, '..') !== false || 
        strpos($folder, '/') !== false || 
        strpos($folder, '~') !== false ||
        !file_exists($PATH.$folder)
    ) {
        $folder = 'root';
    } else {
        $folder = preg_replace('/[^a-zA-Z0-9éèê\ \-_àçù@]+/', '', $folder);
    }
}

if ($isLogged && $folder !== 'root' && isset($_GET['get'])) {
    $get = $_GET['get'];
    if (
        strpos($get, '..') !== false ||
        strpos($get, '/') !== false ||
        strpos($get, '~') !== false ||
        strpos($get, '"') !== false ||
        strpos($get, '#') !== false ||
        strpos($get, '?') !== false ||
        strpos($get, '<') !== false ||
        strpos($get, '>') !== false ||
        !file_exists($PATH.$folder.'/'.$get)
    ) {
        $get = '';
    } else {
        $get = preg_replace('/[^a-zA-Z0-9éèê\ \-_àçù@\.]+/', '', trim($get));
    }
}

$isPano = false;
if ($isLogged && $folder !== 'root' && $get !== '' && isset($_GET['pano'])) {
    $filename = $PATH.$folder.'/'.$get;
    $exif = exif_read_data($filename);
    if ($exif['Make'] === 'MADV') {
        $isPano = true;
    }
}

$isResize = false;
if ($isLogged && $folder !== 'root' && $get !== '' && isset($_GET['resize'])) {
    $isResize = true;
}

if ($isLogged && $folder == 'root' && isset($_POST['submit']) && isset($_POST['folder'])) {
    $newFolder = trim($_POST['folder']);
    if (
        $newFolder != '' &&
        strpos($newFolder, 'deleted__') === false &&
        strpos($newFolder, '.') === false &&
        strpos($newFolder, '..') === false &&
        strpos($newFolder, '/') === false &&
        strpos($newFolder, '~') === false &&
        strpos($newFolder, '"') === false &&
        strpos($newFolder, '#') === false &&
        strpos($newFolder, '?') === false &&
        strpos($newFolder, '<') === false &&
        strpos($newFolder, '>') === false &&
        !file_exists($PATH.$newFolder)
    ) {
        $newFolder = preg_replace('/[^a-zA-Z0-9éèê\ \-_àçù@]+/', '', $newFolder);
        $success .= 'Nouveau dossier : '.$newFolder.'<br/>';
        mkdir($PATH.$newFolder);
    } else {
        $message .= 'Impossible de créer le nouveau dossier<br/>';
    }
}

if ($isLogged && $folder != 'root' && isset($_POST['submit']) && 
    isset($_FILES['files']) && count($_FILES['files']['name']) > 0
) {
    for($i=0;$i<count($_FILES['files']['name']);$i++) {
        $name = $_FILES['files']['name'][$i];
        $tmpName = $_FILES['files']['tmp_name'][$i];
        if (
            $_FILES['files']['error'][$i] != 0 ||
            strpos($name, '..') !== false ||
            strpos($name, '/') !== false ||
            strpos($name, '~') !== false ||
            strpos($name, '"') !== false ||
            strpos($name, '#') !== false ||
            strpos($name, '?') !== false ||
            strpos($name, '<') !== false ||
            strpos($name, '>') !== false ||
            strpos($name, 'thumb__') !== false
        ) {
            $message .= 'Échec de l\'upload<br/>';
            continue;
        }
        if ($_FILES['files']['error'][$i] == UPLOAD_ERR_INI_SIZE || $_FILES['files']['error'][$i] ==  UPLOAD_ERR_FORM_SIZE) {
            $message .= 'Erreur du poids de l\'image : '.$name.'<br/>';
            continue;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if (false === $ext = array_search(
            $finfo->file($tmpName),
            array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ),
            true
        )) {
            $message .= 'Format invalide de l\'image : '.$name.'<br/>';
            continue;
        }

        $name = preg_replace('/[^a-zA-Z0-9éèê\ \-_àçù@\.]+/', '', trim($name));
        if($tmpName != ''){
            $path = $PATH.$folder.'/'.$name;
            if(move_uploaded_file($tmpName, $path)) {
                $success .= 'Upload de l\'image : '.$name.'<br/>'; 
            }
        } else {
            $message .= 'Échec de l\'upload de l\'image : '.$name.'<br/>';
            continue;
        }
    }
}

if ($isLogged && $folder == 'root' && isset($_POST['submit']) && isset($_POST['delete'])) {
    $nameDir = $_POST['delete'];
    $nameDir = preg_replace('/[^a-zA-Z0-9éèê\ \-_àçù@]+/', '', $nameDir);
    if (
        strpos($nameDir, '.') == false &&
        strpos($nameDir, '..') == false &&
        strpos($nameDir, '/') == false &&
        strpos($nameDir, '~') == false &&
        file_exists($PATH.$nameDir)
    ) {
        rename($PATH.$nameDir, $PATH.'deleted__'.date('Y_m_d_H_i_s').'__'.$nameDir);
        $success .= 'Suppression du dossier : '.$nameDir.'<br/>';
    } else {
        $message .= 'Échec de la suppression du dossier<br/>';
    }
}

$directories = array();
if ($isLogged && $handle = opendir($PATH)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && is_dir($PATH.$entry) && strpos($entry, 'deleted__') === false) {
            $nb = 0;
            $handleEntry = opendir($PATH.$entry);
            while (false !== ($entryImg = readdir($handleEntry))) {
                if ($entryImg != "." && $entryImg != ".." && strpos($entryImg, 'thumb__') === false) {
                    $nb++;
                }
            }
            closedir($handleEntry);
            $directories[] = array('name' => $entry, 'nb' => $nb);
        }
    }
    closedir($handle);
}
usort($directories, function ($item1, $item2) {
    return $item1['name'] <=> $item2['name'];
});

$pictures = array();
if ($isLogged && $folder != 'root' && $handle = opendir($PATH.$folder)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && !is_dir($entry) && strpos($entry, 'thumb__') === false) {
            $filePath = $PATH.$folder.'/'.$entry;
            $thumbPath = $PATH.$folder.'/'.'thumb__'.$entry;
            $exif = exif_read_data($filePath);
            $pictures[] = array(
                'path' => $entry, 
                'thumb' => 'thumb__'.$entry,
                'isPano' => ($exif['Make'] === 'MADV'),
            );
            if (!file_exists($PATH.$thumbPath)) {
                $extension = strtolower(strrchr($thumbPath, '.'));
                $img = false;
                switch ($extension) {
                    case '.jpg':
                    case '.jpeg':
                        $img = @imagecreatefromjpeg($filePath);
                        break;
                    case '.gif':
                        $img = @imagecreatefromgif($filePath);
                        break;
                    case '.png':
                        $img = @imagecreatefrompng($filePath);
                        break;
                    default:
                        break;
                }
                if (!$img) {
                    continue;
                }
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $img = imagerotate($img, 180, 0);
                            break;
                        case 6:
                            $img = imagerotate($img, -90, 0);
                            break;
                        case 8:
                            $img = imagerotate($img, 90, 0);
                            break;
                    }
                }
                $targetWidth = 500;
                $width = imagesx($img);
                $height = imagesy($img);
                $desired_width = $width;
                $desired_height = $height;
                if ($width > $height) {
                    $desired_width = $targetWidth;
                    $desired_height = $targetWidth*$height/$width;
                } else {
                    $desired_height = $targetWidth;
                    $desired_width = $targetWidth*$width/$height;
                }
                $virtual_image = imagecreatetruecolor($desired_width, $desired_height);
                imagecopyresampled($virtual_image, $img, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
                imagejpeg($virtual_image, $thumbPath);
            }
        }
    }
    closedir($handle);
}

if ($isLogged && $folder !== 'root' && $get !== '' && $isPano === false) {
    $filename = $PATH.$folder.'/'.$get;
    $file_extension = strtolower(substr(strrchr($filename,'.'),1));
    switch($file_extension) {
        case 'gif': $ctype='image/gif'; break;
        case 'png': $ctype='image/png'; break;
        case 'jpeg':
        case 'jpg': $ctype='image/jpeg'; break;
        default:
    }
    $isThumb = (strpos($get, 'thumb__') !== false);
    if ($ctype === 'image/jpeg' && $isResize && !$isThumb) {
        $imgSrc = @imagecreatefromjpeg($filename);
        $imgCut = @imagescale($imgSrc, 4096);
        header('Content-Type: image/jpeg');
        imagejpeg($imgCut);
        imagedestroy($imgCut);
        exit;
    } else {
        header('Content-type: ' . $ctype);
        readfile($filename);
        exit;
    }
}

?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?php echo $TITLE; ?> - Galerie Photos</title>
    <script src="pannellum/pannellum.js"></script>
    <link rel="stylesheet" href="pannellum//pannellum.css">
    <style>
        html, body, div, span, applet, object, iframe,
        h1, h2, h3, h4, h5, h6, p, blockquote, pre,
        a, abbr, acronym, address, big, cite, code,
        del, dfn, em, img, ins, kbd, q, s, samp,
        small, strike, strong, sub, sup, tt, var,
        b, u, i, center,
        dl, dt, dd, ol, ul, li,
        fieldset, form, label, legend,
        table, caption, tbody, tfoot, thead, tr, th, td,
        article, aside, canvas, details, embed, 
        figure, figcaption, footer, header, hgroup, 
        menu, nav, output, ruby, section, summary,
        time, mark, audio, video {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            font: inherit;
            vertical-align: baseline;
        }
        article, aside, details, figcaption, figure, 
        footer, header, hgroup, menu, nav, section {
            display: block;
        }
        body {
            line-height: 1;
        }
        ol, ul {
            list-style: none;
        }
        blockquote, q {
            quotes: none;
        }
        blockquote:before, blockquote:after,
        q:before, q:after {
            content: '';
            content: none;
        }
        table {
            border-collapse: collapse;
            border-spacing: 0;
        }
        body {
            width: 1600px;
            margin: auto;
        }
        h1 {
            text-align: center;
            font-size: 2.2em;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        h2 {
            text-align: center;
            font-size: 1.3em;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        form {
            border: 1px solid #666;
            padding: 10px;
            background: #ddd;
            margin: auto;
            width: 550px;
        }
        ul {
            margin: auto;
            width: 600px;
            display: block;
        }
        li {
            text-align: center;
            font-size: 1.2em;
            margin: 40px;
            padding: 10px;
            background: #eee;
        }
        a {
            color: black;
            text-align: center;
            margin: auto;
            font-size: 1.5em;
        }
        a:hover {
            color: blue;
        }
        .img {
            margin: auto;
            margin-top: 50px;
            width: 1600px;
            display: block;
        }
        img {
            padding: 0px;
            display: table;
        }
        .img li {
            margin: 11px;
            padding: 0px;
            border: 5px solid #aaa;
            display: table-row;
            float: left
        }
        .img li a {
            margin: 0px;
            padding: 0px;
        }
        .formdelete {
            clear: both;
            margin-bottom: 100px;
            width: 320px;
            position: relative;
            top: 50px;
        }
        #panorama {
            width: 1400px;
            height: 780px;
            margin: auto;
            margin-top: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if ($isLogged) { ?>
    <?php if ($message !== '') { ?>
    <h2 style="color:red;font-size:1.2em;background:#ddd;padding:10px"><?php echo $message; ?></h2>
    <?php } ?>
    <?php if ($success !== '') { ?>
    <h2 style="color:green;font-size:1.2em;background:#ddd;padding:10px"><?php echo $success; ?></h2>
    <?php } ?>
    <h1><?php echo $TITLE; ?> - <span style="font-style: italic;font-size: 0.8em;">Galerie Photos</span></h1>
    <?php if ($folder != 'root' && $get === '') { ?>
    <h2 style="font-style: italic;"><?php echo $folder; ?></h2>
    <?php } ?>
    <?php if ($folder != 'root' && $get === '') { ?>
        <a href="?">Retour</a>
        <form action="" enctype="multipart/form-data" method="post">
            <label for='files'>Ajout de photos :</label>
            <input name="files[]" type='file' multiple='multiple' />
            <input type="submit" name="submit" value="Envoyer" />
        </form>
        <ul class="img">
        <?php foreach($pictures as $pic) { ?>
            <li>
                <?php
                if ($pic['isPano']) {
                    echo '<a href="?folder='.$folder.'&get='.$pic['path'].'&pano">';
                } else {
                    echo '<a href="?folder='.$folder.'&get='.$pic['path'].'">';
                }
                echo '<img src="?folder='.$folder.'&get='.$pic['thumb'].'" />';
                echo '</a>';
                ?>
            </li>
        <?php } ?>
        </ul>
        <form action="?" enctype="multipart/form-data" method="post" class="formdelete">
            <label for='files'>Supprimer le dossier</label>
            <input type="hidden" name="delete" value="<?php echo $folder; ?>" />
            <input type="submit" name="submit" value="Envoyer" />
        </form>
    <?php } else if ($folder != 'root' && $get !== '' && $isPano === true) { ?>
        <div>
        <?php echo '<a href="?folder='.$folder.'">Retour</a>'; ?>
        <span style="float: right; font-style: italic;"><?php echo $get; ?></span>
        </div>
        <div id="panorama"></div>
        <script>
            var viewer = pannellum.viewer("panorama", {
                "type": "equirectangular",
                "autoLoad": true,
                "autoRotate": -10.0,
                "hfov": 100.0,
                "panorama": "<?php echo '?folder='.$folder.'&get='.$get.'&resize'; ?>"
            })
        </script>
    <?php } else { ?>
        <form action="?" method="post">
            <label for='folder'>Nouveau dossier :</label>
            <input name="folder" type='text' value="" />
            <input type="submit" name="submit" value="Créer" />
        </form>
        <ul>
        <?php foreach($directories as $dir) { ?>
            <li>
                <?php echo '<a href="?folder='.$dir['name'].'">'.$dir['name'].'</a>'; ?> 
                <span style="font-size: 0.8em">(<?php echo $dir['nb']; ?> images)</span>
            </li>
        <?php } ?>
        </ul>
    <?php } ?>
    <?php } else { ?>
        <form action="?" method="POST" style="margin-top:50px;">
            <label for='passe'>Mot de passe :</label>
            <input name="passe" type='password' value="" />
            <input type="submit" name="submit" value="Envoyer" />
        </form>
    <?php } ?>
</body>
</html>

