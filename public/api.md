# Baakh API

The public JSON API is read-only. No authentication is required for poets, poems, or the homepage feed.

- [RFC 9727 catalog](https://baakh.com/.well-known/api-catalog)
- [Health](https://baakh.com/api/health)
- [Feed](https://baakh.com/api/v1/feed?lang=sd)
- [Poet](https://baakh.com/api/v1/poets/{slug})
- [Poetry](https://baakh.com/api/v1/poetry/{slug})
- [llms.txt](https://baakh.com/llms.txt)

Unknown API paths return JSON errors. Unknown HTML paths return HTTP 404.
