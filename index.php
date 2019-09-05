<?
ignore_user_abort(true);
set_time_limit(0);
//session_start();
if($_REQUEST["clear"]) ob_start();
?>
<? 
$file_exp = "yandex_export.xml";
if(file_exists($file_exp)){
    $file_act = filectime($file_exp);
    echo date("d.m.Y H:i:s",$file_act)."<br>";
    if((time() - $file_act) > 60*60*24) 
        copy("http://prime-sport.ru/upload/yandex_export.xml", $file_exp);
}else{
    copy("http://prime-sport.ru/upload/yandex_export.xml", $file_exp);
}
?>

    <form action="">
    	<label for="">Без логов<input type="checkbox" <?=($_REQUEST["clear"])?"checked":"";?> name="clear" value="1" /></label> <br>
        <label for="">Шаг № <input type="text" name="step" value="<?=$_REQUEST["step"]+1?>"/></label> <br>
        <label for="">Добавлять категории <input type="checkbox" name="category"/></label><br>
        <button>Start</button>
    </form>
<pre>
<?
//$db->query("TRUNCATE TABLE `category`");
include_once "classes/sport.php";
if($_REQUEST["step"]) $reader = new reader($file_exp);
class reader {
	var $db, $reader,$step,$step_size,$from,$to,$sess_id,$log;
	var $add_cat = false;
	var $add_offer = true;
	var $block_props = array(
        "Цена Золото",
        "Цена Серебро",
        "Цена Бронза",
        "Цена Старт",
        "Цена РРЦ",
        "Цена Прайм",
        "Количество",
        "Артикул"
        );
	var $add_options = array(
	    "Размер"
    );
	private $offer;
	function __construct($file){
        $this->sess_id = session_id();
        $this->step = $_REQUEST["step"];
        $this->step_size = 20;
        $this->from = ($this->step - 1) * $this->step_size;
        $this->to = $this->step * $this->step_size;
        $this->add_cat = ($_REQUEST["category"])?true:false;
        $this->db = new mysqli("localhost","u7031_mag","3Em5qeC5vl","u7031_test");
		if($this->db->connect_errno)
			echo "Ошибка соединения ";
		//$this->db = new mysqli("localhost","u7031_test","","mag");
		$this->reader = XMLReader::open($file);
		$this->init();
	}
	function __destruct(){
		$this->db->close();
		//$this->db->close();
	}

