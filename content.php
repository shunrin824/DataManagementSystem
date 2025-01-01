<?php
/*
20240607～20240608_0219
色々とバグはほとんど直したので、このファイルは後は容量計算の所を絶対パスにするくらいで良いと思います！多分！
今のところは他で直すところは見つからないので、次にこのファイルを触る私、頑張って下さい～！！
私はからぱりいんしゅ部行ってきます！
お疲れさまでした～！
追記 202408の夜くらい
一応全部完了しました！
お疲れさまでした～！
*/
ini_set("memory_limit", "4096M");
ini_set("display_errors","off");
//ini_set('display_errors', "on");
$ProcessStartTime = microtime(true);
$Config = json_decode(file_get_contents('config.json'), 'true');
$Datas;
$SearchDatas;
$FormatDatas;
$ExcludeWord;
$SearchWord;
$SearchEndTime;
$SearchStartTime;
$ReadEndTime;
$ReadStartTime;
$NumberOfSearchWord = 0;

//除外設定を行うかの判断
if ($_COOKIE['Exclude'] == 'FALSE') {
    $Exclude = FALSE;
} else {
    $Exclude = TRUE;
}

//除外する文字列を配列に
if (isset($GLOBALS['Config']['ExcludeWord']) && $Exclude == TRUE) {
    $GLOBALS['ExcludeWord'] = $GLOBALS['Config']['ExcludeWord'];
}

//データのタイプ指定
function DataType()
{
    if (empty($_COOKIE['type'])) {
        return 'all';
    } else {
        return $_COOKIE['type'];
    }
}

//検索文字列を配列に
function SearchWord()
{
    if (stristr($_GET['search'], ' ')) {
        if (!empty($_GET['search'])) {
            $SearchWords = explode(' ', $_GET['search']);
            foreach ($SearchWords as $SearchWord) {
                if (substr($SearchWord, 0, 1) == '-') {
                    $GLOBALS['ExcludeWord'][] = substr($SearchWord, 1);
                } elseif (substr($SearchWord, 0, 3) == 'NOT') {
                    $GLOBALS['ExcludeWord'][] = substr($SearchWord, 3);
                } else {
                    $GLOBALS['SearchWord'][] = $SearchWord;
                    $GLOBALS['NumberOfSearchWord']++;
                }
            }
        }
    } else {
        $SearchWord = h($_GET['search']);
        if (substr($SearchWord, 0, 1) == '-') {
            $GLOBALS['ExcludeWord'][] = substr($SearchWord, 1);
        } elseif (substr($SearchWord, 0, 3) == 'NOT') {
            $GLOBALS['ExcludeWord'][] = substr($SearchWord, 3);
        } else {
            $GLOBALS['SearchWord'][] = $SearchWord;
            $GLOBALS['NumberOfSearchWord']++;
        }
    }
}


//arrayをstringに連結する
function ArrayToString($array)
{
    if (!empty($array)) {
        if (is_array($array)) {
            $string = '[' . implode('][', $array) . ']';
            return $string;
        } else {
            return $array;
        }
    } else {
        return '';
    }
}

//テキストデータの処理
function h($str)
{
    if (isset($str)) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
    return '';
}

//データの表示スタイルの決定(既定ではnormal)
function DisplayStyle()
{
    if (isset($_COOKIE['DisplayStyle'])) {
        return $_COOKIE['DisplayStyle'];
    } else {
        return 'block';
    }
}

//データの表示件数の決定(既定では50)
function NumberOfDisplay()
{
    switch ($_COOKIE['NumberOfDisplay']) {
        case 0:
            return $GLOBALS['Config']['NumberOfDisplays0'];
        case 1:
            return $GLOBALS['Config']['NumberOfDisplays1'];
        case 2:
            return $GLOBALS['Config']['NumberOfDisplays2'];
        case 3:
            return $GLOBALS['Config']['NumberOfDisplays3'];
        case 4:
            return $GLOBALS['Config']['NumberOfDisplays4'];
        default:
            return $GLOBALS['Config']['NumberOfDisplays0'];
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
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo 'JSON Error: ' . json_last_error_msg();
        }
        $GLOBALS['ReadEndTime'] = microtime(true);
        return TRUE;
    }
    return FALSE;
}

