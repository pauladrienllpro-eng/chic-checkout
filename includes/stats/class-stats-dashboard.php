<?php
/**
 * Briques d'affichage de l'écran Performances, communes aux versions gratuite et Pro :
 * période choisie (?chic-period=7|30|90|365 ou dates), cartes d'indicateurs avec bulle
 * d'explication « i », courbes en SVG (dessinées côté serveur, aucune bibliothèque),
 * icônes et chargement des styles et du script de la page (uniquement sur cet écran).
 *
 * Tout est calculé sur les compteurs agrégés du site (Stats_Store) : une requête par
 * période, sans appel externe. L'animation (apparition des cartes, tracé des courbes)
 * est en CSS et désactivée si le système demande moins d'animations.
 *
 * @package ChicCheckout
 */

namespace ChicCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stats_Dashboard {

	/** Périodes proposées (jours complets, jusqu'à hier). 365 : 12 mois, dans la limite de Stats_Sender::KEEP_DAYS. */
	const PERIODS = array( 7, 30, 90, 365 );

	const DEFAULT_DAYS = 30;

	/** Au-delà, la courbe passe en points hebdomadaires (lisibilité). */
	const DAILY_MAX_DAYS = 92;

	/** Repère de la courbe : viewBox 0 0 1000 CHART_H, étirée à la largeur de la carte. */
	const CHART_H = 300;