    /**
     *
     */
	function init(){
		$i = 1;
		echo "FROM ".$this->from."\n";
		echo "TO ".$this->to."\n";
		global $mag;
		// Затираем таблицу с категориями
		if($this->add_cat) $this->db->query("TRUNCATE TABLE category");
		while ($this->reader->read()) {
			$nn = $this->reader->name;
			if($this->reader->nodeType == XMLReader::ELEMENT){
			    switch ($nn){
                    case "category":
                        if (!$this->add_cat) break;
                        $c_parent = $this->reader->getAttribute("parentId");
                        $c_id = $this->reader->getAttribute("id");
                        $c_name = $this->reader->readString();
                        echo $nn." : ".$c_id." : ".$c_name."\n";
                        $this->db->query("INSERT INTO `category` SET `cat_id`=".$c_id.", `parent`=".(int)$c_parent.", `name`='".$c_name."'");
                    break;
                    case "offer":
                        if(!$this->add_offer) break;
                        if($i <= $this->from){
                            $i++;
                            break;
                        }
                            echo "STEP ".$i."\n";
                        //
                        $s_percent = 100 / $this->step_size * ($this->to - $i);
                        $this->counter(array("STATUS"=>"WORCK","STEP"=>$this->step,"PERCENT"=>$s_percent,"SIZE"=>$this->step_size));
                        //
                        $ar_features = array();
                        // Получаем товар
                        $this->offer = $this->get_offer();
                        if($pp_id = $this->get_product_from_articul($this->offer["params"]["Артикул"])){
                            echo "Есть товар с таким артикулом ".$this->offer["params"]["Артикул"]."\n";
                            echo "\t-- UPDATE PRODUCT ".$pp_id." --\n";
                            $this->add_option($pp_id);
                            echo "\t-- >>> Количество ".($this->offer["params"]["Количество"])." --\n";
                            echo "\t-- >>> Цена ".($this->offer["price"])." --\n";
                            $q = "UPDATE bishop_products SET amount = ".($this->offer["params"]["Количество"])." WHERE product_id=".$pp_id;
                            $this->db->query($q);
                            $q = "UPDATE bishop_product_prices SET price = ".($this->offer["price"])." WHERE product_id=".$pp_id;
                            $this->db->query($q);
                            echo "\t-- /UPDATE PRODUCT ".$pp_id." --\n";
                        }else{
                            print_r ($this->offer);
                            // Получаем категорию
                            $q = "SELECT add_id FROM `category` WHERE cat_id=".$this->offer["categoryId"];
                            if($cat_rez = $this->db->query($q)->fetch_assoc()) {
                                echo "cat_id " . $cat_rez["add_id"] . "\n";
                                $this->offer["new_cat_id"] = $cat_rez["add_id"];
                            }else{
                                echo "ERROR Нет такой категории ".$this->offer["categoryId"];
                            }

                            /*
                            *** Обработка характеристик***
                            **/
                            // Получаем характеристики из описания
                            $desc_f = $this->getFeaturesFromDescription($this->offer["description"]);
                            //echo ($desc_f);
                            if(is_array($desc_f["features"])){
                                $this->offer["description"] = $desc_f["description"];
                                $this->offer["params"] = array_merge($this->offer["params"],$desc_f["features"]);
                            }
                            foreach ($this->offer["params"] as $f_key => $f_value) {
                                if(in_array($f_key, $this->block_props)) continue;
                                echo "\t** PROP:: ".$f_key." **\n";
                                if(in_array($f_key, $this->add_options)){
                                    // Если опция
                                    continue;
                                }else{
                                    $f_rez = $this->addFeatureAndVariant($f_key, $f_value);
                                    $ar_features[$f_rez["id"]]= array(
                                        "company_id" => 1,
                                        "description" => $f_key,
                                        "feature_type" => "S",
                                        "variant_id" => $f_rez["variant"],
                                        //"value" => $f_value,
                                        "display_on_product" => "Y",
                                        "display_on_catalog" => "Y",
                                    );
                                }
                                echo "\t** /PROP **\n";
                            }
                            echo "**** ADD PRODUCT ****\n";
                            //echo ($ar_features);
                            //
                            // Добавление
                            print_r ($ppp = $mag->add_product($ar_features, $this->offer), true);
                            $pp_id = $ppp->product_id;
                            // Добавляем опции
                            echo "\t\t-- OPTION --\n";
                            $this->add_option($pp_id);
                            echo "\t\t-- /OPTION --\n";
                            echo "**** /ADD PRODUCT ****\n";
                        }
                        if($i >= $this->to){
                            echo "<a id='next' href='/import/?step=".($this->step + 1)."'>Продолжить</a>";
                            $iii = array("STATUS"=>"STOP","STEP"=>$this->step,"SESSION"=>$this->sess_id);
                            if($_REQUEST["clear"]){
                                ob_end_clean();
                                //echo json_encode($iii);
                            }
                                $this->counter($iii);
                            header("Location: /import/index.php?step=".($this->step + 1)."&clear=1");

                            ?>
                            <script>
                                var n = document.querySelector("#next");
                                if(n){
                                    setTimeout(function () {
                                        document.querySelector("#next").click();
                                    }, 1000);
                                }
                            </script>
                            <? exit();
                        }
                        $i++;
                    break;

                }
			}elseif($this->reader->nodeType == XMLReader::END_ELEMENT){
				if($nn == "categories" && $this->add_cat){
                    $this->add_categories();?>
    ****************************
    *    Категории добавлены   *
    ****************************
                    <?exit();
					//return;
				}elseif ($nn == "offers"){
                    $iii = array("STATUS"=>"END","STEP"=>($this->step + 1),"SESSION"=>$this->sess_id);
                    $this->counter($iii);
				    echo "END";
                }
			}
			
		}
	}

