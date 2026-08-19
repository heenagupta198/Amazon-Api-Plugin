import assert from 'node:assert/strict';
import test from 'node:test';

test('mock product response shape', () => {
  const response = {
    query: 'keyboard',
    source: 'mock',
    items: [{ asin: 'B0MOCK001', title: 'Sample keyboard product' }],
  };

  assert.equal(response.items.length, 1);
  assert.match(response.items[0].asin, /^B0/);
});
