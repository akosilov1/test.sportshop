<?php
/**
 * Created by PhpStorm.
 * User: Aleksandr
 * Date: 19.03.2019
 * Time: 19:17
 */

namespace Product;


class Files extends \Modules
{
    public static $table = "bishop_product_files";
    static function Get($filter=''){
        $db = new \Db();
        $q = 'SELECT * FROM '.self::$table;
        if($filter) $q .= ' WHERE ' .$filter;
        return $db->query($q);
    }

    static function Path($product_id){
        echo $product_id;
        $db = new \Db();
        $q = 'SELECT * FROM bishop_product_file_folders AS f JOIN bishop_product_file_folder_descriptions as fd ON fd.folder_id = f.folder_id WHERE f.product_id='.$product_id;
        return $db->query($q);
    }
}