# Rebuild upload zip (maintainers)

From repo root:

```bash
cd mmi-related-articles
zip -r ../mmi-related-articles.zip . -x '*.git*'
```

Commit `mmi-related-articles.zip` on **main** so the direct download link stays current:

`https://github.com/heenagupta198/Amazon-Api-Plugin/raw/main/mmi-related-articles.zip`