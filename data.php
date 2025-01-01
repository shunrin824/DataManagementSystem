<?php
ini_set('display_errors', "on");
ini_set("memory_limit", "4096M");
$Config = json_decode(file_get_contents('config.json'), 'true');



//HTMLエンティティへ変換
function h($str)
{
    if (isset($str)) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
    return '';
}



//HTMLエンティティから変換
function hd($str)
{
    if (isset($str)) {
        return htmlspecialchars_decode($str, ENT_QUOTES,);
    }
    return '';
}



//日時表示
function DisplayDate($str){
    if (isset($str)){
        return substr($str, '0', '4') . "年" . substr($str, '4', '2') . "月" . substr($str, '6', '2') . "日" . substr($str, '8', '2') . "時" . substr($str, '10', '2') . "分" . substr($str, '12', '2') . "秒";
    }
}



//Jsonファイルの読み込み
function ReadJson()
{
    if (file_exists('data.json')) {
        $GLOBALS['ReadStartTime'] = microtime(true);
        while (file_exists(('Access'))) {
            usleep(50000);
        }
        file_put_contents('Access', 'reading');
        $GLOBALS['Datas'] = json_decode(file_get_contents('data.json'), 'true');
        unlink('Access');
        $GLOBALS['ReadEndTime'] = microtime(true);
        return TRUE;
    }
    return FALSE;
}



//VRCの画像の表示
function Display($type){
    $tags = $GLOBALS['DisplayData']['tag'];
    $memo = hd($GLOBALS['DisplayData']['memo']);
    $html = '<form action="datawrite.php" method="post"><input type="hidden" name="writetype" value="edit">';
    switch ($type) {
        case 'img':
            $html = $html.'<img src='.$GLOBALS['Config']['PictureFolder'].'/'. $GLOBALS['DisplayData']['id'].'.webp crass="ViewImage"></img><br>';
            $html = $html.'<p>作成日時: '.DisplayDate($GLOBALS['DisplayData']['date']).'</p>';
            break;
        case 'vrc':
            $html = $html.'<img src='.$GLOBALS['Config']['PictureFolder'].'/'. $GLOBALS['DisplayData']['id'].'.webp crass="ViewImage"></img><br>';
            $html = $html.'<p>撮影日時: '.DisplayDate($GLOBALS['DisplayData']['date']).'</p>';
            unset($tags['0']);
            $tags = array_values($tags);
            $html = $html.'<p>撮影場所: <a href="content.php?search='.$GLOBALS['DisplayData']['tag']['0'].'">'.$GLOBALS['DisplayData']['tag']['0'].'</a></p><p>';
            break;
        case 'txt':
            $html = $html.'<p>作成日時: '.DisplayDate($GLOBALS['DisplayData']['date']).'</p>';
            break;
        default:
            break;
    }
    $html = $html.'<p>登録ID: '.$GLOBALS['DisplayData']['id'].'</p>';
    foreach($tags as $tag){
        $html = $html.'<a href="datawrite.php?writetype=tagrm&id='.$GLOBALS['DisplayData']['id'].'&tag='.$tag.'">[X]</a> <a href="content.php?search='.$tag.'">'.$tag.'</a><br>';
    }
    $html = $html.'</p><input type="hidden" name="id" value="'.$GLOBALS['DisplayData']['id'].'"><p>タグ追加: <input type="text" name="tag" style="min-width:20%;" id="tag" value=""></p>';
    $html = $html.'文字数: <p id="inputlength" style="display:inline">-文字</p>';
    $memo = str_replace("<br>", '&#010;', $memo);
    $html = $html.'<textarea name="memo" rows="20" style="width:100%;" onkeyup ="ShowLength(value);">'.$memo.'</textarea><input type="submit" value="送信" /></form>';
    $GLOBALS['DataHtml'] = $html;
}



//表示データの整形
function FormatData()
{
    ReadJson();
    foreach ($GLOBALS['Datas'] as $CurrentData) {
        if ($CurrentData['id'] == $_GET['id']) {
            $GLOBALS['DisplayData'] = $CurrentData;
        }
    }
    switch ($GLOBALS['DisplayData']['type']) {
        case 'vrc':
            Display('vrc');
            break;
        case 'img':
            Display('img');
            break;
        case 'snd':
            Display('snd');
            break;
        case 'mov':
            Display('mov');
            break;
        default:
            Display('txt');
    }    
    $GLOBALS['SideHtml'] = preg_replace('/img([0-9]{14})/i', '<img src=webp/$1.webp style="max-width:640px;hieght:auto;width:100%;">', hd($GLOBALS['DisplayData']['memo']));
}



FormatData();

?>
<!DOCTYPE html>
<html>

<head>
    <script>
        function ShowLength(str) {
            document.getElementById("inputlength").innerHTML = str.length + "文字";
        }
    </script>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>データベース参照　<?= ($id) ?>の詳細</title>
    <link rel="stylesheet" type="text/css" href="/resource/lightbox.css" media="screen,tv" />
    <script type="text/javascript" charset="UTF-8" src="/resource/lightbox_plus.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
</head>

<body>
    <div class="wrapper">
        <!--ここからヘッダー-->
        <header>
            <h1>
                <a href="index.html">データ管理システム</a>
            </h1>
        </header>
        <div class="container">
            <div class="main">
                <div class="box">
                    <?=($GLOBALS['DataHtml'])?>
                </div>
            </div>
            <div class="side">
                <?=($GLOBALS['SideHtml'])?>
            </div>
        </div>
        <!--フッター-->
        <footer>
            データ管理システム
        </footer>
    </div>
</body>

</html>