<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/changeLocale/{locale}', function (string $locale) {
    if (in_array($locale, ['en','fr'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
});

Route::get('/', function () {
    return view('front/home');
});

Route::get('/home', function () {
    return view('front/home');
});

Route::get('/nos-services', function () {
    return view('front/nos-services');
});

Route::get('/livreur', function () {
    return view('front/home');
});

Route::get('/expéditeurs', function () {
    return view('front/expéditeurs');
});

Route::get('/nos-engagement', function () {
    return view('front/nos-engagement');
});

Route::get('/wallet', function () {
    return view('front/wallet');
});

Route::get('/user_profiles/{id}', function ($id) {
    return view('front/profil', ['id' => $id]);
})->name('user.profile');

Route::get('/facture', function () {
    return view('front/facture');
});
Route::get('/verification-identite', function () {
    return view('front/verification-identite');
});

Route::get('abonnement', function () {
    return view('front/abonnement');
});

Route::get('notification', function () {
    return view('front/notification');
});
Route::get('/mes-avis', function () {
    return view('front/mes-avis');
});

Route::get('/messages', function () {
    return view('client.messages');
})->name('messages');

Route::get('/document', function () {
    return view('front/document');
})->name('document');

Route::get('/delivery_success/{id}', function ($id) {
    return view('front.delivery_success', ['id' => $id]);
});

Route::get('/booking_success/{id}', function ($id) {
    return view('front.booking_success', ['id' => $id]);
});

Route::get('/wallet/success', function () {
    return view('front.success');
})->name('wallet.success');

Route::get('/reserve-box', function () {
    return view('front/reserve-box');
})->name('reserver un box');

Route::get('/mes-box', function () {
    return view('front/mes-box');
})->name('mes box de stockage');


Route::get('/marketplace', function () {
    return view('front.marketplace');
})->name('front.marketplace');

// ADMIN \\
Route::get('/admin/facturation', function () {
    return view('admin.facturation');
})->name('admin.facturation');

Route::get('/admin/utilisateurs', function () {
    return view('admin.utilisateurs');
})->name('admin.utilisateurs');

Route::get('/admin/entrepot', function () {
    return view('admin.entrepot');
})->name('admin.entrepot');

Route::get('/admin/annonce', function () {
    return view('admin.annonce');
})->name('admin.annonce');
 Route::get('/admin/annonce', function () {
     return view('admin.annonce');
 })->name('admin.annonce');

Route::get('/admin/verification-identites', function () {
    return view('admin.verification-identites');
})->name('admin.verification-identites');

Route::get('/admin/avis', function () {
    return view('admin.avis');
})->name('admin.avis');

Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');


// CLIENT \\
Route::get('/client/home', function () {
    return view('client.client');
})->name('client.client');

Route::get('/client/dashboard', function () {
    return view('client.dashboard');
})->name('client.dashboard');

Route::get('/client/annonce/nouvelle', function () {
    return view('client.annonce-form');
})->name('client.annonce');

Route::get('/client/annonce', function () {
    return view('client.annonce');
})->name('client.annonce');

Route::get('/client/suivis-service', function () {
    return view('client.suivis-service');
})->name('client.suivis-service');

Route::get('/client/reservations/{bookingId}', function () {
    return view('client.reservation-detail');
})->name('client.reservation.detail');

Route::view('/verification-finish', 'front/verification-identite')->name('verification.finish');

Route::get('/client/suivi-colis', function () {
    return view('client.suivi-colis');
})->name('client.suivis-colis');

Route::get('/client/suivi-colis/{listingId}', function ($listingId) {
    return view('client.suivi-colis-detail', ['listingId' => $listingId]);
})->name('client.suivis-colis.detail');





Route::get('/client/annonce/modifier/{id}', function ($id) {
    return view('client.edit-annonce', ['id' => $id]);
})->name('client.edit-annonce');

Route::get('/client/annonce/details/{id}', function ($id) {
    return view('client.annonce-details', ['id' => $id]);
})->name('client.annonce.details');

Route::get('/client/annonce/{id}/avis', function ($id) {
    return view('client.avis', ['listing_id' => $id]);
})->name('client.annonce.avis');

Route::get('/livreur/annonce/{id}/avis-client', function ($id) {
    return view('livreur.avis-client', ['listing_id' => $id]);
})->name('livreur.annonce.avis-client');

Route::get('/client/annonce', function () {
    return view('client.annonce');
})->name('client.annonce');

// COURIER \\ = livreur
Route::get('/livreur/home', function () {
    return view('courier.courier');
})->name('courier.courier');

Route::get('livreur/mes-trajets', function () {
    return view('livreur.mes-trajets');
})->name('Mes trajets');

 Route::get('/mes-trajets/{id}', function($id){
     return view('livreur.mes-trajets-detail', ['deliveryId' => $id]);
 })->name('livreur.mes-trajet-detail');

Route::get('livreur/onboarding', function () {
    return view('livreur.onboarding');
})->name('Onboarding Livreur');

Route::get('livreur/dashboard', function () {
    return view('livreur.dashboard-livreur');
})->name('Dashboard Livreur');

Route::get('livreur/suivis-colis', function () {
    return view('livreur.suivis-colis');
})->name('suivis-colis');


// MERCHANT \\
Route::get('/marchant/home', function () {
    return view('merchant.merchant');
})->name('merchant.merchant');

// SERVICE PROVIDER \\ = prestataire
Route::get('/prestataire/home', function () {
    return view('service_provider.service_provider');
})->name('service_provider.service_provider');

Route::get('/service_provider/planning', function () {
    return view('service_provider.planning');
})->name('service_provider.planning');

// Route universelle pour l'édition de profil
Route::get('/edit-profile', function () {
    return view('front.edit-profile');
})->name('edit-profile');

Route::prefix('profile')->group(function () {
    Route::get('/edit', function () {
        return view('front.edit-profile');
    })->name('profile.edit');
});

// SESSION \\
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
