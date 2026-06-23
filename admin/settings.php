<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
    add_menu_page(
        'SM Agent Kit Settings',
        'SM Agent Kit',
        'manage_options',
        'smak-settings',
        'smak_render_settings',
        'dashicons-superhero',
        79
    );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'toplevel_page_smak-settings' ) return;
    wp_enqueue_style(
        'smak-admin',
        SMAK_URL . 'assets/admin.css',
        array(),
        SMAK_VERSION
    );
} );

add_action( 'admin_init', function () {
    register_setting(
        'smak_settings_group',
        'smak_settings',
        array( 'sanitize_callback' => 'smak_sanitize_settings' )
    );
} );

function smak_sanitize_settings( $input ) {
    if ( ! is_array( $input ) ) return array();

    $clean = array();

    $clean['markdown_enabled'] = ! empty( $input['markdown_enabled'] ) ? 1 : 0;
    $clean['signals_enabled']  = ! empty( $input['signals_enabled'] )  ? 1 : 0;
    $clean['commerce_enabled'] = ! empty( $input['commerce_enabled'] ) ? 1 : 0;

    $allowed_status = array( 'none', 'catalog_only', 'pending' );
    $submitted_status = isset( $input['transactable_status'] ) ? $input['transactable_status'] : 'none';
    $clean['transactable_status'] = in_array( $submitted_status, $allowed_status, true ) ? $submitted_status : 'none';

    $allowed_signal = array( 'yes', 'no' );
    $clean['ai_train'] = in_array( isset( $input['ai_train'] ) ? $input['ai_train'] : '', $allowed_signal, true ) ? $input['ai_train'] : 'no';
    $clean['search']   = in_array( isset( $input['search'] )   ? $input['search']   : '', $allowed_signal, true ) ? $input['search']   : 'yes';
    $clean['ai_input'] = in_array( isset( $input['ai_input'] ) ? $input['ai_input'] : '', $allowed_signal, true ) ? $input['ai_input'] : 'no';

    $clean['api_extra_endpoint'] = esc_url_raw( isset( $input['api_extra_endpoint'] ) ? $input['api_extra_endpoint'] : '' );
    $clean['mcp_version']        = sanitize_text_field( isset( $input['mcp_version'] ) ? $input['mcp_version'] : '' );
    $clean['mcp_contact']        = sanitize_email( isset( $input['mcp_contact'] ) ? $input['mcp_contact'] : '' );

    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    $clean['skills_descriptions'] = array();
    $clean['skills_included']     = array();

    foreach ( $post_types as $pt ) {
        if ( $pt->name === 'attachment' ) continue;
        $clean['skills_descriptions'][ $pt->name ] = sanitize_text_field( isset( $input['skills_descriptions'][ $pt->name ] ) ? $input['skills_descriptions'][ $pt->name ] : '' );
        $clean['skills_included'][ $pt->name ]     = ! empty( $input['skills_included'][ $pt->name ] ) ? 1 : 0;
    }

    add_settings_error(
        'smak_settings',
        'smak_saved',
        'Settings saved. To activate changes to the well-known endpoints, <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">open Permalink Settings</a> and click Save Changes.',
        'success'
    );

    return $clean;
}

