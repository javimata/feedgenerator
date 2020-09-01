<?php
/**
 * Constructor de Feeds XML
 * Basado en https://www.jasondilworth.co.uk/blog/how-create-google-shopping-feed-php/
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$store   = ( isset( $_REQUEST["store"] ) && $_REQUEST["store"] != "" ) ? $_REQUEST["store"] : "";
$ejecuta = ( isset( $_REQUEST["run"] ) && $_REQUEST["run"] != "" ) ? $_REQUEST["run"] : 1;

/**
 * Si se indica una store solo se ejecuta el generador para esa store
 */
if ( $store != "" ) {
    $whereStore = ' id = ' . $store . ' AND ';
}

if ( $ejecuta == 1 ):

    $starttime = microtime(true);
    $conn = mysqli_connect($_ENV["DB_SERVER"],$_ENV["DB_USER"],$_ENV["DB_PASS"],$_ENV["DB_DATABASE"]);
    $query = "SELECT * FROM feeds where " . $whereStore . " state = 1";

    $procesos = mysqli_query($conn,$query);

    while ($proceso = mysqli_fetch_array($procesos)):

        $starttime_prev = microtime(true);
        
        $id         = $proceso["id"];
        $url_store  = $proceso["url_store"];
        $apikey     = $proceso["apikey"];
        $apipass    = $proceso["apipass"];

        // Datos de configuración de la tienda
        $config = [
            'ShopUrl'  => $url_store,
            'ApiKey'   => $apikey,
            'Password' => $apipass
        ];

        // Limite de productos por carga, max 250
        $limit = 250;

        // Configura los parametros para el limite de productos
        $params["limit"] = $limit;
        // $params["collection_id"] ="157922459734";

        // Llamado al SDK y carga en $shopify
        PHPShopify\ShopifySDK::config($config);
        $shopify = new PHPShopify\ShopifySDK;

        // Cuenta el total de productos en el sitio
        $productCount = $shopify->Product->count();

        // Calcula la cantidad de páginas en base al total de productos entre el limite a mostrar por carga
        $paginas = ceil($productCount/$limit);

        // Obtener la info generar de la tienda
        $shop      = $shopify->Shop->get();
        $link_shop = 'https://'.$shop['domain'];
        $location  = $shopify->Location->get();
        $currency  = $shop["currency"];

        // Asignar un header para xml
        // header('Content-Type: text/xml; charset=utf-8', true);

        // Feed de productos totales
        $feed_products = [];

        /**
         * Genera un ciclo para sacar todos los productos
         * Shopify solo permite cargar máximo 250 productos por página
         */

        for ($i=1; $i < $paginas+1; $i++) {
            // Establece el parametro para la página actual
            $params["page"] = $i;
            // $params["collection_id"] ="157922459734";


            // Obtiene los productos dentro de la página actual
            $products  = $shopify->Product->get($params);

            /**
             * Ciclo para recorrer los productos
             */
            foreach ($products as $key => $product) {

                /**
                 * Variable contenedora de cada producto
                 * type: @array
                 */
                $gf_product = [];

                // Guarda los valores de los atriburos en el array
                $gf_product['g:id']           = $product['id'];
                $gf_product['g:title']        = "<![CDATA[ " . $product['title'] . " ]]>";
                $gf_product['g:description']  = "<![CDATA[ " . strip_tags($product['body_html']) . " ]]>";
                $gf_product['g:product_type'] = $product['product_type'];
                $gf_product['g:link']         = "<![CDATA[ " . $link_shop . '/products/' . strip_tags($product['handle']) . " ]]>";
                $gf_product['g:image_link']   = ( isset($product['images'][0]['src']) ) ? "<![CDATA[ " . $product['images'][0]['src'] . " ]]>" : "";
                $gf_product['g:brand']        = "<![CDATA[ " . $product['vendor'] . " ]]>";
                $gf_product['g:condition']    = 'new';

                //$gf_product['g:google_product_category']    = $product['product_type'];
                //if ( $stock > 0 ) {
                    $stock = ( isset($stock) ) ? $stock : "0";
                    $gf_product['g:availability'] = 'in stock';
                    $gf_product['g:inventory']    = $stock;
                //}
                
                if ( $product['variants'][0]['sku'] != "" ){
                    $gtin = $product['variants'][0]['sku'];
                } elseif ( $product['variants'][0]['barcode'] != "" ){
                    $gtin = $product['variants'][0]['barcode'];
                } else {
                    $gtin = $product['id'];
                }


                $gf_product['g:price']           = $product['variants'][0]['price'] . " " . $currency;
                $gf_product['g:gtin']            = $gtin;
                $gf_product['g:shipping_weight'] = $product['variants'][0]['weight'] . " " . $product['variants'][0]['weight_unit'];

                $itemgroupid = ( $product['variants'][0]['sku'] ) ? $product['variants'][0]['sku'] : $product['id'];
                $gf_product['g:item_group_id']   = $itemgroupid;

                $feed_products[] = $gf_product;

            }

        }

        $date_f         = date("D, d M Y H:i:s T", time());
        $build_date     = gmdate(DATE_RFC2822, strtotime($date_f));

        /**
         * Creamos el documento con DOMDocument
         */
        $doc = new DOMDocument('1.0', 'UTF-8');
        $xmlRoot = $doc->createElement("rss");
        $xmlRoot = $doc->appendChild($xmlRoot);
        $xmlRoot->setAttribute('version', '2.0');
        $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:g', "http://base.google.com/ns/1.0");

        /**
         * Crea el nodo principal channel
         */
        $channelNode = $xmlRoot->appendChild($doc->createElement('channel'));
        $channelNode->appendChild($doc->createElement('title', $shop['name']));
        $channelNode->appendChild($doc->createElement('link', $link_shop));
        $channelNode->appendChild($doc->createElement('create_date', $build_date));
        $channelNode->appendChild($doc->createElement('products_count', $productCount));

        /**
         * Creamos los nodos para los productos
         * Se crea un nodo item como contenedor de cada producto
         */
        foreach ($feed_products as $product) {
            $itemNode = $channelNode->appendChild($doc->createElement('item'));
            foreach($product as $key=>$value) {
                if ($value != "") {
                    if (is_array($product[$key])) {
                        $subItemNode = $itemNode->appendChild($doc->createElement($key));
                        foreach($product[$key] as $key2=>$value2){
                            $subItemNode->appendChild($doc->createElement($key2))->appendChild($doc->createTextNode($value2));
                        }
                    } else {
                        $itemNode->appendChild($doc->createElement($key))->appendChild($doc->createTextNode($value));
                    }
                } else {
                    $itemNode->appendChild($doc->createElement($key));
                }
            }
        }


        $doc->formatOutput = true;

        /**
         * Creación de archivo por tienda
         */
        $dir = $shop['domain'];
        if( is_dir($dir) === false ) {
            mkdir($dir);
        }

        $namefile = $dir . "/result.xml";
        $doc->save($namefile);// or die('XML Create Error');
        // echo $doc->saveXML();

        $queryupdate = "UPDATE `feeds` SET `last_count_items` = ".$productCount.", `last_filedate`= now(), `update_time`= CURRENT_TIMESTAMP, `url_filegenerate` = '".$namefile."' WHERE url_store = '" . $url_store ."'";
        $result = mysqli_query($conn, $queryupdate);

        $endtime_prev      = microtime(true);
        $timediff_prev     = $endtime_prev - $starttime_prev;
        $time_overall_prev = bcsub($endtime_prev, $starttime_prev, 2);

        // echo $time_overall_prev;
        
        $querylog = "INSERT INTO feeds_logs (parentid,date,products_count,time_elapsed) VALUES (".$id.", now(), ".$productCount.", '".$time_overall_prev."')";
        $conn->query($querylog);
        // $log = mysqli_query($conn, $querylog);
        
        if (!$result)     
            die("Adding record failed: " . mysqli_error()); 
            
    endwhile;

    $endtime = microtime(true);
    $timediff = $endtime - $starttime;
    $time_overall = bcsub($endtime, $starttime, 2);
    echo "Tiempo de ejecución - $time_overall segundos";

    mysqli_close($conn);

endif;