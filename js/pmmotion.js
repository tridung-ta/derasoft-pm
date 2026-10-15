/* Presentation-only motion. No requests, form handlers, data storage or content rewriting. */
(() => {
    'use strict';
    function start() {
        if (!document.body.matches('.pm-app,.pm-launch-page') || !Element.prototype.animate) return;
        const preference = matchMedia('(prefers-reduced-motion: reduce)');
        const active = new Map();
        const toasted = new WeakSet();
        const ease = 'cubic-bezier(.16,1,.3,1)';
        function cancelWithin(root) {
            for (const [element, animation] of active) {
                if (element === root || root.contains(element)) animation.cancel();
            }
        }
        function motion(element, frames, duration = 220) {
            if (!element || preference.matches || document.hidden) return;
            active.get(element)?.cancel();
            if (active.size >= 12) {
                const [oldElement, oldAnimation] = active.entries().next().value;
                active.delete(oldElement);
                oldAnimation.cancel();
            }
            const animation = element.animate(frames, {duration, easing: ease, fill: 'none'});
            active.set(element, animation);
            animation.finished.catch(() => {}).finally(() => {
                if (active.get(element) === animation) active.delete(element);
            });
        }
        function openDetails(details) {
            const menu = details.matches('.pm-work-menu,.pm-notification-menu,.pm-user-menu');
            const panel = menu
                ? details.querySelector(':scope > nav,:scope > div')
                : details.querySelector(':scope > div,:scope > form,:scope > p');
            if (!details.open) { cancelWithin(details); return; }
            motion(panel, menu ? [
                {opacity: .65, clipPath: 'inset(0 0 24% 0 round 12px)', transform: 'translateY(-5px)'},
                {opacity: 1, clipPath: 'inset(0 0 0 0 round 12px)', transform: 'translateY(0)'}
            ] : [
                {opacity: .65, clipPath: 'inset(0 0 10% 0)'},
                {opacity: 1, clipPath: 'inset(0 0 0 0)'}
            ], menu ? 260 : 180);
        }
        function toast(element) {
            if (toasted.has(element)) return;
            toasted.add(element);
            motion(element, [
                {opacity: .7, transform: 'translateX(16px)', clipPath: 'inset(0 0 0 8% round 12px)'},
                {opacity: 1, transform: 'translateX(0)', clipPath: 'inset(0 0 0 0 round 12px)'}
            ], 260);
        }
        new MutationObserver(records => {
            for (const record of records) {
                const element = record.target;
                if (record.type === 'attributes') {
                    if (record.attributeName === 'open' && element.matches('details')) openDetails(element);
                    else if (record.attributeName === 'open' && element.matches('dialog')) {
                        if (!element.open) cancelWithin(element);
                        else motion(element, [
                            {opacity: .7, transform: 'translateY(12px)', clipPath: 'inset(0 0 3% 0 round 14px)'},
                            {opacity: 1, transform: 'translateY(0)', clipPath: 'inset(0 0 0 0 round 14px)'}
                        ], 280);
                    } else if (record.attributeName === 'hidden' && element.matches('[role=tabpanel],#cost-project-panel,#cost-group-panel')) {
                        if (element.hidden) cancelWithin(element);
                        else motion(element, [{opacity: .6}, {opacity: 1}], 160);
                    }
                } else {
                    for (const node of record.addedNodes) {
                        if (node.nodeType !== Node.ELEMENT_NODE) continue;
                        if (node.matches('.pm-toast')) toast(node);
                        node.querySelectorAll('.pm-toast').forEach(toast);
                    }
                    for (const node of record.removedNodes) if (node.nodeType === Node.ELEMENT_NODE) cancelWithin(node);
                }
            }
        }).observe(document.body, {subtree: true, childList: true, attributes: true, attributeFilter: ['open', 'hidden']});
        document.querySelectorAll('.pm-toast').forEach(toast);
        if (document.body.matches('.pm-launch-page')) motion(document.querySelector('.status'), [
            {opacity: .75, clipPath: 'inset(0 0 12% 0 round 16px)'},
            {opacity: 1, clipPath: 'inset(0 0 0 0 round 16px)'}
        ], 420);
        function stop() { for (const animation of active.values()) animation.cancel(); active.clear(); }
        preference.addEventListener('change', stop);
        document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); });
        // Keyboard focus takes priority over an unfinished reveal.
        document.addEventListener('focusin', event => {
            for (const [element, animation] of active) if (element.contains(event.target)) animation.cancel();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once: true});
    else start();
})();