    /**
     * Плучает товар
     * @return array|bool Массив с параметрами товара
     */
	function get_offer(){
		$ar_offer = array();
		$ar_offer["id"] = $this->reader->getAttribute("id"); 
		while ($this->reader->read()){
			$o_nn = $this->reader->name;
			if($this->reader->nodeType == XMLReader::ELEMENT){
				if($o_nn == 'param'){
					$o_param = $this->reader->getAttribute("name");
					$ar_offer["params"][$o_param] = $this->reader->readString();
				}
				if($o_nn == 'picture'){
                    $ar_offer["pictures"][] = $this->reader->readString();
                }
				else
					$ar_offer[$o_nn] = $this->reader->readString();
			}elseif($this->reader->nodeType == XMLReader::END_ELEMENT && $o_nn == "offer"){
				return $ar_offer;
			}
		}
		return false;
	}
    /**
     * Получает опцию по названию
     * @param $name string Название опции
     * @param $variant string Название варианта
     * @return array|bool [id] - Идентификатор опции, [var_id] - Идентификатор варианта
     */
    function get_option($name,$variant,$product_id){
        $rez = array();
        $res_o = $this->db->query("SELECT option_id FROM `bishop_product_options_descriptions` WHERE option_name='".$name."'");
        if($rez_o = $res_o->fetch_array(MYSQLI_ASSOC)) {
            $rez["id"] = $rez_o["option_id"];
            /*if($product_id){

            }*/
            if ($variant){
                $res_v = $this->db->query("SELECT variant_id FROM `bishop_product_option_variants_descriptions` WHERE variant_name='".$variant."'");
                if($rez_v = $res_v->fetch_array(MYSQLI_ASSOC))
                    $rez["var_id"] = $rez_v["variant_id"];
            }
            return $rez;
        };
        return false;
    }

    /**
     * Добавляет опцию
     *
     * @param $prod_id int Идентификатор товара
     * @return int id новой опции
     */
    function add_option($prod_id){
        if(!$prod_id) return false;
        foreach ($this->add_options as $op_name){
            if($op_val = $this->offer["params"][$op_name]){
                $q = "SELECT option_id FROM bishop_product_options WHERE product_id=".$prod_id;
                $q_res = $this->db->query($q);
                if($q_res->num_rows > 0){
                    $q_rez = $q_res->fetch_assoc();
                    $id = $q_rez["option_id"];
                }else{
                    // Если нет добавляем
                    if(!$this->db->query("INSERT INTO `bishop_product_options` SET product_id=".$prod_id.", company_id=1, required='N',inventory='N', multiupload='N', status='A',missing_variants_handling='M', option_type='S'"))
                        echo "\t\tОшибка добавления опции ".$this->db->error."\n";
                    else{
                        $id = $this->db->insert_id;
                        if(!$this->db->query("INSERT INTO `bishop_product_options_descriptions` SET option_id=".$id.", option_name='".$op_name."', lang_code='ru'"))
                        echo $this->db->error;
                        $this->db->query("REPLACE INTO bishop_ult_objects_sharing (share_object_id, share_object_type, share_company_id) VALUES ( ".$id.", 'product_options', 1)");
                    }


                }
                echo "\t\t>>> Добавление опции $op_name к товару $prod_id вариант $op_val \n";
                if($id) $this->add_option_variant($id,$op_val);
            }
        }
        return true;
    }