//データの検索
function SearchData()
{
    if (ReadJson()) {
        SearchWord();
        $GLOBALS['SearchStartTime'] = microtime(true);
        $GLOBALS['NumberOfData'] = 0;
        foreach ($GLOBALS['Datas'] as $Data) {
            //使う変数の初期化
            $IsWord = TRUE;
            $IsExcludeWord = FALSE;
            $IsDataType = FALSE;

            //文字列検索
            if ($GLOBALS['NumberOfSearchWord'] !== 0) {
                foreach ($GLOBALS['SearchWord'] as $SearchWord) {
                    if (!stristr($Data['originalname'] . $Data['memo'] . ArrayToString($Data['tag']), $SearchWord)) {
                        $IsWord = FALSE;
                    }
                }
            } else {
                $IsWord = TRUE;
            }

            //除外文字列検索
            foreach ($GLOBALS['ExcludeWord'] as $ExcludeWord) {
                if (stristr($Data['originalname'] . $Data['memo'] . ArrayToString($Data['tag']), $ExcludeWord)) {
                    $IsExcludeWord = TRUE;
                }
            }

            //除外文字列検索(セーフサーチ)
            switch ($_COOKIE['Exclude'])
            {
                case 'FALSE':
                    break;
                default:
                    {
                        foreach ($GLOBALS['Config']['HideWord'] as $ExcludeWord) {
                            if (stristr($Data['originalname'] . $Data['memo'] . ArrayToString($Data['tag']), $ExcludeWord)) {
                                $IsExcludeWord = TRUE;
                            }
                        }
                    }        
            }

            switch (DataType()) {
                case 'all':
                    $IsDataType = TRUE;

                default:
                    if (DataType() == $Data['type']) {
                        $IsDataType = TRUE;
                    }
            }

            //文字列が存在し、除外文字列が無ければResultに格納
            if ($IsWord == TRUE && $IsExcludeWord == FALSE && $IsDataType == TRUE) {
                $GLOBALS['SearchDatas'][] = $Data;
                $GLOBALS['NumberOfData']++;
            }
        }
        $GLOBALS['SearchEndTime'] = microtime(true);
        if ($GLOBALS['NumberOfData'] == 0) {
            unset($GLOBALS['Datas']);
            return 1;
        }
        return 0;
    } else {
        return 'nodata';
    }
}

//現在のページ数
function Page()
{
    if (isset($_GET['page'])) {
        return $_GET['page'];
    } else {
        return 1;
    }
}

//表示するデータを整形
function FormatData()
{
    $NumberOfDisplay = NumberOfDisplay();
    SearchData();
    if ($GLOBALS['NumberOfData'] == 0) {
        unset($GLOBALS['SearchDatas']);
        return 0;
    }
    $Datas = $GLOBALS['SearchDatas'];
    $Count = 0;
    $StartData = (Page() - 1) * $NumberOfDisplay + 1;
    $EndData = Page() * $NumberOfDisplay;
    foreach ($Datas as $Data) {
        $Count++;
        if ($StartData <= $Count && $Count <= $EndData) {
            $GLOBALS['FormatDatas'][] = $Data;
        } elseif ($EndData < $Count) {
            unset($GLOBALS['SearchDatas']);
            return 0;
        }
    }
}

function HtmlBlock()
{
    $html = $GLOBALS['NumberOfData'] . '件の検索結果が見つかりました。<br><div class="wrap">';
    foreach ($GLOBALS['FormatDatas'] as $Data) {
        $html = $html . '<div class="cnt">';
        $html = $html . '<a href="data.php?id=' . $Data['id'] . '" target="_blank">';
        switch ($Data['type']) {
            case 'vrc':
                $html = $html . '<img src="' . $GLOBALS['Config']['SmallPictureFolder'] . '/' . $Data['id'] . '.webp" style="padding:0px; margin:0px; max-width:100%; max-height:56%;" />';
                break;
            case 'img':
                $html = $html . '<img src="' . $GLOBALS['Config']['SmallPictureFolder'] . '/' . $Data['id'] . '.webp" style="padding:0px; margin:0px; max-width:100%; max-height:56%;" />';
                break;
            default:
                $html = $html . '<textarea style="padding:0px;margin:0px;width:100%;height:46%;">' . $Data['memo'] . '</textarea>';
        }
        $html = $html . '</a>';
        $html = $html . '<textarea style="padding:0px; margin:0px; height:21%; max-width:100%;">' . ArrayToString($Data['tag']) . '</textarea><br>';
        $html = $html . '<textarea style="padding:0px; margin:0px; height:21%; max-width:100%;">' . $Data['originalname'] . '</textarea></div>';
    }
    $html = $html . '</div>';
    $GLOBALS['html'] = $html;
    return 0;
}

