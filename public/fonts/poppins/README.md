# Poppins local webfonts

Copyright 2020 The Poppins Project Authors (https://github.com/itfoundry/Poppins).
Distributed under the SIL Open Font License 1.1 in `OFL.txt`.

The unmodified normal-style Latin WOFF2 files at weights 400, 500, 600, and 700
were downloaded on 2026-10-16 from the same Bunny Fonts service used by the
panel's default Poppins configuration:

- CSS: https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap
- Files: https://fonts.bunny.net/poppins/files/poppins-latin-{weight}-normal.woff2
- License: https://github.com/google/fonts/blob/main/ofl/poppins/OFL.txt
- Upstream project: https://github.com/itfoundry/Poppins

`poppins.css` preserves the Latin unicode ranges advertised by Bunny Fonts and
uses relative local URLs with `font-display: swap`. This subset supports the
Indonesian interface, Latin text, currency symbols in that range, punctuation,
minus, and up/down arrows. Characters outside the subset retain the browser's
normal fallback font behavior. Additional script subsets can be added if needed.

Keeping these files on the application's origin removes the external font CSS
and font requests while preserving the existing Poppins appearance.
