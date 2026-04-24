/**
 * In `pdfjs-dist`, `SetIterator<T>` is defined as `IterableIterator<T>` to match
 * the return types of `Set.prototype.keys()`, `Set.prototype.values()`, and
 * `Set.prototype.entries()`, which are all iterable iterators.
 *
 * TypeScript doesn't have a built-in `SetIterator` type, so this alias ensures
 * compatibility when working with iterators returned from Set collections.
 * It allows for consistent handling of Set iteration across the library.
 **/
type SetIterator<T> = IterableIterator<T>;