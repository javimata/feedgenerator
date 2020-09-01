<?php 
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$uriSegments = explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$page = $uriSegments[1];

switch ($uriSegments[1]) {
    // case '':
    // case 'index':
    // case 'index.php':
    //     $title = "Dashboard";
    //     $page  = "home";
    //     break;
    
    case 'productos':
        $title = "Productos";
        break;
    
    case 'stock':
        $title = "Stock";
        break;
        
    case 'update_tracker':
        $title = "Actualiza Tracker";
        break;

    default:
        $title = "Dashboard";
        $page  = "home";
        break;

}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $title; ?> - <?php echo $_ENV['SHOP_NAME']; ?></title>

    <link data-react-html="true" rel="shortcut icon" type="image/x-icon" href="images/favicon.ico"/>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">

</head>
<body>

    <nav class="navbar fixed-top navbar-expand-md navbar-dark bg-dark mb-3">
        <div class="flex-row d-flex">
            <button type="button" class="navbar-toggler mr-2 " data-toggle="collapse" data-target="#collapsingNavbarMain" title="Toggle responsive left sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand align-items-center d-flex" href="." title="Shopify Admin">
                <img src="images/logo-shopify-white.svg" height="30" alt="Shopify"> <small><?php echo $_ENV['SHOP_NAME']; ?></small>
            </a>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsingNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-collapse collapse" id="collapsingNavbar">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="https://help.shopify.com/en/api/reference" target="_blank">Documentación Shopify</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://github.com/javimata" target="_blank"><i class="fab fa-github"></i></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://twitter.com/javi_mata" target="_blank"><i class="fab fa-twitter"></i></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://www.javimata.com" target="_blank"><i class="fas fa-globe"></i></a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid" id="main">
        <div class="row row-offcanvas row-offcanvas-left">
            <div class="col-md-3 col-lg-2 sidebar-offcanvas bg-light pl-0" id="sidebar" role="navigation">
                <nav class="navbar navbar-expand-md">
                    <div class="navbar-collapse collapse" id="collapsingNavbarMain">
                        <ul class="nav flex-column sticky-top pl-0 pt-5 mt-3">
                            <?php if ( $page != "home" ): ?>
                            <li class="nav-item"><a class="nav-link" href="."><i class="fas fa-home"></i> Home</a></li>
                            <?php endif; ?>
                            <li class="nav-item"><a class="nav-link" href="#productos" ><i class="fas fa-tshirt"></i> Productos</a></li>
                            <li class="nav-item"><a class="nav-link" href="#colecciones" ><i class="fas fa-layer-group"></i> Colecciones</a></li>
                            <li class="nav-item"><a class="nav-link" href="stock" ><i class="fas fa-socks"></i> Stock</a></li> 
                            <li class="nav-item"><a class="nav-link" href="update_tracker"><i class="fas fa-pallet"></i> Actualizar Tracker</a></li>
                            <li class="nav-item"><a class="nav-link" href="#check_images" ><i class="fas fa-images"></i> Checar imagenes</a></li>
                            <li class="nav-item"><a class="nav-link" href="#test" ><i class="fas fa-vial"></i> Test</a></li>
                            <li class="nav-item"><a class="nav-link" href="https://<?php echo $_ENV['SHOP_URL']; ?>/admin" target="_blank"><i class="fas fa-store"></i> Ir a la tienda <i class="fas fa-external-link-alt fa-xs"></i></a></li>
                        </ul>
                    </div>
                    </nav>
            </div>
            <!--/col-->

            <div class="col main pt-5 mt-3">

                <?php if ( $page != "home" ): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page"><a href=".">Home</a></li>
                        <?php if( $page != "home" ): ?>
                        <li class="breadcrumb-item" aria-current="page"><?php echo $title; ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
                <?php endif; ?>

                <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <h1 class="h4 d-none d-sm-block"><?php echo $title; ?></h1>
                        </div>
                    </div>

                    <?php include_once ($page.".php"); ?>

                </div>
            

            </div>

        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.9.0/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

</body>
</html>