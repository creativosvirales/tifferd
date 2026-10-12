// Páginas Royal Prestige (app React en jsDelivr): portada "/", embudo "/cupon/" y "/gracias/".
// $rp_live = false -> solo se ven con ?rp_preview=1 en cualquier dirección
// $rp_live = true  -> la portada la ven los visitantes sin sesión; /cupon/, /clientes/ y /gracias/ las ve todo el mundo
$rp_live     = true;
$rp_pixel_id = ''; // ID del Pixel de Meta (solo números). Vacío = sin Pixel.

add_action( 'template_redirect', function () use ( $rp_live, $rp_pixel_id ) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$uri     = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
	$path    = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$preview = isset( $_GET['rp_preview'] );
	$funnel  = in_array( $path, array( 'cupon', 'clientes', 'gracias' ), true );
	$front   = ( is_front_page() || is_home() ) && ! is_user_logged_in();
	if ( ! $preview && ! ( $rp_live && ( $front || $funnel ) ) ) {
		return;
	}

	$title   = 'Royal Prestige USA | Equipa tu cocina y construye tu historial crediticio';
	$desc    = 'Conoce los sistemas de cocina Royal Prestige, financiamiento flexible que reporta a las agencias de crédito, cupón de descuento y regalo de bienvenida. Distribuidor autorizado en EE.UU.';
	$noindex = false;
	if ( 'cupon' === $path ) {
		$title   = 'Cupón Royal Prestige de hasta $500 | Solicítalo hoy';
		$desc    = 'Solicita tu cupón de $300 a $500 USD en sistemas de cocina Royal Prestige. Financiamiento con pagos mensuales reportados a las agencias de crédito y regalo de bienvenida.';
		$noindex = true;
	} elseif ( 'clientes' === $path ) {
		$title   = 'Historial de crédito y cocina Royal Prestige | Solicita tu cupón';
		$desc    = 'Financiamiento de sistemas de cocina Royal Prestige con pagos reportados a TransUnion, Experian y Equifax. Cupón de $300 a $500 USD y regalo de bienvenida.';
		$noindex = true;
	} elseif ( 'gracias' === $path ) {
		$title   = 'Solicitud recibida | Royal Prestige USA';
		$desc    = 'Recibimos tu solicitud. Un asesor autorizado te contactará pronto.';
		$noindex = true;
	}

	$pixel = '';
	if ( preg_match( '/^\d{8,20}$/', (string) $rp_pixel_id ) ) {
		$pixel = '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init","' . $rp_pixel_id . '");fbq("track","PageView");</script><noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . $rp_pixel_id . '&amp;ev=PageView&amp;noscript=1"></noscript>';
	}

	nocache_headers();
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	header( 'Content-Type: text/html; charset=utf-8' );
	if ( $noindex ) {
		header( 'X-Robots-Tag: noindex' );
	}
	status_header( 200 );

	$html = <<<'RPAPP'
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{TITLE}}</title>
<meta name="description" content="{{DESC}}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{URL}}">
<meta property="og:title" content="{{TITLE}}">
<meta property="og:description" content="{{DESC}}">
<meta property="og:image" content="https://cdn.jsdelivr.net/gh/creativosvirales/tifferd@0ece6ac74930c947a66ad8364032b2526cddf91e/cdn/assets/img/hero-set-royal-real.jpg">
{{PIXEL}}
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/creativosvirales/tifferd@5a978cf332315c1e96935429507f5ce0ff67821a/cdn/app.css">
</head>
<body>
<div id="root"></div>
<noscript><p style="font-family:sans-serif;padding:24px">Activa JavaScript para ver esta página, o escríbenos por <a href="https://wa.me/19705454299">WhatsApp</a>.</p></noscript>
<script type="module" src="https://cdn.jsdelivr.net/gh/creativosvirales/tifferd@5a978cf332315c1e96935429507f5ce0ff67821a/cdn/app.js"></script>
</body>
</html>
RPAPP;

	echo str_replace(
		array( '{{TITLE}}', '{{DESC}}', '{{URL}}', '{{PIXEL}}' ),
		array( esc_html( $title ), esc_attr( $desc ), esc_url( home_url( '/' . ( $path ? $path . '/' : '' ) ) ), $pixel ),
		$html
	);
	exit;
}, -10 );
