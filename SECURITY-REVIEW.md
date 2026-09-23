# Security Review

## Summary

| # | Severity | File | Lines | Vulnerability | Confidence |
|---|----------|------|-------|---------------|------------|
| 1 | 🟠 HIGH | php/Coin.php | 41-49, 52-60, 90-105, 121-148 | `Coin::__construct()` and `__set()` hydrate properties using magic setters with the wrong data contract: values are passed as scalars (e.g. `int`, `string`) to `hydrateField()`, which expects a row array. This causes runtime TypeError/500 during object creation, breaking all requests that instantiate `Coin` and making the model unusable. | 9/10 |

## Findings

### 1) Critical object hydration bug in `Coin`

File: `php/Coin.php`

The constructor loops through `self::ARRAY_FIELDS` and assigns:

```php
$this->{$property} = $this->hydrateField($field['type'], $data, $field['aliases']);
```

But the `__set()` magic method is defined as:

```php
public function __set(string $key, mixed $value): void
{
    if (array_key_exists($key, self::ARRAY_FIELDS)) {
        $this->property[$key] = $this->hydrateField(self::ARRAY_FIELDS[$key]['type'], $value, self::ARRAY_FIELDS[$key]['aliases']);
    }
}
```

This is inconsistent: `hydrateField()` expects an associative row array (`$data`) but is invoked with a scalar (value) when using the magic setter. `hydrateGrade()` also calls `$this->setGrade($grade)` and then returns `$this->grade`, while `setGrade()` assigns `$this->grade = $normalizedGrade`; because `grade` is not a declared property, this again goes through `__set()` and triggers the same incorrect path.

Impact:
- The object cannot be hydrated reliably.
- Every request that loads a `Coin` can fail with a runtime error (`TypeError`/500).
- This is a functional outage rather than a direct remote exploit, but it is a high-impact reliability issue.

## Additional assessment

- No direct SQL injection issue was found in this file; PDO parameters are used in the query.
- No confirmed XSS issue was identified from the code shown alone; any HTML rendering of these values still requires proper contextual escaping at the presentation layer.
- No insecure filesystem / SSRF / deserialization sinks are present in this file.
- No hardcoded secrets or obvious authentication bypass logic were found here.

## Recommended fix

- Keep a single, consistent hydration contract: either assign directly into a real internal property array or fully avoid magic setters for data that is already normalized.
- Declare actual internal properties (or use dedicated storage) instead of relying on `__set()` for typed field hydration.
- Make `setGrade()` update a dedicated internal property without calling the magic setter again.
- Add a focused test covering constructor hydration and grade assignment.
