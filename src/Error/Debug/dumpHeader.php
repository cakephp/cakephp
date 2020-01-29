<style type="text/css">
.cake-dbg {
  font-family: monospace;
  background: var(--color-bg);
  padding: 5px;

  /*
  Colours based on solarized scheme
  https://ethanschoonover.com/solarized/
  */
  --color-bg: #fdf6e3;
  --color-grey: #586e75;
  --color-dark-grey: #073642;
  --color-cyan: #2aa198;
  --color-blue: #268bd2;
  --color-green: #859900;
  --color-orange: #cb4b16;
  --color-yellow: #b58900;
  --color-violet: #6c71c4;
  --color-red: #dc322f;

  --indent: 20px;
}
.cake-dbg-object {
  display: inline;
}
/*
Array item container and each items are blocks so
nesting works.
*/
.cake-dbg-object-props,
.cake-dbg-array-items {
  display: block;
}
.cake-dbg-prop,
.cake-dbg-array-item {
  display: block;
  padding-left: var(--indent);
}

/* Textual elements */
.cake-dbg-class {
  color: var(--color-cyan);
}
.cake-dbg-property {
  color: var(--color-dark-grey);
}
.cake-dbg-visibility {
  color: var(--color-violet);
}
.cake-dbg-punct {
  color: var(--color-grey);
}
.cake-dbg-string {
  color: var(--color-green);
}
.cake-dbg-number {
  font-weight: bold;
  color: var(--color-blue);
}
.cake-dbg-const {
  color: var(--color-yellow);
  font-weight: bold;
}
.cake-dbg-ref {
  color: var(--color-red);
}
.cake-dbg-special {
  color: var(--color-red);
  font-style: italic;
}
</style>
