# Contracts

## API Authentication

### Headers
- `Authorization`: `Bearer <key>`
- `Idempotency-Key`: Optional UUID
- `X-OriginPay-Request-Id`: Returned in all responses.

### Error Format
```json
{
  "error": {
      "type": "authentication_error",
      "message": "Invalid API key.",
      "code": "invalid_api_key"
  },
  "request_id": "req_xxxxxxxxx"
}
```

### Response Formats
Standardized using `ApiResponse` factory. Includes `success`, `created`, `validation`, `unauthorized`, `error`.