function HtmlList()
{
    $html = $GLOBALS['NumberOfData'] . '件の検索結果が見つかりました。<br><div class="wrap"><table>';
    foreach ($GLOBALS['FormatDatas'] as $Data) {
        if (stristr(ArrayToString($Data['tag']), 'favorite')) {
            $html = $html . '<tr><th><a href="datawrite.php?tag=favorite&id=' . $Data['id'] . '&writetype=tagrm" target="_blank">-★</a><br>Download<br>';
        } else {
            $html = $html . '<tr><th><a href="datawrite.php?tag=favorite&id=' . $Data['id'] . '&writetype=tagadd" target="_blank">+☆</a><br>Download<br>';
        }
        switch ($Data['type']) {
            case 'vrc':
                $html = $html . '<a href="' . $GLOBALS['Config']['PictureFolder'] . '/>' . $Data['id'] . '.webp" download="' . $Data['id'] . '.webp">WEBP</a><br>';
                break;
            case 'img':
                $html = $html . '<a href="' . $GLOBALS['Config']['PictureFolder'] . '/>' . $Data['id'] . '.webp" download="' . $Data['id'] . '.webp">WEBP</a><br>';
                break;
            case 'txt':
                break;
            default:
                break;
        }
        $html = $html . '</th><th><a href="data.php?id=' . $Data['id'] . '">';
        switch ($Data['type']) {
            case 'vrc':
                if (file_exists($GLOBALS['Config']['SmallPictureFolder'] . '/' . $Data['id'] . '.webp')) {
                    $html = $html . '<img src="' . $GLOBALS['Config']['SmallPictureFolder'] . '/' . $Data['id'] . '.webp" style="padding:0px; margin:0px; max-height:480px; max-width:854px;">';
                } else {
                    $html = $html . '<textarea style="padding:0px; margin:0px; max-height:480px; max-width:854px;">画像データがありません。</textarea>';
                }
                break;
            case 'img':
                if (file_exists($GLOBALS['Config']['SmallPictureFolder'] . '/' . $Data['id'] . '.webp')) {
                    $html = $html . '<img src="' . $GLOBALS['Config']['SmallPictureFolder'] . '/' . $Data['id'] . '.webp" style="padding:0px; margin:0px; max-height:480px; max-width:854px;">';
                } else {
                    $html = $html . '<textarea style="padding:0px; margin:0px; max-height:480px; max-width:854px;">画像データがありません。</textarea>';
                }
                break;
            case 'txt':
                $html = $html . '<textarea style="padding:0px; margin:0px; max-height:480px; max-width:854px;">' . $Data['memo'] . '</textarea>';
                break;
            default:
                $html = $html . '<textarea style="padding:0px; margin:0px; max-height:480px; max-width:854px;">' . $Data['memo'] . '</textarea>';
                break;
        }
        $html = $html . '</th><th><textarea style="padding:0px; margin:0px; max-height:240px; max-width:240px;">' . ArrayToString($Data['tag']) . '</textarea><br>';
        $html = $html . '<textarea style="padding:0px; margin:0px; max-height:240px; max-width:240px;">' . $Data['originalname'] . '</textarea><br></th></tr>';
    }
    $html = $html . "</table></div>";
    $GLOBALS['html'] = $html;
    return 0;
}

function HtmlAlbum()
{
    $html = $GLOBALS['NumberOfData'] . '件の検索結果が見つかりました。<br><div class="wrap">';
    foreach ($GLOBALS['FormatDatas'] as $Data) {
        $html = $html . '<div class="fsc">';
        $html = $html . '<a href="data.php?id=' . $Data['id'] . '" target="_blank">';
        switch ($Data['type']) {
            case 'vrc':
                $html = $html . '<img src="' . $GLOBALS['Config']['SmallPictureFolder'] . '/' . $Data['id'] . '.webp" style="max-height:338px; max-width:600px;">';
                break;
            case 'img':
                $html = $html . '<img src="' . $GLOBALS['Config']['SmallPictureFolder'] . '/' . $Data['id'] . '.webp" style="max-height:338px; max-width:600px;">';
                break;
            default:
                break;
        }
        $html = $html . '</a></div>';
    }
    $html = $html . '</div>';
    $GLOBALS['html'] = $html;
    return 0;
}

//データをHTML化
FormatData();
if ($NumberOfData == 0) {
    $html = "検索結果はありませんでした。";
} else {
    switch (DisplayStyle()) {

        case 'block':
            HtmlBlock();
            break;

        case 'list':
            HtmlList();
            break;

        case 'album':
            HtmlAlbum();
            break;
    }
}

//検索欄の文字列整形
if (isset($_GET['search'])) {
    $SearchBox = h($_GET['search']);
} else {
    $SearchBox = '';
}


$ProcessEndTime = microtime(true);

//検索にかかった時間の計算
$ProcessTime = round($ProcessEndTime - $ProcessStartTime, 4);
$ReadTime = round($ReadEndTime - $ReadStartTime, 4);
$SearchTime = round($SearchEndTime - $SearchStartTime, 4);

