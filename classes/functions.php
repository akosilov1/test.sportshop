<?
class Functions{
	private static $db, $pdo;
	static function init($config){
		self::$db = new mysqli($config["db_host"],$config["db_user"],$config["db_password"],$config["db_name"]);
		self::$pdo = new PDO("mysql:host=".$config["db_host"].";dbname=".$config["db_name"].";charset=UTF8",$config["db_user"],$config["db_password"]);
        self::$db->error;
	}
    static function GetCategoryForName($name){
		$q = "SELECT `category_id` FROM `bishop_category_descriptions` WHERE `category`='$name'";
		$res = self::$db->query($q);
		if($res->num_rows == 0)
			return false;
		$rez = $res->fetch_assoc();
		echo self::$db->error;
		return $rez["category_id"];
	}
	// добавляет Товары для обновления в таблицу
    static function WriteProductsForUpdate($prod_id){
	    $q = "INSERT INTO  prod_update SET product_id=$prod_id";
	    self::$db->query($q);
        return self::$db->insert_id;
    }
    // Очищает таблицу для Обновления
    static function ClearTableForUpdate()
    {
        $q = "TRUNCATE TABLE prod_update";
        self::$db->query($q);
        return true;
    }
    // Читает Товары для обновления
    static function ReadProductsForUpdate($step = 1){
        $q = "SELECT product_id FROM  prod_update LIMIT $step, 10";
        $res = self::$db->query($q);
        if($res->num_rows == 0)
			return false;
        $arRez = [];
        while($rez = $res->fetch_array())
            $arRez[] = $rez["product_id"];
        return $arRez;
    }
    /**
	* Сохраняет новую категорию
	**/
	static function AddCatToDb($cat,$new_cat_id){
		$q = "INSERT INTO category SET cat_id=".$cat["id"].", add_id=".$new_cat_id.", name='".$cat["name"]."', parent=".$cat["parent"];
		self::$db->query($q);
		return self::$db->insert_id;
	}
	/**
	* Получает ИД родителя магазина по ИД категории
	*/
    static function GetNewIdForCatId($parent_id){
		$q = "SELECT add_id FROM category WHERE cat_id=".$parent_id;
		$res = self::$db->query($q);
		if($res->num_rows == 0)
			return false;
		$rez = $res->fetch_assoc();
		return $rez["add_id"];
	}

    /**
     * Получает коеффициент для прайса
     * @param $id
     * @return bool
     */
	static function GetCoeffFromId($id){
        $q = "SELECT coeff FROM category WHERE add_id=".$id;
        $res = self::$db->query($q);
        if($res->num_rows == 0)
			return false;
        $rez = $res->fetch_assoc();
        return $rez["coeff"];
    }

    /**
     * Очищает категории
     */
    static function ClearCategories(){
		$q = "TRUNCATE TABLE category";
		self::$db->query($q);
	}

