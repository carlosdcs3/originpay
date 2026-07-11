---
name: OriginPay
description: The merchant control room for fast, reliable payment operations.
colors:
  midnight-ledger: "#090B10"
  ledger-surface: "#11151E"
  ledger-surface-raised: "#131722"
  origin-violet: "#7C3AED"
  origin-violet-hover: "#8B5CF6"
  origin-violet-deep: "#6D28D9"
  settlement-cyan: "#00E5C8"
  operational-blue: "#3B82F6"
  processing-amber: "#F59E0B"
  risk-red: "#EF4444"
  success-green: "#10B981"
  text-main: "#F8FAFC"
  text-secondary: "#E2E8F0"
  text-muted: "#94A3B8"
  border-light: "#FFFFFF0A"
  border-medium: "#FFFFFF14"
  focus-violet: "#7C3AED4D"
typography:
  display:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "-0.01em"
  headline:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"
    fontSize: "1.1rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"
    fontSize: "1rem"
    fontWeight: 600
    lineHeight: 1.35
  body:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"
    fontSize: "0.6875rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "0.06em"
  mono:
    fontFamily: "JetBrains Mono, Fira Code, monospace"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: 1.5
rounded:
  xs: "6px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  pill: "999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
  xxl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.origin-violet}"
    textColor: "{colors.text-main}"
    rounded: "{rounded.md}"
    padding: "0 22px"
    height: "44px"
  button-secondary:
    backgroundColor: "{colors.midnight-ledger}"
    textColor: "{colors.text-secondary}"
    rounded: "{rounded.md}"
    padding: "0 22px"
    height: "44px"
  input-field:
    backgroundColor: "{colors.ledger-surface}"
    textColor: "{colors.text-main}"
    rounded: "{rounded.md}"
    padding: "0 16px"
    height: "48px"
  data-card:
    backgroundColor: "{colors.ledger-surface}"
    textColor: "{colors.text-main}"
    rounded: "{rounded.lg}"
    padding: "24px"
  badge-success:
    backgroundColor: "{colors.success-green}"
    textColor: "{colors.text-main}"
    rounded: "{rounded.pill}"
    padding: "3px 9px"
---

# Design System: OriginPay

## 1. Overview

**Creative North Star: "The Merchant Control Room"**

OriginPay is the operational center where merchants manage receiving money. It is not a bank, not a crypto exchange, not an old ERP, and not a generic SaaS dashboard. The interface should feel like a precise control room: compact, readable, fast, and calm under pressure.

The current merchant dashboard V2 establishes the canonical visual system: Midnight Ledger surfaces, Origin Violet actions, Settlement Cyan confirmations, restrained status colors, dense tables, compact cards, and Inter throughout. The admin enterprise surface has useful component discipline, but future frontend decisions should bias toward the merchant V2 system and OriginPay naming.

The product should transmit that everything is under control. Every screen should help a merchant execute an operation in a few clicks and find information in seconds. Visual polish is valuable only when it improves speed, clarity, trust, or state comprehension.

**Key Characteristics:**
- Compact, high-density interfaces that still breathe.
- Tables and task flows are protagonists; cards organize information but do not decorate.
- Dark operational surfaces use violet sparingly for action, selection, and focus.
- Status color always carries meaning: cyan/success for received money, amber for pending, red for risk or failure.
- Microinteractions are subtle and state-driven.

## 2. Colors

The OriginPay palette is a restrained dark product system: one primary action color, one money-confirmation color, and a small semantic vocabulary for operational state.

### Primary
- **Origin Violet**: The only primary action and selection color. Use it for main CTAs, active navigation, focus rings, selected ranges, and high-priority interactive state.
- **Origin Violet Hover**: The hover lift for primary controls. Use only as a state transition, never as a separate brand accent.
- **Origin Violet Deep**: The lower stop in primary gradients where an existing component already uses depth. Do not let gradients become decoration.

### Secondary
- **Settlement Cyan**: Confirmation, PIX, received money, live availability, and positive settlement state. This is not a general decorative accent.
- **Operational Blue**: Informational status only. Keep it behind badges, method labels, and supporting data states.

