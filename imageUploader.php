<?php

namespace MyApp;

class ImageUploader {

  private $_imageFileName; //プライベート変数 クラス内のみで利用
  private $_imageType;

  public function upload() {
    try {                       //トライしてダメならキャッチ
      // エラーチェック
      $this->_validateUpload();

      // タイプチェック
      $ext = $this->_validateImageType();
      // var_dump($ext);
      // exit;

      // セーブ
      $savePath = $this->_save($ext);

      // サムネを生成する
      $this->_createThumbnail($savePath);

      $_SESSION['success'] = 'Upload Done!'; //セッションの配列に 文字列を入れてるだけ
    } catch (Exception $e) {                      //キャッチ
      $_SESSION['error'] = $e->getMessage();
      // exit;
    }
    // redirect
    header('Location: http://192.168.33.10:8080/');
    exit;
  }

  public function getResults() { //実行結果を返します
    $success = null; //変数の初期化
    $error = null;
    if (isset($_SESSION['success'])) {
      $success = $_SESSION['success'];//変数に結果を入れて
      unset($_SESSION['success']);//削除
    }
    if (isset($_SESSION['error'])) {
      $error = $_SESSION['error'];//エラー内容を変数にいれて
      unset($_SESSION['error']);//削除
    }
    return [$success, $error];//それぞれの変数を返します
  }

  public function getImages() {//ゲットイメージ
    $images = [];//配列の初期化
    $files = [];
    $imageDir = opendir(IMAGES_DIR);//ディレクトリハンドルをオープンします
    while (false !== ($file = readdir($imageDir))) { //ディレクトリから次のエントリの名前を返します ファイル名をすべて取得するまで繰り返す
      if ($file === '.' || $file === '..') {//readdir関数が返す値には「.」と「..」が含まれます。
        continue;
      }
      $files[] = $file;//エントリ名のみを配列に入れる files[0] file名
      if (file_exists(THUMBNAIL_DIR . '/' . $file)) { //ファイルまたはディレクトリが存在するかどうか調べる
        $images[] = basename(THUMBNAIL_DIR) . '/' . $file; //パスの最後にある名前の部分を返す
      } else {
        $images[] = basename(IMAGES_DIR) . '/' . $file;//サムネの画像が無ければ普通の画像を入れる
      }
    }
    array_multisort($files, SORT_DESC, $images);//イメージ配列を降順にソートする
    return $images;
  }

  private function _createThumbnail($savePath) { //サムネ生成処理
    $imageSize = getimagesize($savePath);//画像の大きさを取得する
    $width = $imageSize[0];//横幅取得
    $height = $imageSize[1];//立幅取得
    if ($width > THUMBNAIL_WIDTH) { //横幅が400pxより大きければ処理する
      $this->_createThumbnailMain($savePath, $width, $height);
    }
  }

  private function _createThumbnailMain($savePath, $width, $height) {//サイズが大きいのでサムネ作成
    switch($this->_imageType) {
      case IMAGETYPE_GIF:
        $srcImage = imagecreatefromgif($savePath);  //新しい画像をファイルあるいは URL から作成する 画像 IDを返り値とする
        break;
      case IMAGETYPE_JPEG:
        $srcImage = imagecreatefromjpeg($savePath);//新しい画像をファイルあるいは URL から作成する 画像 IDを返り値とする
        break;
      case IMAGETYPE_PNG:
        $srcImage = imagecreatefrompng($savePath);//新しい画像をファイルあるいは URL から作成する 画像 IDを返り値とする
        break;
    }
    $thumbHeight = round($height * THUMBNAIL_WIDTH / $width);//浮動小数点数を丸める
    $thumbImage = imagecreatetruecolor(THUMBNAIL_WIDTH, $thumbHeight);//TrueColor イメージを新規に作成する　サムネ用の画像 IDが返り値らしい
    imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, THUMBNAIL_WIDTH, $thumbHeight, $width, $height);//再サンプリングを行いイメージの一部をコピー、伸縮する

    switch($this->_imageType) {
      case IMAGETYPE_GIF:
        imagegif($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName); //画像をブラウザあるいはファイルに出力する
        break;
      case IMAGETYPE_JPEG:
        imagejpeg($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);//画像をブラウザあるいはファイルに出力する
        break;
      case IMAGETYPE_PNG:
        imagepng($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);//画像をブラウザあるいはファイルに出力する
        break;
    }

  }

  private function _save($ext) { //セーブ処理
    $this->_imageFileName = sprintf( //フォーマットされた文字列を返す　ファイル名生成
      '%s_%s.%s',//%s = time() _%s =sha1(uniqid(mt_rand(), true)) .%s = $ext
      time(),
      sha1(uniqid(mt_rand(), true)),
      $ext
    );
    $savePath = IMAGES_DIR . '/' . $this->_imageFileName; //セーブファイルの場所指定
    $res = move_uploaded_file($_FILES['image']['tmp_name'], $savePath);//アップロードされたファイルを新しい位置に移動する 一時的に保存していたものを正規的なフォルダに移動する
    if ($res === false) {//ファイル移動が正常に行われなかった場合
      throw new Exception('Could not upload!');
    }
    return $savePath;
  }

  private function _validateImageType() {   //タイプチェック
    $this->_imageType = exif_imagetype($_FILES['image']['tmp_name']); //tmp_nameは一時ファイル名になる画像のタイプが返り値で返ってくる
    switch($this->_imageType) {
      case IMAGETYPE_GIF:
        return 'gif';
      case IMAGETYPE_JPEG:
        return 'jpg';
      case IMAGETYPE_PNG:
        return 'png';
      default:
        throw new Exception('PNG/JPEG/GIF only!'); //それ以外はエラー
    }
  }

  private function _validateUpload() { //エラーチェック
    //  var_dump($_FILES);
    //  exit;

    if (!isset($_FILES['image']) || !isset($_FILES['image']['error'])) { //trur=ファイルがない又はエラーコードが無ければアップロードエラー
      throw new Exception('Upload Error!');
    }

    switch($_FILES['image']['error']) {
      case UPLOAD_ERR_OK: //アップロードエラーコードがUPLOAD_ERR_OKならばTrue 投稿が成功している
        return true;
      case UPLOAD_ERR_INI_SIZE: //以下エラー理由
      case UPLOAD_ERR_FORM_SIZE: //HTML フォームで指定された MAX_FILE_SIZE を超えている
        throw new Exception('File too large!'); 
      default:
        throw new Exception('Err: ' . $_FILES['image']['error']);
    }

  }
}