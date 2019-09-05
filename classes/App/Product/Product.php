<?php
namespace Product;
use \Product\Images;
class Product
{
    public $price;
    public $product_id;
    public $amount;
    public $product_code;
    public function Get($filter){
        if(!$filter) return false;
        $db = new \Db;
        $q = 'SELECT product_id, amount, product_code FROM '.$config['table_prefix'].'products WHERE '.$filter;
        $r = $db->query($q,self::class);
        foreach ($r as $k=>$p)
        $r[$k]->price = \Product\Price::get($p->product_id);
        return $r;
    }

    public function GetFilePath(){
        return Files::Path($this->product_id);
    }
    public function GetImages(){
        $images = Images::Get(" object_id=".$this->product_id);
        foreach ($images as $i_id=>$image){
            $image->image_path = Images::GetPath($image->image_id).$image->image_path;
        }
        return $images;
    }
    public function GetOptions(){
        return \Product\Options::Get('product_id='.$this->product_id);
    }

    public static function GetIdByCode($code){
        if(!$code) return false;
        $db = new \Db();
        $q = 'SELECT * FROM import WHERE product_code=:code';
        $ar_rez = $db->query($q,'',array(':code'=>$code));
        return $ar_rez[0]['product_id'];
    }
}