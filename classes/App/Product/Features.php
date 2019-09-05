<?php
/**
 * Created by PhpStorm.
 * User: Aleksandr
 * Date: 05.03.2019
 * Time: 18:39
 */

namespace Product;


class Features
{
    public $description;
    public $value;
    public $feature_id;
    public $variants;
    static function Get($product_id){
        $db = new \Db();
        echo "ID_".$product_id;
        $q = 'SELECT * FROM bishop_product_features_values AS fv JOIN bishop_product_features_descriptions as fd ON fv.feature_id = fd.feature_id WHERE fv.product_id='.$product_id;
        //$q = 'SELECT * FROM bishop_product_features_values AS fv WHERE fv.product_id='.$product_id;
        $rez = $db->query($q,self::class);
        foreach ($rez as $k=>$v)
            $rez[$k]->variants = self::GetVariant($v->feature_id,$v->variant_id);
        return $rez;
    }
    static function GetVariant($feature_id, $variant_id){
        $db = new \Db();
        $q = 'SELECT * FROM bishop_product_feature_variants AS fv LEFT JOIN bishop_product_feature_variant_descriptions AS vd ON fv.variant_id = vd.variant_id WHERE feature_id='.$feature_id.' AND fv.variant_id='.$variant_id;
        return $db->query($q);
    }
}