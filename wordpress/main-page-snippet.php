// Página principal Royal Prestige (app React) servida desde jsDelivr.
// $rp_live = false -> solo se ve en https://prestigesolution.us/?rp_preview=1
// $rp_live = true  -> la ven todos los visitantes (sin sesión) en la portada "/"
$rp_live = true;

add_action( 'template_redirect', function () use ( $rp_live ) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$preview = isset( $_GET['rp_preview'] );
	$front   = ( is_front_page() || is_home() ) && ! is_user_logged_in();
	if ( ! $preview && ! ( $rp_live && $front ) ) {
		return;
	}
	nocache_headers();
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	header( 'Content-Type: text/html; charset=utf-8' );
	status_header( 200 );
	echo <<<'RPAPP'
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Royal Prestige USA | Equipa tu cocina y construye tu historial crediticio</title>
<meta name="description" content="Conoce los sistemas de cocina Royal Prestige, financiamiento flexible que reporta a las agencias de crédito, cupón de descuento y regalo de bienvenida. Distribuidor autorizado en EE.UU.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://prestigesolution.us/">
<meta property="og:title" content="Royal Prestige USA | Equipa tu cocina y construye tu historial crediticio">
<meta property="og:description" content="Financiamiento flexible que reporta a las agencias de crédito. Cupón de descuento y regalo de bienvenida.">
<meta property="og:image" content="https://cdn.jsdelivr.net/gh/creativosvirales/tifferd@0ece6ac74930c947a66ad8364032b2526cddf91e/cdn/assets/img/hero-set-royal-real.jpg">
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/creativosvirales/tifferd@67cba3db80db3850127a73d22503a9aeb47c0d5e/cdn/app.css">
</head>
<body>
<div id="root"></div>
<noscript><p style="font-family:sans-serif;padding:24px">Activa JavaScript para ver esta página, o escríbenos por <a href="https://wa.me/19705454299">WhatsApp</a>.</p></noscript>
<script type="module" src="https://cdn.jsdelivr.net/gh/creativosvirales/tifferd@5edbddfb510dbf03650e6413f272f6389cc6f39f/cdn/app.js"></script>
</body>
</html>
RPAPP;
	exit;
}, -10 );
