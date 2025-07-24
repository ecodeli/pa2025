<nav class="navbar">
    <div class="logo">
        <a href="/">
            <img src="{{ secure_asset('EDICONKIKI.ico') }}" alt="EcoDeli">
        </a>
    </div>

    <div class="menu" id="menu-links">

    </div>

    <div class="auth d-flex align-items-center gap-3" id="auth-section">

    </div>
    <select name="selectLocale" id="selectLocale" class="select-langue">
        <option @if(app()->getLocale() == 'fr') selected @endif value="fr">fr</option>
        <option @if(app()->getLocale() == 'en') selected @endif value="en">en</option>
    </select>

</nav>
