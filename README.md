# SM Agent Kit

A WordPress plugin that makes your site readable and discoverable by AI agents. Implements emerging agentic web standards — markdown negotiation, content signals, and well-known JSON endpoints — with a settings UI and zero hardcoding.

---

## What it does

AI agents don't browse the web the way humans do. They request structured data, negotiate content formats, and look for standardised discovery endpoints. SM Agent Kit adds all of this to any WordPress site automatically.

**Markdown negotiation** — when an agent sends a request with `Accept: text/markdown`, your site returns the post or page as clean markdown instead of HTML, with a `Content-Type: text/markdown` header and `x-markdown-tokens: 1`. Human visitors never notice a thing.

**Content signals** — adds a `Content-Signal` directive to your `robots.txt` declaring your preferences for AI training, search indexing, and agent input. Supports the `ai-train`, `search`, and `ai-input` fields per the emerging Content Signals standard.

**Well-known endpoints** — serves three auto-generated JSON files that agent frameworks and crawlers look for:

| Endpoint | Purpose |
|---|---|
| `/.well-known/api-catalog` | Lists your REST API and sitemap endpoints (RFC 9727) |
| `/.well-known/mcp/server-card.json` | Declares MCP server capabilities (SEP-1649) |
| `/.well-known/agent-skills/index.json` | Lists what your site can do as an agent-callable service |
| `/.well-known/agentic-commerce/product-feed.json` | Read-only WooCommerce product feed for agent shopping tools |

All four endpoints build dynamically from your WordPress data — site name, tagline, registered post types, REST API root, product catalog — with no hardcoding required.

---

## Installation

1. In WordPress admin, go to **Plugins → Add Plugin** and search for "SM Agent Kit"
2. Click **Install now**, then **Activate**
3. Go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules
4. Go to **SM Agent Kit** in the sidebar to configure

---

## Settings

The plugin adds an **SM Agent Kit** item to the WordPress admin sidebar. All settings save to the database and generate output dynamically — no file editing required.

### Content signals

Toggle the `Content-Signal` directive on or off, and set values for each field:

- `ai-train` — whether AI companies may use your content to train their models
- `search` — whether search engines and AI search tools may index your content
- `ai-input` — whether AI agents may use your content as direct input when processing tasks

### API catalog

Auto-generated from your REST API root and sitemap. Optionally add one extra endpoint (e.g. an RSS feed URL) to include in the catalog.

### MCP server card

Auto-generated from your site name and tagline. Optionally override the version string or add a contact email. If the contact email field is left blank, it's omitted from the output entirely.

### Agent skills index

Auto-detects all public post types and generates a skills entry for each. You can override the description for any post type or uncheck it to exclude it from the index.

### Agentic commerce

If WooCommerce is active, publishes a read-only feed of your published products: name, price, currency, availability, SKU, and URL. No checkout or payment functionality is exposed. If WooCommerce isn't installed, this section is marked as not applicable automatically.

Also publishes UCP and ACP protocol manifests at `/.well-known/ucp/manifest.json` and `/.well-known/acp/manifest.json`, declaring your commerce capabilities to AI shopping agents. A stubbed checkout endpoint at `/acp/checkout_sessions` returns a clear "not implemented" response. **No payment processing is implemented in this plugin.** Live agent checkout requires a separate payment provider integration (e.g. Stripe's ACP tooling), which is outside the scope of this plugin.

### Markdown negotiation

Toggle on to enable `Accept: text/markdown` content negotiation for all singular posts and pages. No further configuration needed.

---

## After saving settings

Any change that affects the well-known endpoints requires a permalink flush to take effect. The settings page reminds you of this after every save, with a direct link to **Settings → Permalinks**.

---

## Compatibility

- WordPress 6.0+
- PHP 7.4+
- Works with any theme
- Compatible with AIOSEO, Yoast SEO, and other robots.txt managers

---

## Standards implemented

| Standard | Reference |
|---|---|
| Markdown content negotiation | `Accept: text/markdown`, `Content-Type: text/markdown` |
| Content Signals | [contentsignals.org](https://contentsignals.org) |
| API Catalog | RFC 9727 |
| MCP Server Card | SEP-1649 |
| Agent Skills Discovery | Agent Skills Discovery RFC v0.2.0 |

---

## Roadmap

- [ ] Per-field AI generation (Anthropic / OpenAI API)
- [ ] `llms.txt` endpoint support
- [ ] OAuth / OIDC discovery endpoints
- [ ] WebMCP support

---

## File structure

```
sm-agent-kit/
├── sm-agent-kit.php       — main plugin file, hooks, and endpoint logic
├── admin/
│   └── settings.php       — WP Admin settings page
├── assets/
│   └── admin.css          — settings page styles
└── readme.txt             — WordPress.org directory listing
```

---

## Author

Mo Shehu, PhD — [mohammedshehu.com](https://mohammedshehu.com)
