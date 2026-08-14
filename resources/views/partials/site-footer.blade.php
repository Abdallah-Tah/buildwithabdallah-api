<footer class="site-footer">
    <div class="shell">
        <div class="footer-grid">
            <div>
                <a class="brand" href="{{ route('page.home') }}">
                    <img src="{{ asset('images/bwa-logo.jpeg') }}" alt="" width="42" height="42" loading="lazy" decoding="async">
                    <span>
                        <span class="brand-name">Build With <span class="hl">Abdallah</span></span>
                        <span class="brand-sub">Central API</span>
                    </span>
                </a>
                <p class="footer-blurb">
                    One signed boundary between external providers and every product on the
                    platform. Built and operated by Abdallah &mdash; see
                    <a class="ul-link" href="https://buildwithabdallah.com" rel="noopener">buildwithabdallah.com</a>
                    for the wider body of work.
                </p>
            </div>

            <div>
                <h3>This service</h3>
                <ul>
                    <li><a href="{{ route('page.home') }}">Overview</a></li>
                    <li><a href="{{ route('page.about') }}">Architecture</a></li>
                    <li><a href="{{ route('page.docs') }}">API documentation</a></li>
                    <li><a href="{{ route('health.ready') }}">Readiness probe</a></li>
                </ul>
            </div>

            <div>
                <h3>Elsewhere</h3>
                <ul>
                    <li><a href="https://buildwithabdallah.com" rel="noopener">Main site</a></li>
                    <li><a href="https://buildwithabdallah.com/services" rel="noopener">Services</a></li>
                    <li><a href="https://buildwithabdallah.com/tutorials" rel="noopener">Journal</a></li>
                    <li><a href="https://buildwithabdallah.com/contact" rel="noopener">Contact</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} Build With Abdallah</span>
            <span>Software &middot; AI &middot; Automation</span>
        </div>
    </div>
</footer>