### Tertiary
- **Processing Amber**: Pending, processing, review-needed, and waiting states.
- **Risk Red**: Errors, failed payments, chargebacks, cancellations, destructive actions, and blocked states.
- **Success Green**: General success where Settlement Cyan would be too payment-method specific.

### Neutral
- **Midnight Ledger**: Main merchant dashboard background. It creates the control-room mood and keeps data legible.
- **Ledger Surface**: Primary card and panel surface.
- **Ledger Surface Raised**: Sidebar, popover, tooltip, and elevated panel surface.
- **Text Main**: Main body and headings on dark surfaces.
- **Text Secondary**: Secondary body, table cells, and supporting labels.
- **Text Muted**: Table headers, helper copy, timestamps, and inactive navigation.
- **Border Light / Border Medium**: Structure lines and component boundaries. Borders should do most of the depth work.

### Named Rules

**The Origin Violet Rule.** Origin Violet is for action, selection, and focus. If a violet element is not interactive or selected, question it.

**The Money Color Rule.** Settlement Cyan means money received, PIX, confirmation, or availability. Do not use it as a decorative glow.

**The No Fake Glow Rule.** Glows are allowed only for focus, support availability, or an active state. Never use glow to make an empty card feel important.

## 3. Typography

**Display Font:** Inter with system fallbacks.
**Body Font:** Inter with system fallbacks.
**Label/Mono Font:** JetBrains Mono for API keys, endpoint paths, IDs, and code-like values.

**Character:** The type system is product-first: compact, readable, and precise. It should feel closer to an operations terminal for merchants than to a marketing site.

### Hierarchy
- **Display** (600, 1.5rem, 1.2): Page titles, settings hub titles, and major dashboard headings. Avoid oversized hero type inside product surfaces.
- **Headline** (600, 1.1rem, 1.3): Header titles, card groups, and compact section introductions.
- **Title** (600, 1rem, 1.35): Card titles, table titles, dialog headings, and reusable component headings.
- **Body** (400, 0.875rem, 1.5): Default product copy, form help, table body text, and descriptions. Keep prose near 65-75ch, but allow dense tables to use the available width.
- **Label** (700, 0.6875rem, 0.06em tracking): Table headers, KPI labels, nav group labels, and compact metadata. Uppercase is acceptable only for structural labels, never for long copy.
- **Mono** (500, 0.8125rem, 1.5): API paths, transaction IDs, request IDs, hashes, and exact technical values.

### Named Rules

**The Product Type Rule.** No display fonts in labels, buttons, table cells, or data-heavy UI. Inter and JetBrains Mono are enough.

**The Density Rule.** Use smaller, stable rem sizes in product surfaces. Do not use fluid heading clamps in dashboards.

## 4. Elevation

OriginPay uses tonal layering and hairline borders first, subtle shadows second. Cards exist to group information, not to create decorative depth. Resting surfaces should feel stable; hover and focus may lift by 1-2px with a restrained shadow.

### Shadow Vocabulary
- **Low State Shadow** (`box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05)`): Tiny separation only.
- **Panel Shadow** (`box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)`): Dropdowns, compact menus, and panels.
- **Popover Shadow** (`box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)`): Support tooltip, date picker, and elevated overlays.
- **Focus Glow** (`box-shadow: 0 0 20px -5px rgba(124, 58, 237, 0.4)`): Active support affordance or focus-critical action only.

### Named Rules

**The Minimal Elevation Rule.** Borders carry structure. Shadows respond to state. Never use exaggerated shadows to make a card feel premium.

**The Card Discipline Rule.** A card must organize real information or a real task. Empty visual containers, fake KPIs, and decorative card grids are prohibited.

## 5. Components

Components should be compact, reusable, and consistent. The merchant dashboard favors direct manipulation, dense tables, clear status, and low vertical waste.