	/**
	 * Période demandée. Les dates personnalisées sont bornées à la conservation locale.
	 *
	 * @return array{from:string,to:string,days:int,key:string,prev_from:string,prev_to:string,comparable:bool}
	 */
	public static function period(): array {
		$yesterday = wp_date( 'Y-m-d', strtotime( Stats_Store::today() . ' -1 day' ) );
		$oldest    = wp_date( 'Y-m-d', strtotime( Stats_Store::today() . ' -' . Stats_Sender::KEEP_DAYS . ' days' ) );
		// Lecture seule de paramètres d'affichage : pas de nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$from = isset( $_GET['chic-from'] ) ? sanitize_text_field( wp_unslash( $_GET['chic-from'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$to = isset( $_GET['chic-to'] ) ? sanitize_text_field( wp_unslash( $_GET['chic-to'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$days = isset( $_GET['chic-period'] ) ? absint( $_GET['chic-period'] ) : self::DEFAULT_DAYS;

		$is_date = static fn( string $date ) => (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && strtotime( $date );
		if ( $is_date( $from ) && $is_date( $to ) ) {
			$from = max( $oldest, min( $from, $yesterday ) );
			$to   = max( $from, min( $to, $yesterday ) );
			$key  = 'custom';
			$days = (int) round( ( strtotime( $to ) - strtotime( $from ) ) / DAY_IN_SECONDS ) + 1;
		} else {
			$days = in_array( $days, self::PERIODS, true ) ? $days : self::DEFAULT_DAYS;
			$key  = (string) $days;
			$to   = $yesterday;
			$from = wp_date( 'Y-m-d', strtotime( $to . ' -' . ( $days - 1 ) . ' days' ) );
		}

		$prev_to   = wp_date( 'Y-m-d', strtotime( $from . ' -1 day' ) );
		$prev_from = wp_date( 'Y-m-d', strtotime( $prev_to . ' -' . ( $days - 1 ) . ' days' ) );
		$first     = Stats_Store::first_day();

		return array(
			'from'       => $from,
			'to'         => $to,
			'days'       => $days,
			'key'        => $key,
			'prev_from'  => $prev_from,
			'prev_to'    => $prev_to,
			// Comparaison seulement si la période précédente est entièrement mesurée
			// (statistiques activées depuis assez longtemps, données encore conservées).
			'comparable' => null !== $first && $first <= $prev_from,
		);
	}

	public static function period_label( string $key ): string {
		$labels = array(
			'7'   => __( '7 jours', 'chic-checkout' ),
			'30'  => __( '30 jours', 'chic-checkout' ),
			'90'  => __( '90 jours', 'chic-checkout' ),
			'365' => __( '12 mois', 'chic-checkout' ),
		);
		return $labels[ $key ] ?? __( 'Personnalisée', 'chic-checkout' );
	}

	/**
	 * Sélecteur de période (liens) et dates personnalisées (formulaire GET).
	 *
	 * @param array<string,string> $keep Paramètres conservés d'une période à l'autre (ex. parcours).
	 */
	public static function render_period_nav( array $period, string $page_slug, array $keep = array() ): void {
		$base = array_merge( array( 'page' => $page_slug ), $keep );
		?>
		<div class="chic-perf__period">
			<nav class="chic-seg" aria-label="<?php esc_attr_e( 'Période', 'chic-checkout' ); ?>">
				<?php foreach ( self::PERIODS as $days ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $base, array( 'chic-period' => $days ) ), admin_url( 'admin.php' ) ) ); ?>"
						<?php echo (string) $days === $period['key'] ? 'aria-current="page"' : ''; ?>><?php echo esc_html( self::period_label( (string) $days ) ); ?></a>
				<?php endforeach; ?>
			</nav>
			<details class="chic-perf__custom" <?php echo 'custom' === $period['key'] ? 'open' : ''; ?>>
				<summary><?php self::icon( 'calendar' ); ?><?php esc_html_e( 'Dates personnalisées', 'chic-checkout' ); ?></summary>
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<?php foreach ( $base as $name => $value ) : ?>
						<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
					<?php endforeach; ?>
					<label><?php esc_html_e( 'Du', 'chic-checkout' ); ?> <input type="date" name="chic-from" value="<?php echo esc_attr( $period['from'] ); ?>" max="<?php echo esc_attr( $period['to'] ); ?>" required></label>
					<label><?php esc_html_e( 'au', 'chic-checkout' ); ?> <input type="date" name="chic-to" value="<?php echo esc_attr( $period['to'] ); ?>" required></label>
					<button type="submit" class="button"><?php esc_html_e( 'Afficher', 'chic-checkout' ); ?></button>
				</form>
			</details>
		</div>
		<p class="chic-perf__dates">
			<?php
			printf(
				/* translators: 1: date de début, 2: date de fin */
				esc_html__( 'Du %1$s au %2$s, jours complets, calculé sur ce site.', 'chic-checkout' ),
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $period['from'] ) ) ),
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $period['to'] ) ) )
			);
			?>
		</p>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Formats                                                            */
	/* ------------------------------------------------------------------ */

	public static function format( string $kind, ?float $value ): string {
		if ( null === $value ) {
			return '—';
		}
		if ( 'rate' === $kind ) {
			return number_format_i18n( $value * 100, 1 ) . ' %';
		}
		if ( 'count' === $kind ) {
			return number_format_i18n( $value );
		}
		$args = 'eur' === $kind ? array( 'currency' => 'EUR' ) : array();
		// Montants arrondis à l'unité au-delà de 1 000 (lisibilité des cartes et des axes).
		if ( abs( $value ) >= 1000 ) {
			$args['decimals'] = 0;
		}
		return wp_strip_all_tags( html_entity_decode( wc_price( $value, $args ) ) );
	}

	/**
	 * Évolution par rapport à la période précédente. Taux : écart en points ; montants et
	 * nombres : écart relatif. null si non comparable.
	 *
	 * @return array{text:string,tone:string,title:string}|null
	 */
	public static function delta( string $kind, ?float $current, ?float $previous, bool $lower_is_better = false ): ?array {
		if ( null === $current || null === $previous ) {
			return null;
		}
		if ( 'rate' === $kind ) {
			// Écart arrondi avant le signe : jamais de « −0,0 pt ».
			$diff = round( ( $current - $previous ) * 100, 1 );
			$text = ( $diff > 0 ? '+' : ( $diff < 0 ? '−' : '' ) ) . number_format_i18n( abs( $diff ), 1 ) . ' ' . __( 'pt', 'chic-checkout' );
		} else {
			if ( $previous <= 0 ) {
				return null;
			}
			$diff = round( ( $current - $previous ) / $previous * 100 );
			$text = ( $diff > 0 ? '+' : ( $diff < 0 ? '−' : '' ) ) . number_format_i18n( abs( $diff ), 0 ) . ' %';
		}
		$better = $lower_is_better ? $diff < 0 : $diff > 0;
		$tone   = 0.0 === (float) $diff ? 'flat' : ( $better ? 'good' : 'bad' );
		return array(
			'text'  => $text,
			'tone'  => $tone,
			/* translators: %s: valeur de la période précédente */
			'title' => sprintf( __( 'Période précédente : %s', 'chic-checkout' ), self::format( $kind, $previous ) ),
		);
	}

	/* ------------------------------------------------------------------ */
	/* Cartes et explications                                             */
	/* ------------------------------------------------------------------ */

	/** Bouton « i » et sa bulle (survol, focus clavier, ou toucher). */
	public static function render_info( string $text, string $label = '' ): void {
		static $n = 0;
		++$n;
		$id = 'chic-tip-' . $n;
		?>
		<span class="chic-info">
			<button type="button" class="chic-info__btn" aria-describedby="<?php echo esc_attr( $id ); ?>" aria-expanded="false">
				<?php self::icon( 'info' ); ?>
				<span class="screen-reader-text">
					<?php
					/* translators: %s: nom de l'indicateur */
					echo esc_html( '' !== $label ? sprintf( __( 'Explication : %s', 'chic-checkout' ), $label ) : __( 'Explication', 'chic-checkout' ) );
					?>
				</span>
			</button>
			<span class="chic-info__tip" role="tooltip" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $text ); ?></span>
		</span>
		<?php
	}

	/**
	 * Carte d'indicateur.
	 *
	 * @param array{icon?:string,label:string,info?:string,value:string,delta?:array|null,foot?:string,verdict?:array|null,tone?:string} $card
	 */
	public static function render_card( array $card, int $index = 0 ): void {
		$delta   = $card['delta'] ?? null;
		$verdict = $card['verdict'] ?? null;
		?>
		<div class="chic-card chic-anim-up<?php echo ! empty( $card['tone'] ) ? ' chic-card--' . esc_attr( $card['tone'] ) : ''; ?>" style="--i:<?php echo (int) $index; ?>">
			<div class="chic-card__head">
				<?php if ( ! empty( $card['icon'] ) ) : ?>
					<span class="chic-card__icon"><?php self::icon( $card['icon'] ); ?></span>
				<?php endif; ?>
				<span class="chic-card__label"><?php echo esc_html( $card['label'] ); ?></span>
				<?php
				if ( ! empty( $card['info'] ) ) {
					self::render_info( $card['info'], $card['label'] );
				}
				?>
			</div>
			<div class="chic-card__value"><?php echo esc_html( $card['value'] ); ?></div>
			<?php if ( $delta ) : ?>
				<div class="chic-card__delta chic-tone--<?php echo esc_attr( $delta['tone'] ); ?>" title="<?php echo esc_attr( $delta['title'] ); ?>">
					<?php self::icon( 'flat' === $delta['tone'] ? 'minus' : ( 0 === strpos( $delta['text'], '−' ) ? 'trend-down' : 'trend-up' ) ); ?>
					<span><?php echo esc_html( $delta['text'] ); ?></span>
					<span class="chic-card__muted"><?php esc_html_e( 'vs période précédente', 'chic-checkout' ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $card['foot'] ) || $verdict ) : ?>
				<div class="chic-card__foot">
					<?php if ( ! empty( $card['foot'] ) ) : ?>
						<span><?php echo esc_html( $card['foot'] ); ?></span>
					<?php endif; ?>
					<?php if ( $verdict ) : ?>
						<span class="chic-chip chic-tone--<?php echo esc_attr( $verdict['tone'] ); ?>"><?php echo esc_html( $verdict['text'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/** Titre de section avec explication facultative. */
	public static function render_heading( string $title, string $info = '', string $icon = '' ): void {
		?>
		<h2 class="chic-perf__h2">
			<?php
			if ( '' !== $icon ) {
				self::icon( $icon );
			}
			echo esc_html( $title );
			if ( '' !== $info ) {
				self::render_info( $info, $title );
			}
			?>
		</h2>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Courbes                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * Valeurs par jour (ou par semaine complète au-delà de DAILY_MAX_DAYS) d'une métrique,
	 * à partir de Stats_Store::daily_totals().
	 *
	 * @param callable $pick fn( array $row ): float|null — valeur de la ligne pour cette série, null pour l'ignorer.
	 * @return array<string, float> premier jour du point => valeur
	 */
	public static function buckets( array $period, array $rows, callable $pick ): array {
		$weekly = $period['days'] > self::DAILY_MAX_DAYS;
		$from   = $period['from'];
		if ( $weekly && 0 !== $period['days'] % 7 ) {
			// Semaines complètes, comptées depuis la fin : la semaine incomplète du début est
			// écartée de la courbe (elle ferait croire à une chute). Les totaux n'en dépendent pas.
			$from = wp_date( 'Y-m-d', strtotime( $period['from'] . ' +' . ( $period['days'] % 7 ) . ' days' ) );
		}
		$buckets = array();
		$cursor  = strtotime( $from );
		$end     = strtotime( $period['to'] );
		while ( $cursor <= $end ) {
			$buckets[ self::bucket_key( wp_date( 'Y-m-d', $cursor ), $from, $weekly ) ] = 0.0;
			$cursor = strtotime( '+1 day', $cursor );
		}
		foreach ( $rows as $row ) {
			$value = $pick( $row );
			if ( null === $value || $row['day'] < $from ) {
				continue;
			}
			$key = self::bucket_key( $row['day'], $from, $weekly );
			if ( isset( $buckets[ $key ] ) ) {
				$buckets[ $key ] += $value;
			}
		}
		return $buckets;
	}

	/** Semaines de 7 jours comptées depuis $from. */
	private static function bucket_key( string $day, string $from, bool $weekly ): string {
		if ( ! $weekly ) {
			return $day;
		}
		$offset = (int) floor( ( strtotime( $day ) - strtotime( $from ) ) / DAY_IN_SECONDS / 7 );
		return wp_date( 'Y-m-d', strtotime( $from . ' +' . ( $offset * 7 ) . ' days' ) );
	}

	/**
	 * Carte « Évolution » : une ou plusieurs séries, en onglets.
	 *
	 * @param array<int, array{id:string,label:string,kind:string,values:array<string,float>,total:string}> $series
	 */
	public static function render_chart( array $series, array $period, string $title, string $info = '' ): void {
		$weekly = $period['days'] > self::DAILY_MAX_DAYS;
		?>
		<section class="chic-panel chic-chart" data-chic-chart>
			<div class="chic-panel__head">
				<?php self::render_heading( $title, $info, 'chart' ); ?>
				<?php if ( count( $series ) > 1 ) : ?>
					<div class="chic-seg chic-seg--small" role="tablist" aria-label="<?php echo esc_attr( $title ); ?>">
						<?php foreach ( $series as $i => $serie ) : ?>
							<button type="button" role="tab" id="chic-tab-<?php echo esc_attr( $serie['id'] ); ?>" aria-controls="chic-pane-<?php echo esc_attr( $serie['id'] ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>"><?php echo esc_html( $serie['label'] ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php foreach ( $series as $i => $serie ) : ?>
				<div class="chic-chart__pane" role="tabpanel" id="chic-pane-<?php echo esc_attr( $serie['id'] ); ?>" aria-labelledby="chic-tab-<?php echo esc_attr( $serie['id'] ); ?>" <?php echo 0 === $i ? '' : 'hidden'; ?>>
					<p class="chic-chart__total">
						<span class="chic-chart__total-value"><?php echo esc_html( $serie['total'] ); ?></span>
						<span class="chic-card__muted"><?php echo esc_html( $weekly ? __( 'sur la période, point par semaine', 'chic-checkout' ) : __( 'sur la période, point par jour', 'chic-checkout' ) ); ?></span>
					</p>
					<?php self::render_curve( $serie, $weekly ); ?>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
	}

	private static function render_curve( array $serie, bool $weekly ): void {
		$values = array_values( $serie['values'] );
		$days   = array_keys( $serie['values'] );
		$count  = count( $values );
		$max    = self::nice_max( $values ? max( $values ) : 0 );
		$top    = 12;
		$height = self::CHART_H - $top;
		$points = array();
		foreach ( $values as $i => $value ) {
			$points[] = array(
				$count > 1 ? $i / ( $count - 1 ) * 1000 : 500,
				self::CHART_H - ( $max > 0 ? $value / $max * $height : 0 ),
			);
		}
		$line  = self::smooth_path( $points );
		$area  = $line ? $line . ' L1000,' . self::CHART_H . ' L0,' . self::CHART_H . ' Z' : '';
		$data  = array();
		$fmt   = get_option( 'date_format' );
		foreach ( $points as $i => $point ) {
			$label  = date_i18n( 'j M', strtotime( $days[ $i ] ) );
			$data[] = array(
				'x' => round( $point[0] / 10, 2 ),
				'y' => round( $point[1] / self::CHART_H * 100, 2 ),
				/* translators: %s: date du premier jour de la semaine */
				'd' => $weekly ? sprintf( __( 'Semaine du %s', 'chic-checkout' ), $label ) : date_i18n( $fmt, strtotime( $days[ $i ] ) ),
				'v' => self::format( $serie['kind'], $values[ $i ] ),
			);
		}
		$ticks = array( $max, $max / 2, 0 );
		$xlabs = array();
		$steps = min( 5, $count );
		for ( $k = 0; $k < $steps; $k++ ) {
			$i       = $steps > 1 ? (int) round( $k * ( $count - 1 ) / ( $steps - 1 ) ) : 0;
			$xlabs[] = array( $data[ $i ]['x'] ?? 0, date_i18n( 'j M', strtotime( $days[ $i ] ?? 'now' ) ) );
		}
		$total_label = $serie['label'];
		?>
		<div class="chic-curve">
			<div class="chic-curve__y" aria-hidden="true">
				<?php foreach ( $ticks as $tick ) : ?>
					<span><?php echo esc_html( self::format( $serie['kind'], $tick ) ); ?></span>
				<?php endforeach; ?>
			</div>
			<div class="chic-curve__plot" data-points="<?php echo esc_attr( (string) wp_json_encode( $data ) ); ?>">
				<div class="chic-curve__draw chic-anim-draw">
					<?php
					$svg  = '<svg viewBox="0 0 1000 ' . self::CHART_H . '" preserveAspectRatio="none" role="img" aria-label="' . esc_attr( $total_label ) . '">';
					$svg .= '<line x1="0" y1="' . $top . '" x2="1000" y2="' . $top . '" class="chic-curve__grid"/>';
					$svg .= '<line x1="0" y1="' . ( $top + $height / 2 ) . '" x2="1000" y2="' . ( $top + $height / 2 ) . '" class="chic-curve__grid"/>';
					$svg .= '<line x1="0" y1="' . self::CHART_H . '" x2="1000" y2="' . self::CHART_H . '" class="chic-curve__base"/>';
					if ( $area ) {
						$svg .= '<path d="' . esc_attr( $area ) . '" class="chic-curve__area"/>';
						$svg .= '<path d="' . esc_attr( $line ) . '" class="chic-curve__line"/>';
					}
					$svg .= '</svg>';
					echo wp_kses( $svg, self::svg_allowed() );
					?>
				</div>
				<div class="chic-curve__cursor" hidden><span class="chic-curve__dot"></span></div>
				<div class="chic-curve__tip" hidden></div>
			</div>
			<div class="chic-curve__x" aria-hidden="true">
				<?php foreach ( $xlabs as $xlab ) : ?>
					<span style="left:<?php echo esc_attr( (string) $xlab[0] ); ?>%"><?php echo esc_html( $xlab[1] ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/** Plafond « rond » de l'axe (1, 2 ou 5 × 10^n). */
	private static function nice_max( float $max ): float {
		if ( $max <= 0 ) {
			return 1.0;
		}
		$power = 10 ** floor( log10( $max ) );
		foreach ( array( 1, 2, 2.5, 5, 10 ) as $step ) {
			if ( $max <= $step * $power ) {
				return $step * $power;
			}
		}
		return 10 * $power;
	}

	/** Courbe lissée sans dépassement (cubique monotone de Fritsch-Carlson). */
	private static function smooth_path( array $p ): string {
		$n = count( $p );
		if ( 0 === $n ) {
			return '';
		}
		if ( 1 === $n ) {
			return sprintf( 'M0,%1$.1f L1000,%1$.1f', $p[0][1] );
		}
		$d = array();
		$m = array();
		for ( $i = 0; $i < $n - 1; $i++ ) {
			$d[ $i ] = ( $p[ $i + 1 ][1] - $p[ $i ][1] ) / ( $p[ $i + 1 ][0] - $p[ $i ][0] );
		}
		$m[0]       = $d[0];
		$m[ $n - 1 ] = $d[ $n - 2 ];
		for ( $i = 1; $i < $n - 1; $i++ ) {
			$m[ $i ] = $d[ $i - 1 ] * $d[ $i ] <= 0 ? 0 : ( $d[ $i - 1 ] + $d[ $i ] ) / 2;
		}
		for ( $i = 0; $i < $n - 1; $i++ ) {
			if ( 0.0 === (float) $d[ $i ] ) {
				$m[ $i ]     = 0;
				$m[ $i + 1 ] = 0;
				continue;
			}
			$a = $m[ $i ] / $d[ $i ];
			$b = $m[ $i + 1 ] / $d[ $i ];
			$s = $a * $a + $b * $b;
			if ( $s > 9 ) {
				$t           = 3 / sqrt( $s );
				$m[ $i ]     = $t * $a * $d[ $i ];
				$m[ $i + 1 ] = $t * $b * $d[ $i ];
			}
		}
		$path = sprintf( 'M%.1f,%.1f', $p[0][0], $p[0][1] );
		for ( $i = 0; $i < $n - 1; $i++ ) {
			$h     = ( $p[ $i + 1 ][0] - $p[ $i ][0] ) / 3;
			$path .= sprintf(
				' C%.1f,%.1f %.1f,%.1f %.1f,%.1f',
				$p[ $i ][0] + $h,
				$p[ $i ][1] + $m[ $i ] * $h,
				$p[ $i + 1 ][0] - $h,
				$p[ $i + 1 ][1] - $m[ $i + 1 ] * $h,
				$p[ $i + 1 ][0],
				$p[ $i + 1 ][1]
			);
		}
		return $path;
	}

	/* ------------------------------------------------------------------ */
	/* Barres                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Barres horizontales (répartition, comparaison).
	 *
	 * @param array<int, array{label:string,value:float,text:string,tone?:string}> $bars
	 */
	public static function render_bars( array $bars, float $max = 0 ): void {
		$max = $max > 0 ? $max : max( array_merge( array( 0.0 ), array_column( $bars, 'value' ) ) );
		?>
		<ul class="chic-bars">
			<?php foreach ( $bars as $i => $bar ) : ?>
				<li>
					<span class="chic-bars__label"><?php echo esc_html( $bar['label'] ); ?></span>
					<span class="chic-bars__track"><span class="chic-bars__fill chic-anim-grow<?php echo ! empty( $bar['tone'] ) ? ' chic-bars__fill--' . esc_attr( $bar['tone'] ) : ''; ?>" style="--w:<?php echo esc_attr( (string) round( $max > 0 ? $bar['value'] / $max * 100 : 0, 2 ) ); ?>%;--i:<?php echo (int) $i; ?>"></span></span>
					<span class="chic-bars__value"><?php echo esc_html( $bar['text'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Icônes (tracés Lucide, licence ISC) et ressources                  */
	/* ------------------------------------------------------------------ */

	public static function icon( string $name ): void {
		$paths = array(
			'info'       => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
			'money'      => '<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
			'bag'        => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
			'cart'       => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
			'target'     => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
			'check'      => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
			'card'       => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
			'zap'        => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
			'gift'       => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>',
			'login'      => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/>',
			'truck'      => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
			'alert'      => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
			'chart'      => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
			'funnel'     => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
			'calendar'   => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
			'lock'       => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
			'users'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
			'trend-up'   => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
			'trend-down' => '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>',
			'minus'      => '<path d="M5 12h14"/>',
			'arrow-down' => '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
		);
		if ( ! isset( $paths[ $name ] ) ) {
			return;
		}
		echo wp_kses( '<svg class="chic-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>', self::svg_allowed() );
	}

	private static function svg_allowed(): array {
		$shape = array(
			'd'               => true,
			'class'           => true,
			'cx'              => true,
			'cy'              => true,
			'r'               => true,
			'x'               => true,
			'y'               => true,
			'rx'              => true,
			'ry'              => true,
			'width'           => true,
			'height'          => true,
			'x1'              => true,
			'x2'              => true,
			'y1'              => true,
			'y2'              => true,
			'points'          => true,
		);
		return array(
			'svg'      => array(
				'class'               => true,
				'viewbox'             => true,
				'preserveaspectratio' => true,
				'fill'                => true,
				'stroke'              => true,
				'stroke-width'        => true,
				'stroke-linecap'      => true,
				'stroke-linejoin'     => true,
				'aria-hidden'         => true,
				'aria-label'          => true,
				'role'                => true,
				'focusable'           => true,
			),
			'path'     => $shape,
			'circle'   => $shape,
			'rect'     => $shape,
			'line'     => $shape,
			'polyline' => $shape,
			'polygon'  => $shape,
		);
	}

	/** Styles et script de l'écran Performances, sur cet écran uniquement. */
	public static function enqueue( string $hook, string $page_hook ): void {
		if ( $hook !== $page_hook ) {
			return;
		}
		wp_enqueue_style( 'chic-checkout-performance', CHIC_CHECKOUT_URL . 'assets/css/performance.css', array(), (string) filemtime( CHIC_CHECKOUT_DIR . 'assets/css/performance.css' ) );
		wp_enqueue_script( 'chic-checkout-performance', CHIC_CHECKOUT_URL . 'assets/js/performance.js', array(), (string) filemtime( CHIC_CHECKOUT_DIR . 'assets/js/performance.js' ), true );
	}
}
