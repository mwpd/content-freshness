<!--
Title: a plain imperative sentence, like the commit log ("Add the outdated post notice").

Use Closes only when merging meets every acceptance criterion. If anything
remains, use Refs # instead and name it under "Anything left open".
-->
Closes #

## What changed

<!-- One paragraph. What the plugin does now that it did not do before, or
     stopped doing. Not a list of files. -->

## Why this shape

<!-- The alternative you did not take, and what decided it. Keep it short if
     the issue already chose the approach. -->

## Proof

<!-- What you ran and what it showed. A test covering a fix is shown FAILING
     before the change. -->

```sh
composer test
composer lint
npm run e2e
```

## Checklist

- [ ] `composer test`, `composer lint` and `npm run e2e` pass locally, and CI is green on the current head.
- [ ] Every acceptance criterion from the issue is met, or the gap is named below.
- [ ] `src/Infrastructure/` is unchanged, or the API snapshot is regenerated and the change is proposed upstream to `mwpd/basic-scaffold`.
- [ ] Docs and inline comments reflect the change, or nothing documented moved.

## Anything left open

<!-- Known gaps, follow-up issues filed, decisions deferred. Write "nothing"
     rather than deleting the section. -->
