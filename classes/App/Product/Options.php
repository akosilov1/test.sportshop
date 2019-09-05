<?php
/**
 * Created by PhpStorm.
 * User: Aleksandr
 * Date: 01.04.2019
 * Time: 19:26
 */

namespace Product;


class Options extends \Modules
{
    public static $table = 'bishop_product_options';
    public static $sub_table = 'bishop_product_option_variants';
    public static function Get($filter)
    {
        $db = new \Db();
        $q = 'SELECT * FROM '.self::$table.' AS v LEFT JOIN bishop_product_options_descriptions AS od ON od.option_id = v.option_id WHERE '.$filter;
        $options = $db->query($q,self::class);
        foreach ($options as $op_k => $op){
            $options[$op_k]->variants = self::GetVariants($op->option_id);
        }
        return $options;
    }
    public static function GetVariants($id){
        $db = new \Db();
        $q = 'SELECT * FROM '.self::$sub_table.' AS v JOIN bishop_product_option_variants_descriptions AS vd ON vd.variant_id = v.variant_id WHERE v.option_id='.$id;
        return $db->query($q);
    }
    public static function Add(int $product_id, array $params){
        echo $product_id;
        /*$ar_option = array(
            'option_type'=>'I','inventory'=>'N','required'=>'N','status'=>'A','position'=>0,'value'=>''
        );
        $q = 'INSERT INTO bishop_product_options SET product_id=:prod_id, company_id=1';
        $q_data = array(':prod_id' => $product_id);
        pre_print($q);
        pre_print($q_data);
        foreach ($ar_option as $op_name => $op_val){
            if(array_key_exists($op_name, $ar_option)){
                $q .= ', '.$op_name.'=:'.$op_name;
                $q_data[':'.$op_name] = $params[$op_name];
            }else{
                $q .= ', '.$op_name.'=:'.$op_name;
                $q_data[':'.$op_name] = $op_val;
            }
        }

        pre_print($q);
        pre_print($q_data);*/
        return true;
    }
}