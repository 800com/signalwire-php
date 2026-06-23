# SignalWire PHP

![Packagist Version](https://img.shields.io/packagist/v/signalwire-community/signalwire.svg?color=brightgreen)

The Relay SDK for PHP enables PHP developers to connect and use SignalWire's Relay APIs within their own PHP code. Our Relay SDK allows developers to build or add robust and innovative communication services to their applications.

> ⚠️ Disclaimer:
>
> The libraries in this repository are NOT supported by SignalWire.

## Getting Started

Read the implementation documentation, guides and API Reference at the [Relay SDK for PHP Documentation](https://signalwire-community.github.io/docs/php/) site.

---

## Contributing

If you'd like to contribute, feel free to visit our [Slack channel](https://signalwire.community/) and read our developer section to get the code running in your local environment.

## Developers

### Standard Development Setup

To setup the dev environment follow these steps:

1. Fork this repository and clone it.
2. Create a new branch from `master` for your change.
3. Make changes!

### 800com Development Environment

**About this Fork**: This fork of the SignalWire PHP SDK has been enhanced for the 800com platform with:

- **PHP 8.3.23 Compatibility**: Full upgrade from PHP 7.0+ to PHP 8.3.23
- **Modern Dependencies**: Updated PHPUnit, Monolog, and all dependencies to latest versions
- **Automated Fax Support**: Complete restoration of Fax functionality removed from newer Twilio SDK versions
- **Docker Integration**: Seamless integration with 800com Docker development environment
- **100% Test Coverage**: Comprehensive test suite covering all 800com use cases

#### Key Features

🚀 **Vendored Fax Shim**: The Fax classes removed from newer Twilio SDK versions are shipped directly in `src/Twilio/Rest/Fax/` and autoloaded via this package's `composer.json` — no patches, plugins, or `composer install` hooks required.

🔧 **Complete Fax Restoration**: All Fax classes removed from Twilio SDK after v6.37.1 are restored (verified against v6.44.4):
- Fax domain class (`Fax.php`)
- Client Fax support (`getFax()` method)
- Fax V1 main class (`V1.php`)
- All Fax V1 implementation classes (Context, Instance, List, Options, Page)
- All Fax Media classes (Context, Instance, List, Page)

✅ **Zero Manual Intervention**: Complete automation - no manual patching required

📊 **100% Success Rate**: All 20 integration tests pass with perfect automation

#### Prerequisites
- Docker and Docker Compose
- 800com development environment set up

#### Setup Steps

The SignalWire development container is already configured in the Docker Compose setup. See `compose.override.yml.example-andre` for the complete configuration example.

1. **Ensure the SignalWire service is included in your compose override file**
2. **Build and start your development environment:**
   ```bash
   docker compose --profile=default up -d
   ```
   
   The container will now stay running with a `tail -f /dev/null` command.

3. **Access the SignalWire development container:**
   ```bash
   docker compose exec 800-signalwire-dev bash
   ```

4. **Install dependencies:**
   ```bash
   composer install
   ```
   
   The install will:
   - Install all dependencies
   - Autoload the vendored Twilio Fax shim classes from `src/Twilio/Rest/Fax/` (no patches applied)
   - Require zero manual intervention

5. **Test the setup (run from inside the container):**
   ```bash
   chmod +x test-setup.sh
   ./test-setup.sh
   ```
   
   This script will test PHP version, Composer, and SignalWire class loading.

6. **Run comprehensive 800com integration tests:**
   ```bash
   php test-800com-integration.php
   ```
   
   This test suite covers all SignalWire functionality used in 800com:
   - ✅ VoiceResponse (LaML/TwiML) generation
   - ✅ MessagingResponse and FaxResponse creation
   - ✅ SignalWire REST client with complete Fax support
   - ✅ Call routing and SIP bridging
   - ✅ Recording and voicemail functionality
   - ✅ DTMF gathering and conferencing
   - ✅ Webhook routing and redirects

#### Vendored Fax Shim

Twilio removed the Fax domain from `twilio/sdk` after v6.37.1. Rather than pinning to that old SDK (or relying on a `composer-patches` plugin), this fork ships the Fax classes directly in source and autoloads them:

- **Classes** live under `src/Twilio/Rest/Fax/` (the `V1` domain, `FaxList`/`FaxInstance`/`FaxContext`/`FaxOptions`/`FaxPage`, and the `Fax/Fax/FaxMedia*` classes) plus `src/Twilio/Rest/Fax.php`.
- **Autoloading** is configured in `composer.json` via the `Twilio\Rest\Fax\` PSR-4 prefix and the `src/Twilio/Rest/Fax.php` classmap entry — no plugin, no `patches/` directory, no `composer install` hook.
- **Client compatibility**: `src/Rest/Client.php` re-declares the `$_fax` property so `getFax()` keeps working without a PHP 8.2+ dynamic-property deprecation.

This lets `twilio/sdk` be upgraded to `^6.44` while preserving the Fax API surface 800com relies on.

**Testing from Scratch**:
```bash
# Remove vendor and install fresh
rm -rf vendor composer.lock
composer install
php test-800com-integration.php
```

#### Development Commands

```bash
# Check PHP version (should be 8.3.23)
docker compose exec 800-signalwire-dev php -v

# Fresh install (vendored Fax shim autoloads automatically)
docker compose exec 800-signalwire-dev composer install

# Update dependencies for PHP 8.3 compatibility
docker compose exec 800-signalwire-dev composer update

# Run comprehensive integration tests
docker compose exec 800-signalwire-dev php test-800com-integration.php

# Run basic setup tests
docker compose exec 800-signalwire-dev ./test-setup.sh

# Install new dependencies
docker compose exec 800-signalwire-dev composer require package/name

# Check container logs
docker compose logs 800-signalwire-dev
```

#### Integration with 800com-api

To use the local development version in your 800com-api:

1. **Configure local package in 800com-api:**
   ```json
   // In 800com-api/composer.json
   {
     "repositories": [
       {
         "type": "path",
         "url": "../signalwire-php"
       }
     ],
     "require": {
       "signalwire-community/signalwire": "dev-main"
     }
   }
   ```

2. **Install the local package:**
   ```bash
   docker compose exec 800-api-php composer require signalwire-community/signalwire:@dev
   ```

3. **Test integration:**
   ```bash
   docker compose exec 800-api-php php artisan test --filter=Signalwire
   ```

#### Technical Implementation Details

**Fax Functionality Restoration**:
- **Problem**: Twilio SDK after v6.37.1 removed Fax classes that SignalWire depends on
- **Solution**: Fax classes are vendored directly into `src/Twilio/Rest/Fax/` and autoloaded via `composer.json`
- **Implementation**: PSR-4 (`Twilio\Rest\Fax\`) + classmap (`src/Twilio/Rest/Fax.php`); `Client::$_fax` re-declared for PHP 8.2+
- **Result**: `twilio/sdk` upgradeable to `^6.44` with full compatibility for existing 800com Fax workflows

**PHP 8.3 Compatibility**:
- Updated all dependencies to support PHP 8.3.23
- Resolved PHPUnit and phpspec/prophecy conflicts
- Fixed TwiML API changes in test suite
- Maintained backward compatibility with existing code

**Docker Integration**:
- ARM64 platform support for Apple Silicon
- Volume mounting for development workflow
- Persistent vendor directory with named volumes
- Integration with 800com development environment

#### Test Results

The automated system achieves **100% success rate** on all tests:

```
============================================================
📊 TEST RESULTS
============================================================
✅ VoiceResponse instantiation
✅ VoiceResponse Say element
✅ VoiceResponse Play element
✅ VoiceResponse Hangup element
✅ VoiceResponse Reject element
✅ MessagingResponse instantiation
✅ MessagingResponse Message element
✅ FaxResponse instantiation
✅ FaxResponse Receive element
✅ SignalWire REST Client instantiation
✅ SignalWire Fax class instantiation
✅ SignalWire Fax baseUrl functionality
✅ Call routing with Dial
✅ SIP bridging
✅ Recording functionality
✅ SIP domain handling
✅ Redirect for webhook routing
✅ DTMF gathering
✅ Conference functionality
✅ Voicemail recording setup

------------------------------------------------------------
✅ Passed: 20
❌ Failed: 0
📈 Success Rate: 100.0%
```

#### Troubleshooting

- **Dependency conflicts**: Run `composer update` to resolve PHP 8.3 compatibility issues
- **Container won't start**: Check logs with `docker compose logs 800-signalwire-dev`
- **Permission issues**: Ensure proper file permissions in the mounted volume
- **Fax functionality missing**: Confirm `composer dump-autoload` ran and the `Twilio\Rest\Fax\` PSR-4 prefix + `src/Twilio/Rest/Fax.php` classmap are present in `composer.json`

## Versioning

Relay SDK for PHP follows Semantic Versioning 2.0 as defined at <http://semver.org>.

## License

Relay SDK for PHP is free software, and may be redistributed under the terms specified in the [MIT-LICENSE](https://github.com/signalwire-community/signalwire-php/blob/master/LICENSE) file.
