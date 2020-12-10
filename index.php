<?php

session_start();

ini_set('display_errors', 1);// 1=true;  エラーの表示
define('MAX_FILE_SIZE', 1 * 1024 * 1024); // 1MB
define('THUMBNAIL_WIDTH', 400); //サムネのサイズ横幅
define('IMAGES_DIR', __DIR__ . '/images');//__DIR__フルパス+/imagesフォルダ
define('THUMBNAIL_DIR', __DIR__ . '/thumbs');//サムネフォルダ

if (!function_exists('imagecreatetruecolor')) {//function_exists関数が定義されていればTrue
  echo 'GD not installed'; //基本的にはfalse画像モジュールがインストールされていない
  exit;
}

function h($s) { //< > 文字をプログラムと認識してしまうので文字として扱うように変換してる
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

require 'imageUploader.php'; //imageUploader.phpプログラムを継承

$uploader = new \MyApp\ImageUploader(); //インスタンス化

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //ブラウザからPOSTがあった場合True
  $uploader->upload(); //これを実行しますよ
}

list($success, $error) = $uploader->getResults();//成功結果と失敗結果を返します

$images = $uploader->getImages();//ソート順になった画像

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Image Uploader</title>
  <style>
  body {
    text-align: center;
    font-family: Arial, sans-serif;
  }
  ul {
    list-style: none;
    margin: 0;
    padding: 0;
  }
  li {
    margin-bottom: 5px;
  }
  input[type=file] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    opacity: 0;
  }
  .btn {
    position: relative;
    display: inline-block;
    width: 300px;
    padding: 7px;
    border-radius: 5px;
    margin: 10px auto 20px;
    font-family: 'Hiragino Kaku Gothic Pro','ヒラギノ角ゴ Pro W3','メイリオ',Meiryo,'ＭＳ Ｐゴシック',sans-serif;
    color: #fff;
    font-size:18px;
    box-shadow: 0 4px #000;
    background: #333;
  }
  .btn:hover {
    opacity: 0.8;
    cursor:pointer;
  }
  .msg {
    margin: 0 auto 15px;
    width: 400px;
    font-weight: bold;
  }
  .msg.success {
    color: #4caf50;
  }
  .msg.error {
    color: #f44336;
  }
  </style>
</head>
<body>
<div class="readme">
  <h2>投稿条件</h2>
  <p>画像の拡張子は、<b>.jpg .png .gif</b> のみです。<br>画像サイズは<b>1MB</b>までにしています。</p>
</div>

<div class="btn">
  Upload images
  <form action="" method="post" enctype="multipart/form-data" id="my_form">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo h(MAX_FILE_SIZE); ?>">
    <input type="file" name="image" id="my_file">
  </form>
</div>

  <?php if (isset($success)) : ?>
    <div class="msg success"><?php echo h($success); ?></div>
  <?php endif; ?>
  <?php if (isset($error)) : ?>
    <div class="msg error"><?php echo h($error); ?></div>
  <?php endif; ?>

  <ul>
    <?php foreach ($images as $image) : ?>
      <li>
        <a href="<?php echo h(basename(IMAGES_DIR)) . '/' . h(basename($image)); ?>">
          <img src="<?php echo h($image); ?>">
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<script>
$(function() {
  $('.msg').fadeOut(3000);
  $('#my_file').on('change', function() {
    $('#my_form').submit();
  });
});
</script>
</body>
</html>