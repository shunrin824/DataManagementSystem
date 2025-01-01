<?php
ini_set('display_errors', "on");
$Config = json_decode(file_get_contents('config.json'), 'true');
ini_set("memory_limit", "4096M");
//Global変数の初期化
$ReadStartTime;
$ReadEndTime;
$ProcessStartTime;
$ProcessEndTime;
$WriteStartTime;
$WriteEndTime;
$Datas;

file_put_contents("post_data", $_POST['memo']);

//テキストデータの処理
function h($str)
{
    if (isset($str)) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
    return '';
}


//JSONファイルの読み込み
function ReadJson()
{
    unset($GLOBALS['Datas']);
    if (file_exists('data.json')) {
        $GLOBALS['ReadStartTime'] = microtime(true);

        //他プロセスのアクセス終了待ち
        while (file_exists(('Access'))) {
            usleep(50000);
        }

        //重複アクセス防止
        file_put_contents('Access', 'reading');

        //Json読み込み
        $GLOBALS['Datas'] = json_decode(file_get_contents('data.json'), 'true');

        //アクセス終了
        unlink('Access');
        $GLOBALS['ReadEndTime'] = microtime(true);
        return TRUE;
    }
    return FALSE;
}


//JSONファイルの書き込み
function WriteJson($WriteData)
{
    $GLOBALS['WriteStartTime'] = microtime(true);

    //他プロセスのアクセス終了待ち
    while (file_exists(('Access'))) {
        usleep(50000);
    }

    //重複アクセス防止
    file_put_contents('Access', 'reading');

    //Json書き込み
    file_put_contents("data.json", json_encode($WriteData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));


    //アクセス終了
    unlink('Access');
    $GLOBALS['WriteEndTime'] = microtime(true);
    return 0;
}

//画像の書き込みなど
function ImageWrite($id, $OriginalImageInfo, $WriteImageInfo, $OriginalImage)
{
    $WriteImage = imagecreatetruecolor($WriteImageInfo['width'], $WriteImageInfo['hight']);
    imagecopyresampled($WriteImage, $OriginalImage, 0, 0, 0, 0, $WriteImageInfo['width'], $WriteImageInfo['hight'], $OriginalImageInfo['width'], $OriginalImageInfo['hight']);
    switch ($WriteImageInfo['type']) {
        case 'webp':
            unlink($WriteImageInfo['name']);
            imagewebp($WriteImage, $WriteImageInfo['name'], $WriteImageInfo['comp']);
    }
}


//画像の処理
function ImageProcess($id)
{
    $GLOBALS['Datas']['FileName'] = $_FILES['file']['name'];
    //画像を読み込む
    switch ((strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1)))) {
        case 'png':
            $OriginalImage = imagecreatefrompng($_FILES['file']['tmp_name']);
            imagewebp($OriginalImage, $GLOBALS['Config']['PictureFolder'] . '/' . $id . '.webp', 100);
            break;

        case 'jpg':
        case 'jpeg':
            $OriginalImage = imagecreatefromjpeg($_FILES['file']['tmp_name']);
            imagewebp($OriginalImage, $GLOBALS['Config']['PictureFolder'] . '/' . $id . '.webp', 100);
            break;
        case 'webp':
            $OriginalImage = imagecreatefromwebp($_FILES['file']['tmp_name']);
            imagewebp($OriginalImage, $GLOBALS['Config']['PictureFolder'] . '/' . $id . '.webp', 100);
            break;
        default:
            return 'noimage';
    }
    list($OriginalImageInfo['width'], $OriginalImageInfo['hight'], $OriginalImageInfo['type']) = getimagesize($_FILES['file']['tmp_name']);

    //サムネ画像を生成
    //サムネ画像解像度の計算(長辺1920px)
    if ($OriginalImageInfo['width'] > $OriginalImageInfo['hight']) {
        $WriteSmallImageInfo['width'] = 1920;
        $WriteSmallImageInfo['hight'] = round(1920 / $OriginalImageInfo['width'] * $OriginalImageInfo['hight']);
    } elseif ($OriginalImageInfo['width'] < $OriginalImageInfo['hight']) {
        $WriteSmallImageInfo['hight'] = 1920;
        $WriteSmallImageInfo['width'] = round(1920 / $OriginalImageInfo['hight'] * $OriginalImageInfo['width']);
    } else {
        $WriteSmallImageInfo['hight'] = 1920;
        $WriteSmallImageInfo['width'] = 1920;
    }
    $WriteSmallImageInfo['name'] = $GLOBALS['Config']['SmallPictureFolder'] . '/' . $id . '.webp';
    $WriteSmallImageInfo['comp'] = '80';
    $WriteSmallImageInfo['type'] = 'webp';
    ImageWrite($id, $OriginalImageInfo, $WriteSmallImageInfo, $OriginalImage);
    //unlink($OriginalFilePath);
}


