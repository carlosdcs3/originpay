# Transparent Checkout Blueprint

## Authentication

All S2S calls require a valid Secret Key. 
Public keys are used for client-side operations (e.g., tokenizing cards).

## Request IDs

Every API request will automatically generate a `req_xxxxxxxxx` ID for tracing.

## TODO Sprint 7
- Full database integration for Keys.
- Validate Keys against Merchants.
