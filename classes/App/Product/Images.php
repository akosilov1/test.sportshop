<?php
/**
 * Created by PhpStorm.
 * User: Aleksandr
 * Date: 19.03.2019
 * Time: 19:40
 */

namespace Product;


class Images extends \Modules
{
    static $table = 'bishop_images_links';
    const SUB_TABLE = 'bishop_images';
    static function Get($filter=''){
        $db = new \Db;
        $q = 'SELECT * FROM '.self::$table. ' JOIN '.self::SUB_TABLE.' ON '.self::SUB_TABLE.'.image_id = '.self::$table.'.image_id';
        if($filter)$q .=' WHERE '.$filter;
        return $db->query($q,self::class);
    }
    static function GetPath($id){
        $new_dir = $_SERVER['DOCUMENT_ROOT'].'/images/product/'.floor($id/1000).'/';
        if(!mkdir($new_dir, 0777) && is_dir($new_dir))
            return $new_dir;

        return $new_dir;
    }
    static function CheckImages($from = 0){
        $db = new \Db;$not_file=array('111');
        $q = 'SELECT * FROM '.self::$table. ' JOIN '.self::SUB_TABLE.' ON '.self::SUB_TABLE.'.image_id = '.self::$table.'.image_id WHERE '.self::$table.'.`object_type`="product" LIMIT '.$from.',1000';
        $images = $db->query($q);
        //pre_print($images);
        if(!is_array($images))
            return false;
        foreach ($images as $img){
            $file = $_SERVER['DOCUMENT_ROOT'].self::GetPath($img['image_id']).$img['image_path'];
            //echo "Размер файла ".filesize($file)."<br>";
            if(!file_exists($file)){
                $not_file[] = $img['object_id'];
                echo 'Нет файла '.$img['object_id'].'<br>';
            }elseif (!filesize($file)){
                $not_file[] = $img['object_id'];
                echo 'Файл пустой '.$img['object_id'].'<br>';
            }
        }
        return $not_file;
    }
    static function AddImage($source,$obg_type='product',$obj_id=0,$type='M'){
        // type M - Основная, A - дополнительная
        if(pathinfo($source, PATHINFO_EXTENSION ) == "png")
            $source = self::Prepare($source);
        $db = new \Db();
        $f_name = basename($source);
        // Получаем последний ID
        $q = 'SELECT image_id FROM bishop_images t ORDER BY image_id DESC LIMIT 1';
        $l_id = $db->query($q);
        $id = $l_id[0]['image_id'] + 1;
        //
        $dest = self::GetPath($id).$f_name;
        echo $obj_id.': '.$source." > ".$dest."<br>";
        if(!copy($source, $dest)){
            echo 'Ошибка копирования '.print_r(error_get_last(), true).'<br>';
        }
        $ar_size = getimagesize($dest);
        $q = 'INSERT INTO '.self::SUB_TABLE.' SET image_path=:path, image_x=:x, image_y=:y';
        $id = $db->insert($q,array(':path'=>$f_name, ':x'=>$ar_size[0],':y'=>$ar_size[1]));
        if(!$id){
            echo 'Ошибка ';
            pre_print($db->error);
            return false;
        }
        echo "NEW img ".$id."<br>";
        $q = 'INSERT INTO '.self::$table.' SET image_id=:id, object_id=:prod_id, object_type=:obj, type=:type ';
        return $db->insert($q,array(':id'=>$id,':prod_id'=>$obj_id,':obj'=>$obg_type, ':type'=>$type));
    }
    static function Prepare($source){
        array_map('unlink', glob(__DIR__."/../../../images/*.png"));
        array_map('unlink', glob(__DIR__."/../../../images/*.jpeg"));
        $filename = basename($source,".png");
        $t_file = __DIR__."/../../../images/".$filename.".png";
        copy($source, $t_file);

        //echo $filename."<br>";
        $imgInfo = getimagesize($t_file);
        $source = imagecreatefrompng($t_file);
        //pre_print($imgInfo);
        $new_width = 800;
        $new_height = 800;
        $new_height = $imgInfo[1] / ($imgInfo[0] / $new_height);

        $newImg = imagecreatetruecolor($new_width, $new_height);
        $white = imagecolorallocate($newImg, 255, 255, 255);
        imagefill( $newImg , 0 , 0 , $white);
        imagealphablending($newImg , true);
        imagecopyresampled($newImg, $source, 0, 0, 0, 0, $new_width, $new_height, $imgInfo[0], $imgInfo[1]);
        imagedestroy($source);
        imagejpeg($newImg, __DIR__."/../../../images/".$filename.".jpeg", 85);
        $rez = $_SERVER['DOCUMENT_ROOT']."/import/images/".$filename.".jpeg";
        return $rez;
    }
}