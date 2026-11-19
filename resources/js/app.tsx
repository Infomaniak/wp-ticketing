import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { App } from "@/components/App";

// Mount point id shared with src/Shortcode.php (Shortcode::MOUNT_ID) — change both together.
const mount = document.getElementById("itkt-app");

if (mount) {
    createRoot(mount).render(
        <StrictMode>
            <App />
        </StrictMode>
    );
}
