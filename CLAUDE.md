# tinyPacker

Notes for AI assistants working on the Dotclear plugin tinyPacker.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

One button packager for plugins and themes direct from Dotclear blog manager.

No admin page. Nothing on the public side.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* Behaviors used: `adminModulesListGetActions`, `adminModulesListDoActions`.

## Pitfalls

* Packages are written in the blog public directory (`public/packages`), so they can be downloaded without authentication: this is wanted, keep it documented in README.

## Testing

* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