//動画の処理
function VideoProcess()
{
}


//音声の処理
function SoundProcess()
{
}


//アーカイブファイルの処理
function ArchiveProcess()
{
}


//データの新規作成
function NewData()
{
    ReadJson();
    $NewData['id'] = sprintf('%014d', $GLOBALS['Datas']['0']['id'] + 1);

    //データタイプを処理
    if (isset($_POST['type'])) {
        $NewData['type'] = $_POST['type'];
    } else {
        switch (strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1))) {
            case 'png':
            case 'jpeg':
            case 'jpg':
            case 'webp':
                $NewData['type'] = 'img';
                break;

            case 'wav':
            case 'ogg':
            case 'mp3':
            case 'm4a':
                $NewData['type'] = 'snd';
                break;

            case 'mp4':
            case 'mkv':
            case 'webm':
            case 'mov':
                $NewData['type'] = 'mov';
                break;

            case 'zip':
            case 'gz':
            case '7z':
            case 'lzh':
                $NewData['type'] = 'fil';
                break;

            default:
                $NewData['type'] = 'txt';

        }
    }

    //タグデータの処理
    if (strpos($_POST['tag'], "00and00")) {
        $NewData['tag'] = explode("00and00", $_POST['tag']);
    } else {
        $NewData['tag'] = explode(' ', $_POST['tag']);
    }

    if (isset($_POST['date'])) {
        $NewData['date'] = $_POST['date'];
    } else {
        $NewData['date'] = date('YmdHis');
    }
    if (strlen($_POST['url']) > 1) {
        $NewData['memo'] = str_replace("\r\n", '', nl2br(h($_POST['memo']), false)) . '<a href="' . $_POST['url'] . '">' . $_POST['url'] . '</a>';
    } else {
        $NewData['memo'] = str_replace("\r\n", '', nl2br(h($_POST['memo']), false));
    }
    if (isset($_FILES['file'])) {
        $NewData['originalname'] = $_FILES['file']['name'];
    }

    //データを書き込み
    $WriteData = $GLOBALS['Datas'];
    array_unshift($WriteData, $NewData);
    WriteJson($WriteData);
    unset($WriteData);

    //アップロードファイルの処理
    switch ($NewData['type']) {
        case 'vrc':
        case 'img':
            ImageProcess($NewData['id']);
            break;
        case 'snd':
            SoundProcess($NewData['id']);
            break;
        case 'mov':
            VideoProcess($NewData['id']);
            break;
        case 'fli':
            ArchiveProcess($NewData['id']);
            break;
    }
}


//データの編集
function EditData($id, $memo, $tag)
{
    ReadJson();
    foreach ($GLOBALS['Datas'] as $CurrentData) {
        if ($CurrentData['id'] == $id) {
            if (strlen($memo) > 1) {
                $CurrentData['memo'] = str_replace("\r\n", '', nl2br(h($_POST['memo']), false));
            }
            if (strlen($tag) > 1) {
                foreach (explode(' ', $tag) as $AddTag) {
                    $CurrentData['tag'][] = $AddTag;
                }
            }
        }
        $WriteData[] = $CurrentData;
    }
    WriteJson($WriteData);
}

//データタイプの変更
function EditType($id, $type)
{
    ReadJson();
    foreach ($GLOBALS['Datas'] as $CurrentData) {
        if ($CurrentData['id'] == $id) {
            $CurrentData['type'] = $type;
        }
        $WriteData[] = $CurrentData;
    }
    WriteJson($WriteData);
}

//タグの削除
function TagRm($id, $tag)
{
    ReadJson();
    foreach ($GLOBALS['Datas'] as $CurrentData) {
        if ($CurrentData['id'] == $id) {
            $OriginalTags = $CurrentData['tag'];
            unset($CurrentData['tag']);
            foreach ($OriginalTags as $OriginalTag) {
                if ($OriginalTag !== $tag) {
                    $CurrentData['tag'][] = $OriginalTag;
                }
            }
        }
        $WriteData[] = $CurrentData;
    }
    WriteJson($WriteData);
}


//データの新規作成、データの編集によって処理を分ける
switch ($_POST['writetype']) {
    case 'new':
        NewData();
        break;
    case 'edit':
        EditData(sprintf('%014d', $_POST['id']), $_POST['memo'], $_POST['tag']);
        break;
}
switch ($_GET['writetype']) {
    case 'tagadd':
        EditData(sprintf('%014d', $_GET['id']), $_GET['memo'], $_GET['tag']);
        break;
    case 'tagrm':
        TagRm(sprintf('%014d', $_GET['id']), $_GET['tag']);
        break;
}
?>
<html>
<meta http-equiv="refresh" content="1; URL='<?= $_SERVER['HTTP_REFERER'] ?>'">
test

</html>