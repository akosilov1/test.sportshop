<?php
ignore_user_abort(true);
set_time_limit(0);
require_once __DIR__.'/config.php';
$start_time = time();

include __DIR__.'/classes/App/v/header.php';
include __DIR__.'/classes/App/v/form.php';

require_once(__DIR__."/classes/sport.php");
$mag = new Sport($seo_settings);

include __DIR__."/classes/functions.php";
Functions::init($config);

require_once(__DIR__.'/classes/soap.php');
//echo "<pre>";print_r($config);
$import = new Soap();

//$i->GetProducts();
if((int)$_GET["step"] > 0) {
    $import->GetProducts();
}
//ob_start();
$link = "";
$log = "";
//print_r($mag->GetProduct("product_id=12114"));
switch ($_GET["action"]){
    case "update":
        if((int)$_GET["date-m"] > 0 || (int)$_GET["date-d"] > 0){
            $date_m = (int)$_GET["date-m"];
            $date_d = (int)$_GET["date-d"];
            $u_step = (int)$_GET["u_step"];
            cron_log(["STATUS"=>"WORCK","STEP"=>$u_step]);
            echo"<pre>";
            if($u_step == 0){
                $rez_prod = $import->getProductIdAllByChanged($date_m,$date_d);
                $rez_prod = $rez_prod->productId;
                echo "ВСЕГО ".count($rez_prod)." товаров\n";
                Functions::ClearTableForUpdate();
                foreach ($rez_prod as $p_id){
                    Functions::WriteProductsForUpdate($p_id);//fwrite($f_i,$p_id."\n");
                }
                
                $u_step = 1;
            }

            $rez_prod = Functions::ReadProductsForUpdate($u_step);
            echo "RPU =".count($rez_prod)."<br>";
         
            if($rez_prod){
                $ii =($u_step > 0)?$u_step:1;
                //$ar_prods = array_slice($rez_prod,$u_step,15);

                foreach ($rez_prod as $product){
                    echo "\n***************\n*     $ii     *\n***************\n";
                    $item = $import->getProduct($product);
                    pre_print(Functions::UpdateProduct($item));
//stop_time(2);                     
                    $ii++;

            //print_r($item);
            //die("STOP");     
                }
                $link = "<a id='unext' href='?date-m=$date_m&date-d=$date_d&action=update&u_step=$ii'>Продолжить</a>";
                $u_url = "?date-m=$date_m&date-d=$date_d&action=update&u_step=$ii";
                cron_log(["STATUS"=>"STOP", "URL"=>$u_url, "STEP"=>$ii]);
            }else{
                cron_log(["STATUS"=>"END", "TIME"=>time()]);
                echo "*** КОНЕЦ ***";
            }
        }
        break;
    case "add_category":
        Functions::ClearCategories();
        $import->GetCategories();
        $import->AddCategories();
        break;
}

function cron_log($data){
	if($_GET["cron"] != "y") return false;
    $log = fopen("update.log","w");
    fwrite($log,json_encode($data));
    fclose($log);
}
function stop_time($text){
    global $start_time;
    die("<<<--- STOP TIME ".(time() - $start_time)." | ".$text." --->>><br>");  
}

//$log = ob_get_clean();
//print_r($mag->add_features("TEST111333","S","Вариант1"));//array("111","222","333")
//print_r($mag->GetProduct("pname=111"));
echo $link;?>
<p>Time: <?=(time() - $start_time);?></p>
<pre><?=$log?></pre>
<?
include __DIR__.'/classes/App/v/footer.php';