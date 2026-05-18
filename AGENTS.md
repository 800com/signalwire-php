# AGENTS.md — signalwire-php (800com fork)

This is **800com's internal fork** of [`signalwire-community/signalwire-php`](https://github.com/signalwire-community/signalwire-php). It is consumed by [`800com-api`](https://github.com/800com/api) as the SDK used to talk to SignalWire (LaML/TwiML responses, REST client for number provisioning, Relay for functional tests).

---

## Why this fork exists

Two reasons:

1. **PHP 8.3 + Twilio SDK 6.44+ compatibility (DEV-503).** Upstream signalwire-community ties itself to `twilio/sdk ^6.37.1` — anything newer removed the `Twilio\Rest\Fax\V1\*` namespace, which the `SignalWire\Rest\Client` constructor transitively references via its `getFax()` method. This fork **re-declares the removed Twilio Fax/V1 classes inside this package's own source tree** (under `src/Twilio/Rest/Fax/`) so the SignalWire Client autoloads cleanly against `twilio/sdk ^6.44`.

2. **`monolog/monolog` v3 compatibility.** 800com-api uses `monolog/monolog ^3.0`; upstream signalwire-community pinned `^1.24 || ^2.0`. We relax that constraint to `^1.24 || ^2.0 || ^3.0`.

The fork is intentionally minimal: everything else from upstream is unchanged. Both deltas live in `composer.json` plus the new files under `src/Twilio/Rest/`.

---

## What's "custom" in this fork (full inventory)

### Source files we added under the `Twilio\` namespace

These restore the classes that `twilio/sdk` v6.38+ removed. They live HERE, not in twilio/sdk's vendor tree:

```
src/Twilio/Rest/Fax.php                          # Twilio\Rest\Fax (domain)
src/Twilio/Rest/Fax/V1.php                       # Twilio\Rest\Fax\V1 (version)
src/Twilio/Rest/Fax/V1/FaxList.php
src/Twilio/Rest/Fax/V1/FaxContext.php
src/Twilio/Rest/Fax/V1/FaxInstance.php
src/Twilio/Rest/Fax/V1/FaxPage.php
src/Twilio/Rest/Fax/V1/FaxOptions.php
src/Twilio/Rest/Fax/V1/Fax/FaxMediaContext.php
src/Twilio/Rest/Fax/V1/Fax/FaxMediaInstance.php
src/Twilio/Rest/Fax/V1/Fax/FaxMediaList.php
src/Twilio/Rest/Fax/V1/Fax/FaxMediaPage.php
```

These are exact copies of what Twilio shipped through `twilio/sdk` 6.37.x — verbatim source as published by Twilio's code generator (note the `\ / _    _  _|   _  _` ASCII banner at the top of each file).