function smak_render_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to access this page.' );
    }

    $opts       = get_option( 'smak_settings', array() );
    $site_url   = get_site_url();
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    $site_name  = get_bloginfo( 'name' );

    $markdown_on = ! empty( $opts['markdown_enabled'] );
    $signals_on  = ! empty( $opts['signals_enabled'] );
    $commerce_on = ! empty( $opts['commerce_enabled'] );
    $transactable_status = isset( $opts['transactable_status'] ) ? $opts['transactable_status'] : 'none';
    $ai_train    = isset( $opts['ai_train'] ) ? $opts['ai_train'] : 'no';
    $search      = isset( $opts['search'] )   ? $opts['search']   : 'yes';
    $ai_input    = isset( $opts['ai_input'] ) ? $opts['ai_input'] : 'no';
    ?>
    <div class="wrap smak-wrap">

        <div class="smak-header">
            <p class="smak-version">SM Agent Kit &middot; v<?php echo SMAK_VERSION; ?></p>
            <h1>Agent Readiness Settings</h1>
        </div>

        <?php settings_errors( 'smak_settings' ); ?>

        <div class="smak-status-row">
            <div class="smak-status-card">
                <p class="smak-status-label">Markdown negotiation</p>
                <p class="smak-status-value <?php echo $markdown_on ? 'active' : 'inactive'; ?>">
                    <?php echo $markdown_on ? '&#10003; Active' : '&#8212; Inactive'; ?>
                </p>
            </div>
            <div class="smak-status-card">
                <p class="smak-status-label">Content signals</p>
                <p class="smak-status-value <?php echo $signals_on ? 'active' : 'inactive'; ?>">
                    <?php echo $signals_on ? '&#10003; Active' : '&#8212; Inactive'; ?>
                </p>
            </div>
            <div class="smak-status-card">
                <p class="smak-status-label">Well-known endpoints</p>
                <p class="smak-status-value active">&#10003; 6 active</p>
            </div>
            <div class="smak-status-card">
                <p class="smak-status-label">Agentic commerce</p>
                <p class="smak-status-value <?php echo class_exists( 'WooCommerce' ) ? ( $commerce_on ? 'active' : 'inactive' ) : 'inactive'; ?>">
                    <?php
                    if ( ! class_exists( 'WooCommerce' ) ) {
                        echo '&#8212; No WooCommerce';
                    } else {
                        echo $commerce_on ? '&#10003; Active' : '&#8212; Inactive';
                    }
                    ?>
                </p>
            </div>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields( 'smak_settings_group' ); ?>

            <div class="smak-section">
                <div class="smak-section-header">
                    <h2>Content signals</h2>
                    <label class="smak-toggle">
                        <input type="checkbox" name="smak_settings[signals_enabled]" value="1" <?php checked( $signals_on ); ?>>
                        Enabled
                        <span class="smak-tip" title="When enabled, adds a Content-Signal directive to robots.txt declaring your AI usage preferences to crawlers and agents.">?</span>
                    </label>
                </div>
                <p class="smak-desc">Written to <code>robots.txt</code> to declare your AI content usage preferences.</p>
                <div class="smak-grid-3">
                    <div>
                        <label>ai-train <span class="smak-tip" title="Whether AI companies may use your content to train their models.">?</span></label>
                        <select name="smak_settings[ai_train]">
                            <option value="no"  <?php selected( $ai_train, 'no' );  ?>>no</option>
                            <option value="yes" <?php selected( $ai_train, 'yes' ); ?>>yes</option>
                        </select>
                    </div>
                    <div>
                        <label>search <span class="smak-tip" title="Whether search engines and AI search tools may index and surface your content.">?</span></label>
                        <select name="smak_settings[search]">
                            <option value="yes" <?php selected( $search, 'yes' ); ?>>yes</option>
                            <option value="no"  <?php selected( $search, 'no' );  ?>>no</option>
                        </select>
                    </div>
                    <div>
                        <label>ai-input <span class="smak-tip" title="Whether AI agents may use your content as direct input when processing tasks for users.">?</span></label>
                        <select name="smak_settings[ai_input]">
                            <option value="no"  <?php selected( $ai_input, 'no' );  ?>>no</option>
                            <option value="yes" <?php selected( $ai_input, 'yes' ); ?>>yes</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="smak-section">
                <div class="smak-section-header">
                    <h2>API catalog <span class="smak-badge">auto-generated</span></h2>
                    <a href="<?php echo esc_url( $site_url . '/.well-known/api-catalog' ); ?>" target="_blank" class="smak-preview-link">Preview output &#8599;</a>
                </div>
                <p class="smak-desc">Served at <code>/.well-known/api-catalog</code> &middot; Built from your REST API and sitemap automatically.</p>
                <label>
                    Additional endpoint <span class="smak-optional">(optional)</span>
                    <span class="smak-tip" title="An additional URL to include in the API catalog, such as an RSS feed or custom endpoint. Leave blank if not needed.">?</span>
                </label>
                <div class="smak-label-gap"></div>
                <input type="url"
                    name="smak_settings[api_extra_endpoint]"
                    value="<?php echo esc_attr( isset( $opts['api_extra_endpoint'] ) ? $opts['api_extra_endpoint'] : '' ); ?>"
                    placeholder="<?php echo esc_attr( $site_url ); ?>/feed/"
                    class="smak-input-wide">
            </div>

            <div class="smak-section">
                <div class="smak-section-header">
                    <h2>MCP server card <span class="smak-badge">auto-generated</span></h2>
                    <a href="<?php echo esc_url( $site_url . '/.well-known/mcp/server-card.json' ); ?>" target="_blank" class="smak-preview-link">Preview output &#8599;</a>
                </div>
                <p class="smak-desc">Served at <code>/.well-known/mcp/server-card.json</code> &middot; Reads site name and tagline from WordPress settings.</p>
                <div class="smak-grid-2">
                    <div>
                        <label>Version override <span class="smak-optional">(optional)</span> <span class="smak-tip" title="Overrides the version string in the MCP server card. Defaults to 1.0.0 if left blank.">?</span></label>
                        <input type="text"
                            name="smak_settings[mcp_version]"
                            value="<?php echo esc_attr( isset( $opts['mcp_version'] ) ? $opts['mcp_version'] : '' ); ?>"
                            placeholder="1.0.0">
                    </div>
                    <div>
                        <label>Contact email <span class="smak-optional">(optional &mdash; omitted if blank)</span> <span class="smak-tip" title="A contact email included in the MCP server card. Leave blank to omit it from the output entirely.">?</span></label>
                        <input type="email"
                            name="smak_settings[mcp_contact]"
                            value="<?php echo esc_attr( isset( $opts['mcp_contact'] ) ? $opts['mcp_contact'] : '' ); ?>"
                            placeholder="Leave blank to omit">
                    </div>
                </div>
            </div>

            <div class="smak-section">
                <div class="smak-section-header">
                    <h2>Agent skills index</h2>
                    <a href="<?php echo esc_url( $site_url . '/.well-known/agent-skills/index.json' ); ?>" target="_blank" class="smak-preview-link">Preview output &#8599;</a>
                </div>
                <p class="smak-desc">Served at <code>/.well-known/agent-skills/index.json</code> &middot; Post types auto-detected. Override descriptions below.</p>
                <table class="smak-skills-table">
                    <thead>
                        <tr>
                            <th>Post type</th>
                            <th>Description override <span class="smak-tip" title="Optional custom description. If left blank, a description generates automatically from the post type name.">?</span></th>
                            <th>Include <span class="smak-tip" title="Uncheck to exclude this post type from the agent skills index entirely.">?</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $post_types as $pt ) :
                        if ( $pt->name === 'attachment' ) continue;
                        $desc     = isset( $opts['skills_descriptions'][ $pt->name ] ) ? $opts['skills_descriptions'][ $pt->name ] : '';
                        $included = isset( $opts['skills_included'][ $pt->name ] ) ? $opts['skills_included'][ $pt->name ] : 1;
                        $auto     = 'Retrieve ' . strtolower( $pt->label ) . ' from ' . $site_name;
                    ?>
                        <tr>
                            <td><?php echo esc_html( $pt->label ); ?></td>
                            <td>
                                <input type="text"
                                    name="smak_settings[skills_descriptions][<?php echo esc_attr( $pt->name ); ?>]"
                                    value="<?php echo esc_attr( $desc ); ?>"
                                    placeholder="Auto: <?php echo esc_attr( $auto ); ?>"
                                    class="smak-input-wide">
                            </td>
                            <td class="smak-center">
                                <input type="checkbox"
                                    name="smak_settings[skills_included][<?php echo esc_attr( $pt->name ); ?>]"
                                    value="1" <?php checked( (int) $included, 1 ); ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="smak-section">
                <div class="smak-section-header">
                    <h2>Agentic commerce <span class="smak-badge">auto-generated</span></h2>
                    <a href="<?php echo esc_url( $site_url . '/.well-known/agentic-commerce/product-feed.json' ); ?>" target="_blank" class="smak-preview-link">Preview feed &#8599;</a>
                </div>
                <p class="smak-desc">Served at <code>/.well-known/agentic-commerce/product-feed.json</code> &middot; Read-only product feed for agent and AI shopping tools. Built from WooCommerce data, no checkout or payment access.</p>
                <?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
                    <p class="smak-desc"><em>Not applicable &mdash; WooCommerce is not active on this site. This section will activate automatically if WooCommerce is installed later.</em></p>
                <?php else : ?>
                    <label class="smak-toggle">
                        <input type="checkbox" name="smak_settings[commerce_enabled]" value="1" <?php checked( $commerce_on ); ?>>
                        Enabled
                        <span class="smak-tip" title="When enabled, publishes a read-only feed of your published WooCommerce products (name, price, availability, SKU, URL) for agents to discover. No transaction or checkout capability is exposed.">?</span>
                    </label>
                <?php endif; ?>
            </div>

            <div class="smak-section">
                <div class="smak-section-header">
                    <h2>Protocol manifests <span class="smak-badge">auto-generated</span></h2>
                </div>
                <p class="smak-desc">Declares your commerce capabilities to AI shopping agents per the UCP and ACP open standards. These are discovery files only &mdash; no payment processing happens through this plugin.</p>
                <div class="smak-grid-2">
                    <div>
                        <label>UCP manifest</label>
                        <a href="<?php echo esc_url( $site_url . '/.well-known/ucp/manifest.json' ); ?>" target="_blank" class="smak-preview-link">/.well-known/ucp/manifest.json &#8599;</a>
                    </div>
                    <div>
                        <label>ACP manifest</label>
                        <a href="<?php echo esc_url( $site_url . '/.well-known/acp/manifest.json' ); ?>" target="_blank" class="smak-preview-link">/.well-known/acp/manifest.json &#8599;</a>
                    </div>
                </div>
                <div class="smak-label-gap"></div>
                <label>
                    Checkout status
                    <span class="smak-tip" title="What your manifests tell agents about checkout. 'No checkout' means catalog only. 'Catalog ready' signals your product data is reliable for agents to browse. 'Integration pending' signals checkout is coming soon. Live checkout requires a payment provider integration that isn't included in this plugin yet.">?</span>
                </label>
                <div class="smak-label-gap"></div>
                <select name="smak_settings[transactable_status]">
                    <option value="none"         <?php selected( $transactable_status, 'none' ); ?>>No checkout &mdash; catalog discovery only</option>
                    <option value="catalog_only" <?php selected( $transactable_status, 'catalog_only' ); ?>>Catalog ready &mdash; checkout not yet offered</option>
                    <option value="pending"      <?php selected( $transactable_status, 'pending' ); ?>>Integration pending &mdash; checkout coming soon</option>
                </select>
                <p class="smak-desc smak-note-spaced"><em>Live agent checkout requires a payment provider integration (e.g. Stripe's ACP tooling) that this plugin does not implement. The checkout endpoint currently returns a clear "not implemented" response regardless of this setting, so no payment data can be submitted through it.</em></p>
            </div>

            <div class="smak-section">
                <div class="smak-section-header">
                    <h2>Markdown negotiation</h2>
                    <label class="smak-toggle">
                        <input type="checkbox" name="smak_settings[markdown_enabled]" value="1" <?php checked( $markdown_on ); ?>>
                        Enabled
                        <span class="smak-tip" title="When enabled, pages and posts return plain markdown instead of HTML when an AI agent requests them with Accept: text/markdown.">?</span>
                    </label>
                </div>
                <p class="smak-desc">Returns <code>text/markdown</code> with <code>x-markdown-tokens: 1</code> when agents request it. No further configuration needed.</p>
            </div>

            <div class="smak-footer">
                <?php submit_button( 'Save changes', 'primary', 'submit', false ); ?>
            </div>

        </form>
    </div>
    <?php
}