### Buttons
- **Shape:** Controlled and modern (12px radius).
- **Primary:** Origin Violet with white text, 44px height, 22px horizontal padding, 0.875rem text. Use for the one main action in a local context.
- **Hover / Focus:** Slight upward translation, stronger violet state, and restrained glow. Focus must remain visible.
- **Secondary / Ghost / Tertiary:** Transparent or very low-contrast surfaces with border-light structure. Use for lower-priority actions and utilities.

### Chips
- **Style:** Pill shape (999px), compact padding, low-alpha background, semantic color text, and optional border.
- **State:** Status chips must map to actual state. Do not use chips as ornament.

### Cards / Containers
- **Corner Style:** Standard panels use 16px; dense nav and filters use 8-12px.
- **Background:** Ledger Surface over Midnight Ledger.
- **Shadow Strategy:** Flat by default with border-light. Hover may lift 1-2px only when the card is interactive.
- **Border:** Always subtle. Use border-light or border-medium; avoid heavy outlines.
- **Internal Padding:** 24px for normal cards, 10-16px for KPI and table-dense areas.

### Inputs / Fields
- **Style:** Dark filled field, 12px radius, 48px height, 16px horizontal padding, text-main foreground.
- **Focus:** Violet border and low-alpha focus ring. No layout shift.
- **Error / Disabled:** Red for actual error, muted disabled text, and no hidden contrast failures.

### Navigation
- **Style:** Persistent left sidebar for desktop merchant surfaces, compact 260px width, 7-9px vertical item padding, 8px radius, clear active state.
- **Active State:** Low-alpha Origin Violet background, violet indicator, and stronger text. Avoid thick side stripes; active markers should be subtle.
- **Mobile Treatment:** Collapse sidebar structurally at tablet widths. Do not rely on shrinking typography to make desktop navigation fit.

### Tables
- **Style:** Tables are first-class product surfaces. Headers use muted uppercase labels, compact row padding, hairline separators, and strong hover readability.
- **Density:** Fit naturally in Full HD. Prefer inline filters, compact actions, and row-level details over extra pages or modal-first flows.
- **State:** Empty states must explain what happened and what to do next. Skeletons are preferred over center spinners for loading.

### Support / Status
- **Style:** Floating support is allowed when it helps the merchant recover quickly. It should be compact, stateful, and not distract from the workflow.
- **Status:** Live/online indicators must represent real availability or system state.

## 6. Do's and Don'ts

### Do:
- **Do** use OriginPay as the canonical product identity. DigiKash is legacy/internal and must not shape UX, visual architecture, or copy.
- **Do** optimize every screen for fewer clicks, lower cognitive load, less vertical waste, and faster task completion.
- **Do** make tables the center of payment, customer, withdrawal, analytics, and reporting workflows.
- **Do** use Origin Violet for primary action, active state, selected state, and focus.
- **Do** use Settlement Cyan for money received, PIX, confirmation, and availability.
- **Do** use Amber for pending or processing states, and Red for errors, chargebacks, cancellations, and destructive states.
- **Do** keep cards compact and information-bearing.
- **Do** favor borders, spacing, and hierarchy over decorative shadows.
- **Do** keep layouts useful at Full HD without excessive scrolling.
- **Do** support WCAG 2.1 AA contrast, keyboard focus, reduced motion, and non-color status cues.

### Don't:
- **Don't** make OriginPay look like a generic SaaS dashboard.
- **Don't** make OriginPay look like a cryptocurrency exchange.
- **Don't** make OriginPay look like a traditional bank or old ERP.
- **Don't** use fake metrics, placeholder charts, or production placeholders.
- **Don't** waste space with decorative cards or charts that do not answer a real merchant question.
- **Don't** cite competing gateways inside the product interface.
- **Don't** present OriginPay as an intermediary for other gateways. OriginPay is the payment infrastructure.
- **Don't** use gradient text in product UI.
- **Don't** use decorative motion that does not convey state.
- **Don't** use exaggerated shadows, glassmorphism, or glow as default styling.
- **Don't** create thick colored side-stripe borders on cards or list items.
- **Don't** invent new component vocabulary when an existing OriginPay pattern can solve the task.
