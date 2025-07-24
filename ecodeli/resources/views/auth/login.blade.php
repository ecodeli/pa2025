@extends('layouts.app')

@section('content')
<div class="login-container">
    <link href="{{ secure_asset('css/layout-login.css') }}" rel="stylesheet">

    <div id="error-message" class="error-message" style="color: red; display: none;"></div>

    <form id="loginForm">
        <label for="email">Email :</label>
        <input type="email" id="email" required>

        <label for="password">Mot de passe :</label>
        <input type="password" id="password" required>

        <button type="submit">Se connecter</button>
    </form>

    <p>Pas encore de compte ? <a href="{{ route('register') }}">Inscription</a></p>
</div>
@endsection

@push('scripts')
<script>
document.getElementById("loginForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const res = await fetch("/api(/api/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            email: document.getElementById("email").value,
            password: document.getElementById("password").value
        })
    });

    const result = await res.json();
    console.log(result);

    if (res.ok) {
        localStorage.setItem("token", result.token);
        const role = result.user?.type;
        if (role === "client") window.location.href = "/client/dashboard";
        else if (role === "courier") window.location.href = "/livreur/dashboard";
        else if (role === "service_provider") window.location.href = "/prestataire/dashboard";
        else if (role === "admin") window.location.href = "/admin/dashboard";
        else window.location.href = "/";
    } else {
        document.getElementById("error-message").style.display = "block";
        document.getElementById("error-message").innerText = result.message || "Erreur de connexion.";
    }
});
</script>
@endpush
