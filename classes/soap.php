<?php
class Soap{
	var $client;
	var $categories, $db, $step, $count, $s_width, $erroors;
	function __construct(){
		$this->step = (int)$_REQUEST["step"];
        $this->s_width = 10;
		// проверяем наличие класса SoapClient
		if (class_exists('SoapClient')){
			// отключаем кэширование
			ini_set("soap.wsdl_cache_enabled", "0" );
			// подключаемся к серверу
			$this->client = new SoapClient(
				"http://prime-sport.ru/so-sync-service/v3/partner/service.wsdl",
				array(
					'soap_version'=>SOAP_1_2,
					'exceptions'=>true,
					'trace'=>1,
					'cache_wsdl'=>WSDL_CACHE_NONE,
					'login' => SOAP_LOGIN, // логин
					'password' => SOAP_PASS // пароль
				)
			);
		}else{
			echo "Включите поддержку SOAP в PHP!";
		}
	}

    /**
     * Получает изменения за период
     *
     * @return mixed
     */
    function getProductIdAllByChanged($m, $d){
        $date_m = ($m > 0)?date("m") - $m:date("m");
        $date_d = ($d > 0)?date("d") - $d:date("d");
        $date_c = date("Y-m-d\TH:i:sP",mktime(0, 0, 0, $date_m, $date_d, date("Y")));
        echo $date_c;
        $result = $this->client->getProductIdAllByChanged(array('startDate' =>$date_c));//getProductIdAllByChanged // getProductAllByChanged
        return $result;
    }

    /**
     * @param $id int ID Товара
     * @return mixed
     */
    function getProduct($id){
        try{
            return $this->client->getProduct($id);
        } catch (
            Exception $e
        ){
            $this->erroors = array($e->getCode(),$e->getMessage());
            return false;
        }

    }
    /**
     * Получает и добавляет товары
     */
	function GetProducts(){
	    global $mag;
	    ob_start();
	    echo "<pre>";
		// обращаемся к функции, передаем параметры
		$result = $this->client->getProductIdAll();
		//print_r($result);
		$i=1;
        $this->count = count($result->productId);
        echo "*** STEP ".$this->step." ".date("H:i:s")." ***\n";
		$prod = array_slice($result->productId, ($this->s_width * $this->step - 1), $this->s_width);
		foreach ($prod as $key => $product) {
			$item = $this->client->getProduct($product);
			$p_mag = $mag->GetProduct("pcode={$item->code}");
			if ($p_mag->params->total_items > 0){
			    echo "ERROR: Найден товар с кодом (".$item->code.") ПРОПУСКАЕМ\n";
			    continue;
			}
			$ar_prod = Functions::ProductPrepare($item, $i);
			$prod_rez = $mag->add_product($ar_prod);
			if(!isset($prod_rez->product_id)){
                echo "ERROR: Ошибка добавления товара с кодом (".$item->code.") ПРОПУСКАЕМ\n";
                echo "ARR_PRODREZ >> ".print_r($prod_rez, true)."\n\n";
                echo "ARR_PROD >> ".print_r($ar_prod, true)."\n\n";
			    continue;
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


			$i++;
		}
		$log = ob_get_clean();
		$f = fopen("import_log_".date("d_m_Y").".txt","a");
		fwrite($f,$log);
		fclose($f);
		echo "\n\t<<< END STEP ".$this->step." >>>";
		$st = $this->step * $this->s_width;
		echo "<div id='END' data-st='$st' data-count='{$this->count}'>".$st." | ".$this->count."</div>";
	}
	/**
	*
	**/
	function GetCategories(){
		global $log;
		$log .= "*** GetCategories ***\n";
		$rez = array();
		$cat = $this->client->getCategoryAll();
		//print_r($cat);
		foreach($cat->category as $c){
			$rez[$c->depth][$c->id]=(array)$c;
		}
		//$log .= print_r($rez, true);
		$log .= "*** GetCategories END ***\n";
		$this->categories = $rez;
	}
    function AddCategories(){
    	global $mag, $log;
    	$log .= "**** AddCategories ****\n";
    	$i = 1;
    	foreach ($this->categories as $p_key => $p_cat) {
    		foreach ($p_cat as $key => $cat) {
    			$log .= "$i| ($key) ".$cat["name"]."\n";
    			if($cat["parent"] > 0){
    				if($id = Functions::GetNewIdForCatId($cat["id"]))
    					$log .=  "НАЙДЕНА категория $id\n";
    				else{
	    				// ищем родителя 
	    				if($c_parent_id = $this->categories[($cat["depth"] - 1)][$cat["parent"]]["id"]){
		    				$log .=  "\tCAT PARENT ID $c_parent_id\n";
		    				//получаем ID
		    				$p_id = Functions::GetNewIdForCatId($c_parent_id);
		    				if($p_id){
		    					$log .=  "\tMAG PARENT ID $p_id\n";
		    					$new_cat_id = $mag->add_cat($p_id,$cat["name"]);
		    					$log .=  "\tNEW CAT $new_cat_id\n";
		    					Functions::AddCatToDb($cat, $new_cat_id);
		    				}else
		    					$log .=  "\t\t!!!ERROR!!! НЕТ ТАКОЙ КАТЕГОРИИ\n";
	    				}
	    			}
    			}else{
    				if($id = Functions::GetNewIdForCatId($cat["id"]))
    					$log .=  "НАЙДЕНА категория $id\n";
    				else{
    					$new_cat_id = $mag->add_cat(0,$cat["name"]);
    					$log .=  "\tNEW CAT $new_cat_id\n";
						Functions::AddCatToDb($cat, $new_cat_id);
    				}
    			}
    			
    			$i ++;

    			//if($i>50) exit();
    			//$mag->add_cat(,$cat["name"]);
    		}
    		
    	}
    }


}