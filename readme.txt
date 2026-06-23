=== SM Agent Kit ===
Contributors: shehuphd
Tags: ai, agents, mcp, well-known, markdown, robots, agentic, llm
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.8
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Make your WordPress site readable and discoverable by AI agents. Markdown negotiation, content signals, and well-known JSON endpoints with a clean admin UI.

== Description ==

AI agents don't browse the web the way humans do. They request structured data, negotiate content formats, and look for standardised discovery endpoints. SM Agent Kit adds all of this to any WordPress site automatically, with a clean admin UI and zero hardcoding.

= Markdown negotiation =

When an agent sends a request with Accept: text/markdown, your site returns the post or page as clean markdown instead of HTML, with a Content-Type: text/markdown header and x-markdown-tokens: 1. Human visitors never notice a thing.

= Content signals =

Adds a Content-Signal directive to your robots.txt declaring your preferences for AI training, search indexing, and agent input. Supports the ai-train, search, and ai-input fields per the emerging Content Signals standard.

= Well-known endpoints =

Serves four auto-generated JSON files that agent frameworks and crawlers look for:

* /.well-known/api-catalog - lists your REST API and sitemap endpoints (RFC 9727)
* /.well-known/mcp/server-card.json - declares MCP server capabilities (SEP-1649)
* /.well-known/agent-skills/index.json - lists what your site can do as an agent-callable service
* /.well-known/agentic-commerce/product-feed.json - read-only product feed for agent shopping tools, built from WooCommerce data

All four endpoints build dynamically from your WordPress data including site name, tagline, registered post types, REST API root, and product catalog with no hardcoding required. Install on multiple sites and each one generates correct, site-specific output automatically.

= Agentic commerce =

If WooCommerce is active, SM Agent Kit can publish a read-only feed of your published products: name, price, currency, availability, SKU, and URL. This makes your catalog discoverable to AI shopping agents without exposing any checkout or payment functionality. If WooCommerce is not installed, this section is automatically marked as not applicable.

The plugin also publishes UCP and ACP protocol manifests declaring your commerce capabilities to AI shopping agents, and a stubbed checkout endpoint that responds clearly when checkout is not available. No payment processing is implemented in this plugin. Live agent checkout requires a separate payment provider integration.

= Settings UI =

SM Agent Kit adds its own item to the WordPress admin sidebar. Every feature is togglable. Tooltips explain each field. A permalink reminder appears after every save so you never miss the rewrite flush.

== Installation ==

1. Upload the plugin files to /wp-content/plugins/sm-agent-kit, or install via the WordPress plugin screen.
2. Activate the plugin through the Plugins screen in WordPress admin.
3. Go to Settings then Permalinks and click Save Changes to flush rewrite rules.
4. Go to SM Agent Kit in the sidebar to configure your preferences.

== Frequently Asked Questions ==

= Do I need to edit any files? =

No. All settings save to the WordPress database through the admin UI. The well-known endpoints generate dynamically from your existing WordPress data.

= Will this affect how my site looks to human visitors? =

No. Markdown negotiation only fires when a request includes Accept: text/markdown, which browsers never send. The well-known endpoints are separate URLs that only agent crawlers look for. Content signals only affect robots.txt.

= Does it work with AIOSEO or Yoast SEO? =

Yes. The content signal appends to robots.txt via the do_robotstxt action rather than editing the file directly, so it's compatible with any plugin that manages robots.txt.

= Why do I need to flush permalinks after saving? =

The well-known endpoints register custom rewrite rules. WordPress only applies new rewrite rules after a flush. The plugin reminds you with a direct link after every save.

= Can I install this on multiple sites? =

Yes, and that is the point. The plugin reads site name, tagline, REST API root, and registered post types from WordPress at runtime, so each site generates its own correct output automatically with no per-site configuration needed beyond your preferences.

= What PHP version do I need? =

PHP 7.4 or higher. The plugin avoids short-hand syntax that would break on older PHP versions.

== Screenshots ==

1. The SM Agent Kit sidebar item and status cards showing active features.
2. Content signals section with ai-train, search, and ai-input toggles.
3. Agent skills index with auto-detected post types and description overrides.
4. The permalink reminder notice after saving settings.

== Changelog ==

= 1.8 =
* Added UCP manifest at /.well-known/ucp/manifest.json.
* Added ACP manifest at /.well-known/acp/manifest.json.
* Added stubbed ACP checkout endpoint at /acp/checkout_sessions, returning a clear not-implemented response.
* Added checkout status control in admin UI (no checkout, catalog ready, integration pending).
* Live checkout is not implemented in this version. No payment provider integration is included.

= 1.7 =
* Added agentic commerce product feed at /.well-known/agentic-commerce/product-feed.json.
* Read-only feed, built from WooCommerce data when active. No transaction or checkout capability.
* Commerce section in admin UI auto-detects WooCommerce and shows "Not applicable" if absent.
* Linked commerce feed from the API catalog when enabled.

= 1.6 =
* Renamed plugin to SM Agent Kit.
* Updated all internal prefixes to smak_.

= 1.5 =
* Renamed plugin to Simple Agent Kit.
* Updated all internal prefixes to siak_.
* Added License header to plugin file.

= 1.4.1 =
* Added Plugin Update Checker for GitHub-based auto-updates.
* Made repository public for open-source distribution.

= 1.4 =
* Added top-level Agent Kit sidebar menu item.
* Added tooltips to all settings fields.
* Fixed padding below Additional endpoint label.
* Updated all internal prefixes from mo_wp_ to akw_.

= 1.3 =
* Added WP Admin settings UI with per-feature toggles.
* Added Content Signals section with ai-train, search, and ai-input controls.
* Added API catalog, MCP server card, and agent skills index sections.
* Added permalink reminder notice after save.
* Replaced all hardcoded values with dynamic WordPress data.

= 1.2 =
* Added three well-known JSON endpoints: api-catalog, mcp/server-card.json, and agent-skills/index.json.
* All endpoints generate dynamically from site data.

= 1.1 =
* Added Content-Signal directive to robots.txt via do_robotstxt action.

= 1.0 =
* Initial release. Markdown negotiation for singular posts and pages.

== Upgrade Notice ==

= 1.6 =
Plugin renamed to SM Agent Kit. Settings saved under the previous version will not carry over automatically. Re-enter your preferences after upgrading.

== Standards ==

* Markdown content negotiation: Accept: text/markdown and Content-Type: text/markdown
* Content Signals: contentsignals.org
* API Catalog: RFC 9727
* MCP Server Card: SEP-1649
* Agent Skills Discovery RFC v0.2.0
