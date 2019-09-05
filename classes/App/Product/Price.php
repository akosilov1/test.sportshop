<?php
/**
 * Created by PhpStorm.
 * User: Aleksandr
 * Date: 25.02.2019
 * Time: 19:32
 */

namespace Product;


class Price
{
    var $price;
    /*function __construct($product_id)
    {
        if($product_id){
            return $this->get("$product_id");
        }
    }*/

    static function get($product_id){
        $db = new \db;
        //echo "PID_".$product_id;
        $q = "SELECT price FROM bishop_product_prices WHERE product_id=".$product_id;
        return $db->query($q,self::class);
    }
    function update($product_id, $price){
        if(!$product_id || !$price) return false;
        $db = new \Db();
        $q = "UPDATE bishop_product_prices SET price=? WHERE product_id=?";
        $st = $db->update($q,array($price,$product_id));

        if($st == 00000) return true;
        else return $st;
    }
}