/* ========================================================================
   Build With Abdallah — Central API
   Public site behaviour. No framework, no build step.

   Every motion path checks prefers-reduced-motion and degrades to the final
   state rather than being skipped, so content is never left mid-animation.
   ======================================================================== */
(() => {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const prefersReduced = () => reduceMotion.matches;

    /* --------------------------------------------------------------------
       1. Theme — Auto → Light → Dark, shared contract with the main site
       -------------------------------------------------------------------- */
    const THEME_KEY = 'bwa.theme';
    const THEME_ORDER = ['auto', 'light', 'dark'];
    const THEME_COLOR = { dark: '#000000', light: '#dbe4f2' };
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)');

    const applyTheme = (choice) => {
        const html = document.documentElement;
        const dark = choice === 'dark' || (choice === 'auto' && systemDark.matches);

        html.classList.toggle('dark', dark);
        html.dataset.theme = choice;
        html.dataset.resolvedTheme = dark ? 'dark' : 'light';

        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) {
            meta.setAttribute('content', dark ? THEME_COLOR.dark : THEME_COLOR.light);
        }

        document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
            btn.setAttribute('aria-label', `Theme: ${choice}. Click to change.`);
        });
    };

    const readTheme = () => {
        try {
            const stored = localStorage.getItem(THEME_KEY);
            return THEME_ORDER.includes(stored) ? stored : 'auto';
        } catch {
            return 'auto';
        }
    };

    applyTheme(readTheme());

    // Follow the OS while the visitor has not made an explicit choice.
    systemDark.addEventListener('change', () => {
        if (readTheme() === 'auto') {
            applyTheme('auto');
        }
    });

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-theme-toggle]');
        if (!toggle) {
            return;
        }

        const next = THEME_ORDER[(THEME_ORDER.indexOf(readTheme()) + 1) % THEME_ORDER.length];

        try {
            localStorage.setItem(THEME_KEY, next);
        } catch {
            /* Private mode: the choice simply will not persist. */
        }

        applyTheme(next);
    });

    /* --------------------------------------------------------------------
       2. Mobile navigation
       -------------------------------------------------------------------- */
    const navToggle = document.querySelector('[data-nav-toggle]');
    const mobileMenu = document.getElementById('mobile-menu');

    if (navToggle && mobileMenu) {
        navToggle.addEventListener('click', () => {
            const open = mobileMenu.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', String(open));
        });

        mobileMenu.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                mobileMenu.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && mobileMenu.classList.contains('open')) {
                mobileMenu.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.focus();
            }
        });
    }

    /* --------------------------------------------------------------------
       3. Scroll reveal + one-shot triggers (flow diagram, stat counters)
       -------------------------------------------------------------------- */
    const revealTargets = document.querySelectorAll('.reveal');
    const flow = document.querySelector('.flow');
    const counters = document.querySelectorAll('[data-count]');

    const runCounter = (el) => {
        const target = Number(el.dataset.count);
        const suffix = el.dataset.countSuffix || '';

        if (!Number.isFinite(target)) {
            return;
        }

        if (prefersReduced() || target === 0) {
            el.textContent = target + suffix;
            return;
        }

        const duration = 900;
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            // easeOutCubic
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(target * eased) + suffix;

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    };

    if (typeof IntersectionObserver === 'undefined') {
        revealTargets.forEach((el) => el.classList.add('in'));
        flow?.classList.add('live');
        counters.forEach((el) => {
            el.textContent = el.dataset.count + (el.dataset.countSuffix || '');
        });
    } else {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const el = entry.target;

                if (el.classList.contains('reveal')) {
                    el.classList.add('in');
                }

                if (el === flow) {
                    el.classList.add('live');
                }

                if (el.hasAttribute('data-count')) {
                    runCounter(el);
                }

                observer.unobserve(el);
            });
        }, { rootMargin: '0px 0px -12% 0px', threshold: 0.15 });

        revealTargets.forEach((el) => observer.observe(el));
        counters.forEach((el) => observer.observe(el));

        if (flow) {
            observer.observe(flow);
        }
    }

    /* --------------------------------------------------------------------
       3b. Tagline reveal — words sit muted and light up one at a time in
       reading order once the section crosses into view
       -------------------------------------------------------------------- */
    document.querySelectorAll('[data-tagline]').forEach((el) => {
        const words = el.textContent.trim().split(/\s+/);

        el.textContent = '';

        const spans = words.map((word, index) => {
            const span = document.createElement('span');
            span.className = 'tagline-word';
            span.textContent = word;
            span.style.transitionDelay = `${index * 70}ms`;
            el.append(span, document.createTextNode(' '));

            return span;
        });

        if (prefersReduced() || typeof IntersectionObserver === 'undefined') {
            spans.forEach((span) => span.classList.add('on'));

            return;
        }

        const taglineObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                spans.forEach((span) => span.classList.add('on'));
                taglineObserver.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -20% 0px', threshold: 0.4 });

        taglineObserver.observe(el);
    });

    /* --------------------------------------------------------------------
       4. Code sample tabs
       -------------------------------------------------------------------- */
    document.querySelectorAll('[data-code-tabs]').forEach((group) => {
        const tabs = [...group.querySelectorAll('.code-tab')];
        const panes = [...group.querySelectorAll('pre.code')];

        const select = (name) => {
            tabs.forEach((tab) => {
                tab.setAttribute('aria-selected', String(tab.dataset.lang === name));
            });
            panes.forEach((pane) => {
                pane.hidden = pane.dataset.lang !== name;
            });
        };

        group.addEventListener('click', (event) => {
            const tab = event.target.closest('.code-tab');
            if (tab) {
                select(tab.dataset.lang);
            }
        });

        // Roving arrow-key navigation across the tab list.
        group.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
                return;
            }

            const current = tabs.findIndex((tab) => tab.getAttribute('aria-selected') === 'true');
            if (current === -1) {
                return;
            }

            const step = event.key === 'ArrowRight' ? 1 : -1;
            const next = tabs[(current + step + tabs.length) % tabs.length];

            select(next.dataset.lang);
            next.focus();
            event.preventDefault();
        });
    });

    /* --------------------------------------------------------------------
       5. Lightweight syntax highlighting

       Deliberately generic rather than per-language: one pass over comments,
       strings, variables, keywords and numbers covers PHP, JS, bash and JSON
       well enough for documentation, and keeps the markup free of hand-written
       token spans. Blocks marked data-lang="text" are left alone.
       -------------------------------------------------------------------- */
    const TOKENS = new RegExp([
        /(\/\*[\s\S]*?\*\/|\/\/[^\n]*|#[^\n]*)/,                        // comments
        /("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|`(?:\\.|[^`\\])*`)/,      // strings
        /(\$\{?[A-Za-z_]\w*\}?)/,                                       // variables
        /(\b(?:import|from|export|use|const|let|var|function|return|new|await|async|class|extends|public|private|protected|static|null|true|false|if|else|throw|require)\b)/,
        /(\b\d+(?:\.\d+)?\b)/,                                          // numbers
    ].map((part) => part.source).join('|'), 'g');

    const escapeHtml = (text) => text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    document.querySelectorAll('pre.code[data-lang]').forEach((pane) => {
        if (pane.dataset.lang === 'text') {
            return;
        }

        pane.innerHTML = escapeHtml(pane.textContent).replace(
            TOKENS,
            (match, comment, string, variable, keyword, number) => {
                if (comment)  { return `<span class="tok-com">${match}</span>`; }
                if (string)   { return `<span class="tok-str">${match}</span>`; }
                if (variable) { return `<span class="tok-var">${match}</span>`; }
                if (keyword)  { return `<span class="tok-kw">${match}</span>`; }
                if (number)   { return `<span class="tok-num">${match}</span>`; }
                return match;
            },
        );
    });

    /* --------------------------------------------------------------------
       6. Copy to clipboard
       -------------------------------------------------------------------- */
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('.copy-btn');
        if (!btn) {
            return;
        }

        const block = btn.closest('.code-block');
        const pane = block?.querySelector('pre.code:not([hidden])');
        const label = btn.querySelector('[data-copy-label]');

        if (!pane) {
            return;
        }

        try {
            await navigator.clipboard.writeText(pane.innerText.trim());
            btn.dataset.copied = 'true';
            if (label) {
                label.textContent = 'Copied';
            }
        } catch {
            if (label) {
                label.textContent = 'Press ⌘C';
            }
        }

        setTimeout(() => {
            delete btn.dataset.copied;
            if (label) {
                label.textContent = 'Copy';
            }
        }, 1800);
    });

    /* --------------------------------------------------------------------
       7. Docs sidebar scrollspy
       -------------------------------------------------------------------- */
    const docsNav = document.querySelector('.docs-nav');

    if (docsNav && typeof IntersectionObserver !== 'undefined') {
        const links = [...docsNav.querySelectorAll('a[href^="#"]')];
        const sections = links
            .map((link) => document.getElementById(decodeURIComponent(link.hash.slice(1))))
            .filter(Boolean);

        const setActive = (id) => {
            links.forEach((link) => {
                link.classList.toggle('active', link.hash === `#${id}`);
            });
        };

        const spy = new IntersectionObserver((entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

            if (visible.length) {
                setActive(visible[0].target.id);
            }
        }, { rootMargin: '-96px 0px -68% 0px', threshold: 0 });

        sections.forEach((section) => spy.observe(section));
    }

    /* --------------------------------------------------------------------
       8. Live readiness probe
       -------------------------------------------------------------------- */
    const statusPanel = document.querySelector('[data-status-url]');

    if (statusPanel) {
        const pill = statusPanel.querySelector('[data-status-pill]');
        const label = statusPanel.querySelector('[data-status-label]');
        const checksEl = statusPanel.querySelector('[data-status-checks]');

        const setPill = (state, text) => {
            if (label) {
                label.textContent = text;
            }
            if (pill) {
                pill.classList.toggle('pill--ok', state === 'ok');
            }
        };

        fetch(statusPanel.dataset.statusUrl, { headers: { Accept: 'application/json' } })
            .then(async (response) => ({ ok: response.ok, body: await response.json() }))
            .then(({ ok, body }) => {
                setPill(ok ? 'ok' : 'down', ok ? 'All systems ready' : 'Degraded');

                if (!checksEl || !body.checks) {
                    return;
                }

                checksEl.innerHTML = '';

                Object.entries(body.checks).forEach(([name, state]) => {
                    const chip = document.createElement('span');
                    chip.className = 'check';
                    chip.dataset.state = state;
                    chip.innerHTML = '<i class="dot"></i>';
                    chip.append(name.replace(/_/g, ' '));
                    checksEl.append(chip);
                });
            })
            .catch(() => setPill('down', 'Status unavailable'));
    }
})();
