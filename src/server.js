import express from 'express';

const app = express();
const port = Number(process.env.PORT ?? 3000);

app.use(express.json());

app.get('/health', (_req, res) => {
  res.json({ status: 'ok', service: 'amazon-api-plugin' });
});

app.get('/api/products', (req, res) => {
  const query = String(req.query.q ?? 'laptop').trim() || 'laptop';

  res.json({
    query,
    source: 'mock',
    items: [
      {
        asin: 'B0MOCK001',
        title: `Sample ${query} product`,
        price: { amount: 49.99, currency: 'USD' },
      },
    ],
  });
});

app.listen(port, '0.0.0.0', () => {
  console.log(`Amazon API plugin dev server listening on http://0.0.0.0:${port}`);
});
