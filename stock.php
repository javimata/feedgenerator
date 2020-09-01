<?php
/**
 * Sincroniza inventario de stock en shopify
 * Se deben dar permisos a la APP a: 
 * Inventory (Read and write)
 * Locations (Read access)
 * Product, variants and collections (Read and write)
 * 
 * Configuración:
 * @int $updateStock 0|1 Genera la actualización 
 * @Array $config con los datos de la tienda y claves de la API
 * @int $limit establece el limite de productos por página, max. 250
 */

// Datos de configuración de la tienda
$config = [
    'ShopUrl'  => $_ENV['SHOP_URL'],
    'ApiKey'   => $_ENV['SHOP_APIKEY'],
    'Password' => $_ENV['SHOP_APIPASS']
];

// Indica si se debe actualizar el stock
$updateStock = 0;
$updateInventoryTracker = 1;

// Limite de productos por carga, max 250
$limit = 50;

// Configura los parametros para el limite de productos
$params["limit"] = $limit;

// Llamado al SDK y carga en $shopify
PHPShopify\ShopifySDK::config($config);
$shopify      = new PHPShopify\ShopifySDK;

// Cuenta el total de productos en el sitio
$productCount = $shopify->Product->count();

// Calcula la cantidad de páginas en base al total de productos entre el limite a mostrar por carga
$paginas      = ceil($productCount/$limit);

// Contador (opcional)
$contador     = 1;

// Crea el encabezado resumen y el de la tabla
echo "Limite: " . $limit . " Cantidad total: " .$productCount . " Paginas: " . $paginas;
echo "<table class='table table-hover' cellpadding='5' cellspacing='2'>";

// Obtiener la locación(es) del inventario
$location = $shopify->Location->get();

// Ciclo que recorre los productos paginados
for ($i=1; $i < $paginas+1; $i++) {

    // Header de la sección de la tabla, agrupado por el bloque de limites
    echo "<tr><td colspan='4'>Pagina: " . $i . " de ". $paginas ."</td></tr>";
    echo "<tr><td>#</td><td>ID</td><td>PRODUCTO</td><td>STOCK</td></tr>";

    // Establece el parametro para la página actual
    $params["page"] = $i;

    // Obtiene los productos dentro de la página actual
    $products  = $shopify->Product->get($params);

    // Ciclo que recorre cada producto en la página actual y genera una nueva fila en la tabla
    foreach ($products as $key => $producto) {

        // Fila de producto con #, ID, nombre e imagen
        echo "<tr><td>" . $contador . "</td><td>" . $producto['id'] . "</td><td>" . $producto['title'] . "</td><td>";
        
        /* Ciclo para variantes, indicando Stock de cada una de ellas, además del titulo
         * $stock almacena la suma de stock de variantes
        */
        $stock = 0;

        foreach($producto['variants'] as $keyv => $variante) {

            // Suma stock de variantes al total
            $stock = $variante["inventory_quantity"] + $stock;

            // Imprime el stock de la variante actual
            echo $variante['inventory_quantity'] . " ";

            /* 
             * Si updateStock es == 1 actualiza el stock 
             * En este ejemplo actualiza el stock en las variantes que tengan stock de 1 y lo pone en 10
             * Si updateStock == 0 solo imprime el ID de la variante
            */
            if ( $variante['inventory_quantity'] == 10 && $updateStock == 1 ) {

                /*
                 * @array $data con los datos a proveer para actualizar el inventario de la variante
                 * available         = Cantidad en stock
                 * inventory_item_id = El ID de la variante a actualizar
                 * location_id       = El ID del almacen, este valor lo toma automaticamente cuando solo se tiene un almacen identificado dentro de Shopify 
                */
                $data = array(
                    "inventory_item_id" => $variante['inventory_item_id'],
                    "location_id" => $location[0]['id'],
                    "available" => 3
                );

                // Envia los datos de inventario 
                $shopify->InventoryLevel->set($data);

            } else {

                echo " | " . $variante['inventory_item_id'];

            }

            if ( $updateInventoryTracker == 1 ) {

                $data = array(
                    "id" => $variante['id'],
                    "inventory_management" => "shopify"
                );
                $shopify->ProductVariant($variante['id'])->put($data);

            }

            // Imprime el nombre de la variante
            echo " " . $variante['title'] . " | " . $variante['inventory_management'];
            echo "<br>";
            
        }

        // Imprime la suma total del stock
        echo "STOCK TOTAL: " . $stock;
        
        // Cierra la fila de producto
        echo "</td></tr>";

        // Aumenta el contador de productos (opcional)
        $contador++;

    }

}

// Cierra la tabla
echo "</table>";