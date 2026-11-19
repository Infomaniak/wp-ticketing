import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { App } from "@/components/App";

describe("App", () => {
    it("renders the widget placeholder content", () => {
        render(<App />);

        expect(screen.getByRole("heading", { name: "Infomaniak Ticketing" })).toBeInTheDocument();
    });
});
