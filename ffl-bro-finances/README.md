# FFL-BRO Finances (AP)

Complete Accounts Payable module for WordPress/FFL operations.

## Features
- Vendor management
- Bill tracking and approval
- Payment scheduling
- Check generation with MICR
- Positive Pay export formats
- Audit trail system

## Installation

```bash
# Copy to plugins directory
cp -r ffl-bro-finances /path/to/wp-content/plugins/

# Activate via WP-CLI
wp plugin activate ffl-bro-finances
wp option add fflbro_fin_enable 0  # Start disabled for safety

# Go live when ready
wp option update fflbro_fin_enable 1
```

## Safety Toggle

The plugin includes a safety toggle (`fflbro_fin_enable`):
- Default: OFF (0) after activation
- Runtime only activates when set to 1
- Emergency stop: `wp option update fflbro_fin_enable 0`

## REST API

Base: `/wp-json/fflbro/v1/finance`

- `GET /ping` - Health check
- `GET /vendors` - List vendors
- `POST /checks/prepare` - Prepare check batch

## Requirements
- WordPress 5.8+
- PHP 7.4+ (8.x compatible)

## License
GPL v2 or later
