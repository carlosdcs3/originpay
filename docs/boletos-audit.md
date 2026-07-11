# Auditoria inicial do modulo Boletos

Data: 2026-06-28

## Escopo

Auditoria inicial realizada antes de qualquer implementacao do modulo de Boletos. Nenhum codigo de producao foi alterado.

## Estado atual

| Area | Estado | Evidencia |
| --- | --- | --- |
| Rota web do cliente | Existe | `GET /user/boletos` -> `Frontend\BoletoController@index` |
| Menu V2 | Existe | Layouts V2 apontam para `user.boleto.index` |
| Controller web | Parcial | `app/Http/Controllers/Frontend/BoletoController.php` lista `charges` do merchant com `payment_method = boleto` |
| View V2 | Parcial | `resources/views/frontend/user/boleto/index.blade.php` possui KPIs, filtros, tabela e empty state |
| Model dedicado | Nao existe | Nao foi encontrado model `Boleto`; o modulo usa `App\Models\Charge` |
| Tabela dedicada | Nao existe | Nao foi encontrada migration/tabela `boletos` |
| API publica especifica | Nao existe | API atual usa `/api/v1/payments` com `method=boleto`, mas retorna mock em sandbox e `501` em producao |
| ChargeService | Parcial | Aceita `payment_method = boleto` e resolve `PaymentMethod::BOLETO` |
| Gateway routing | Parcial | `PaymentOperation::BOLETO` existe e filtra por `supports_boleto` |
| Provider real de boleto | Nao existe | Providers em `app/Services/Gateway/Providers/*` lancam `createBoleto not implemented`; adapter usado pelo `ChargeService` esta orientado a PIX/mock |
| Download/segunda via | Placeholder | Na tabela de boletos, botoes de download e mais opcoes usam `href="#"` |
| Testes especificos | Nao encontrados | Nao foram encontrados testes `Boleto*` |

## Rotas encontradas

| Verbo | URI | Nome | Controller |
| --- | --- | --- | --- |
| GET/HEAD | `/user/boletos` | `user.boleto.index` | `Frontend\BoletoController@index` |

Rotas relacionadas:

| Verbo | URI | Uso atual |
| --- | --- | --- |
| GET | `/user/charge/create` | A view de Boletos aponta para `user.charge.create` com `method=boleto` |
| POST | `/user/charge/store` | `ChargeController@store`, mas valida somente `pix`, `card`, `pix_card`; boleto nao passa hoje por esse formulario |
| POST | `/api/v1/payments` | Aceita `method=boleto`, porem a implementacao atual e sandbox/mock; em producao responde `501` |

## Fluxo atual observado

```text
Dashboard do merchant
-> /user/boletos
-> BoletoController@index
-> Charge::where(user_id, merchant)->where(payment_method, boleto)
-> resources/views/frontend/user/boleto/index.blade.php
```

Para criacao pelo dashboard, a tela tenta direcionar para `user.charge.create?method=boleto`, mas `ChargeController@store` ainda nao aceita `boleto` na validacao do formulario.

## Dependencias atuais

- `App\Models\Charge`
- `App\Enums\PaymentMethod::BOLETO`
- `App\Enums\PaymentOperation::BOLETO`
- `App\Enums\ChargeStatus`
- `App\Services\ChargeService`
- `App\Gateway\GatewayResolver`
- `App\Gateway\GatewayManager`
- `PaymentGateway.supports_boleto`

## Principais lacunas

1. Nao existe entidade/tabela dedicada de boleto.
2. Nao existe provider real para emissao de boleto bancario.
3. `ChargeController@store` nao aceita `boleto`, apesar do botao "Novo Boleto" apontar para a tela de criacao de cobranca.
4. API `/api/v1/payments` aceita `boleto` no payload, mas nao cria cobranca real em producao.
5. Download/segunda via ainda e placeholder na tela.
6. Nao ha testes especificos de Boletos.
7. Nao ha webhook/evento especifico de boleto alem do pipeline generico de charges.
8. Nao ha documentacao funcional do boleto como produto/API.

## Classificacao inicial

| Item | Estado |
| --- | --- |
| Listagem no dashboard | Parcial |
| Criacao de boleto | Nao pronta |
| Integracao com gateway | Nao pronta |
| Consulta/status | Parcial via `Charge` |
| Download/segunda via | Placeholder |
| API publica | Parcial/mock |
| Testes | Nao existe |

## Recomendacao para proxima fase

Antes de implementar tela ou UX adicional, definir o contrato canonico:

- boleto como especializacao de `Charge` ou tabela complementar `boleto_details`;
- campos obrigatorios: linha digitavel, codigo de barras, nosso numero, vencimento, pagador, documento, PDF/link de segunda via;
- provider prioritario para emissao real;
- eventos/webhooks esperados;
- testes de criacao, listagem, vencimento, pagamento confirmado e segunda via.
