// Base de datos de leads Royal Prestige: guarda cada registro en WordPress (menú "Leads"),
// permite dar seguimiento, buscar, filtrar y exportar a CSV, y avisa por correo.
$rp_lead_email = ''; // vacío = correo del administrador del sitio

$rp_lead_fields = array(
	'nombre'       => 'Nombre',
	'telefono'     => 'Teléfono',
	'email'        => 'Correo',
	'estado'       => 'Estado',
	'producto'     => 'Producto de interés',
	'regalo'       => 'Regalo elegido',
	'consent_at'   => 'Aceptó ser contactado',
	'variant'      => 'Formulario',
	'page'         => 'Página',
	'utm_source'   => 'utm_source',
	'utm_medium'   => 'utm_medium',
	'utm_campaign' => 'utm_campaign',
	'utm_content'  => 'utm_content',
	'utm_term'     => 'utm_term',
	'fbclid'       => 'fbclid',
	'referrer'     => 'Referido desde',
	'event_id'     => 'ID del evento (Meta)',
);

$rp_status_options = array(
	'nuevo'         => 'Nuevo',
	'contactado'    => 'Contactado',
	'interesado'    => 'Interesado',
	'cerrado'       => 'Cerrado (venta)',
	'no_interesado' => 'No interesado',
);