    /**
     * Добавляет вариант для опции
     * @param $option_id int Идентификатор опции
     * @param $variant_name string Название Варианта опции
     * @return bool|mysqli_result Идентификатор варианта
     */
    function add_option_variant($option_id,$variant_name){
        if(!$variant_name){
            echo "\t\tERROR ПУСТОЕ НАЗВАНИЕ ВАРИАНТА ОПЦИИ".print_r($this->offer["params"],true)."\n";
           return false;
        }
        $q="SELECT
              v.variant_id
            FROM
              bishop_product_option_variants_descriptions as vd
            INNER JOIN bishop_product_option_variants as v ON v.variant_id = vd.variant_id
            WHERE
              vd.variant_name = '".$variant_name."'
            AND
                v.option_id =".$option_id;
        $vv_res = $this->db->query($q);
        if($vv_res->num_rows >0){
            $vv_rez = $vv_res->fetch_assoc();
            $vv_id = $vv_rez["variant_id"];
            echo "\t\tERROR Есть такой вариант опции в базе $vv_id"."\n";
        }else{
            $q = "INSERT INTO bishop_product_option_variants SET option_id=".$option_id.", status='A'";
            $this->db->query($q);
            $v_id = $this->db->insert_id;
            $q_2 = "INSERT INTO bishop_product_option_variants_descriptions SET variant_id=".$v_id.",lang_code='ru',variant_name='".$variant_name."'";
            $this->db->query($q_2);
            return $v_id;
        }

        return false;
    }

    /**
     * Возвращает Идентификатор товара по его артикулу
     * @param $articul string Артикул
     * @return int/bool Идентификатор товара
     */
    function get_product_from_articul($articul){
        $q = "SELECT product_id FROM bishop_products WHERE product_code='".$articul."'";
        $res = $this->db->query($q);
        if($res->num_rows > 0){
            $rez = $res->fetch_assoc();
            return $rez["product_id"];
        }
        return false;
    }

    /**
     * Добавляет характеристики и варианты
     * @param $name string Название характеристики
     * @param $value string Выбранный Вариант характеристики
     * @return array id - Идентификатор Характеристики, variant - Идентификатор Выбранного варианта
     */
    function addFeatureAndVariant($name, $value){
        global $mag;
        // Ищем Характеристику по названию в базе
        $f_q = "SELECT feature_id FROM `bishop_product_features_descriptions` WHERE description='".$name."'";
        $f_res = $this->db->query($f_q);
        if($f_res->num_rows > 0){
            $f_rez = $f_res->fetch_assoc();
            //Характеристика Есть в базе
            $f_id = $f_rez["feature_id"];
            echo "\t\t** Характеристика № ".$f_rez["feature_id"]." = {".$value."}\n";
            // Ищем вариант
            $f_q_variant = "SELECT variant_id FROM bishop_product_feature_variant_descriptions WHERE variant='".$value."'";
            $f_rez_variant_s = $this->db->query($f_q_variant);
            if($f_rez_variant_s->num_rows > 0){
                $f_rez_variant = $f_rez_variant_s->fetch_assoc();
                //Есть такой вариант
                echo "\t\t\tВариант № ".$f_rez_variant["variant_id"]."\n";
                $f_variant = $f_rez_variant["variant_id"];
            }else {
                // Добавляем нужный вариант
                $f_add_q = "INSERT INTO bishop_product_feature_variants SET feature_id=" . $f_rez["feature_id"] . ", position=0";
                if ($this->db->query($f_add_q)) {
                    $f_add_id = $this->db->insert_id;
                    $f_add_q = "INSERT INTO bishop_product_feature_variant_descriptions SET variant_id=" . $f_add_id . ", variant='" . $value . "', lang_code='ru'";
                    if ($this->db->query($f_add_q)) {
                        $this->db->query("REPLACE INTO bishop_ult_objects_sharing (share_object_id, share_object_type, share_company_id) VALUES ( ".$f_rez["feature_id"].", 'product_features', 1)");
                        echo "\t\t-->Добавлен Вариант № " . $f_add_id . " Значение: {" . $value . "}\n";
                    }else{
                        echo "\t\t\ERROR Ошибка добавления значения варианта\n".$this->db->error."\nЗапрос ".$f_add_q."\n";
                    }
                }else{
                    echo "\t\tERROR Ошибка добавления варианта\n".$this->db->error."\n";
                }
                $f_variant = $f_add_id;
            }
        }else{
            //Нет в базе Добавляем характеристику
            $f_obj = $mag->add_features($name,"S");
            echo "\t\tДобавляем характеристику $name\n";
            //echo ($f_obj);
            $f_id = $f_obj->feature_id;
        }
        return array("id"=>$f_id, "variant"=>$f_variant);
    }

