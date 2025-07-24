export async function requireAuth(expectedRole = null) {
    const token = localStorage.getItem("token");
    if (!token) {
        window.location.href = "/login";
        return;
    }

    try {
        const res = await fetch("/api/api/user", {
            headers: {
                Authorization: "Bearer " + token
            }
        });

        const data = await res.json();

        if (!res.ok) throw new Error("Token invalide");

        if (expectedRole) {
            const allowedRoles = Array.isArray(expectedRole) ? expectedRole : [expectedRole];
            if (!allowedRoles.includes(data.user.type)) {
                window.location.href = "/login";
            }
        }

        return data.user;

    } catch (err) {
        console.error("Accès refusé :", err);
        window.location.href = "/login";
    }
}
