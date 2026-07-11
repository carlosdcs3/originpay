# Product

## Register

product

## Users

OriginPay is built primarily for merchants: companies that use the platform every day to receive payments, create charges and payment links, manage subscriptions, track payments, manage customers, request withdrawals, review analytics and reports, and configure API integrations.

The secondary audience is OriginPay's internal operations team, including admin, finance, compliance, support, and platform operators who monitor risk, gateway health, treasury, billing, support, and system activity.

Merchants usually arrive with a concrete operational task and limited patience for exploration. They need compact screens, clear status, fast actions, reliable feedback, and routes that make the next step obvious.

## Product Purpose

OriginPay is the payment infrastructure merchants use to operate online payments from one place. It should present OriginPay as the infrastructure itself, not as a wrapper around competing gateways or third-party payment brands.

The product succeeds when merchants can create and manage revenue workflows quickly, understand payment state without ambiguity, and trust the platform for daily financial operations. The interface should reduce clicks, reduce cognitive load, conserve vertical space, and help users complete tasks faster while staying consistent with the OriginPay design system.

## Brand Personality

OriginPay should feel trustworthy, simple, fast, precise, and modern. The tone is operationally confident: clear labels, direct copy, no hype, no crypto-exchange language, and no decorative complexity.

The product may be inspired by the quality bar of Stripe, Vercel, Linear, and GitHub: compact, polished, consistent, and productivity-focused. Do not copy their layouts or cite those products inside the OriginPay interface.

DigiKash is a legacy/internal name only and must not influence UX decisions, visual architecture, or product copy. All frontend work should treat OriginPay as the canonical product and brand.

## Anti-references

OriginPay should not look like a generic SaaS dashboard, a cryptocurrency exchange, an old banking panel, or a decorative metrics wall.

Avoid excessive cards, charts used only to fill space, fake metrics, production placeholders, visual effects that slow down task completion, and copy that frames OriginPay as an intermediary for other gateways. Never cite competing payment gateways inside the product UI.

Avoid fragmented UI vocabulary across merchant and admin surfaces. Legacy DigiKash naming, Bootstrap/CoreUI leftovers, `x-admin.*`, `x-ds.*`, and newer OriginPay components should be consolidated toward a consistent OriginPay system over time.

## Design Principles

Reduce work per task. When more than one visual solution is viable, choose the one that reduces clicks, saves vertical space, and lets the user complete the task faster while preserving consistency.

Show real operational state. Do not ship fake KPIs, placeholder charts, or empty visual containers as if they were product value.

Make money movement legible. Payment state, customer state, settlement state, withdrawal state, risk, and errors must be easy to scan and hard to misunderstand.

Prefer compact clarity over spectacle. Use restrained motion, dense but organized layouts, clear hierarchy, and familiar controls.

Keep OriginPay first. The interface should reinforce OriginPay as the merchant's payment infrastructure and avoid legacy DigiKash language or competitor names in user-facing surfaces.

## Accessibility & Inclusion

Target WCAG 2.1 AA for product surfaces. Body text and controls must meet contrast requirements, focus states must be visible, keyboard navigation must remain intact, and reduced-motion preferences must be respected.

Use color as a supporting signal, not the only signal. Payment, risk, success, warning, and error states need text or icon support so color-blind users can understand them.

Optimize for frequent daily use: predictable navigation, compact information density, responsive layouts, readable tables, accessible forms, clear validation, and meaningful empty and error states.
