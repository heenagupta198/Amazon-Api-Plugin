# Amazon-Api-Plugin

Node.js development workspace for an Amazon Product Advertising API plugin.

## Development

```bash
./scripts/cloud-agent-install.sh   # install dependencies
npm run dev                        # start API server on port 3000
npm test                           # run tests
npm run lint                       # syntax check
```

### API endpoints

- `GET /health` — service health check
- `GET /api/products?q=keyboard` — mock product search response