/*
//残り容量計算
//20240607の日記で書いたところはここです！！
//追記20240609
//システムによっては利用出来ない場合があるため、この機能は削除
$disk_free['Picture'] = round(disk_free_space($Config['PictureFolder']) / "1073741824", 2); //PNGを格納しているストレージの残容量を取得
$disk_tortal['Picture'] = round(disk_total_space($Config['PictureFolder']) / "1073741824", 2); //PNGを格納しているストレージの総容量を取得
$disk_use['Picture'] = round(($disk_tortal['Picture'] - $disk_free['Picture']) / $disk_tortal['Picture'] * "100", 2); //PNGを格納しているストレージの利用率を取得。
$disk_free['SmallPicture'] = round(disk_free_space($Config['SmallPictureFolder']) / "1073741824", 2); //WEBPを格納しているストレージの残容量を取得
$disk_tortal['SmallPicture'] = round(disk_total_space($Config['SmallPictureFolder']) / "1073741824", 2); //WEBPを格納しているストレージの総容量を取得
$disk_use['SmallPicture'] = round(($disk_tortal['SmallPicture'] - $disk_free['SmallPicture']) / $disk_tortal['SmallPicture'] * "100", 2); //WEBPを格納しているストレージの使用率を取得。
*/
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>IDMS SearchResult</title>
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
        <!--ここから本文-->
        <div class="container">
            <div class="main">
                <div class="box">
                    <a href="index.html">データベース（登録用ページ）</a>
                    <form action="content.php" method="get">
                        検索<input type="text" name="search" value="<?= ($SearchBox) ?>"><input type="hidden" name="page" value="1"><button type="submit">検索</button>
                    </form>
                    <table>
                        <tr>
                            <th>
                                Process
                            </th>
                            <th>
                                JsonRead
                            </th>
                            <th>
                                Search
                            </th>
                        <tr>
                            <th>
                                <?= ($ProcessTime) ?>s
                            </th>
                            <th>
                                <?= ($ReadTime) ?>s
                            </th>
                            <th>
                                <?= ($SearchTime) ?>s
                            </th>
                        </tr>
                    </table>
                    <a href="cookie.php?DisplayStyle=list&search=<?= h($_GET['search']) ?>&page=<?= page() ?>">リスト</a>
                    <a href="cookie.php?DisplayStyle=album&&search=<?= h($_GET['search']) ?>&page=<?= page() ?>">アルバム</a>
                    <a href="cookie.php?search=<?= h($_GET['search']) ?>&page=<?= page() ?>">ブロック</a><br>
                    <a href="content.php?page=<?= (page() - 1) ?>&search=<?= ($_GET['search']) ?>">前ページ</a><?= (page()) ?><a href="content.php?page=<?= (page() + 1) ?>&search=<?= ($_GET['search']) ?>">次のページ</a><br>
                    <?= ($html) ?>
                    <a href="content.php?page=<?= (page() - 1) ?>&search=<?= ($_GET['search']) ?>">前ページ</a><?= (page()) ?><a href="content.php?page=<?= (page() + 1) ?>&search=<?= ($_GET['search']) ?>">次のページ</a>
                </div>
                <div class="box">
                    <form action="cookie.php" method="get">
                        検索<input type="text" name="search" value="<?= ($_GET['search']) ?>"><br>
                        ※全角スペースで区切ってください。<br>
                        <input type="radio" name="type" value="all" checked="checked">すべて
                        <input type="radio" name="type" value="vrc">VRC写真
                        <input type="radio" name="type" value="txt">メモ
                        <input type="radio" name="type" value="fil">ファイル
                        <input type="radio" name="type" value="img">画像
                        <input type="radio" name="type" value="mov">動画
                        <input type="radio" name="type" value="tod">予定<br>
                        <input type="radio" name="Exclude" value="TRUE" checked="checked">制限
                        <input type="radio" name="Exclude" value="FALSE">解除<br>
                        <input type="radio" name="NumberOfDisplay" value="0" checked="checked"><?= h($Config['NumberOfDisplays0']) ?>
                        <input type="radio" name="NumberOfDisplay" value="1"><?= h($Config['NumberOfDisplays1']) ?>
                        <input type="radio" name="NumberOfDisplay" value="2"><?= h($Config['NumberOfDisplays2']) ?>
                        <input type="radio" name="NumberOfDisplay" value="3"><?= h($Config['NumberOfDisplays3']) ?>
                        <input type="radio" name="NumberOfDisplay" value="4"><?= h($Config['NumberOfDisplays4']) ?><br>
                        <button type="submit">検索</button><br>
                    </form>
                </div>
            </div>
        </div>
        <!--フッター-->
        <footer>
            データ管理システム
        </footer>
    </div>
</body>

</html>