    /**
     * Вытаскивает характеристики из описания
     * @param $text string Строка с описанием
     * @return array [description] - Описание [features] - Характеристики
     */
    function getFeaturesFromDescription($text){
        $rez = $text_rez = array();
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
        	$r_str = str_replace(array("Характеристики:","Основные характеристики:","Дополнительные характеристики:"),"",$ttt);
            //preg_match_all("#^.*$#m",$ttt,$rrr);
            $matches = preg_replace("#^([^:]+)#mu","|$1", $r_str);
            $rrr = explode("|", $matches);
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
    function addOptionForProduct(){

    }

    function counter($data){
        $f = fopen("counter.txt","w");
        ftruncate($f,0);
        fwrite($f,json_encode($data, JSON_UNESCAPED_UNICODE));
        fclose($f);
        /*$_COOKIE["parse"]=json_encode($data);
        session_start();
        $_SESSION["PARSE"]=$data;
        session_write_close();*/
    }

    // Добавляет категории в магазин
    function add_categories($f_parent = 570){
        global $mag;
        $res = $this->db->query("SELECT * FROM `category` ORDER BY parent ASC");
        while ( $rez = $res->fetch_array()) {
            //echo ($rez);
            echo "*********************************\n";
            echo "cat_id ".$rez["cat_id"]." name ".$rez["name"]."\n";
            //
            $q = "SELECT add_id FROM `category` WHERE cat_id=".$rez["cat_id"];
            if(!$res_cat = $this->db->query($q)) echo $this->db->error;
            else{
                if ($rez_cat = $res_cat->fetch_assoc()){
                    if(!$rez_cat["add_id"]){
                        $this->add_cat_cat($rez["cat_id"], $rez["name"], $rez["parent"]);
                    }else{
                        echo "\t\t--> Есть такая категория в магазине \n";
                    }
                }
            }
        }
    }
    function add_cat_cat($id,$name,$parent){
        global $mag;

        if($parent == 0){
            $new_cat = $mag->add_cat(0,$name);
            echo "\t\t--> new id0 ".$new_cat->category_id."\n";
            $q = "UPDATE `category` SET add_id=".$new_cat->category_id." WHERE cat_id=".$id;
            $this->db->query($q);
            return $new_cat->category_id;
        }else{
            // Получаем предыдущюю
            $q = "SELECT cat_id, name, add_id, parent FROM `category` WHERE cat_id=".$parent;
            if(!$res_p = $this->db->query($q)) echo $this->db->error;
            if ($rez_p = $res_p->fetch_assoc()){
                // Есть категория в магазине
                if ($rez_p["add_id"]){
                    $new_cat = $mag->add_cat($rez_p["add_id"],$name);
                    echo "\t\t--> new id1 ".$new_cat->category_id."\n";
                    $q = "UPDATE `category` SET add_id=".$new_cat->category_id." WHERE cat_id=".$id;
                    $this->db->query($q);
                    return $new_cat->category_id;
                }else{
                    // Добавим родителя
                    $new_cat = $mag->add_cat($this->add_cat_cat_parent($rez_p["parent"]),$rez_p["name"]);
                    echo "\t\t--> new parent ".$new_cat->category_id."\n";
                    $q = "UPDATE `category` SET add_id=".$new_cat->category_id." WHERE cat_id=".$rez_p["cat_id"];
                    $this->db->query($q);
                    // Добавляем текущюю
                    $new_id = $this->add_cat_cat($id,$name,$parent);
                    //$q = "UPDATE `category` SET add_id=".$new_id." WHERE cat_id=".$id;
                    //$this->db->query($q);
                    echo "\t\t--> new id2 ".$new_id."\n";
                    return $new_id;
                }
            }else
                echo $this->db->error;
        }

        return false;
    }
    function add_cat_cat_parent($id){
    $q = "SELECT add_id FROM `category` WHERE cat_id=".$id;
    if(!$res_p = $this->db->query($q)) echo $this->db->error;
    if ($rez_p = $res_p->fetch_assoc()){
        return $rez_p["add_id"];
    }
    return false;
}
}
?>