	static function ProductPrepare($item, $i = 0){
		echo  "\n*** START ".$item->code." | $i |***\n";
		echo '*** CATEGORY'.print_r($item->category, true);
		//if($i==1)print_r($item);
		//echo  "\nACTIVE >> ".$item->active."\n";
		//echo "SIZES => ".print_r($item->size,true)."\n";
        $price = 0;
        $quantity = 0;
        $ar_options = array();
		if(is_array($item->size)){
			//echo "*** ARRAY SAZES ***\n";
			//$price = $item->size[0]->price[3]->_;
            foreach ($item->size as $size){
                if($size->active == 1){
                    $price = $size->price[PRICE]->_;
                    break;
                }
            }
			foreach ($item->size as $key => $size) {
//				echo "\t** SIZE **\n";
//				echo "\t* NAME >> ".$size->name."\n";
//				echo "\t* ACTIVE >> ".$size->active."\n";
//				echo "\t* QUANTITY >> ".$size->quantity."\n";
//				echo "\t* PRICE >> ".$size->price[3]->_."\n";
                $ar_options[] = array(
                    "variant_name" => trim($size->name),
                    "amount" => $size->quantity,
                    "active" => $size->active
                );
			}
		}else{
            $price = $item->size->price[PRICE]->_;
            $quantity = $item->size->quantity;
//			echo "*** ARRAY SAZES ***\n";
//			echo "\t** SIZE **\n \t* NAME >> ".$item->size->name."\n";
//			echo "\t* ACTIVE >> ".$item->size->active."\n";
//			echo "\t* QUANTITY >> ".$item->size->quantity."\n";
//			echo "\t* PRICE >> ".$item->size->price[3]->_."\n";
		}

		$ar_features = self::GetFeaturesFromDescription($item->description);
//		echo "\t* Характеристики\n";
        $ar_features["features"]["Бренд"] = $item->brand;
//		print_r($ar_features);
        // Получаем категории и Коэффициенты для цены
		$ar_cat = array();
		$ar_coeff = array();
        if(is_array($item->category)){
            foreach ($item->category as $c){
                $c_id = self::GetNewIdForCatId($c->uuid);
                $ar_cat[] = $c_id;
                $ar_coeff[] = self::GetCoeffFromId($c_id);
            }
        }else{
            $c_id = self::GetNewIdForCatId($item->category->uuid);
            $ar_cat[] = $c_id;//self::GetNewIdForCatId($c_id);
            $ar_coeff[] = self::GetCoeffFromId($c_id);
        }
        // Умножаем на Коэффициент для цены
        $co_old = 1;
        foreach ($ar_coeff as $co){
            if($co > $co_old)
                $co_old = $co;
        }
        $price = $price * $co_old;
        //
		$rez_product = array(
			"product"           => $item->name,
        	"price"             => $price,
        	"category_ids"      => $ar_cat,
        	"product_code"      => $item->code,
        	"full_description"  => $ar_features["description"],
        	"amount"            => $quantity,
        	"status"			=> ($item->active == 1)?"A":"D",
            "options"           => $ar_options,
            "features"          => $ar_features["features"],
		);
		//pictures
		foreach ($item->picture as $key => $img) {
		    if($key > 2)
			    $rez_product["pictures"][] = $img->_;
		}
        $rez_product["pictures"] = self::prepareImages($rez_product["pictures"]);
        //print_r($rez_product["pictures"]);
		//
        //echo "\t* PRODUCT >>\n";
		//print_r($rez_product);
		//echo "\n*** END | $i |***\n";
		return $rez_product;
	}
    static function GetFeaturesFromDescription($text){
        $rez = $text_rez = array();
        $desc = "";
        // Не добавлять
        $stop_array=array("Размер");
        if($poz = strpos($text,"Характеристики:")){
            $text_rez[] = substr($text,$poz);
            $desc = substr($text,0, $poz);
        }elseif ($poz_osn = strpos($text,"Основные характеристики:")){
            $poz_dop = strpos($text,"Дополнительные характеристики:");
            $desc = substr($text,0, $poz_osn);
            if($poz_osn && $poz_dop) {
                $text_rez[] = substr($text,$poz_osn,($poz_dop - $poz_osn));
                $text_rez[] = substr($text,$poz_dop);
            }elseif($poz_osn) $text_rez[] = substr($text,$poz_osn);
            elseif($poz_dop) $text_rez[] = substr($text,$poz_dop);
        }
        //echo ($text_rez);
        // Парсим характеристики из текста
        foreach ($text_rez as $ttt){
            $ar_char = str_replace(array("Характеристики:","Основные характеристики:","Дополнительные характеристики:"),"",$ttt);
            //preg_match_all("#^.*$#m",$ttt,$rrr);
            $ar_char = html_entity_decode($ar_char);
            $ar_char = preg_replace(array("#^([^:\n]+:)#m","#\\n#m","/\s,/im"), array("|$1"," ",","), $ar_char);
            $rrr = explode("|", $ar_char);
            foreach ($rrr as $k => $str){
                if($k == 0 || trim($str) == "") continue;
                $ar = explode(":",$str);
                if(count($ar) == 2){
                    if(in_array($ar[0],$stop_array)) continue;

                    $converted = strtr($ar[1], array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)));
                    $converted = trim($converted,chr(0xC2).chr(0xA0));
                    $r_converted = trim($converted);
                    if($r_converted)
                        $rez["features"][trim($ar[0])] = $r_converted;
                }

            }
        }
        $rez["description"] = $desc;
        return $rez;
    }

    /**
     * Возвращает ID характеристики по её названию
     * @param $name string Название характеристики
     * @return int ИД Характеристики
     */
    static function GetFeaturesFromName($name){
        $q = "SELECT feature_id FROM bishop_product_features_descriptions WHERE description=\"$name\"";
        $res = self::$pdo->prepare($q);
        $res->execute();
        if(!$rez = $res->fetch(PDO::FETCH_ASSOC))
           return false;
        return $rez["feature_id"];
    }

    /**
     * Возвращает ID Варианта характекристики по её названию
     * @param $id int ИД Характеристики
     * @param $name string Название варианта
     * @return int/bool ИД Варианта
     */
    static function GetFeatureVariantFromName($id, $name){
        $q = 'SELECT
            vd.variant,
            vd.variant_id,
            v.feature_id
            FROM
            bishop_product_feature_variants AS v
            INNER JOIN bishop_product_feature_variant_descriptions AS vd ON v.variant_id = vd.variant_id
            WHERE vd.variant = :name AND  v.feature_id = :id';
        $res = self::$pdo->prepare($q);
        $res->execute(array(":name" => $name,":id"=>$id));
        if(!$rez = $res->fetch(PDO::FETCH_ASSOC))
            return false;
        return $rez["variant_id"];
    }

    /**
     * Получает id Варианта опции по Названию
     * @param $id int option_id
     * @param $name string variant name
     * @return bool/int
     */
    static function GetOptionsVariantIdFromName($id, $name){
        $q = "SELECT vd.variant_id 
            FROM bishop_product_option_variants_descriptions AS vd
            INNER JOIN bishop_product_option_variants AS ov ON vd.variant_id = ov.variant_id
            WHERE variant_name=\"$name\" AND ov.option_id=$id";
        $res = self::$db->query($q);
        if($res->num_rows == 0)
            return false;
        $rez = $res->fetch_assoc();
        return $rez["variant_id"];
    }


    /**
     * @param $ar_options array
     * @param $name string Название опци
     * @return array|bool
     */
    static function GetOptionForName($ar_options,$name){
        if (is_array($ar_options) && trim($name) != "") {
            //echo "GO name:: ".trim($name)."\n";
            foreach ($ar_options as $k => $option) {
                //echo "GO option:: $k | ".$option->variant_name."\n";
                if (trim($option->variant_name) == trim($name))
                    return array($option->option_id, $option->variant_id);
            }
        }
        return false;
    }

    /**
     * @param $ar_combinations array
     * @param $ar_opt array [option_id, variant_id]
     * @return bool|array [hash, amount]
     */
    static function GetCombinationHash($ar_combinations,$ar_opt){
        if(!$ar_opt) return false;
        if (is_array($ar_combinations)){
            foreach ($ar_combinations as $comb){
                $comb_v = $comb->combination;
                if($comb_v->$ar_opt[0] == $ar_opt[1])
                    return array("hash"=>$comb->combination_hash, "amount"=>$comb->amount);
            }
        }

        return false;
    }
    static function UpdateProduct($item){

        global $mag;
        $get_prod = $mag->GetProduct("pcode=".$item->code);
        if(count($get_prod->products) > 0){
            $prod_id = $get_prod->products[0]->product_id;
            echo "* GET PROD >> ".$item->code." | id ".($prod_id)." *\n";
            if(is_array($item->size)){
                echo "ISSET SIZES\n";
                if(!$get_prod->products[0]->has_options){
                    echo "ADD OPT\n";
                }else{
                    echo "*** UPDATE OPTIONS ***\n";
                    $rez_opt = (array)$mag->get_options($prod_id);
                    $rez_opt = current($rez_opt);
                    $rez_opt = (array)$rez_opt->variants;
                    // Опции из магазина
                    //print_r($rez_opt);
                    // Комбинации опций из магазина
                    $rez_opt_comb = $mag->get_option_combination($prod_id);

                    foreach ($item->size as $size){
                        if($size->active == 1){
                            $s_price = $size->price[PRICE]->_;
                            break;
                        }
                    }

                    // Коэффициент
                    echo "<< PRICE $s_price >>";
                    $s_coeff = self::GetCoeffFromId($get_prod->products[0]->category_ids[0]);
                    echo "PRICE COEFFICIENT = $s_coeff\n";
                    if($s_coeff > 0)
                        $s_price = $s_price * $s_coeff;
                    // Обновление цены
                    echo "\tUPDATE PRICE id $prod_id | price $s_price >>\n\t";
                        //print_r($mag->put_product($prod_id,array("price"=>$s_price)));
                    foreach ($item->size as $size){
                        $s_quantity = $size->quantity;
                        $s_name = $size->name;
                        echo "\t* SIZE NAME:: ".$s_name." *\n";
                        if(!$ar_option_variant = self::GetOptionForName($rez_opt, $s_name)){
                            echo "\tНет такого варианта опции\n";
                            //print_r($rez_opt);
                            $op_params = array(
                                "variants"      => $rez_opt,
                                );
                            $op_params["variants"][] = ["variant_name"=>$s_name];
                            $option_id = current($rez_opt);
                            $option_id = $option_id->option_id;
                            echo "OP ID::".$option_id."\nДобавляем вариант >>\n";
                                //print_r($mag->put_options($option_id,$op_params));
                            unset($op_params);
                            $ar_option_variant = self::GetOptionForName($rez_opt, $s_name);
                        }
                        echo "OP_VARS::";//print_r($ar_option_variant);
                        if(!$ar_comb_hash = self::GetCombinationHash($rez_opt_comb,$ar_option_variant)){
                            echo "\tНет такой комбинации опции\n";
                            $op_params = [
                                "product_id"    => $prod_id,
                                "amount"        => $s_quantity,
                                "option_id"     => $ar_option_variant[0],
                                "variant_id"    => $ar_option_variant[1]
                            ];
                            echo "\tДобавляем комбинацию >>\n\t";
                                //print_r($mag->add_option_combinations($op_params));
                        }else{
                            // Обновляем количество
                            echo "\tUPDATE COMBINATION HASH:: ".$ar_comb_hash["hash"]." | Q:: ".$ar_comb_hash["amount"]." | new quantity:: $s_quantity >>\n";
                            if($ar_comb_hash["amount"] != $s_quantity){
                                echo "\tОбновление >>\n\t";
                                    //print_r($mag->update_option_combination($ar_comb_hash["hash"],$s_quantity));
                            }else{
                                echo "\tПропускаем >>\n";
                            }
                        }

                        echo "\t* END SIZE ".$s_name." *\n";

                    }

                }

            }else{
                echo "NO SIZES\n";
                $i_price = $item->size->price[PRICE]->_;

                echo "ORIG PRICE: ".$i_price."\n";
                // Коэффициент
                $s_coeff = self::GetCoeffFromId($get_prod->products[0]->category_ids[0]);
                echo "PRICE COEFFICIENT = $s_coeff\n";
                if($s_coeff > 0)
                    $i_price = $i_price * $s_coeff;
                // Количество
                $i_quantity = $item->size->quantity;
                echo "QUANTITY ".$i_quantity."\n";
                echo "PRICE ".$i_price."\n";
                return $mag->put_product($prod_id,array("price"=>$i_price,"amount"=>$i_quantity));
            }
        }else{
            echo "ТОВАР {$item->code} НЕ НАЙДЕН \n";
            echo "Добавляем \n";
            self::AddProduct($item);
        }


        return false;
    }
        /**
     * @param $item
     * @return bool
     */
    static function AddProduct($item){
        global $mag;
        $ar_prod = Functions::ProductPrepare($item);
        $prod_rez = $mag->add_product($ar_prod);
        //pre_print($prod_rez);
        if(!isset($prod_rez->product_id)){
            echo "ERROR: Ошибка добавления товара с кодом (".$item->code.") ПРОПУСКАЕМ\n";
            echo "ARR_PRODREZ >> ".print_r($prod_rez, true)."\n\n";
            echo "ARR_PROD >> ".print_r($ar_prod, true)."\n\n";
            return false;
        }
        //echo "AR PROD:\n".print_r($ar_prod, true)."\n";
        echo "AR PRODREZ:\n".print_r($prod_rez, true)."\n";

        if(count($ar_prod["options"]) > 0){
            $ar_options = array(
                "product_id"    => $prod_rez->product_id,
                "option_name"   => "Размер",
                "variants"      => $ar_prod["options"],
            );
            $o_rez = $mag->add_options($ar_options);

            foreach ($ar_prod["options"] as $op){
                if($v_id = Functions::GetOptionsVariantIdFromName($o_rez->option_id,$op["variant_name"])){
                    $ar_op = array(
                        "product_id"    => $prod_rez->product_id,
                        "amount"   => $op["amount"],
                        "option_id" => $o_rez->option_id,
                        "variant_id" => $v_id,
                    );
                    echo"OPTION COMBINATION ARR \n";
                    print_r($ar_op);
                    print_r($mag->add_option_combinations($ar_op));
                    echo"OPTION COMBINATION END \n";
                }else{
                    echo "\tERROR: Вариант {$op["variant_name"]} не найден\n";
                }

            }
        }
        return $prod_rez->product_id;
    }
    static function prepareImages($ar_images){
        array_map('unlink', glob(__DIR__."/../images/*.png"));
        array_map('unlink', glob(__DIR__."/../images/*.jpeg"));
        $rez = array();
        foreach ($ar_images as $image){
            //echo "IMG>>> ".$image."<br>";
            $filename = basename($image,".png");
            $t_file = __DIR__."/../images/".$filename.".png";
            copy($image, $t_file);

            //echo $filename."<br>";
            $imgInfo = getimagesize($t_file);
            $image = imagecreatefrompng($t_file);
            //pre_print($imgInfo);
            $new_width = 800;
            $new_height = 800;
            $new_height = $imgInfo[1] / ($imgInfo[0] / $new_height);

            $newImg = imagecreatetruecolor($new_width, $new_height);
            $white = imagecolorallocate($newImg, 255, 255, 255);
            imagefill( $newImg , 0 , 0 , $white);
            imagealphablending($newImg , true);
            imagecopyresampled($newImg, $image, 0, 0, 0, 0, $new_width, $new_height, $imgInfo[0], $imgInfo[1]);
            imagedestroy($image);
            imagejpeg($newImg, __DIR__."/../images/".$filename.".jpeg", 85);
            $rez[] = "/import/images/".$filename.".jpeg";
        }
        return $rez;
    }
}