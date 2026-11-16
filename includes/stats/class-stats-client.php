<?php

/**
 * Identité du site pour les statistiques et appels signés à l'API Chic Insights.
 *
 * Identité : un UUID aléatoire et une clé d'installation de 32 octets, générés sur le
 * site, stockés dans une option non chargée automatiquement et jamais affichés. Chaque
 * requête est signée en HMAC-SHA256 avec cette clé (format v1, voir INGEST.md).
 *
 * Adresses : constantes CHIC_CHECKOUT_STATS_*_URL ou filtre chic_checkout_stats_endpoints,
 * pour pouvoir changer d'hébergement sans modifier le code.
 *
 * @package ChicCheckout
 */
namespace ChicCheckout;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Stats_Client {
    const IDENTITY_OPTION = 'chic_checkout_stats_identity';

    const API_BASE = 'https://qttpbbtohzftyitewfnp.supabase.co/functions/v1/';

    const TIMEOUT = 5;

    /** @return array{ingest:string,site:string,benchmark:string} */
    public static function endpoints() : array {
        $endpoints = array(
            'ingest'    => ( defined( 'CHIC_CHECKOUT_STATS_INGEST_URL' ) ? (string) CHIC_CHECKOUT_STATS_INGEST_URL : self::API_BASE . 'ingest' ),
            'site'      => ( defined( 'CHIC_CHECKOUT_STATS_SITE_URL' ) ? (string) CHIC_CHECKOUT_STATS_SITE_URL : self::API_BASE . 'site' ),
            'benchmark' => ( defined( 'CHIC_CHECKOUT_STATS_BENCHMARK_URL' ) ? (string) CHIC_CHECKOUT_STATS_BENCHMARK_URL : self::API_BASE . 'benchmark' ),
        );
        return (array) apply_filters( 'chic_checkout_stats_endpoints', $endpoints ) + $endpoints;
    }

    /**
     * Identité du site, créée au premier besoin.
     *
     * @return array{site_id:string,install_key:string,registered:bool}
     */
    public static function identity() : array {
        $identity = get_option( self::IDENTITY_OPTION );
        if ( is_array( $identity ) && !empty( $identity['site_id'] ) && !empty( $identity['install_key'] ) ) {
            return $identity + array(
                'registered' => false,
            );
        }
        $identity = array(
            'site_id'     => wp_generate_uuid4(),
            'install_key' => rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' ),
            'registered'  => false,
        );
        update_option( self::IDENTITY_OPTION, $identity, false );
        return $identity;
    }

    public static function has_identity() : bool {
        $identity = get_option( self::IDENTITY_OPTION );
        return is_array( $identity ) && !empty( $identity['site_id'] );
    }

    public static function mark_registered() : void {
        $identity = self::identity();
        $identity['registered'] = true;
        update_option( self::IDENTITY_OPTION, $identity, false );
    }

    /** Retrait du consentement, 410 ou désinstallation : une nouvelle adhésion repartira d'une nouvelle identité. */
    public static function forget_identity() : void {
        delete_option( self::IDENTITY_OPTION );
    }

    /**
     * Requête signée. Ne lève jamais d'exception : en cas d'échec réseau, status = 0.
     *
     * @return array{status:int,body:array}
     */
    public static function request( string $method, string $url, string $body = '' ) : array {
        $identity = self::identity();
        $timestamp = (string) time();
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        $query = (string) wp_parse_url( $url, PHP_URL_QUERY );
        $signed = implode( "\n", array(
            'v1',
            $timestamp,
            strtoupper( $method ),
            $path . (( '' !== $query ? '?' . $query : '' )),
            hash( 'sha256', $body )
        ) );
        $response = wp_remote_request( $url, array(
            'method'      => strtoupper( $method ),
            'timeout'     => self::TIMEOUT,
            'redirection' => 0,
            'headers'     => array(
                'Content-Type'     => 'application/json',
                'X-Chic-Site'      => $identity['site_id'],
                'X-Chic-Timestamp' => $timestamp,
                'X-Chic-Signature' => 'v1=' . hash_hmac( 'sha256', $signed, $identity['install_key'] ),
            ),
            'body'        => ( '' === $body ? null : $body ),
        ) );
        if ( is_wp_error( $response ) ) {
            return array(
                'status' => 0,
                'body'   => array(),
            );
        }
        $decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
        return array(
            'status' => (int) wp_remote_retrieve_response_code( $response ),
            'body'   => ( is_array( $decoded ) ? $decoded : array() ),
        );
    }

}
