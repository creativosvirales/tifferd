// Leads del embudo Royal Prestige: guarda cada solicitud en WordPress (menú "Leads") y avisa por correo.
$rp_lead_email = ''; // vacío = correo del administrador del sitio

$rp_lead_fields = array(
	'telefono'    => 'Teléfono',
	'email'       => 'Correo',
	'estado'      => 'Estado',
	'producto'    => 'Producto de interés',
	'consent_at'  => 'Aceptó ser contactado',
	'variant'     => 'Formulario',
	'page'        => 'Página',
	'utm_source'  => 'utm_source',
	'utm_medium'  => 'utm_medium',
	'utm_campaign' => 'utm_campaign',
	'utm_content' => 'utm_content',
	'utm_term'    => 'utm_term',
	'fbclid'      => 'fbclid',
	'referrer'    => 'Referido desde',
	'event_id'    => 'ID del evento (Meta)',
);

add_action( 'init', function () {
	register_post_type( 'rp_lead', array(
		'labels'       => array(
			'name'          => 'Leads',
			'singular_name' => 'Lead',
			'menu_name'     => 'Leads',
			'all_items'     => 'Todos los leads',
			'edit_item'     => 'Ver lead',
			'search_items'  => 'Buscar leads',
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'menu_icon'    => 'dashicons-groups',
		'menu_position' => 25,
		'supports'     => array( 'title' ),
		'capability_type' => 'post',
		'map_meta_cap' => true,
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
	) );
} );

add_filter( 'manage_rp_lead_posts_columns', function ( $cols ) {
	return array(
		'cb'        => isset( $cols['cb'] ) ? $cols['cb'] : '',
		'title'     => 'Lead',
		'rp_tel'    => 'Teléfono',
		'rp_email'  => 'Correo',
		'rp_estado' => 'Estado',
		'rp_src'    => 'Origen',
		'date'      => 'Fecha',
	);
} );

add_action( 'manage_rp_lead_posts_custom_column', function ( $col, $id ) {
	$map = array( 'rp_tel' => 'telefono', 'rp_email' => 'email', 'rp_estado' => 'estado' );
	if ( isset( $map[ $col ] ) ) {
		echo esc_html( get_post_meta( $id, $map[ $col ], true ) );
	}
	if ( 'rp_src' === $col ) {
		$src = get_post_meta( $id, 'utm_source', true );
		echo esc_html( $src ? $src : get_post_meta( $id, 'page', true ) );
	}
}, 10, 2 );

add_action( 'add_meta_boxes_rp_lead', function () use ( $rp_lead_fields ) {
	add_meta_box( 'rp_lead_data', 'Datos del lead', function ( $post ) use ( $rp_lead_fields ) {
		echo '<table class="widefat striped"><tbody>';
		foreach ( $rp_lead_fields as $key => $label ) {
			$val = get_post_meta( $post->ID, $key, true );
			if ( '' === $val ) {
				continue;
			}
			echo '<tr><th style="width:190px">' . esc_html( $label ) . '</th><td>' . esc_html( $val ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}, 'rp_lead', 'normal', 'high' );
} );

add_action( 'rest_api_init', function () use ( $rp_lead_email, $rp_lead_fields ) {
	register_rest_route( 'rp/v1', '/lead', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) use ( $rp_lead_email, $rp_lead_fields ) {
			$d  = (array) $req->get_json_params();
			$ok = array( 'ok' => true );

			// Trampas para bots: se responde "ok" sin guardar nada.
			if ( ! empty( $d['website'] ) || ( isset( $d['elapsed'] ) && (int) $d['elapsed'] < 1500 ) ) {
				return $ok;
			}

			$nombre = sanitize_text_field( isset( $d['nombre'] ) ? $d['nombre'] : '' );
			$tel    = sanitize_text_field( isset( $d['telefono'] ) ? $d['telefono'] : '' );
			$digits = preg_replace( '/\D+/', '', $tel );
			$raw_em = isset( $d['email'] ) ? trim( (string) $d['email'] ) : '';
			$email  = sanitize_email( $raw_em );
			$bad    = strlen( $nombre ) < 2 || strlen( $nombre ) > 80
				|| strlen( $digits ) < 10 || strlen( $digits ) > 15
				|| empty( $d['consent'] )
				|| ( '' !== $raw_em && '' === $email );
			if ( $bad ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid' ), 400 );
			}

			// Tope general por hora (evita inundaciones) y un solo registro por teléfono cada 24 h.
			$flood = (int) get_transient( 'rp_lead_flood' );
			if ( $flood >= 200 ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'busy' ), 429 );
			}
			set_transient( 'rp_lead_flood', $flood + 1, HOUR_IN_SECONDS );
			$dupe_key = 'rp_lead_p_' . md5( $digits );
			if ( get_transient( $dupe_key ) ) {
				return $ok;
			}
			set_transient( $dupe_key, 1, DAY_IN_SECONDS );

			$id = wp_insert_post( array(
				'post_type'   => 'rp_lead',
				'post_status' => 'publish',
				'post_title'  => $nombre . ' — ' . $tel,
			) );
			if ( ! $id || is_wp_error( $id ) ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'save' ), 500 );
			}

			$lines = array( 'Nombre: ' . $nombre );
			foreach ( $rp_lead_fields as $key => $label ) {
				if ( 'consent_at' === $key ) {
					$val = current_time( 'mysql' );
				} elseif ( 'email' === $key ) {
					$val = $email;
				} else {
					$val = sanitize_text_field( mb_substr( isset( $d[ $key ] ) ? (string) $d[ $key ] : '', 0, 300 ) );
				}
				update_post_meta( $id, $key, $val );
				if ( '' !== $val && 'consent_at' !== $key && 'event_id' !== $key ) {
					$lines[] = $label . ': ' . $val;
				}
			}

			$to      = $rp_lead_email ? $rp_lead_email : get_option( 'admin_email' );
			$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
			if ( $email ) {
				$headers[] = 'Reply-To: ' . $nombre . ' <' . $email . '>';
			}
			wp_mail( $to, 'Nuevo lead: ' . $nombre, implode( "\n", $lines ), $headers );

			return $ok;
		},
	) );
} );
