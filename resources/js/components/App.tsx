import type { JSX } from "react";

/**
 * Root component of the ticketing widget, mounted by app.tsx.
 * Placeholder content: the real ticketing UI replaces it.
 */
export function App(): JSX.Element {
    return (
        <div className="itkt-app">
            <h1>Infomaniak Ticketing</h1>
            <p>Widget placeholder — the ticketing UI will live here.</p>
        </div>
    );
}
