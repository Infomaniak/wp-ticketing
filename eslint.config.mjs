import globals from "globals";
import pluginJs from "@eslint/js";
import pluginReact from "eslint-plugin-react";
import pluginReactHooks from "eslint-plugin-react-hooks";
import pluginJsxA11y from "eslint-plugin-jsx-a11y";
import tseslint from "typescript-eslint";
import eslintConfigPrettier from "eslint-config-prettier";

export default tseslint.config(
    {
        ignores: [
            "node_modules/**",
            "build/**",
            "vendor/**",
            "coverage/**",
            ".yarn/**",
        ],
    },
    {
        files: ["**/*.{js,mjs,cjs,jsx,ts,tsx}"],
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: "module",
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
            },
            globals: {
                ...globals.browser,
            },
        },
        settings: {
            react: {
                version: "detect",
            },
            "import/resolver": {
                typescript: {
                    alwaysTryTypes: true,
                },
            },
        },
        plugins: {
            react: pluginReact,
            "react-hooks": pluginReactHooks,
            "jsx-a11y": pluginJsxA11y,
        },
        rules: {
            "no-console": "warn",
            "no-debugger": "warn",
            "no-empty": "warn",
            eqeqeq: ["warn", "always"],
            "no-eval": "warn",
            "no-implied-eval": "warn",
            "no-prototype-builtins": "warn",
            "no-return-assign": ["warn", "except-parens"],
            "no-self-assign": "warn",
            "no-self-compare": "warn",
            "no-unused-expressions": "warn",
            "no-useless-call": "warn",
            "no-useless-catch": "warn",
            "no-useless-concat": "warn",
            "no-useless-return": "warn",

            // React specific rules
            "react/jsx-uses-react": "warn",
            "react/jsx-uses-vars": "warn",
            "react/no-children-prop": "warn",
            "react/no-danger": "warn",
            "react/no-deprecated": "warn",
            "react/no-direct-mutation-state": "warn",
            "react/no-find-dom-node": "warn",
            "react/no-is-mounted": "warn",
            "react/no-multi-comp": ["warn", { ignoreStateless: true }],
            "react/no-redundant-should-component-update": "warn",
            "react/no-render-return-value": "warn",
            "react/no-string-refs": "warn",
            "react/no-this-in-sfc": "warn",
            "react/no-unknown-property": "warn",
            "react/no-unsafe": "warn",
            "react/no-unused-state": "warn",
            "react/prefer-es6-class": "warn",
            "react/prefer-stateless-function": "warn",
            "react/prop-types": "off",
            "react/react-in-jsx-scope": "off",
            "react/require-render-return": "warn",
            "react/self-closing-comp": "warn",
            "react/sort-comp": "warn",
            "react/style-prop-object": "warn",
            "react/void-dom-elements-no-children": "warn",
            "react/jsx-curly-brace-presence": ["warn", { props: "never", children: "ignore" }],

            // JSX a11y rules
            "jsx-a11y/alt-text": "warn",
            "jsx-a11y/anchor-has-content": "warn",
            "jsx-a11y/anchor-is-valid": ["warn", { aspects: ["invalidHref"] }],
            "jsx-a11y/aria-activedescendant-has-tabindex": "warn",
            "jsx-a11y/aria-props": "warn",
            "jsx-a11y/aria-proptypes": "warn",
            "jsx-a11y/aria-role": "warn",
            "jsx-a11y/aria-unsupported-elements": "warn",
            "jsx-a11y/heading-has-content": "warn",
            "jsx-a11y/html-has-lang": "warn",
            "jsx-a11y/iframe-has-title": "warn",
            "jsx-a11y/img-redundant-alt": "warn",
            "jsx-a11y/interactive-supports-focus": "warn",
            "jsx-a11y/label-has-associated-control": [
                "warn",
                {
                    required: {
                        some: ["nesting", "id"],
                    },
                },
            ],
            "jsx-a11y/lang": "warn",
            "jsx-a11y/media-has-caption": "warn",
            "jsx-a11y/mouse-events-have-key-events": "warn",
            "jsx-a11y/no-access-key": "warn",
            "jsx-a11y/no-autofocus": "warn",
            "jsx-a11y/no-distracting-elements": "warn",
            "jsx-a11y/no-interactive-element-to-noninteractive-role": "warn",
            "jsx-a11y/no-noninteractive-element-interactions": "warn",
            "jsx-a11y/no-noninteractive-tabindex": "warn",
            "jsx-a11y/no-redundant-roles": "warn",
            "jsx-a11y/no-static-element-interactions": "off",
            "jsx-a11y/role-has-required-aria-props": "warn",
            "jsx-a11y/role-supports-aria-props": "warn",
            "jsx-a11y/scope": "warn",
            "jsx-a11y/tabindex-no-positive": "warn",

            // Additional rules
            "arrow-body-style": ["warn", "as-needed"],
            "func-names": "off",
            "linebreak-style": "off",
            "no-underscore-dangle": "off",
            "react/destructuring-assignment": "off",
            "react/display-name": "warn",
            "react/jsx-filename-extension": ["warn", { extensions: [".js", ".jsx", ".tsx"] }],
            "react/jsx-props-no-spreading": "off",
            "react/no-array-index-key": "warn",
        },
    },
    pluginJs.configs.recommended,
    ...tseslint.configs.recommended,
    eslintConfigPrettier,
);
