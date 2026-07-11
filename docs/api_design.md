# OriginPay API Design

## Authentication Flow (Sprint 6)

- **Public Keys**: `pk_test_...` / `pk_live_...`
- **Secret Keys**: `sk_test_...` / `sk_live_...`
- Provided via `Authorization: Bearer <key>` header.

## Request Context

Available via `ApiRequestContext` and `MerchantContext`. Includes:
- Request ID
- Merchant Info
- Environment (Sandbox / Production)

## Idempotency Skeleton

Clients can provide `Idempotency-Key` header. Validated in-memory (Sprint 6). Final persistence in Sprint 7.

## TODO Sprint 7
- Real DB persistence for API Credentials
- Full Idempotency storage logic
- Rate Limiting integration