add_action( 'init', function () {
	register_post_type( 'rp_lead', array(
		'labels'          => array(
			'name'          => 'Leads',
			'singular_name' => 'Lead',
			'menu_name'     => 'Leads',
			'all_items'     => 'Todos los leads',
			'edit_item'     => 'Ver lead',
			'search_items'  => 'Buscar leads',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_icon'       => 'dashicons-groups',
		'menu_position'   => 25,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
	) );
} );

add_filter( 'manage_rp_lead_posts_columns', function ( $cols ) {
	return array(
		'cb'        => isset( $cols['cb'] ) ? $cols['cb'] : '',
		'title'     => 'Lead',
		'rp_tel'    => 'Teléfono',
		'rp_email'  => 'Correo',
		'rp_estado' => 'Estado (EE.UU.)',
		'rp_status' => 'Seguimiento',
		'rp_src'    => 'Origen',
		'date'      => 'Fecha',
	);
} );

add_action( 'manage_rp_lead_posts_custom_column', function ( $col, $id ) use ( $rp_status_options ) {
	$map = array( 'rp_tel' => 'telefono', 'rp_email' => 'email', 'rp_estado' => 'estado' );
	if ( isset( $map[ $col ] ) ) {
		echo esc_html( get_post_meta( $id, $map[ $col ], true ) );
	}
	if ( 'rp_status' === $col ) {
		$st = get_post_meta( $id, 'rp_status', true );
		$st = isset( $rp_status_options[ $st ] ) ? $st : 'nuevo';
		echo esc_html( $rp_status_options[ $st ] );
	}
	if ( 'rp_src' === $col ) {
		$src = get_post_meta( $id, 'utm_source', true );
		echo esc_html( $src ? $src : get_post_meta( $id, 'page', true ) );
	}
}, 10, 2 );

add_action( 'restrict_manage_posts', function ( $post_type ) use ( $rp_status_options ) {
	if ( 'rp_lead' !== $post_type ) {
		return;
	}
	$cur = isset( $_GET['rp_status'] ) ? sanitize_key( wp_unslash( $_GET['rp_status'] ) ) : '';
	echo '<select name="rp_status"><option value="">Todos los seguimientos</option>';
	foreach ( $rp_status_options as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '"' . selected( $cur, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select> ';
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=rp_leads_export' ), 'rp_leads_export' );
	echo '<a class="button button-primary" href="' . esc_url( $url ) . '">Exportar todo a CSV</a>';
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || 'rp_lead' !== $q->get( 'post_type' ) ) {
		return;
	}
	if ( ! empty( $_GET['rp_status'] ) ) {
		$q->set( 'meta_query', array(
			array( 'key' => 'rp_status', 'value' => sanitize_key( wp_unslash( $_GET['rp_status'] ) ) ),
		) );
	}
} );

add_action( 'add_meta_boxes_rp_lead', function () use ( $rp_lead_fields, $rp_status_options ) {
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

	add_meta_box( 'rp_lead_follow', 'Seguimiento', function ( $post ) use ( $rp_status_options ) {
		wp_nonce_field( 'rp_lead_follow', 'rp_lead_follow_nonce' );
		$cur = get_post_meta( $post->ID, 'rp_status', true );
		$cur = isset( $rp_status_options[ $cur ] ) ? $cur : 'nuevo';
		echo '<p><label for="rp_status"><strong>Estado</strong></label><br><select name="rp_status" id="rp_status" style="width:100%">';
		foreach ( $rp_status_options as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $cur, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p>';
		echo '<p><label for="rp_notes"><strong>Notas</strong></label><br><textarea name="rp_notes" id="rp_notes" rows="6" style="width:100%">' . esc_textarea( get_post_meta( $post->ID, 'rp_notes', true ) ) . '</textarea></p>';
	}, 'rp_lead', 'side', 'high' );
} );

add_action( 'save_post_rp_lead', function ( $post_id ) use ( $rp_status_options ) {
	if ( ! isset( $_POST['rp_lead_follow_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rp_lead_follow_nonce'] ) ), 'rp_lead_follow' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$st = isset( $_POST['rp_status'] ) ? sanitize_key( wp_unslash( $_POST['rp_status'] ) ) : 'nuevo';
	update_post_meta( $post_id, 'rp_status', isset( $rp_status_options[ $st ] ) ? $st : 'nuevo' );
	update_post_meta( $post_id, 'rp_notes', sanitize_textarea_field( wp_unslash( isset( $_POST['rp_notes'] ) ? $_POST['rp_notes'] : '' ) ) );
} );

add_action( 'admin_post_rp_leads_export', function () use ( $rp_status_options ) {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rp_leads_export' ) ) {
		wp_die( 'No autorizado.', 'Error', array( 'response' => 403 ) );
	}
	$ids = get_posts( array(
		'post_type'      => 'rp_lead',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	$head = array( 'Fecha', 'Nombre', 'Teléfono', 'Correo', 'Estado (EE.UU.)', 'Producto', 'Regalo elegido', 'Seguimiento', 'Notas', 'Formulario', 'Página', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'Referido desde' );
	fputcsv( $out, $head, ',', '"', '\\' );
	$safe = function ( $v ) {
		$v = (string) $v;
		return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v;
	};
	foreach ( $ids as $id ) {
		$st   = get_post_meta( $id, 'rp_status', true );
		$st   = isset( $rp_status_options[ $st ] ) ? $st : 'nuevo';
		$name = get_post_meta( $id, 'nombre', true );
		$row  = array(
			get_the_date( 'Y-m-d H:i', $id ),
			$name ? $name : get_the_title( $id ),
			get_post_meta( $id, 'telefono', true ),
			get_post_meta( $id, 'email', true ),
			get_post_meta( $id, 'estado', true ),
			get_post_meta( $id, 'producto', true ),
			get_post_meta( $id, 'regalo', true ),
			$rp_status_options[ $st ],
			get_post_meta( $id, 'rp_notes', true ),
			get_post_meta( $id, 'variant', true ),
			get_post_meta( $id, 'page', true ),
			get_post_meta( $id, 'utm_source', true ),
			get_post_meta( $id, 'utm_medium', true ),
			get_post_meta( $id, 'utm_campaign', true ),
			get_post_meta( $id, 'utm_content', true ),
			get_post_meta( $id, 'utm_term', true ),
			get_post_meta( $id, 'referrer', true ),
		);
		fputcsv( $out, array_map( $safe, $row ), ',', '"', '\\' );
	}
	fclose( $out );
	exit;
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

			$estado_us = sanitize_text_field( isset( $d['estado'] ) ? $d['estado'] : '' );
			$id        = wp_insert_post( array(
				'post_type'    => 'rp_lead',
				'post_status'  => 'publish',
				'post_title'   => $nombre . ' — ' . $tel,
				'post_content' => trim( $email . ' ' . $estado_us ),
			) );
			if ( ! $id || is_wp_error( $id ) ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'save' ), 500 );
			}
			update_post_meta( $id, 'rp_status', 'nuevo' );

			$lines = array( 'Nombre: ' . $nombre );
			foreach ( $rp_lead_fields as $key => $label ) {
				if ( 'nombre' === $key ) {
					update_post_meta( $id, 'nombre', $nombre );
					continue;
				}
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
