== Changelog ==

## [1.2.0] - 2026-08-24

### Added
- DuitNow QR group support: `duitnow_qr` and `dnqr` treated as a single group. Resolved against the merchant's actual `/payment_methods/` response at checkout, preferring `dnqr` when both are available.
- ShopeePay support: `shopee_pay` replaces `razer_shopeepay`; the configured whitelist is resolved against `/payment_methods/`, preferring `shopee_pay` when both are available.
- `crypto_coin` added to the admin payment-method whitelist.
- `payment_methods()` now accepts a real order amount (defaults to RM10) instead of a hardcoded value, so availability checks match the actual purchase.

### Changed
- Dropped the "Razer" prefix from payment-method labels (Atome, GrabPay, Maybank QR, Touch 'n Go, Shopee Pay).
- Group resolution for DuitNow QR and ShopeePay now runs through a single `/payment_methods/` call (mirrors chip-for-woocommerce).

## [1.1.0] - 2024-10-27

- Tag AbanteCart version 1.4.0 as compatible.
- Default to `novator` template.
- Use fast_checkout (integrated into AbanteCart core).

## [1.0.0] - 2024-07-02
- Initial release.
