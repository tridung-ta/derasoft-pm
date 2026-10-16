# Operating interface - Midnight Gold

User-selected visual world, 2026-10-16: dark by default, canvas #0E1320, card #171E30, gold #D9B26A, main text #EDE6D8, secondary text #9AA0B2. Gold is the primary action and selected-state accent; dark ink on gold. Error and success states retain explicit labels and contrasting red/green tints. Form controls, menus, tables, dialogs, empty/error states and charts all use the dark palette. No automatic light-theme fallback.

Preserve self-hosted Inter and operating workbench layouts. Navigation left, DeraSoft centered, account/notifications right. Mobile stacks in DOM order. Keep native forms/details, CSP, CSRF, permission checks and existing backend content unchanged. Inputs, placeholders, focus, selection and scrollbars are themed.

User-requested material motion: a single 800ms light sweep across primary buttons on hover; project cards and workspace links lift 6px with a soft offset shadow and gold border. Never loop, block pointer input or move data tables. Reduced motion removes sweep and lift. Existing menu/tab/dialog feedback remains cancelable.

Local review only. No production access, push, merge or deployment without separate approval.
