@extends('layouts.app')

@section('title', 'Devenir livreur')

@section('content')

    <link rel="stylesheet" href="{{ secure_asset('css/livreur/onboarding.css') }}">

    <div class="max-w-3xl mx-auto p-6 bg-white shadow-md rounded">
        <h1 class="text-2xl font-bold mb-4">Devenir livreur sur EcoDeli</h1>

        <p>Vous êtes sur le point de devenir livreur. Cela vous permettra de réserver des annonces de colis et de gagner de l'argent en effectuant des livraisons.</p>

        <ul class="list-disc ml-5 my-4 text-gray-700">
            <li>Vous pourrez visualiser les annonces de colis à livrer</li>
            <li>Vous serez noté par les clients</li>
            <li>Vous devrez respecter les délais et conditions de transport</li>
        </ul>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 rounded mb-4">
            <p class="font-semibold">Vérification d'identité requise</p>
            <p class="mt-2">
                Pour garantir la sécurité de notre plateforme et de nos utilisateurs, vous devrez fournir des justificatifs d'identité une fois inscrit :
            </p>
            <ul class="list-disc ml-5 mt-2">
                <li>Une pièce d'identité valide (carte d'identité ou passeport)</li>
                <li>Un justificatif de domicile récent</li>
                <li>Un permis de conduire si vous effectuez des transports motorisés</li>
            </ul>
            <p class="mt-2">Ces documents seront traités de manière confidentielle et sécurisée.</p>
        </div>

        <button id="devenirLivreurBtn" class="btn-blue">Je deviens livreur</button>

        <script>
            document.getElementById("devenirLivreurBtn").addEventListener("click", async () => {
                const token = localStorage.getItem("token");
                try {
                    const res = await fetch("/api(/api/user/become-courier", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Authorization: "Bearer " + token
                        }
                    });

                    const data = await res.json();

                    if (res.ok) {
                        alert("Félicitations, vous êtes maintenant livreur !");
                        window.location.href = "/client/dashboard";
                    } else {
                        alert("Erreur : " + data.message);
                    }

                } catch (err) {
                    console.error(err);
                    alert("Une erreur est survenue.");
                }
            });
        </script>

    </div>
@endsection