PSR-4 picks the most-specific prefix → our `Twilio\Rest\Fax\` mapping wins over twilio/sdk's `Twilio\` mapping, so these resolve from our `src/` and twilio/sdk's actual `src/Twilio/Rest/Api/...` etc. keeps resolving normally.

### Edits to `src/Rest/Client.php`

Added one line: `protected $_fax;`

When upstream Twilio still shipped Fax, this property was declared on `Twilio\Rest\Client` to cache the lazy-loaded Fax domain. After v6.38 removed it, our `SignalWire\Rest\Client::getFax()` (which already uses `$this->_fax` as a cache slot) would trigger a "dynamic property" deprecation under PHP 8.2+. Re-declaring `$_fax` on this subclass fixes that without touching twilio/sdk's source.

### Edits to `composer.json`

```json
"autoload": {
  "psr-4": {
    "SignalWire\\": "src/",
    "Twilio\\Rest\\Fax\\": "src/Twilio/Rest/Fax/"   // NEW
  },
  "classmap": [
    "src/Twilio/Rest/Fax.php"                       // NEW — top-level Fax domain
  ],
  "files": [ "src/functions.php", "src/Version.php" ]
},
"require": {
  "twilio/sdk": "^6.44",                            // CHANGED from "^6.37"
  "monolog/monolog": "^1.24 || ^2.0 || ^3.0"        // CHANGED — added ^3.0
}
```

No `cweagans/composer-patches` plugin. No `extra.patches` block. Patches in the older `patches/` directory have been removed — we briefly used composer-patches but Option 1 ("ship the classes in our own source") is simpler and doesn't force consumers to allowlist a Composer plugin.

---

## How 800com-api consumes this fork

`800com-api/composer.json` references this repo as a VCS repository:

```json
"repositories": [
  { "type": "vcs", "url": "https://github.com/800com/signalwire-php.git" }
],
"require": {
  "signalwire-community/signalwire": "dev-improvement/DEV-503"
}
```

Once DEV-503 ships and merges, that constraint will flip to `dev-master` (or, ideally, a semver tag like `^2.5.0`).

### Where 800com-api actually uses each surface

**Production code:**

| File | Symbol from this package |
|---|---|
| `app/Providers/SignalwireServiceProvider.php` | `SignalWire\Rest\Client` (singleton factory — canonical risk point) |
| `app/Services/Providers/Signalwire/Signalwire.php` | `SignalWire\Rest\Client`, `SignalWire\LaML\VoiceResponse` |
| `app/Services/Providers/Signalwire/SignalwireNumberService.php` | `$client->availablePhoneNumbers($country)->local` / `->tollFree`, `$client->incomingPhoneNumbers->create/read/update`, return type `Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance` |
| `app/Services/Transcribers/Commands/ReprocessSignalwireRecording.php` | `$client->calls($id)->recordings->read()` |
| `app/Http/Controllers/Signalwire/InboundSIPController.php` | `new VoiceResponse()`, `->dial()`, `->hangup()`, `->play()`, `->say()` |
| `packages/800com/telco/src/Carriers/Signalwire/Signalwire.php` | `\SignalWire\LaML\VoiceResponse` (instantiated as XML builder) |
| `packages/800com/telco/src/Pbx/Stages/Routers/SipBridgeStage.php` | `$laml->dial()->number(...)` |
| `packages/800com/telco/src/Traits/Signalwire/PlaysAudio.php` | `VoiceResponse $laml` parameter |
| `packages/800com/telco/src/Console/Commands/ImportSignalwireNumbers.php` | `$client->incomingPhoneNumbers->read()` (`telco:sw:numbers:load` cron) |
| `packages/800com/telco/src/Console/Commands/ImportSignalwireApplications.php` | `$client->applications->read()` (`telco:sw:apps:load` cron) |
| `packages/800com/telco/src/Console/Commands/SignalwireUtilCommand.php` | `$client->applications->create([...])` |

**Functional tests (Behat call-forwarding harness):**

| File | Symbol |
|---|---|
| `tests/Functional/CallForwarding/Bootstrap/CallForwardingContext.php` | `SignalWire\Relay\Task`, `SignalWire\Log` |
| `tests/Functional/CallForwarding/Bootstrap/RelayProxy.php` | `SignalWire\Log` |
| `tests/Functional/Common/RelayConsumer.php` | `SignalWire\Relay\Consumer`, `SignalWire\Relay\Client`, `SignalWire\Relay\Calling\Call`, `SignalWire\Log` |

### What 800com-api does NOT use

- No direct import or call of `Twilio\Rest\Fax\*` anywhere. The Fax classes we ship are pure load-bearing scaffolding so the SignalWire Client doesn't crash on autoload.
- No use of `SignalWire\Messages\*` (Relay blade protocol primitives) in production — only in unit tests for this package.

---

## Testing

CI: `.github/workflows/build-test.yml` runs on `ubuntu-latest` with `shivammathur/setup-php@v2` (PHP 8.3). Triggers on push to `master` and any `improvement/**`, `feature/**`, `bugfix/**`, `hotfix/**` branch + PR to `master`.

Test suites under `tests/`:

- `tests/FaxV1Test.php` — verifies the restored `Twilio\Rest\Fax\V1\*` classes exist, extend the right parents, and that the SignalWire Client chain (`->fax->v1->faxes(sid)`) resolves end-to-end. **This is what catches regressions if anyone accidentally deletes a Fax file or breaks the autoload mapping.**
- `tests/SignalWireUsageTest.php` — mirrors every chain pattern 800com-api uses (`availablePhoneNumbers->local/tollFree`, `incomingPhoneNumbers`, `applications`, `calls(sid)->recordings`, `VoiceResponse::dial/hangup/play/say`). If 800com-api adds a new SDK call pattern, add a row here.
- `tests/laml/*` — upstream LaML tests (VoiceResponse, FaxResponse, etc.).
- `tests/relay/*` — upstream Relay tests.

Running locally (matches CI):

```bash
docker run --rm -v "$(pwd)":/app -w /app composer:2 sh -c "
  composer install --prefer-dist --no-interaction --no-progress &&
  vendor/bin/phpunit tests
"
```

Note: the `provisioning/docker-compose.yml` dev container is currently broken on recent Docker due to a stale PHP base image, but CI doesn't use it.

---

## Maintenance playbook

### When upstream signalwire-community/signalwire-php publishes a release we want to merge

1. Merge upstream master into our `master`.
2. Re-apply the two diffs:
   - Re-add the `Twilio\Rest\Fax\` PSR-4 mapping + the `classmap` entry in `composer.json` if upstream rewrote it.
   - Re-add `protected $_fax;` to `src/Rest/Client.php`.
   - Restore monolog v3 in `composer.json`.
3. The `src/Twilio/Rest/Fax/*` files are independent — they don't need to track upstream.
4. Run the full test suite — `FaxV1Test` will catch most regressions immediately.

### When twilio/sdk releases a new major version

If Twilio re-introduces Fax/V1 under a new path, we can delete `src/Twilio/Rest/Fax/*` entirely and drop the autoload mappings. If they remove more things, expect new tests/coverage from `SignalWireUsageTest.php` to start failing — that's the signal to add more shim classes.

### When 800com-api starts using a new SDK surface

Add a corresponding assertion in `tests/SignalWireUsageTest.php` that instantiates a Client and walks the new chain. This is the contract test that protects 800com-api from this package being out of step.

---

## Related Jira

- **DEV-503** — "Remove Signalwire package Fax Dependency". The literal title was to *remove* the Fax dependency; in practice we kept it as load-bearing scaffolding so the upstream SignalWire Client autoloads. A future ticket could remove the Fax shim entirely if `SignalWire\Rest\Client::getFax()` is overridden to throw instead of constructing.
