<?php
/**
 * Constructor de Feeds XML
 * Basado en https://www.jasondilworth.co.uk/blog/how-create-google-shopping-feed-php/
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Datos de configuración de la tienda
$config = [
    'ShopUrl'  => $_ENV['SHOP_URL'],
    'ApiKey'   => $_ENV['SHOP_APIKEY'],
    'Password' => $_ENV['SHOP_APIPASS']
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
header('Content-Type: text/xml; charset=utf-8', true);

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
        $gf_product['g:title']        = '<![CDATA[ ' . $product['title'] . ' ]]>';
        $gf_product['g:description']  = '<![CDATA[ ' . strip_tags($product['body_html']) . ' ]]>';
        $gf_product['g:product_type'] = $product['product_type'];
        $gf_product['g:link']         = '<![CDATA[ ' . $link_shop . '/products/' . strip_tags($product['handle']) . ' ]]>';
        $gf_product['g:image_link']   = '<![CDATA[ ' . $product['images'][0]['src'] . ' ]]>';
        $gf_product['g:brand']        = '<![CDATA[ ' . $product['vendor'] . ' ]]>';
        $gf_product['g:condition']    = 'new';

        if ( $stock > 0 ) {
            $gf_product['g:availability'] = $product['in stock'];
            $gf_product['g:inventory']    = $stock;
        }

        $gf_product['g:price']           = $product['variants'][0]['price'] . " " . $currency;
        $gf_product['g:gtin']            = $product['variants'][0]['barcode'];
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
echo $doc->saveXML();