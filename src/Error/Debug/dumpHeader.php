<style type="text/css">
.cake-dbg {
  font-family: monospace;
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
.cake-dbg-punct {
}
.cake-dbg-string {
}
.cake-dbg-number {
  font-weight: bold;
}
.cake-dbg-const {
  font-weight: bold;
}
</style>
