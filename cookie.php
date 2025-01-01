<?php
ini_set('display_errors', "on");

//表示するデータのタイプを指定(vrc,img,txtなど)
if(isset($_GET['type'])){
    if($_GET['type'] == "all"){
        setcookie("type", $_GET['type'], time()-100);
    }else{
        setcookie("type", $_GET['type'], time()+2592000);
    }
}

//1ページに表示するデータのタイプを指定
if(isset($_GET['NumberOfDisplay'])){
    setcookie("NumberOfDisplay", $_GET['NumberOfDisplay'], time()+2592000);
}

//表示するスタイルを変更
if(isset($_GET['DisplayStyle'])){
    setcookie("DisplayStyle", $_GET['DisplayStyle'], time()+2592000);
}else{
    setcookie("DisplayStyle", 'none', time()-100);
}

//セーフサーチ()の設定。configで指定された除外ワードを使うかどうかの設定
if(isset($_GET['Exclude'])){
    setcookie("Exclude", $_GET['Exclude'], time()+2592000);
}else{
    setcookie("Exclude", 'none', time()-100);
}

//検索ワードをCookieへ保存。
if(isset($_GET['search'])){
    if(isset($_GET['page'])){
        $get = '?search='.$_GET['search'].'&page='.$_GET['page'];
    }else{
        $get = '?search='.$_GET['search'];
    }
}else{
    if(isset($_GET['page'])){
        $get = '?page='.$_GET['page'];
    }else{
        $get = '';
    }
}

//content.phpへ転送
if(isset($get)){
    echo('<html></meta><meta http-equiv="refresh" content="0; URL=/idms/content.php'.$get.'"></meta></html>');
}else{
    echo('<html></meta><meta http-equiv="refresh" content="0; URL=/idms/content.php"></meta></html>');
}