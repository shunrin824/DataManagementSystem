<?php
$htmlheader = '<html><head><style>img{width:100%;min-width:360px;max-width:1080px;}</style><meta charset="utf-8"><html lang="ja"></head><body>';
$htmlfooter = '</body></html>';
$html_template_path = "/var/www/html/idms/template.html";
$html_template = file_get_contents($html_template_path);
ini_set('display_errors', "on");
$idms_path = "/var/www/html/idms/";
$html_path = "/var/www/html/article/";
ini_set('display_errors', "on");
$s = microtime(true);
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


while ('1' == '1') {
    if (!file_exists('access')) {
        break;
    }
    usleep(500000);
}
list($width, $height, $type) = getimagesize($idms_path . 'banner.png'); // 元の画像名を指定してサイズを取得
echo ("imagetype_" . $type);
$baseImage = imagecreatefrompng($idms_path . 'banner.png');
$image = imagecreatetruecolor($width, $height); // サイズを指定して新しい画像のキャンバスを作成
imagecopyresampled($image, $baseImage, 0, 0, 0, 0, $width, $height, $width, $height); // 画像のコピーと伸縮
echo ($html_path . 'banner.webp');
imagewebp($image, $html_path . 'banner.webp', 50); // コピーした画像を出力する
imagejpeg($image, $html_path . 'banner.jpg', 80); // コピーした画像を出力する
unset($baseImage);
if (!file_exists($html_path)) {
    mkdir($html_path);
}
if (!file_exists($html_path . "webp/")) {
    mkdir($html_path . "webp/");
}
if (!file_exists($html_path . "jpeg/")) {
    mkdir($html_path . "jpeg/");
}
file_put_contents('access', 'writing');
copy('back.svg',$html_path.'back.svg');
$datas = json_decode(mb_convert_encoding(file_get_contents('data.json'), 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN'), 'true');
unlink('access');
foreach ($datas as $data) {
    if (!isset($data['tag'])) continue;
    if (!is_array($data['tag'])) continue;
    if (stristr(implode("", $data['tag']), 'public') && stristr(implode("", $data['tag']), 'article')) {
        if ($data['type'] == 'txt') {
            echo ($data['id']);
            $article = preg_replace('/img([0-9]{14})/i', '<picture class="img"><source type="image/webp" srcset="webp/$1.webp"><source type="image/jpeg" srcset="jpeg/$1.jpg"><img src="jpeg/$1.jpg" type="image/jpeg" class="img"></picture>', htmlspecialchars_decode($data['memo']));
            $html = str_replace('[article]', $article, $html_template);
            file_put_contents($html_path . $data['id'] . ".htm", $html);
        } elseif ($data['type'] == 'vrc' || $data['type'] == 'img') {
            if (file_exists($idms_path . 'png/' . $data['id'] . '.png')) {
                list($width, $height, $type) = getimagesize($idms_path . 'png/' . $data['id'] . '.png'); // 元の画像名を指定してサイズを取得
                echo ("imagetype_" . $type);
                $baseImage = imagecreatefrompng($idms_path . 'png/' . $data['id'] . '.png');
                if ($width > $height) {
                    $hdheight = round(1920 * $height / $width);
                    $hdwidth = 1920;
                } elseif ($height > $width) {
                    $hdwidth = round(1920 * $width / $height);
                    $hdheight = 1920;
                } else {
                    $hdwidth = 1920;
                    $hdheight = 1920;
                }
                $image = imagecreatetruecolor($hdwidth, $hdheight); // サイズを指定して新しい画像のキャンバスを作成
                imagecopyresampled($image, $baseImage, 0, 0, 0, 0, $hdwidth, $hdheight, $width, $height); // 画像のコピーと伸縮
                imagewebp($image, $html_path . "webp/" . $data['id'] . '.webp', 50); // コピーした画像を出力する
                imagejpeg($image, $html_path . "jpeg/" . $data['id'] . '.jpg', 80); // コピーした画像を出力する
                unset($image);
                unset($baseImage);
            }
        }
    } else {
        //記事など削除
    }
}
$e = microtime(true);
echo ("complete" . ($s - $e) . "<br>");
