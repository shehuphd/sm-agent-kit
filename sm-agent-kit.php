<?php
/**
 * Plugin Name: SM Agent Kit
 * Description: Agent-readiness toolkit for WordPress — markdown negotiation, content signals, and well-known endpoints.
 * Version: 1.8
 * Author: Mo Shehu
 * Author URI: https://mohammedshehu.com
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: sm-agent-kit
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SMAK_VERSION', '1.8' );
define( 'SMAK_PATH', plugin_dir_path( __FILE__ ) );
define( 'SMAK_URL', plugin_dir_url( __FILE__ ) );

// Load admin settings page only in admin context
if ( is_admin() ) {
    $smak_settings_file = SMAK_PATH . 'admin/settings.php';
    if ( file_exists( $smak_settings_file ) ) {
        require_once $smak_settings_file;
    } else {
        add_action( 'admin_notices', function () {
            echo wp_kses_post( '<div class="notice notice-error"><p><strong>SM Agent Kit:</strong> settings.php not found. Please reinstall the plugin.</p></div>' );
        } );
    }
}

// Helper: get a saved option with a default fallback
function smak_opt( $key, $default = '' ) {
    $opts = get_option( 'smak_settings', array() );
    return isset( $opts[ $key ] ) ? $opts[ $key ] : $default;
}

// ─── Markdown negotiation ─────────────────────────────────────────────────────

add_action( 'template_redirect', function () {
    if ( ! smak_opt( 'markdown_enabled', 0 ) ) return;

    $accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
    if ( strpos( $accept, 'text/markdown' ) === false ) return;
    if ( ! is_singular() ) return;

    $post = get_queried_object();
    if ( ! $post instanceof WP_Post ) return;

    header( 'Content-Type: text/markdown; charset=utf-8' );
    header( 'x-markdown-tokens: 1' );
    echo '# ' . esc_html( $post->post_title ) . "\n\n";
    echo esc_html( wp_strip_all_tags( $post->post_content ) );
    exit;
} );

// ─── Content signals in robots.txt ───────────────────────────────────────────

add_action( 'do_robotstxt', function () {
    if ( ! smak_opt( 'signals_enabled', 0 ) ) return;

    $train  = smak_opt( 'ai_train', 'no' );
    $search = smak_opt( 'search',   'yes' );
    $input  = smak_opt( 'ai_input', 'no' );

    echo 'Content-Signal: ai-train=' . esc_attr( $train ) . ', search=' . esc_attr( $search ) . ', ai-input=' . esc_attr( $input ) . "\n";
} );

// ─── Rewrite rules for well-known endpoints ───────────────────────────────────

add_action( 'init', function () {
    add_rewrite_rule( '^\.well-known/api-catalog$',                          'index.php?smak_well_known=api-catalog',    'top' );
    add_rewrite_rule( '^\.well-known/mcp/server-card\.json$',               'index.php?smak_well_known=mcp-server-card', 'top' );
    add_rewrite_rule( '^\.well-known/agent-skills/index\.json$',            'index.php?smak_well_known=agent-skills',    'top' );
    add_rewrite_rule( '^\.well-known/agentic-commerce/product-feed\.json$', 'index.php?smak_well_known=product-feed',    'top' );
    add_rewrite_rule( '^\.well-known/ucp/manifest\.json$',                  'index.php?smak_well_known=ucp-manifest',    'top' );
    add_rewrite_rule( '^\.well-known/acp/manifest\.json$',                  'index.php?smak_well_known=acp-manifest',    'top' );
    add_rewrite_rule( '^acp/checkout_sessions$',                            'index.php?smak_well_known=acp-checkout',    'top' );
} );

add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'smak_well_known';
    return $vars;
} );

// ─── Well-known endpoint responses ───────────────────────────────────────────

add_action( 'template_redirect', function () {
    $endpoint = get_query_var( 'smak_well_known' );
    if ( ! $endpoint ) return;

    $site_name   = get_bloginfo( 'name' );
    $site_desc   = get_bloginfo( 'description' );
    $site_url    = get_site_url();
    $rest_url    = get_rest_url();
    $sitemap_url = $site_url . '/sitemap.xml';

    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Access-Control-Allow-Origin: *' );

    if ( $endpoint === 'api-catalog' ) {

        $items = array(
            array( 'href' => $rest_url . 'wp/v2/posts', 'type' => 'application/json' ),
            array( 'href' => $rest_url . 'wp/v2/pages', 'type' => 'application/json' ),
            array( 'href' => $sitemap_url,              'type' => 'application/xml'  ),
        );

        if ( class_exists( 'WooCommerce' ) && smak_opt( 'commerce_enabled', 0 ) ) {
            $items[] = array(
                'href' => $site_url . '/.well-known/agentic-commerce/product-feed.json',
                'type' => 'application/json',
            );
        }

        $extra_endpoints = smak_opt( 'api_extra_endpoints', array() );
        if ( ! is_array( $extra_endpoints ) ) {
            $extra_endpoints = array_filter( array( $extra_endpoints ) );
        }
        foreach ( $extra_endpoints as $extra ) {
            if ( $extra ) {
                $items[] = array( 'href' => esc_url_raw( $extra ), 'type' => 'application/json' );
            }
        }

        echo wp_json_encode( array(
            'linkset' => array( array(
                'anchor'      => $site_url,
                'service-doc' => array( array( 'href' => $rest_url ) ),
                'item'        => $items,
            ) ),
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    } elseif ( $endpoint === 'mcp-server-card' ) {

        $version = smak_opt( 'mcp_version', '' );
        $card = array(
            'schema'      => 'https://modelcontextprotocol.io/schemas/server-card/v1',
            'serverInfo'  => array(
                'name'    => $site_name,
                'version' => $version ? $version : '1.0.0',
            ),
            'description' => $site_desc,
            'transport'   => array( 'type' => 'http', 'endpoint' => $rest_url ),
            'capabilities' => array(
                'resources' => true,
                'tools'     => false,
                'prompts'   => false,
            ),
        );

        $contact = smak_opt( 'mcp_contact', '' );
        if ( $contact ) {
            $card['contact'] = sanitize_email( $contact );
        }

        echo wp_json_encode( $card, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    } elseif ( $endpoint === 'agent-skills' ) {

        $post_types     = get_post_types( array( 'public' => true ), 'objects' );
        $excluded       = array( 'attachment' );
        $skills         = array();
        $overrides      = smak_opt( 'skills_descriptions', array() );
        $included_types = smak_opt( 'skills_included', array() );

        foreach ( $post_types as $pt ) {
            if ( in_array( $pt->name, $excluded, true ) ) continue;
            if ( isset( $included_types[ $pt->name ] ) && ! $included_types[ $pt->name ] ) continue;

            $desc = ( ! empty( $overrides[ $pt->name ] ) )
                ? $overrides[ $pt->name ]
                : 'Retrieve ' . strtolower( $pt->label ) . ' from ' . $site_name;

            $skills[] = array(
                'name'        => $pt->label,
                'type'        => 'content-retrieval',
                'description' => $desc,
                'url'         => $rest_url . 'wp/v2/' . $pt->name,
            );
        }

        echo wp_json_encode( array(
            '$schema' => 'https://agentskills.io/schema/v0.2.0',
            'name'    => $site_name,
            'skills'  => $skills,
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    } elseif ( $endpoint === 'product-feed' ) {

        if ( ! smak_opt( 'commerce_enabled', 0 ) || ! class_exists( 'WooCommerce' ) ) {
            echo wp_json_encode( array(
                'name'     => $site_name,
                'products' => array(),
                'note'     => 'No commerce data available for this site.',
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
            exit;
        }

        $currency = get_woocommerce_currency();
        $products = array();

        $wc_products = wc_get_products( array(
            'status' => 'publish',
            'limit'  => -1,
        ) );

        foreach ( $wc_products as $product ) {
            $products[] = array(
                'id'           => $product->get_id(),
                'sku'          => $product->get_sku(),
                'name'         => $product->get_name(),
                'price'        => $product->get_price(),
                'currency'     => $currency,
                'availability' => $product->is_in_stock() ? 'in_stock' : 'out_of_stock',
                'url'          => get_permalink( $product->get_id() ),
            );
        }

        echo wp_json_encode( array(
            'schema'   => 'https://agenticcommerce.dev/schema/product-feed/v1',
            'name'     => $site_name,
            'currency' => $currency,
            'products' => $products,
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    } elseif ( $endpoint === 'ucp-manifest' ) {

        $status = smak_opt( 'transactable_status', 'none' );

        echo wp_json_encode( array(
            'name'         => $site_name,
            'description'  => $site_desc,
            'capabilities' => array(
                'catalog'   => class_exists( 'WooCommerce' ) && smak_opt( 'commerce_enabled', 0 ),
                'checkout'  => $status === 'live',
            ),
            'status'       => $status,
            'productFeed'  => $site_url . '/.well-known/agentic-commerce/product-feed.json',
            'checkout'     => $status === 'live' ? $site_url . '/acp/checkout_sessions' : null,
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    } elseif ( $endpoint === 'acp-manifest' ) {

        $status = smak_opt( 'transactable_status', 'none' );

        echo wp_json_encode( array(
            'protocol_version' => '2025-09-29',
            'name'             => $site_name,
            'status'           => $status,
            'namespaces'       => array(
                'catalog'  => class_exists( 'WooCommerce' ) && smak_opt( 'commerce_enabled', 0 ) ? 'active' : 'inactive',
                'checkout' => $status === 'live' ? 'active' : 'not_implemented',
                'identity' => 'not_implemented',
            ),
            'endpoints'        => array(
                'product_feed' => $site_url . '/.well-known/agentic-commerce/product-feed.json',
                'checkout'     => $site_url . '/acp/checkout_sessions',
            ),
            'note'             => $status === 'live'
                ? 'Checkout namespace active.'
                : 'Checkout namespace is not yet active for live transactions. This site currently supports catalog discovery only.',
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    } elseif ( $endpoint === 'acp-checkout' ) {

        $status = smak_opt( 'transactable_status', 'none' );

        if ( $status !== 'live' ) {
            status_header( 501 );
            echo wp_json_encode( array(
                'error'   => 'not_implemented',
                'status'  => $status,
                'message' => 'This site does not currently accept agent-initiated checkout. Catalog data is available at the product feed endpoint.',
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
            exit;
        }

        // Live checkout processing is not yet implemented in this plugin.
        // When ready, this is where session creation, payment token handling,
        // and order confirmation would be wired to your payment service provider.
        status_header( 501 );
        echo wp_json_encode( array(
            'error'   => 'not_implemented',
            'message' => 'Live checkout is marked active but no payment provider is wired up yet.',
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

    exit;
} );

// ─── Flush rewrite rules on activation ───────────────────────────────────────

register_activation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );
